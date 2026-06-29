<div class="border-b border-slate-100 p-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-black text-[#071a3b]">Notifications</h2>
            <p data-notification-summary class="mt-1 text-xs text-slate-500">{{ $topbarNotificationCount ? $topbarNotificationCount.' unread workspace update'.($topbarNotificationCount === 1 ? '' : 's') : 'You are all caught up.' }}</p>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="rounded-xl bg-blue-50 px-3 py-2 text-[11px] font-black text-blue-700">Mark all read</button>
        </form>
    </div>
    @if(config('services.webpush.public_key'))
        <div data-push-panel class="mt-3 rounded-2xl bg-blue-50 p-3">
            <div class="flex items-center gap-3">
                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-blue-600">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0" /></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-black text-[#071a3b]">Device push alerts</p>
                    <p data-push-status class="mt-0.5 text-[11px] font-semibold text-slate-500">Enable alerts for this browser/app.</p>
                </div>
                <button type="button" data-push-enable class="rounded-xl bg-blue-600 px-3 py-2 text-[11px] font-black text-white">Enable</button>
                <button type="button" data-push-test class="hidden rounded-xl bg-white px-3 py-2 text-[11px] font-black text-blue-700">Test</button>
            </div>
        </div>
    @else
        <p class="mt-3 rounded-2xl bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-700">Push keys are not configured yet. Run <span class="font-black">php artisan webpush:vapid</span>.</p>
    @endif
</div>
<div data-notification-list class="max-h-[420px] space-y-2 overflow-y-auto p-2">
    @php
        $safeTopbarNotifications = collect($topbarNotifications ?? [])->filter(
            fn ($item) => $item instanceof \App\Models\NotificationLog
        );
    @endphp
    @forelse($safeTopbarNotifications as $notification)
        @php
            $displayStatus = $notification->sent_at ? 'sent' : $notification->status;
            $isRead = (bool) $notification->is_read;
        @endphp
        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="mb-2 block last:mb-0">
            @csrf
            <button class="w-full rounded-2xl p-3 text-left transition {{ $isRead ? 'hover:bg-slate-50' : 'bg-blue-50 hover:bg-blue-100' }}">
                <span class="flex items-start gap-3">
                    <span class="mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $isRead ? 'bg-slate-100 text-slate-500' : 'bg-blue-600 text-white' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="line-clamp-1 text-xs font-black text-[#071a3b]">{{ $notification->subject ?: str($notification->channel)->replace('_', ' ')->headline() }}</span>
                            <span class="shrink-0 rounded-full {{ $displayStatus === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2 py-0.5 text-[10px] font-black">{{ str($displayStatus)->headline() }}</span>
                        </span>
                        <span class="mt-1 block line-clamp-2 text-xs leading-5 text-slate-500">{{ $notification->message ?: 'System notification' }}</span>
                        <span class="mt-2 block text-[10px] font-bold text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </span>
            </button>
        </form>
    @empty
        <div class="px-4 py-10 text-center">
            <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            </div>
            <p class="mt-3 text-sm font-bold text-slate-500">No notifications yet.</p>
        </div>
    @endforelse
</div>
