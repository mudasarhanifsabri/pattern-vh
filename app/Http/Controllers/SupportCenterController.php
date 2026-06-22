<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AutoReplyRule;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\QuickReply;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TicketAttachment;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserOnlineStatus;
use App\Support\SupportConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SupportCenterController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SupportTicket::class);
        $manage = $request->user()->can('support.manage');

        $tickets = SupportTicket::query()
            ->with(['category', 'assignee.onlineStatus', 'messages' => fn ($query) => $query
                ->when(! $manage, fn ($query) => $query->where('is_internal_note', false))
                ->latest()
                ->limit(1)])
            ->when(! $manage, fn ($query) => $query->where(fn ($query) => $query
                ->where('requester_user_id', $request->user()->id)
                ->orWhere('requester_email', $request->user()->email)))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('support_category_id', $request->integer('category')))
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('ticket_no', 'like', '%'.$request->string('search').'%')
                ->orWhere('subject', 'like', '%'.$request->string('search').'%')
                ->orWhere('requester_name', 'like', '%'.$request->string('search').'%')))
            ->orderByRaw("case when priority = 'urgent' then 1 when priority = 'high' then 2 when priority = 'medium' then 3 else 4 end")
            ->latest('updated_at')
            ->get();

        $selected = $request->filled('ticket')
            ? SupportTicket::with($this->ticketRelations())->findOrFail($request->integer('ticket'))
            : $tickets->first()?->load($this->ticketRelations());

        if ($selected) {
            $this->authorize('view', $selected);
            if (! $manage) {
                $selected->setRelation('messages', $selected->messages->where('is_internal_note', false)->values());
            }
        }

        return view('support.index', array_merge($this->formData($manage), [
            'tickets' => $tickets,
            'selected' => $selected,
            'manage' => $manage,
            'quickReplies' => QuickReply::where('is_active', true)->orderBy('title')->get(),
        ]));
    }

    public function create(Request $request)
    {
        $this->authorize('create', SupportTicket::class);
        $manage = $request->user()->can('support.manage');

        return view('support.create', $this->formData($manage) + [
            'manage' => $manage,
        ]);
    }

    public function store(Request $request, SupportConversationService $service)
    {
        $this->authorize('create', SupportTicket::class);
        $validated = $this->validateTicket($request);
        $user = $request->user();
        $validated = array_merge($validated, [
            'requester_type' => 'existing',
            'requester_role' => $user->getRoleNames()->first() ?: 'User',
            'requester_name' => $user->name,
            'requester_email' => $user->email,
            'requester_user_id' => $user->id,
            'channel' => 'portal',
        ]);

        $ticket = $service->create($validated, $user, $request->file('attachment'));

        return redirect()->route('support.index', ['ticket' => $ticket->id])->with('status', 'Support conversation created.');
    }

    public function update(Request $request, SupportTicket $supportTicket)
    {
        $this->authorize('update', $supportTicket);
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:191'],
            'support_category_id' => ['nullable', 'exists:support_categories,id'],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
            'owner_id' => ['nullable', 'exists:owners,id'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'operations_team_member_id' => ['nullable', 'exists:operations_team_members,id'],
            'payment_id' => ['nullable', 'exists:payments,id'],
        ]);

        $validated['updated_by'] = $request->user()->id;
        $validated['resolved_at'] = $validated['status'] === 'resolved' ? ($supportTicket->resolved_at ?: now()) : null;
        $validated['closed_at'] = $validated['status'] === 'closed' ? ($supportTicket->closed_at ?: now()) : null;
        $originalStatus = $supportTicket->status;
        $originalAssignee = $supportTicket->assigned_to;
        $supportTicket->update($validated);

        if ($originalStatus !== $supportTicket->status) {
            $this->logPushEvent($supportTicket, 'Support status updated', "{$supportTicket->ticket_no} is now ".str($supportTicket->status)->replace('_', ' ')->headline(), collect([$supportTicket->requester_user_id, $supportTicket->assigned_to]));
        }

        if ($supportTicket->assigned_to && $originalAssignee !== $supportTicket->assigned_to) {
            $this->logPushEvent($supportTicket, 'Support ticket assigned', "{$supportTicket->ticket_no}: {$supportTicket->subject}", collect([$supportTicket->assigned_to]));
        }

        return back()->with('status', 'Ticket details updated.');
    }

    public function destroy(SupportTicket $supportTicket)
    {
        $this->authorize('delete', $supportTicket);
        $supportTicket->delete();

        return redirect()->route('support.index')->with('status', 'Support ticket deleted.');
    }

    public function reply(Request $request, SupportTicket $supportTicket, SupportConversationService $service)
    {
        $this->authorize('view', $supportTicket);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_internal_note' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        if (! $request->user()->can('support.manage')) {
            $validated['is_internal_note'] = false;
        }
        $service->reply($supportTicket, $validated, $request->user(), $request->file('attachment'));

        return redirect()->route('support.index', ['ticket' => $supportTicket->id])->with('status', $validated['is_internal_note'] ?? false ? 'Private note added.' : 'Reply sent.');
    }

    public function convert(Request $request, SupportTicket $supportTicket)
    {
        $this->authorize('update', $supportTicket);
        $supportTicket->update(['mode' => 'ticket', 'status' => 'open', 'updated_by' => $request->user()->id]);

        return back()->with('status', 'Chat converted to ticket.');
    }

    public function messages(Request $request, SupportTicket $supportTicket)
    {
        $this->authorize('view', $supportTicket);
        $messages = $supportTicket->messages()->with('attachments')->when(! $request->user()->can('support.manage'), fn ($query) => $query->where('is_internal_note', false))->oldest()->get();

        return response()->json($messages);
    }

    public function attachment(Request $request, TicketAttachment $attachment)
    {
        $this->authorize('view', $attachment->ticket);

        return $this->downloadAttachment($attachment);
    }

    public function ping(Request $request)
    {
        UserOnlineStatus::updateOrCreate(['user_id' => $request->user()->id], ['is_online' => true, 'last_seen_at' => now()]);

        return response()->json(['ok' => true, 'at' => now()->toIso8601String()]);
    }

    public function reports(Request $request)
    {
        abort_unless($request->user()->can('support.reports'), 403);
        $tickets = SupportTicket::query();
        $resolved = SupportTicket::whereNotNull('resolved_at')->get();
        $staff = User::permission('support.manage')->withCount(['assignedSupportTickets as assigned_count', 'supportMessages as reply_count' => fn ($query) => $query->where('sender_type', 'staff')->where('is_internal_note', false)])->get();

        return view('support.reports', [
            'stats' => [
                'total' => (clone $tickets)->count(),
                'open' => (clone $tickets)->whereIn('status', ['open', 'waiting_for_customer', 'in_progress'])->count(),
                'resolved' => (clone $tickets)->where('status', 'resolved')->count(),
                'average_response_minutes' => round(SupportTicket::whereNotNull('first_response_at')->get()->avg(fn ($ticket) => $ticket->created_at->diffInMinutes($ticket->first_response_at)) ?? 0, 1),
            ],
            'categoryRows' => SupportCategory::withCount('tickets')->orderByDesc('tickets_count')->get(),
            'staffRows' => $staff,
        ]);
    }

    public function quickReplies(Request $request)
    {
        abort_unless($request->user()->can('support.manage'), 403);

        return view('support.quick-replies', [
            'quickReplies' => QuickReply::with('category')->latest()->get(),
            'categories' => SupportCategory::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function autoReplyRules(Request $request)
    {
        abort_unless($request->user()->can('support.manage'), 403);

        return view('support.auto-reply-rules', [
            'rules' => AutoReplyRule::with('category')->orderBy('priority')->get(),
            'categories' => SupportCategory::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function storeQuickReply(Request $request)
    {
        abort_unless($request->user()->can('support.manage'), 403);
        QuickReply::create($request->validate(['support_category_id' => ['nullable', 'exists:support_categories,id'], 'title' => ['required', 'string', 'max:191'], 'body' => ['required', 'string', 'max:3000']]) + ['is_active' => true]);
        return back()->with('status', 'Quick reply saved.');
    }

    public function storeRule(Request $request)
    {
        abort_unless($request->user()->can('support.manage'), 403);
        $validated = $request->validate(['support_category_id' => ['nullable', 'exists:support_categories,id'], 'name' => ['required', 'string', 'max:191'], 'keywords' => ['required', 'string', 'max:1000'], 'response' => ['required', 'string', 'max:3000']]);
        $validated['keywords'] = collect(explode(',', $validated['keywords']))->map(fn ($item) => trim($item))->filter()->values()->all();
        $validated['is_active'] = true;
        AutoReplyRule::create($validated);
        return back()->with('status', 'Auto reply rule saved.');
    }

    private function validateTicket(Request $request): array
    {
        return $request->validate([
            'mode' => ['required', Rule::in(SupportTicket::MODES)],
            'support_category_id' => ['nullable', 'exists:support_categories,id'],
            'subject' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'status' => ['nullable', Rule::in(SupportTicket::STATUSES)],
            'booking_id' => ['nullable', 'exists:bookings,id'], 'unit_id' => ['nullable', 'exists:units,id'],
            'tenant_id' => ['nullable', 'exists:tenants,id'], 'owner_id' => ['nullable', 'exists:owners,id'],
            'agent_id' => ['nullable', 'exists:agents,id'], 'operations_team_member_id' => ['nullable', 'exists:operations_team_members,id'],
            'payment_id' => ['nullable', 'exists:payments,id'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]) + ['status' => 'open'];
    }

    private function formData(bool $manage): array
    {
        return [
            'categories' => SupportCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'staff' => $manage ? User::permission('support.manage')->orderBy('name')->get() : collect(),
            'bookings' => $manage ? Booking::with(['tenant', 'unit.building'])->latest()->limit(100)->get() : collect(),
            'units' => $manage ? Unit::with('building')->orderBy('unit_no')->get() : collect(),
            'tenants' => $manage ? Tenant::orderBy('full_name')->get() : collect(),
            'owners' => $manage ? Owner::orderBy('full_name')->get() : collect(),
            'agents' => $manage ? Agent::orderBy('full_name')->get() : collect(),
            'maintainers' => $manage ? OperationsTeamMember::orderBy('full_name')->get() : collect(),
            'payments' => $manage ? Payment::latest('paid_at')->limit(100)->get() : collect(),
        ];
    }

    private function ticketRelations(): array
    {
        return ['category', 'assignee.onlineStatus', 'messages.attachments', 'booking.unit.building', 'unit.building', 'tenant', 'owner', 'agent', 'maintainer', 'payment.invoice'];
    }

    private function downloadAttachment(TicketAttachment $attachment)
    {
        $disk = Storage::disk($attachment->disk);
        if (method_exists($disk, 'temporaryUrl')) {
            try { return redirect()->away($disk->temporaryUrl($attachment->path, now()->addMinutes(10))); } catch (\Throwable) {}
        }
        return Response::streamDownload(fn () => print $disk->get($attachment->path), $attachment->original_name);
    }

    private function logPushEvent(SupportTicket $ticket, string $title, string $body, $userIds): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        collect($userIds)->filter()->unique()->each(function ($userId) use ($ticket, $title, $body): void {
            NotificationLog::create([
                'channel' => 'push',
                'recipient' => 'user:'.$userId,
                'subject' => $title,
                'message' => $body,
                'status' => 'pending',
                'payload' => [
                    'type' => 'support',
                    'ticket_id' => $ticket->id,
                    'ticket_no' => $ticket->ticket_no,
                    'url' => route('support.index', ['ticket' => $ticket->id]),
                ],
            ]);
        });
    }
}
