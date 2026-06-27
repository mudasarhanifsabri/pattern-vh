@php
    $plainDoorCode = str_replace(' ', '', (string) $smartLockCodeDisplay);
    $maskedDoorCode = is_numeric($plainDoorCode) ? trim(str_repeat('• ', max(4, strlen($plainDoorCode)))) : 'Hidden';
@endphp

<div x-data="{ visible: false }" class="mt-3 rounded-2xl bg-blue-50 px-4 py-3">
    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-500">Door code</p>
            <p class="mt-1 min-w-0 break-all text-2xl font-black tracking-[0.28em] text-blue-700 max-[380px]:text-xl" x-text="visible ? @js($smartLockCodeDisplay) : @js($maskedDoorCode)"></p>
        </div>
        <button type="button" x-on:click="visible = ! visible" class="touch-target grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-white text-blue-600" :aria-label="visible ? 'Hide door code' : 'Show door code'">
            <svg x-show="!visible" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
            <svg x-cloak x-show="visible" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.5 5.4A10.5 10.5 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.1 4.1"/><path d="M6.6 6.6C3.7 8.4 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/></svg>
        </button>
    </div>
</div>

<details class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
    <summary class="cursor-pointer text-sm font-black text-[#071a3b]">Change private code</summary>
    <form method="POST" action="{{ route('bookings.tenant-door-code.update', $booking) }}" class="mt-3 grid grid-cols-[1fr_auto] gap-2">
        @csrf
        <input name="door_code" inputmode="numeric" pattern="[0-9]{4,9}" maxlength="9" autocomplete="one-time-code" class="erp-focus h-12 min-w-0 rounded-2xl border border-slate-200 bg-white px-4 text-center text-lg font-black tracking-[0.2em] text-[#071a3b]" placeholder="New code">
        <button class="h-12 rounded-2xl bg-slate-950 px-4 text-sm font-black text-white">Set</button>
    </form>
</details>
