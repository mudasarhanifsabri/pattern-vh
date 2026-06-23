<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\NotificationLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PushEventLogger
{
    public function toUserId(?int $userId, string $subject, string $message, array $payload = [], ?Booking $booking = null): ?NotificationLog
    {
        if (! $userId) {
            return null;
        }

        return $this->create('user:'.$userId, $subject, $message, $payload, $booking);
    }

    public function toUserIds($userIds, string $subject, string $message, array $payload = [], ?Booking $booking = null): Collection
    {
        return collect($userIds)
            ->filter()
            ->unique()
            ->map(fn (int|string $userId) => $this->toUserId((int) $userId, $subject, $message, $payload, $booking))
            ->filter()
            ->values();
    }

    public function toEmail(?string $email, string $subject, string $message, array $payload = [], ?Booking $booking = null): ?NotificationLog
    {
        if (! filled($email)) {
            return null;
        }

        return $this->create($email, $subject, $message, $payload, $booking);
    }

    public function toTenant($tenant, string $subject, string $message, array $payload = [], ?Booking $booking = null): ?NotificationLog
    {
        return $tenant?->user_id
            ? $this->toUserId((int) $tenant->user_id, $subject, $message, $payload, $booking)
            : $this->toEmail($tenant?->email, $subject, $message, $payload, $booking);
    }

    public function toOwner($owner, string $subject, string $message, array $payload = [], ?Booking $booking = null): ?NotificationLog
    {
        return $owner?->user_id
            ? $this->toUserId((int) $owner->user_id, $subject, $message, $payload, $booking)
            : $this->toEmail($owner?->email, $subject, $message, $payload, $booking);
    }

    public function toOperationsMember($member, string $subject, string $message, array $payload = [], ?Booking $booking = null): ?NotificationLog
    {
        return $member?->user_id
            ? $this->toUserId((int) $member->user_id, $subject, $message, $payload, $booking)
            : $this->toEmail($member?->email, $subject, $message, $payload, $booking);
    }

    private function create(string $recipient, string $subject, string $message, array $payload = [], ?Booking $booking = null): ?NotificationLog
    {
        if (! Schema::hasTable('notification_logs')) {
            return null;
        }

        return NotificationLog::create([
            'booking_id' => $booking?->id,
            'channel' => 'push',
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'status' => 'pending',
            'payload' => array_merge(['url' => route('dashboard')], $payload),
        ]);
    }
}
