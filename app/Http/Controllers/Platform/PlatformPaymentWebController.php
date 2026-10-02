<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformPaymentWebController extends Controller
{
    public function __construct(
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        $query = Payment::with(['organization', 'subscription.plan', 'invoice']);

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('provider_reference', $like, "%{$search}%")
                    ->orWhereHas('organization', fn ($orgQ) => $orgQ->where('name', $like, "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $payments = $query->latest('paid_at')->paginate(15)->withQueryString()->through(function ($p) {
            return [
                'id' => $p->id,
                'organization_id' => $p->organization_id,
                'organization_name' => $p->organization?->name ?? 'N/A',
                'plan_name' => $p->subscription?->plan?->name ?? 'N/A',
                'invoice_number' => $p->invoice?->invoice_number,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'provider' => $p->provider,
                'provider_reference' => $p->provider_reference,
                'paid_at' => $p->paid_at?->format('d/m/Y H:i'),
            ];
        });

        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        $totalPaidAmount = (int) Payment::where('status', 'paid')->sum('amount');

        return Inertia::render('Platform/Payments/Index', [
            'payments' => $payments,
            'organizations' => $organizations,
            'totalPaidAmount' => $totalPaidAmount,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Record a manual payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'max:10'],
            'provider' => ['required', 'string', 'max:50'],
            'provider_reference' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['required', 'date'],
        ]);

        $org = Organization::findOrFail($validated['organization_id']);
        $subscription = $org->currentSubscription;

        $payment = Payment::create([
            'organization_id' => $org->id,
            'subscription_id' => $subscription?->id,
            'invoice_id' => $validated['invoice_id'] ?? null,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'status' => 'paid',
            'provider' => $validated['provider'],
            'provider_reference' => $validated['provider_reference'] ?? 'PAY-MAN-'.strtoupper(bin2hex(random_bytes(4))),
            'paid_at' => $validated['paid_at'],
        ]);

        // If an invoice is linked, mark it paid
        if (! empty($validated['invoice_id'])) {
            Invoice::where('id', $validated['invoice_id'])->update([
                'status' => 'paid',
                'paid_at' => $validated['paid_at'],
            ]);
        }

        $this->auditService->log(
            action: 'platform.payment.recorded',
            description: "Paiement de {$payment->amount} {$payment->currency} enregistré pour '{$org->name}'.",
            target: $payment,
            organizationId: $org->id
        );

        return back()->with('success', 'Paiement enregistré avec succès.');
    }
}
