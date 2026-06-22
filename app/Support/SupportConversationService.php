<?php

namespace App\Support;

use App\Mail\SupportConversationMail;
use App\Models\NotificationLog;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportConversationService
{
    public function __construct(private SupportAutoReply $autoReply) {}

    public function create(array $data, ?User $user = null, ?UploadedFile $attachment = null): SupportTicket
    {
        $ticket = SupportTicket::create(array_merge($data, [
            'ticket_no' => $this->nextTicketNo(),
            'public_token' => Str::random(48),
            'requester_user_id' => $data['requester_user_id'] ?? $user?->id,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]));

        $message = $ticket->messages()->create([
            'user_id' => $user?->id,
            'sender_type' => 'customer',
            'sender_name' => $ticket->requester_name,
            'body' => $data['description'] ?: $data['subject'],
            'delivery_status' => 'sent',
        ]);

        if ($attachment) {
            $this->storeAttachment($ticket, $message, $attachment);
        }

        $this->autoReply->respond($ticket, $message->body);

        if ($ticket->requester_email) {
            Mail::to($ticket->requester_email)->queue(new SupportConversationMail($ticket, $message, true));
        }

        $this->logPushEvent($ticket, 'New support request', "{$ticket->ticket_no}: {$ticket->subject}", $this->supportManagers());

        ActivityLogger::log('support.created', "Created support conversation {$ticket->ticket_no}.", $ticket);

        return $ticket->fresh(['messages.attachments', 'category']);
    }

    public function reply(SupportTicket $ticket, array $data, ?User $user = null, ?UploadedFile $attachment = null): SupportMessage
    {
        $isStaff = $user?->can('support.manage') ?? false;
        $internal = $isStaff && (bool) ($data['is_internal_note'] ?? false);

        $message = $ticket->messages()->create([
            'user_id' => $user?->id,
            'sender_type' => $internal ? 'staff' : ($isStaff ? 'staff' : 'customer'),
            'sender_name' => $user?->name ?: $ticket->requester_name,
            'body' => $data['body'],
            'is_internal_note' => $internal,
            'delivery_status' => 'sent',
            'whatsapp_template' => $internal ? null : $data['body'],
        ]);

        if ($attachment) {
            $this->storeAttachment($ticket, $message, $attachment);
        }

        if ($isStaff && ! $internal) {
            $ticket->forceFill([
                'first_response_at' => $ticket->first_response_at ?: now(),
                'last_response_at' => now(),
                'status' => $ticket->status === 'open' ? 'in_progress' : $ticket->status,
                'updated_by' => $user->id,
            ])->save();

            if ($ticket->requester_email) {
                Mail::to($ticket->requester_email)->queue(new SupportConversationMail($ticket, $message));
                $message->update(['emailed_at' => now()]);
            }

            if ($ticket->requester_user_id) {
                $this->logPushEvent($ticket, 'Support replied', Str::limit($message->body, 120), collect([$ticket->requester_user_id]));
            }
        } elseif (! $internal) {
            $ticket->forceFill(['status' => $ticket->status === 'waiting_for_customer' ? 'in_progress' : $ticket->status])->save();
            $this->autoReply->respond($ticket, $message->body);

            $staffEmail = $ticket->assignee?->email ?: config('mail.from.address');
            if ($staffEmail && $staffEmail !== $ticket->requester_email) {
                Mail::to($staffEmail)->queue(new SupportConversationMail($ticket, $message));
            }

            $this->logPushEvent($ticket, 'Customer replied', Str::limit($message->body, 120), $ticket->assigned_to ? collect([$ticket->assigned_to]) : $this->supportManagers());
        }

        ActivityLogger::log($internal ? 'support.note_added' : 'support.replied', "Updated support conversation {$ticket->ticket_no}.", $ticket);

        return $message->fresh('attachments');
    }

    private function storeAttachment(SupportTicket $ticket, SupportMessage $message, UploadedFile $file): void
    {
        $disk = config('filesystems.default');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::random(6).'.'.$file->getClientOriginalExtension();
        $path = ErpStoragePath::documentPath('Support', $ticket->ticket_no, 'attachments', $file, $filename);
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        $ticket->attachments()->create([
            'support_message_id' => $message->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    private function nextTicketNo(): string
    {
        return 'SUP-'.now()->format('Ymd').'-'.str_pad((string) (SupportTicket::withTrashed()->whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function supportManagers()
    {
        return User::permission('support.manage')->pluck('id');
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
