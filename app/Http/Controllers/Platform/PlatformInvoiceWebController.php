<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlatformInvoiceWebController extends Controller
{
    public function __construct(
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Display listing of invoices.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::with(['organization', 'subscription.plan']);

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('invoice_number', $like, "%{$search}%")
                    ->orWhereHas('organization', fn ($orgQ) => $orgQ->where('name', $like, "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString()->through(function ($inv) {
            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'organization_id' => $inv->organization_id,
                'organization_name' => $inv->organization?->name ?? 'N/A',
                'plan_name' => $inv->subscription?->plan?->name ?? 'N/A',
                'amount' => $inv->amount,
                'currency' => $inv->currency,
                'status' => $inv->status,
                'due_at' => $inv->due_at?->format('d/m/Y'),
                'paid_at' => $inv->paid_at?->format('d/m/Y'),
                'created_at' => $inv->created_at?->format('d/m/Y'),
            ];
        });

        $totalIssuedAmount = (int) Invoice::sum('amount');
        $totalPaidAmount = (int) Invoice::where('status', 'paid')->sum('amount');

        return Inertia::render('Platform/Invoices/Index', [
            'invoices' => $invoices,
            'totalIssuedAmount' => $totalIssuedAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Display detailed invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['organization', 'subscription.plan', 'payments']);

        return Inertia::render('Platform/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Mark an invoice as paid.
     */
    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $now = now();
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $now,
        ]);

        Payment::create([
            'organization_id' => $invoice->organization_id,
            'subscription_id' => $invoice->subscription_id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => 'paid',
            'provider' => 'manual',
            'provider_reference' => 'PAY-INV-'.strtoupper(bin2hex(random_bytes(4))),
            'paid_at' => $now,
        ]);

        $this->auditService->log(
            action: 'platform.invoice.paid',
            description: "Facture '{$invoice->invoice_number}' marquée comme payée.",
            target: $invoice,
            organizationId: $invoice->organization_id
        );

        return back()->with('success', "Facture {$invoice->invoice_number} marquée comme payée.");
    }
}
