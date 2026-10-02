<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformUser;
use App\Models\SupportTicket;
use App\Services\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSupportWebController extends Controller
{
    public function __construct(
        protected PlatformAuditService $auditService
    ) {}

    /**
     * Display a listing of support tickets.
     */
    public function index(Request $request): Response
    {
        $query = SupportTicket::with(['organization', 'reporter', 'assignedAgent']);

        if ($search = $request->input('search')) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('ticket_number', $like, "%{$search}%")
                    ->orWhere('subject', $like, "%{$search}%")
                    ->orWhereHas('organization', fn ($orgQ) => $orgQ->where('name', $like, "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString()->through(function ($t) {
            return [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'priority' => $t->priority,
                'status' => $t->status,
                'organization_id' => $t->organization_id,
                'organization_name' => $t->organization?->name ?? 'N/A',
                'reporter_name' => $t->reporter?->name ?? 'Client',
                'assigned_agent_name' => $t->assignedAgent?->name ?? 'Non assigné',
                'created_at' => $t->created_at?->format('d/m/Y H:i'),
                'resolved_at' => $t->resolved_at?->format('d/m/Y H:i'),
            ];
        });

        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $agents = PlatformUser::where('is_active', true)->get(['id', 'name']);

        return Inertia::render('Platform/Support/Index', [
            'tickets' => $tickets,
            'organizations' => $organizations,
            'agents' => $agents,
            'filters' => $request->only(['search', 'status', 'priority']),
        ]);
    }

    /**
     * Store a new support ticket.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'platform_user_id' => ['nullable', 'exists:platform_users,id'],
        ]);

        $ticket = SupportTicket::create([
            'organization_id' => $validated['organization_id'],
            'ticket_number' => 'TCK-'.strtoupper(Str::random(8)),
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'platform_user_id' => $validated['platform_user_id'] ?? Auth::guard('platform')->id(),
        ]);

        $this->auditService->log(
            action: 'platform.support.ticket_created',
            description: "Ticket support #{$ticket->ticket_number} créé pour '{$ticket->organization?->name}'.",
            target: $ticket,
            organizationId: $ticket->organization_id
        );

        return back()->with('success', "Ticket {$ticket->ticket_number} créé avec succès.");
    }

    /**
     * Update status or assignment of a support ticket.
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'platform_user_id' => ['nullable', 'exists:platform_users,id'],
        ]);

        if ($validated['status'] === 'resolved' && ! $ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $old = $ticket->only(['status', 'priority', 'platform_user_id']);
        $ticket->update($validated);

        $this->auditService->log(
            action: 'platform.support.ticket_updated',
            description: "Ticket support #{$ticket->ticket_number} mis à jour.",
            target: $ticket,
            oldValues: $old,
            newValues: $validated,
            organizationId: $ticket->organization_id
        );

        return back()->with('success', 'Ticket mis à jour avec succès.');
    }
}
