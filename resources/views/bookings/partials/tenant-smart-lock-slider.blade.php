@php
    $smartLockNow = now();
    $smartLockCanControl = $booking
        && in_array($booking->booking_status, ['confirmed', 'checked_in', 'checkout_requested'], true)
        && $smartLockValidFrom
        && $smartLockValidUntil
        && $smartLockNow->betweenIncluded($smartLockValidFrom, $smartLockValidUntil)
        && (bool) $booking->unit?->ttLock?->setting;

    $smartLockDisabledMessage = 'Smart lock is not ready yet.';
    if ($booking && ! in_array($booking->booking_status, ['confirmed', 'checked_in', 'checkout_requested'], true)) {
        $smartLockDisabledMessage = 'Smart lock access is not active for this booking.';
    } elseif ($smartLockValidFrom && $smartLockNow->lt($smartLockValidFrom)) {
        $smartLockDisabledMessage = 'Access starts '.$smartLockValidFrom->format('d M Y, h:i A').'.';
    } elseif ($smartLockValidUntil && $smartLockNow->gt($smartLockValidUntil)) {
        $smartLockDisabledMessage = 'Access ended '.$smartLockValidUntil->format('d M Y, h:i A').'.';
    } elseif (! $booking?->unit?->ttLock?->setting) {
        $smartLockDisabledMessage = 'No connected smart lock is attached to this unit yet.';
    }
@endphp

<div class="mt-5" data-smart-lock-slider data-smart-lock-url="{{ $booking ? route('bookings.smart-lock-control', $booking) : '' }}" data-smart-lock-action="unlock" data-smart-lock-enabled="{{ $smartLockCanControl ? '1' : '0' }}">
    <div data-smart-lock-track class="relative h-16 touch-none overflow-hidden rounded-full {{ $smartLockCanControl ? 'cursor-grab bg-slate-950 shadow-xl shadow-slate-950/15 active:cursor-grabbing' : 'bg-slate-200' }} p-1.5">
        <div data-smart-lock-progress class="absolute inset-y-1.5 left-1.5 w-[52px] rounded-full bg-blue-600 transition-all duration-150"></div>
        <div data-smart-lock-label class="pointer-events-none absolute inset-0 grid place-items-center pl-14 pr-5 text-sm font-black {{ $smartLockCanControl ? 'text-white' : 'text-slate-500' }}">
            {{ $smartLockCanControl ? 'Swipe to unlock' : $smartLockDisabledMessage }}
        </div>
        <div data-smart-lock-thumb role="button" tabindex="{{ $smartLockCanControl ? '0' : '-1' }}" aria-disabled="{{ $smartLockCanControl ? 'false' : 'true' }}" class="touch-target absolute left-1.5 top-1.5 grid h-[52px] w-[52px] select-none place-items-center rounded-full bg-white text-blue-600 shadow-lg transition-transform duration-150 {{ $smartLockCanControl ? '' : 'opacity-60' }}" aria-label="Swipe smart lock control">
            <svg data-smart-lock-icon class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M7 11V7a5 5 0 0 1 9.8-1.4" /><rect x="5" y="11" width="14" height="10" rx="2" /></svg>
        </div>
    </div>
    <p data-smart-lock-message class="mt-3 text-center text-xs font-semibold {{ $smartLockCanControl ? 'text-slate-500' : 'text-amber-600' }}">
        {{ $smartLockCanControl ? 'Swipe fully right near the door. It will switch to lock after opening.' : $smartLockDisabledMessage }}
    </p>
</div>

