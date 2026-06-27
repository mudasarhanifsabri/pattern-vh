<?php

namespace App\Support;

use App\Models\NotificationLog;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    public function send(NotificationLog $notification): int
    {
        if ($notification->channel !== 'push' || ! $this->configured()) {
            return 0;
        }

        $subscriptions = $this->subscriptionsFor($notification);
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $sent = 0;
        $payload = json_encode($this->payloadFor($notification), JSON_THROW_ON_ERROR);
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], [
            'TTL' => 3600,
            'urgency' => 'normal',
            'batchSize' => 20,
        ], 8);

        foreach ($subscriptions as $subscription) {
            try {
                $report = $webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                    ]),
                    $payload
                );

                if ($report->isSuccess()) {
                    $sent++;
                    $subscription->forceFill(['last_used_at' => now()])->save();
                } elseif ($report->isSubscriptionExpired()) {
                    $subscription->delete();
                } else {
                    Log::warning('Web push failed.', [
                        'notification_id' => $notification->id,
                        'subscription_id' => $subscription->id,
                        'reason' => $report->getReason(),
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::warning('Web push exception.', [
                    'notification_id' => $notification->id,
                    'subscription_id' => $subscription->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($sent > 0 && ! $notification->sent_at) {
            $notification->forceFill([
                'status' => 'sent',
                'sent_at' => now(),
            ])->saveQuietly();
        }

        return $sent;
    }

    public function sendTest(User $user): int
    {
        $notification = new NotificationLog([
            'channel' => 'push',
            'recipient' => 'user:'.$user->id,
            'subject' => 'Pattern RMS notifications enabled',
            'message' => 'You will now receive ERP alerts on this device.',
            'status' => 'pending',
            'payload' => [
                'type' => 'test',
                'url' => route('dashboard'),
            ],
        ]);

        return $this->send($notification);
    }

    private function configured(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'))
            && filled(config('services.webpush.subject'));
    }

    private function subscriptionsFor(NotificationLog $notification): Collection
    {
        $userIds = collect();
        $recipient = (string) $notification->recipient;

        if (str_starts_with($recipient, 'user:')) {
            $userIds->push((int) str($recipient)->after('user:')->toString());
        } elseif (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $userIds = User::query()->where('email', $recipient)->pluck('id');
        }

        return PushSubscription::query()
            ->whereIn('user_id', $userIds->filter()->unique()->values())
            ->latest('last_used_at')
            ->get();
    }

    private function payloadFor(NotificationLog $notification): array
    {
        return [
            'title' => $notification->subject ?: 'Pattern RMS',
            'body' => $notification->message ?: 'You have a new Pattern RMS update.',
            'url' => data_get($notification->payload, 'url') ?: ($notification->booking_id ? route('bookings.show', $notification->booking_id) : route('dashboard')),
            'icon' => asset('icons/pattern-192.png'),
            'badge' => asset('icons/pattern-192.png'),
            'notification_id' => $notification->id,
        ];
    }
}
