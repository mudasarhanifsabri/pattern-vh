<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\ActivityLogger;
use App\Support\BookingConfirmationPdf;
use App\Support\BookingInvoiceScheduler;
use App\Support\BookingWorkflow;
use App\Support\TaxCalculator;
use App\Support\TtLockApi;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index()
    {
        $tenant = $this->tenantForAuth();
        $bookings = Booking::query()
            ->with(['unit.building', 'tenant', 'agent'])
            ->when($tenant && ! auth()->user()->can('bookings.manage'), fn ($query) => $query->where('tenant_id', $tenant->id))
            ->when(request('search'), function ($query, string $search): void {
                $query->where('booking_no', 'like', "%{$search}%")
                    ->orWhereHas('tenant', fn ($query) => $query->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn ($query) => $query->where('unit_no', 'like', "%{$search}%"));
            })
            ->when(request('status'), fn ($query, string $status) => $query->where('booking_status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        return view('bookings.create', $this->formData());
    }

    public function store(Request $request, BookingWorkflow $workflow, BookingInvoiceScheduler $invoiceScheduler)
    {
        $validated = $this->validated($request);
        $validated['rental_periods'] = $this->rentalPeriods($request);
        $this->ensureTenantHasSingleActiveBooking($validated);

        $validated['booking_no'] = $this->nextBookingNumber();
        $validated['vat_amount'] = TaxCalculator::rentVat(TaxCalculator::rentFromBookingData($validated));
        $validated['total_amount'] = TaxCalculator::bookingTotal($validated);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $booking = Booking::create($validated);
        $this->syncSmartLockAccess($booking);
        $workflow->afterSaved($booking);
        $invoiceScheduler->syncInvoices($booking->fresh(['tenant', 'unit']));

        ActivityLogger::log('bookings.created', "Created booking {$booking->booking_no}.", $booking);

        return redirect()->route('bookings.show', $booking)->with('status', 'Booking created successfully.');
    }

    public function show(Booking $booking)
    {
        $tenant = $this->tenantForAuth();
        abort_if($tenant && ! auth()->user()->can('bookings.manage') && (int) $booking->tenant_id !== (int) $tenant->id, 403);

        return view('bookings.show', [
            'booking' => $booking->load(['unit.building', 'unit.ttLock.setting', 'tenant', 'agent', 'tasks.assignee', 'tasks.events.user', 'notificationLogs', 'dtcmCheckin', 'extensionRequests.invoice', 'depositRefund', 'checkInInspectionItems', 'invoices.payments']),
        ]);
    }

    public function edit(Booking $booking)
    {
        return view('bookings.edit', array_merge($this->formData(), [
            'booking' => $booking,
        ]));
    }

    public function update(Request $request, Booking $booking, BookingWorkflow $workflow, BookingInvoiceScheduler $invoiceScheduler)
    {
        $validated = $this->validated($request, $booking);
        $validated['rental_periods'] = $this->rentalPeriods($request);
        $this->ensureTenantHasSingleActiveBooking($validated, $booking);

        $validated['vat_amount'] = TaxCalculator::rentVat(TaxCalculator::rentFromBookingData($validated));
        $validated['total_amount'] = TaxCalculator::bookingTotal($validated);
        $validated['updated_by'] = auth()->id();

        $booking->update($validated);
        $this->syncSmartLockAccess($booking);
        $workflow->afterSaved($booking->fresh(['unit', 'tenant']));
        $invoiceScheduler->syncInvoices($booking->fresh(['tenant', 'unit']));

        ActivityLogger::log('bookings.updated', "Updated booking {$booking->booking_no}.", $booking);

        return redirect()->route('bookings.show', $booking)->with('status', 'Booking updated successfully.');
    }

    public function destroy(Booking $booking)
    {
        ActivityLogger::log('bookings.deleted', "Deleted booking {$booking->booking_no}.", $booking);
        $booking->delete();

        return redirect()->route('bookings.index')->with('status', 'Booking deleted successfully.');
    }

    public function confirmationPdf(Booking $booking, BookingConfirmationPdf $pdf)
    {
        $booking->forceFill(['confirmation_sent_at' => now()])->save();
        $booking->notificationLogs()
            ->where('subject', 'Booking confirmation')
            ->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

        return response($pdf->make($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$booking->booking_no.'-confirmation.pdf"',
        ]);
    }

    public function updateSmartLockAccess(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'smart_lock_code_mode' => ['required', Rule::in(['auto', 'manual'])],
            'smart_lock_code' => ['nullable', 'string', 'max:32', 'required_if:smart_lock_code_mode,manual'],
            'smart_lock_code_valid_from' => ['nullable', 'date'],
            'smart_lock_code_valid_until' => ['nullable', 'date', 'after:smart_lock_code_valid_from'],
            'smart_lock_code_note' => ['nullable', 'string', 'max:1000'],
            'regenerate' => ['nullable', 'boolean'],
        ]);

        $booking->forceFill([
            'smart_lock_code_mode' => $validated['smart_lock_code_mode'],
            'smart_lock_code' => $validated['smart_lock_code_mode'] === 'manual'
                ? preg_replace('/\s+/', '', (string) $validated['smart_lock_code'])
                : ($request->boolean('regenerate') ? null : $booking->smart_lock_code),
            'smart_lock_code_valid_from' => $validated['smart_lock_code_valid_from'] ?? null,
            'smart_lock_code_valid_until' => $validated['smart_lock_code_valid_until'] ?? null,
            'smart_lock_code_note' => $validated['smart_lock_code_note'] ?? null,
            'updated_by' => auth()->id(),
        ])->save();

        $this->syncSmartLockAccess($booking);

        ActivityLogger::log('bookings.smart_lock_access_updated', "Updated smart lock access for {$booking->booking_no}.", $booking);

        return redirect()->route('bookings.show', $booking)->with('status', 'Smart lock access updated.');
    }

    public function controlSmartLock(Request $request, Booking $booking, TtLockApi $api): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['unlock', 'lock'])],
        ]);

        $tenant = $this->tenantForAuth();
        abort_if(! $tenant || (int) $booking->tenant_id !== (int) $tenant->id, 403);

        $booking->loadMissing(['unit.ttLock.setting']);
        $validFrom = $this->bookingDateTime($booking->check_in_date, $booking->check_in_time, '15:00');
        $validUntil = $this->bookingDateTime($booking->check_out_date, $booking->check_out_time, '11:00');

        if (! in_array($booking->booking_status, ['confirmed', 'checked_in', 'checkout_requested'], true)) {
            return response()->json(['message' => 'Smart lock access is not active for this booking.'], 423);
        }

        if (now()->lt($validFrom)) {
            return response()->json(['message' => 'Smart lock access starts '.$validFrom->format('d M Y, h:i A').'.'], 423);
        }

        if (now()->gt($validUntil)) {
            return response()->json(['message' => 'Smart lock access ended '.$validUntil->format('d M Y, h:i A').'.'], 423);
        }

        $lock = $booking->unit?->ttLock;
        if (! $lock || ! $lock->setting) {
            return response()->json(['message' => 'No connected smart lock is attached to this unit yet.'], 422);
        }

        try {
            $payload = $api->controlLock($lock, $validated['action']);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        ActivityLogger::log(
            'bookings.smart_lock_'.$validated['action'],
            str($validated['action'])->headline()." command sent for {$booking->booking_no}.",
            $booking,
        );

        return response()->json([
            'ok' => true,
            'status' => $validated['action'] === 'unlock' ? 'unlocked' : 'locked',
            'next_action' => $validated['action'] === 'unlock' ? 'lock' : 'unlock',
            'message' => $validated['action'] === 'unlock' ? 'Door unlocked. Swipe again to lock.' : 'Door locked. Swipe again to unlock.',
            'payload' => $payload,
        ]);
    }

    private function formData(): array
    {
        return [
            'units' => Unit::query()->with('building')->orderBy('unit_no')->get(),
            'tenants' => Tenant::query()->orderBy('full_name')->get(),
            'agents' => Agent::query()->orderBy('full_name')->get(),
            'types' => Booking::TYPES,
            'statuses' => ['draft', 'confirmed'],
        ];
    }

    private function validated(Request $request, ?Booking $booking = null): array
    {
        $request->merge([
            'check_in_time' => $this->normalizeTime($request->input('check_in_time')),
            'check_out_time' => $this->normalizeTime($request->input('check_out_time')),
        ]);

        $rules = [
            'booking_type' => ['required', Rule::in(Booking::TYPES)],
            'unit_id' => ['required', 'exists:units,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'check_in_date' => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:99'],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'dtcm_fee' => ['nullable', 'numeric', 'min:0'],
            'cleaning_fee' => ['nullable', 'numeric', 'min:0'],
            'agency_fee' => ['nullable', 'numeric', 'min:0'],
            'rental_periods' => ['nullable', 'array'],
            'rental_periods.*.index' => ['nullable', 'integer', 'min:1'],
            'rental_periods.*.label' => ['nullable', 'string', 'max:50'],
            'rental_periods.*.start' => ['nullable', 'date'],
            'rental_periods.*.end' => ['nullable', 'date'],
            'rental_periods.*.rent_amount' => ['nullable', 'numeric', 'min:0'],
            'booking_status' => ['required', Rule::in(['draft', 'confirmed'])],
            'source' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];

        if ($booking) {
            unset($rules['booking_status']);
        }

        $validated = $request->validate($rules);

        if ($booking) {
            $validated['booking_status'] = $booking->booking_status;
        }

        return $validated;
    }

    private function rentalPeriods(Request $request): array
    {
        return collect($request->input('rental_periods', []))
            ->filter(fn (array $period): bool => ! empty($period['start']) && ! empty($period['end']))
            ->values()
            ->map(fn (array $period, int $index): array => [
                'index' => (int) ($period['index'] ?? $index + 1),
                'label' => $period['label'] ?? '',
                'start' => $period['start'],
                'end' => $period['end'],
                'rent_amount' => (float) ($period['rent_amount'] ?? 0),
            ])
            ->all();
    }

    private function ensureTenantHasSingleActiveBooking(array $data, ?Booking $currentBooking = null): void
    {
        if (! in_array($data['booking_status'] ?? null, ['confirmed', 'checked_in', 'checkout_requested'], true)) {
            return;
        }

        $exists = Booking::query()
            ->when($currentBooking, fn ($query) => $query->whereKeyNot($currentBooking->id))
            ->where('tenant_id', $data['tenant_id'])
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tenant_id' => 'This tenant already has an active booking. Complete checkout or cancel the existing booking before creating another active booking.',
            ]);
        }
    }

    private function syncSmartLockAccess(Booking $booking, bool $force = false): void
    {
        if (! in_array($booking->booking_status, ['confirmed', 'checked_in', 'checkout_requested'], true)) {
            return;
        }

        $mode = $booking->smart_lock_code_mode ?: 'auto';
        $validFrom = $this->bookingDateTime($booking->check_in_date, $booking->check_in_time, '15:00');
        $validUntil = $this->bookingDateTime($booking->check_out_date, $booking->check_out_time, '11:00');
        $code = $booking->smart_lock_code;
        $durationChanged = ! $booking->smart_lock_code_valid_from?->equalTo($validFrom)
            || ! $booking->smart_lock_code_valid_until?->equalTo($validUntil);
        $ttLockAlreadyConfirmed = str_contains((string) $booking->smart_lock_code_note, 'TTLock passcode')
            && ! str_contains((string) $booking->smart_lock_code_note, 'not confirmed');
        $shouldSyncPasscode = $mode === 'auto' && ($force || ! $code || $durationChanged || ! $ttLockAlreadyConfirmed);
        $updates = [
            'smart_lock_code_mode' => $mode,
            'smart_lock_code_valid_from' => $validFrom,
            'smart_lock_code_valid_until' => $validUntil,
        ];

        if ($shouldSyncPasscode && ($force || ! $code || $durationChanged)) {
            $code = (string) random_int(100000, 999999);
            $updates['smart_lock_code'] = $code;
            $updates['smart_lock_code_generated_at'] = now();
        }

        $booking->forceFill($updates)->save();
        $booking->loadMissing(['unit.ttLock.setting']);

        if (! $shouldSyncPasscode || ! $code || ! $booking->unit?->ttLock?->setting) {
            return;
        }

        try {
            $result = app(TtLockApi::class)->addTimedPasscode(
                $booking->unit->ttLock,
                $code,
                $validFrom,
                $validUntil,
                "Booking {$booking->booking_no}",
            );

            $booking->forceFill([
                'smart_lock_code_note' => trim(sprintf(
                    'TTLock passcode %s%s for %s to %s.',
                    $result['keyboardPwdId'] ? '#'.$result['keyboardPwdId'] : 'created',
                    $result['verified'] ? ' verified' : ' sent',
                    $validFrom->format('M d, Y H:i'),
                    $validUntil->format('M d, Y H:i'),
                )),
            ])->save();
        } catch (\Throwable $exception) {
            $booking->forceFill([
                'smart_lock_code_note' => 'TTLock passcode not confirmed: '.$exception->getMessage(),
            ])->save();
        }
    }

    private function bookingDateTime($date, ?string $time, string $fallbackTime): Carbon
    {
        return Carbon::parse($date->format('Y-m-d').' '.($time ?: $fallbackTime));
    }

    private function normalizeTime(?string $time): ?string
    {
        if (! filled($time)) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return $time;
        }
    }

    private function nextBookingNumber(): string
    {
        return 'BK-'.now()->format('Ymd').'-'.str_pad((string) (Booking::withTrashed()->whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function tenantForAuth(): ?Tenant
    {
        if (! auth()->check() || ! auth()->user()->can('portal.tenant')) {
            return null;
        }

        return Tenant::query()->where('user_id', auth()->id())->orWhere('email', auth()->user()->email)->first();
    }
}