@once
    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const iconPaths = {
                unlock: '<path d="M7 11V7a5 5 0 0 1 9.8-1.4" /><rect x="5" y="11" width="14" height="10" rx="2" />',
                lock: '<rect x="5" y="11" width="14" height="10" rx="2" /><path d="M8 11V7a4 4 0 0 1 8 0v4" />',
            };

            const initSlider = (slider) => {
                if (slider.dataset.ready === '1') return;
                slider.dataset.ready = '1';

                const thumb = slider.querySelector('[data-smart-lock-thumb]');
                const progress = slider.querySelector('[data-smart-lock-progress]');
                const label = slider.querySelector('[data-smart-lock-label]');
                const message = slider.querySelector('[data-smart-lock-message]');
                const icon = slider.querySelector('[data-smart-lock-icon]');
                const track = slider.querySelector('[data-smart-lock-track]');
                if (!thumb || !progress || !label || !message || !track || slider.dataset.smartLockEnabled !== '1') return;

                let dragging = false;
                let submitting = false;
                let startX = 0;
                let startLeft = 0;

                const maxLeft = () => Math.max(0, track.clientWidth - thumb.offsetWidth - 12);
                const setLeft = (left) => {
                    const safeLeft = Math.max(0, Math.min(left, maxLeft()));
                    thumb.style.transform = `translateX(${safeLeft}px)`;
                    progress.style.width = `${safeLeft + thumb.offsetWidth}px`;
                    return safeLeft;
                };
                const reset = () => {
                    thumb.classList.add('duration-150');
                    setLeft(0);
                    setTimeout(() => thumb.classList.remove('duration-150'), 170);
                };
                const setActionLabel = () => {
                    const action = slider.dataset.smartLockAction || 'unlock';
                    label.textContent = action === 'unlock' ? 'Swipe to unlock' : 'Swipe to lock';
                    icon.innerHTML = iconPaths[action] || iconPaths.unlock;
                    progress.classList.toggle('bg-emerald-600', action === 'lock');
                    progress.classList.toggle('bg-blue-600', action !== 'lock');
                };
                const submit = async () => {
                    if (submitting) return;
                    submitting = true;
                    thumb.setAttribute('aria-disabled', 'true');
                    const action = slider.dataset.smartLockAction || 'unlock';
                    label.textContent = action === 'unlock' ? 'Unlocking...' : 'Locking...';
                    message.textContent = 'Sending secure command to the smart lock.';

                    try {
                        const response = await fetch(slider.dataset.smartLockUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ action }),
                        });
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(data.message || 'Smart lock command failed.');

                        slider.dataset.smartLockAction = data.next_action || (action === 'unlock' ? 'lock' : 'unlock');
                        message.textContent = data.message || 'Smart lock command sent.';
                    } catch (error) {
                        message.textContent = error.message || 'Smart lock command failed.';
                    } finally {
                        submitting = false;
                        thumb.setAttribute('aria-disabled', 'false');
                        setActionLabel();
                        reset();
                    }
                };

                setActionLabel();

                const startDrag = (event) => {
                    if (submitting) return;
                    event.preventDefault();
                    dragging = true;
                    startX = event.clientX;
                    startLeft = Number(thumb.style.transform.match(/translateX\((\d+(?:\.\d+)?)px\)/)?.[1] || 0);
                    track.setPointerCapture(event.pointerId);
                    thumb.classList.remove('duration-150');
                };

                const moveDrag = (event) => {
                    if (!dragging) return;
                    setLeft(startLeft + event.clientX - startX);
                };

                const endDrag = () => {
                    if (!dragging) return;
                    dragging = false;
                    const currentLeft = Number(thumb.style.transform.match(/translateX\((\d+(?:\.\d+)?)px\)/)?.[1] || 0);
                    currentLeft >= maxLeft() * 0.82 ? submit() : reset();
                };

                track.addEventListener('pointerdown', startDrag);
                track.addEventListener('pointermove', moveDrag);
                track.addEventListener('pointerup', endDrag);
                track.addEventListener('pointercancel', () => {
                    dragging = false;
                    reset();
                });
                thumb.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    submit();
                });
            };

            const boot = () => document.querySelectorAll('[data-smart-lock-slider]').forEach(initSlider);
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', boot) : boot();
        })();
    </script>
@endonce
