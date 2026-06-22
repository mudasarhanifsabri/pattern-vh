<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Support\SupportConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PublicSupportController extends Controller
{
    public function create()
    {
        return view('support.public-create', [
            'categories' => SupportCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'roles' => ['Tenant', 'Owner/Landlord', 'Agent', 'Maintainer', 'Employee', 'Guest'],
        ]);
    }

    public function store(Request $request, SupportConversationService $service)
    {
        $validated = $request->validate([
            'requester_type' => ['required', Rule::in(['new', 'existing'])],
            'requester_role' => ['required', 'string', 'max:50'],
            'requester_name' => ['required', 'string', 'max:191'],
            'requester_email' => ['required', 'email', 'max:191'],
            'requester_mobile' => ['nullable', 'string', 'max:50'],
            'support_category_id' => ['nullable', 'exists:support_categories,id'],
            'subject' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'booking_reference' => ['nullable', 'string', 'max:191'],
            'payment_reference' => ['nullable', 'string', 'max:191'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $user = User::where('email', $validated['requester_email'])->first();
        $links = $this->resolveLinks($validated['requester_email'], $validated['booking_reference'] ?? null, $validated['payment_reference'] ?? null);
        unset($validated['booking_reference'], $validated['payment_reference'], $validated['attachment']);
        $validated = array_merge($validated, $links, [
            'mode' => 'chat', 'status' => 'open', 'channel' => 'public_link', 'requester_user_id' => $user?->id,
        ]);

        $ticket = $service->create($validated, null, $request->file('attachment'));

        return redirect()->route('support.public.thread', [$ticket, $ticket->public_token])->with('status', 'Your support conversation is open. Save this link to return later.');
    }

    public function thread(SupportTicket $supportTicket, string $token)
    {
        $this->validateToken($supportTicket, $token);
        $supportTicket->load(['category', 'messages' => fn ($query) => $query->where('is_internal_note', false)->with('attachments')->oldest(), 'booking.unit.building', 'unit.building']);
        $this->markPublicRead($supportTicket);

        return view('support.public-thread', ['ticket' => $supportTicket, 'token' => $token]);
    }

    public function reply(Request $request, SupportTicket $supportTicket, string $token, SupportConversationService $service)
    {
        $this->validateToken($supportTicket, $token);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        $service->reply($supportTicket, $validated, null, $request->file('attachment'));

        return back()->with('status', 'Message sent.');
    }

    public function messages(SupportTicket $supportTicket, string $token)
    {
        $this->validateToken($supportTicket, $token);
        $this->markPublicRead($supportTicket);

        return response()->json($supportTicket->messages()->where('is_internal_note', false)->oldest()->get(['id', 'sender_type', 'sender_name', 'body', 'delivery_status', 'read_at', 'created_at']));
    }

    public function typing(Request $request, SupportTicket $supportTicket, string $token)
    {
        $this->validateToken($supportTicket, $token);

        if ($request->isMethod('post')) {
            Cache::put($this->typingKey($supportTicket, 'customer'), [
                'name' => $supportTicket->requester_name ?: 'Customer',
                'side' => 'customer',
                'at' => now()->toIso8601String(),
            ], now()->addSeconds(8));

            return response()->json(['ok' => true]);
        }

        $typing = Cache::get($this->typingKey($supportTicket, 'staff'));

        return response()->json([
            'is_typing' => (bool) $typing,
            'name' => $typing['name'] ?? null,
        ]);
    }

    public function attachment(SupportTicket $supportTicket, string $token, TicketAttachment $attachment)
    {
        $this->validateToken($supportTicket, $token);
        abort_unless((int) $attachment->support_ticket_id === (int) $supportTicket->id, 404);
        $disk = Storage::disk($attachment->disk);
        if (method_exists($disk, 'temporaryUrl')) {
            try { return redirect()->away($disk->temporaryUrl($attachment->path, now()->addMinutes(10))); } catch (\Throwable) {}
        }
        return Response::streamDownload(fn () => print $disk->get($attachment->path), $attachment->original_name);
    }

    private function resolveLinks(string $email, ?string $bookingReference, ?string $paymentReference): array
    {
        $tenant = Tenant::where('email', $email)->first();
        $owner = Owner::where('email', $email)->first();
        $agent = Agent::where('email', $email)->first();
        $maintainer = OperationsTeamMember::where('email', $email)->first();
        $booking = $bookingReference ? Booking::where('booking_no', $bookingReference)->first() : null;
        $payment = $paymentReference ? Payment::where('payment_no', $paymentReference)->orWhere('reference_no', $paymentReference)->first() : null;

        return [
            'tenant_id' => $tenant?->id,
            'owner_id' => $owner?->id,
            'agent_id' => $agent?->id,
            'operations_team_member_id' => $maintainer?->id,
            'booking_id' => $booking?->id,
            'unit_id' => $booking?->unit_id,
            'payment_id' => $payment?->id,
        ];
    }

    private function validateToken(SupportTicket $ticket, string $token): void
    {
        abort_unless(hash_equals((string) $ticket->public_token, $token), 403);
    }

    private function markPublicRead(SupportTicket $ticket): void
    {
        $ticket->messages()
            ->whereNull('read_at')
            ->whereIn('sender_type', ['staff', 'bot'])
            ->where('is_internal_note', false)
            ->update(['read_at' => now()]);
    }

    private function typingKey(SupportTicket $ticket, string $side): string
    {
        return "support:typing:{$ticket->id}:{$side}";
    }
}
