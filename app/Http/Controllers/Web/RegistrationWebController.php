<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterAdminRequest;
use App\Http\Requests\RegisterOrganizationRequest;
use App\Http\Requests\RegisterPlanRequest;
use App\Models\Plan;
use App\Services\RegistrationService;
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
     * Étape 1 : Affichage du formulaire de création de l'organisation.
     */
    public function createOrganization(Request $request): Response
    {
        return Inertia::render('Auth/RegisterOrganization', [
            'organization' => $request->session()->get('registration.organization', []),
        ]);
    }

    /**
     * Étape 1 : Validation et mise en session des informations de l'organisation.
     */
    public function storeOrganization(RegisterOrganizationRequest $request): RedirectResponse
    {
        $request->session()->put('registration.organization', $request->validated());

        return redirect()->route('register.admin');
    }

    /**
     * Étape 2 : Affichage du formulaire de création de l'administrateur principal.
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
     * Étape 2 : Validation et mise en session des informations de l'administrateur.
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
        if (! $request->session()->has('registration.organization')) {
            return redirect()->route('register.organization')
                ->with('error', "Veuillez d'abord renseigner les informations de votre organisation.");
        }

        if (! $request->session()->has('registration.admin')) {
            return redirect()->route('register.admin')
                ->with('error', "Veuillez d'abord renseigner les informations de l'administrateur.");
        }

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Auth/RegisterPlan', [
            'plans' => $plans,
            'organization' => $request->session()->get('registration.organization'),
            'admin' => Arr::except($request->session()->get('registration.admin'), ['password', 'password_confirmation']),
        ]);
    }

    /**
     * Étape 3 : Finalisation atomique de l'inscription (Organization + Admin + Role + Subscription).
     */
    public function storePlan(RegisterPlanRequest $request): RedirectResponse
    {
        $orgData = $request->session()->get('registration.organization');
        $adminData = $request->session()->get('registration.admin');

        if (! $orgData) {
            return redirect()->route('register.organization')
                ->with('error', "La session d'inscription a expiré. Veuillez recommencer.");
        }

        if (! $adminData) {
            return redirect()->route('register.admin')
                ->with('error', "Veuillez renseigner les informations de l'administrateur.");
        }

        $validated = $request->validated();

        // Création atomique via le service
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
