<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\OwnerPayoutTransfer;
use App\Models\Payment;
use App\Models\Expense;
use App\Support\PushEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OwnerPayoutController extends Controller
{
    public function index(Request $request)
    {
        $owner = $this->ownerFor($request);
        $rows = $this->payoutRows($owner);

        return view('owner-payouts.index', [
            'owners' => Owner::orderBy('full_name')->get(),
            'owner' => $owner,
            'rows' => $rows,
            'stats' => [
                'upcoming' => $rows->where('status', 'upcoming')->sum('net_payout'),
                'ready' => $rows->where('status', 'ready')->sum('net_payout'),
                'transferred' => $rows->where('status', 'transferred')->sum('net_payout'),
                'total' => $rows->sum('net_payout'),
                'count' => $rows->count(),
            ],
        ]);
    }

    public function storeTransfer(Request $request, PushEventLogger $push)
    {
        $data = $request->validate([
            'owner_id' => ['required', 'exists:owners,id'],
            'payment_id' => ['required', 'exists:payments,id'],
            'transferred_at' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = Payment::query()
            ->with(['invoice.booking.unit.owners', 'booking.unit.owners'])
            ->where('status', 'approved')
            ->findOrFail($data['payment_id']);

        $row = $this->payoutRows(Owner::findOrFail($data['owner_id']))
            ->firstWhere('payment.id', $payment->id);

        abort_unless($row && in_array($row['status'], ['ready', 'transferred'], true), 422, 'This payout is not ready to transfer yet.');

        $transfer = OwnerPayoutTransfer::updateOrCreate(
            ['owner_id' => $data['owner_id'], 'payment_id' => $payment->id],
            [
                'booking_id' => $row['booking']?->id,
                'unit_id' => $row['unit']?->id,
                'gross_share' => $row['gross_share'],
                'management_fee' => $row['management_fee'],
                'owner_expenses' => $row['owner_expenses'],
                'net_payout' => $row['net_payout'],
                'collection_date' => $row['collection_date'],
                'payable_on' => $row['payable_on'],
                'transferred_at' => $data['transferred_at'],
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ],
        );

        $owner = Owner::findOrFail($data['owner_id']);
        $push->toOwner(
            $owner,
            'Owner payout transferred',
            'Your payout of AED '.number_format((float) $transfer->net_payout, 2).' has been marked as transferred.',
            ['type' => 'owner_payout_transferred', 'owner_payout_transfer_id' => $transfer->id, 'url' => route('owner-payouts.index')],
            $transfer->booking
        );

        return back()->with('status', 'Owner payout transfer recorded.');
    }

    private function ownerFor(Request $request): ?Owner
    {
        if ($request->user()->can('owner-payouts.manage') && $request->filled('owner_id')) {
            return Owner::with('units')->find($request->integer('owner_id'));
        }

        if ($request->user()->can('portal.owner')) {
            return Owner::with('units')
                ->where('user_id', $request->user()->id)
                ->orWhere('email', $request->user()->email)
                ->first();
        }

        return null;
    }

    private function payoutRows(?Owner $owner = null): Collection
    {
        $transfers = OwnerPayoutTransfer::query()
            ->get()
            ->keyBy(fn (OwnerPayoutTransfer $transfer): string => $transfer->owner_id.'-'.$transfer->payment_id);

        return Payment::query()
            ->with(['invoice.booking.unit.building', 'invoice.booking.unit.owners', 'booking.unit.building', 'booking.unit.owners'])
            ->where('status', 'approved')
            ->whereHas('invoice', fn ($query) => $query
                ->where('status', 'paid')
                ->where('balance_amount', '<=', 0)
            )
            ->latest('approved_at')
            ->get()
            ->flatMap(function (Payment $payment) use ($owner, $transfers): Collection {
                $booking = $payment->invoice?->booking ?: $payment->booking;
                $unit = $booking?->unit;

                if (! $unit) {
                    return collect();
                }

                $owners = $unit->owners;
                if ($owner) {
                    $owners = $owners->where('id', $owner->id);
                }

                if ($owners->isEmpty()) {
                    return collect();
                }

                $rentCollected = min((float) $payment->amount, (float) ($payment->invoice?->rent_amount ?: $booking?->rent_amount ?: 0));
                if ($rentCollected <= 0) {
                    return collect();
                }

                $managementPercent = (float) ($unit->management_fee_percent ?? 0);

                return $owners->map(function (Owner $owner) use ($payment, $booking, $unit, $rentCollected, $managementPercent, $transfers): array {
                    $sharePercent = (float) ($owner->pivot?->share_percent ?? 100);
                    $grossShare = $rentCollected * ($sharePercent / 100);
                    $managementFee = $grossShare * ($managementPercent / 100);
                    $transfer = $transfers->get($owner->id.'-'.$payment->id);
                    $collectionDate = $booking?->check_in_date;
                    $payableOn = $booking?->check_out_date;
                    $ownerExpenses = (float) Expense::query()
                        ->where('owner_id', $owner->id)
                        ->where('unit_id', $unit->id)
                        ->when($booking?->check_out_date, fn ($query) => $query->whereDate('incurred_on', '<=', $booking->check_out_date->toDateString()))
                        ->sum('amount');
                    $netPayout = max($grossShare - $managementFee - $ownerExpenses, 0);

                    return [
                        'owner' => $owner,
                        'payment' => $payment,
                        'booking' => $booking,
                        'unit' => $unit,
                        'collection_date' => $collectionDate,
                        'payable_on' => $payableOn,
                        'share_percent' => $sharePercent,
                        'gross_share' => $grossShare,
                        'management_fee' => $managementFee,
                        'owner_expenses' => $ownerExpenses,
                        'net_payout' => $netPayout,
                        'transfer' => $transfer,
                        'status' => ($transfer && $transfer->transferred_at) ? 'transferred' : ($payableOn && $payableOn->isFuture() ? 'upcoming' : 'ready'),
                    ];
                });
            })
            ->values();
    }
}
