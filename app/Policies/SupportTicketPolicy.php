<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('support.view') || $user->can('support.manage');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->can('support.manage')
            || (int) $ticket->requester_user_id === (int) $user->id
            || ($ticket->requester_email && strcasecmp($ticket->requester_email, $user->email) === 0);
    }

    public function create(User $user): bool
    {
        return $user->can('support.view') || $user->can('support.manage');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->can('support.manage');
    }

    public function delete(User $user, SupportTicket $ticket): bool
    {
        return $user->can('support.manage');
    }
}
