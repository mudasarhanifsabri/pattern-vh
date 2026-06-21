<?php

namespace App\Support;

use App\Models\AutoReplyRule;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Support\Str;

class SupportAutoReply
{
    public function respond(SupportTicket $ticket, string $message): ?SupportMessage
    {
        $role = Str::lower((string) $ticket->requester_role);
        $haystack = Str::lower($message);

        $rule = AutoReplyRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get()
            ->first(function (AutoReplyRule $rule) use ($haystack, $role): bool {
                $roleAllowed = empty($rule->roles) || collect($rule->roles)->map(fn ($item) => Str::lower($item))->contains($role);
                $keywordMatched = collect($rule->keywords)->contains(fn ($keyword) => Str::contains($haystack, Str::lower($keyword)));

                return $roleAllowed && $keywordMatched;
            });

        if (! $rule) {
            return null;
        }

        if (! $ticket->support_category_id && $rule->support_category_id) {
            $ticket->update(['support_category_id' => $rule->support_category_id]);
        }

        return $ticket->messages()->create([
            'sender_type' => 'bot',
            'sender_name' => 'Pattern Help Bot',
            'body' => $rule->response,
            'is_auto_reply' => true,
            'delivery_status' => 'sent',
            'whatsapp_template' => $rule->response,
        ]);
    }
}
