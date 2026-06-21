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
            'booking' => $booking->load(['unit.building', 'tenant', 'agent', 'tasks.assignee', 'tasks.events.user', 'notificationLogs', 'dtcmCheckin', 'extensionRequests.invoice', 'depositRefund', 'checkInInspectionItems']),
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
        $validated = $this->validated($request);
        $validated['rental_periods'] = $this->rentalPeriods($request);
        $this->ensureTenantHasSingleActiveBooking($validated, $booking);

        $validated['vat_amount'] = TaxCalculator::rentVat(TaxCalculator::rentFromBookingData($validated));
        $validated['total_amount'] = TaxCalculator::bookingTotal($validated);
        $validated['updated_by'] = auth()->id();

        $booking->update($validated);
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

    private function formData(): array
    {
        return [
            'units' => Unit::query()->with('building')->orderBy('unit_no')->get(),
            'tenants' => Tenant::query()->orderBy('full_name')->get(),
            'agents' => Agent::query()->orderBy('full_name')->get(),
            'types' => Booking::TYPES,
            'statuses' => Booking::STATUSES,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
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
            'booking_status' => ['required', Rule::in(Booking::STATUSES)],
            'source' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
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
