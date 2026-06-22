<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class NotificationCenterController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        if (! self::tablesReady()) {
            return response()->json(['unread_count' => 0, 'items' => []]);
        }

        $user = $request->user();
        $notifications = $this->queryFor($request)
            ->withExists(['reads as is_read' => fn (Builder $query) => $query->where('user_id', $user->id)])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'unread_count' => $this->unreadCount($request),
            'items' => $notifications->map(fn (NotificationLog $notification): array => [
                'id' => $notification->id,
                'title' => $notification->subject ?: str($notification->channel)->replace('_', ' ')->headline()->toString(),
                'message' => str($notification->message ?: 'System notification')->limit(120)->toString(),
                'status' => $notification->sent_at ? 'sent' : $notification->status,
                'created_at' => $notification->created_at->diffForHumans(),
                'is_read' => (bool) $notification->is_read,
                'url' => route('notifications.read', $notification),
            ])->values(),
        ]);
    }

    public function read(Request $request, NotificationLog $notificationLog): RedirectResponse
    {
        abort_unless($this->canSee($request, $notificationLog), 403);
        $this->markRead($request, $notificationLog);

        return redirect()->to($this->targetUrl($notificationLog));
    }

    public function readAll(Request $request): RedirectResponse
    {
        if (self::tablesReady()) {
            $this->queryFor($request)->limit(100)->get()->each(fn (NotificationLog $notification) => $this->markRead($request, $notification));
        }

        Cache::forget($this->topbarCacheKey($request));

        return back()->with('status', 'Notifications marked as read.');
    }

    public static function topbarData(Request $request): array
    {
        if (! $request->user() || ! self::tablesReady()) {
            return ['topbarNotifications' => collect(), 'topbarNotificationCount' => 0];
        }

        $controller = app(self::class);

        return Cache::remember($controller->topbarCacheKey($request), now()->addSeconds(20), function () use ($controller, $request): array {
            $notifications = $controller->queryFor($request)
                ->withExists(['reads as is_read' => fn (Builder $query) => $query->where('user_id', $request->user()->id)])
                ->latest()
                ->limit(8)
                ->get();

            return [
                'topbarNotifications' => $notifications,
                'topbarNotificationCount' => $controller->unreadCount($request),
            ];
        });
    }

    private function queryFor(Request $request): Builder
    {
        $user = $request->user();

        return NotificationLog::query()
            ->with('booking.unit.building')
            ->when(! $user->can('notifications.manage'), fn (Builder $query) => $query->where(function (Builder $query) use ($user): void {
                $query->where('recipient', 'user:'.$user->id)
                    ->orWhere('recipient', $user->email);
            }));
    }

    private function unreadCount(Request $request): int
    {
        return $this->queryFor($request)
            ->whereDoesntHave('reads', fn (Builder $query) => $query->where('user_id', $request->user()->id))
            ->count();
    }

    private function canSee(Request $request, NotificationLog $notification): bool
    {
        $user = $request->user();

        return $user->can('notifications.manage')
            || $notification->recipient === 'user:'.$user->id
            || $notification->recipient === $user->email;
    }

    private function markRead(Request $request, NotificationLog $notification): void
    {
        $notification->reads()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['read_at' => now()]
        );

        Cache::forget($this->topbarCacheKey($request));
    }

    private static function tablesReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('notification_logs') && Schema::hasTable('notification_reads');
    }

    private function topbarCacheKey(Request $request): string
    {
        return 'topbar_notifications:user:'.$request->user()->id;
    }

    private function targetUrl(NotificationLog $notification): string
    {
        $payloadUrl = data_get($notification->payload, 'url');
        if ($payloadUrl) {
            return $payloadUrl;
        }

        if ($notification->booking_id) {
            return route('bookings.show', $notification->booking_id);
        }

        return route('dashboard');
    }
}
