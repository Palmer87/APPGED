<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterAdminRequest;
use App\Http\Requests\RegisterOrganizationRequest;
use App\Http\Requests\RegisterPlanRequest;
use App\Models\Plan;
use App\Services\RegistrationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

class RegistrationWebController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService
    ) {}

    /**
     * Parcours Inscription V1 — Étape 1 : Créer son compte administrateur (/inscription).
     */
    public function createAccount(Request $request): Response
    {
        $adminData = Arr::except($request->session()->get('registration.admin', []), ['password', 'password_confirmation']);

        return Inertia::render('Auth/RegisterAccount', [
            'account' => $adminData,
            'organization' => $request->session()->get('registration.organization', []),
        ]);
    }

    /**
     * Parcours Inscription V1 — Étape 1 : Validation et mise en session du compte.
     */
    public function storeAccount(RegisterAdminRequest $request): RedirectResponse
    {
        $request->session()->put('registration.admin', $request->validated());

        return redirect()->route('inscription.organisation');
    }

    /**
     * Parcours Inscription V1 — Étape 2 : Création de l'organisation (/inscription/organisation ou /register/organization).
     */
    public function createOrganization(Request $request): Response|RedirectResponse
    {
        // En parcours /inscription, vérifier que le compte est déjà renseigné
        if ($request->routeIs('inscription.organisation') && ! $request->session()->has('registration.admin')) {
            return redirect()->route('inscription')
                ->with('error', "Veuillez d'abord créer votre compte administrateur.");
        }

        return Inertia::render('Auth/RegisterOrganization', [
            'organization' => $request->session()->get('registration.organization', []),
            'account' => Arr::except($request->session()->get('registration.admin', []), ['password', 'password_confirmation']),
            'isInscriptionFlow' => $request->routeIs('inscription.organisation'),
        ]);
    }

    /**
     * Parcours Inscription V1 — Étape 2 : Validation et mise en session de l'organisation.
     */
    public function storeOrganization(RegisterOrganizationRequest $request): RedirectResponse
    {
        $request->session()->put('registration.organization', $request->validated());

        // Si la requête provient du parcours /inscription/organisation
        if ($request->routeIs('inscription.organisation.store') || $request->session()->has('registration.admin')) {
            return redirect()->route('inscription.plan');
        }

        // Flux legacy /register/organization -> /register/admin
        return redirect()->route('register.admin');
    }

    /**
     * Flux legacy — Étape 2 : Affichage du formulaire de création de l'administrateur principal.
     */
    public function createAdmin(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('registration.organization')) {
            return redirect()->route('register.organization')
                ->with('error', "Veuillez d'abord renseigner les informations de votre organisation.");
        }

        $adminData = Arr::except($request->session()->get('registration.admin', []), ['password', 'password_confirmation']);

        return Inertia::render('Auth/RegisterAdmin', [
            'organization' => $request->session()->get('registration.organization'),
            'admin' => $adminData,
        ]);
    }

    /**
     * Flux legacy — Étape 2 : Validation et mise en session des informations de l'administrateur.
     */
    public function storeAdmin(RegisterAdminRequest $request): RedirectResponse
    {
        if (! $request->session()->has('registration.organization')) {
            return redirect()->route('register.organization')
                ->with('error', "Veuillez d'abord renseigner les informations de votre organisation.");
        }

        $request->session()->put('registration.admin', $request->validated());

        return redirect()->route('register.plan');
    }

    /**
     * Étape 3 : Affichage du choix de la formule / plan avec période d'essai 14 jours.
     */
    public function createPlan(Request $request): Response|RedirectResponse
    {
        $isInscription = $request->routeIs('inscription.plan');

        if (! $request->session()->has('registration.organization')) {
            $fallbackRoute = $isInscription ? 'inscription.organisation' : 'register.organization';

            return redirect()->route($fallbackRoute)
                ->with('error', "Veuillez d'abord renseigner les informations de votre organisation.");
        }

        if (! $request->session()->has('registration.admin')) {
            $fallbackRoute = $isInscription ? 'inscription' : 'register.admin';

            return redirect()->route($fallbackRoute)
                ->with('error', "Veuillez d'abord renseigner les informations de votre compte.");
        }

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($plans->isEmpty()) {
            (new PlanSeeder)->run();
            $plans = Plan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return Inertia::render('Auth/RegisterPlan', [
            'plans' => $plans,
            'organization' => $request->session()->get('registration.organization'),
            'admin' => Arr::except($request->session()->get('registration.admin'), ['password', 'password_confirmation']),
            'isInscriptionFlow' => $isInscription,
        ]);
    }

    /**
     * Étape 3 : Finalisation atomique de l'inscription (Organization + Admin + Role + Subscription + Trial 14j).
     */
    public function storePlan(RegisterPlanRequest $request): RedirectResponse
    {
        $isInscription = $request->routeIs('inscription.plan.store');

        $orgData = $request->session()->get('registration.organization');
        $adminData = $request->session()->get('registration.admin');

        if (! $orgData) {
            $fallbackRoute = $isInscription ? 'inscription.organisation' : 'register.organization';

            return redirect()->route($fallbackRoute)
                ->with('error', "La session d'inscription a expiré. Veuillez recommencer.");
        }

        if (! $adminData) {
            $fallbackRoute = $isInscription ? 'inscription' : 'register.admin';

            return redirect()->route($fallbackRoute)
                ->with('error', "Veuillez renseigner les informations de l'administrateur.");
        }

        $validated = $request->validated();

        // Si l'utilisateur choisit Enterprise sur /inscription/plan :
        // Rediriger vers la demande commerciale /enterprise sans créer d'organisation ou abonnement payant
        if ($isInscription && $validated['plan'] === 'enterprise') {
            return redirect()->route('enterprise')->with(
                'info',
                "L'offre Enterprise nécessite une configuration sur-mesure. Complétez votre demande pour être recontacté sous 24h."
            );
        }

        // Création atomique via le service (Transaction DB)
        $result = $this->registrationService->register(
            orgData: $orgData,
            adminData: $adminData,
            planSlug: $validated['plan'],
            billingCycle: $validated['billing_cycle']
        );

        $user = $result['user'];
        $organization = $result['organization'];
        $plan = $result['plan'];

        // Vider la session d'inscription
        $request->session()->forget('registration');

        // Authentifier automatiquement le nouvel administrateur
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        if ($user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }

        // Préparer la notification et les données d'onboarding sur le Dashboard
        $isEnterprise = $plan->slug === 'enterprise';

        $request->session()->flash('welcome_onboarding', [
            'organization_name' => $organization->name,
            'admin_name' => $user->name,
            'plan_name' => $plan->name,
            'is_enterprise' => $isEnterprise,
            'trial_days' => 14,
        ]);

        if ($isEnterprise) {
            $request->session()->flash('info', 'Notre équipe va vous contacter pour configurer votre offre Entreprise.');
        }

        return redirect()->route('dashboard')->with(
            'success',
            "Bienvenue sur GEDAPP ! Votre organisation {$organization->name} a été créée avec succès."
        );
    }
}
