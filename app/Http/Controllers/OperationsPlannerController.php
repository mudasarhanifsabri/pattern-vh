<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\DtcmCheckin;
use App\Models\Invoice;
use App\Models\PaymentCollectionRequest;
use App\Models\UtilityAccount;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Dompdf\Dompdf;
use Illuminate\Http\Request;

class OperationsPlannerController extends Controller
{
    public function index(Request $request)
    {
        return view('planning-sheet.index', $this->plannerData($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->plannerData($request);
        $html = view('planning-sheet.pdf', $data)->render();

        $dompdf = new Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        $filename = 'pattern-planning-sheet-'.$data['start']->format('Y-m-d').'-to-'.$data['end']->format('Y-m-d').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function plannerData(Request $request): array
    {
        [$start, $end, $preset] = $this->resolveRange($request);
        $days = collect(CarbonPeriod::create($start, $end))->mapWithKeys(fn (Carbon $date) => [
            $date->toDateString() => [
                'date' => $date->copy(),
                'events' => collect(),
            ],
        ]);

        $this->bookings($start, $end)->each(function (Booking $booking) use ($days) {
            $unit = trim(($booking->unit?->building?->name ? $booking->unit->building->name.' / ' : '').($booking->unit?->unit_no ?? 'Unit'));
            if ($booking->check_in_date && $days->has($booking->check_in_date->toDateString())) {
                $days[$booking->check_in_date->toDateString()]['events']->push([
                    'type' => 'Check-in',
                    'tone' => 'blue',
                    'time' => $booking->check_in_time ?: 'Arrival',
                    'title' => $booking->tenant?->full_name ?: 'Guest check-in',
                    'subtitle' => $unit.' / '.$booking->booking_no,
                    'status' => str($booking->booking_status)->headline(),
                    'url' => route('bookings.show', $booking),
                ]);
            }
            if ($booking->check_out_date && $days->has($booking->check_out_date->toDateString())) {
                $days[$booking->check_out_date->toDateString()]['events']->push([
                    'type' => 'Check-out',
                    'tone' => 'slate',
                    'time' => $booking->check_out_time ?: 'Departure',
                    'title' => $booking->tenant?->full_name ?: 'Guest check-out',
                    'subtitle' => $unit.' / '.$booking->booking_no,
                    'status' => str($booking->booking_status)->headline(),
                    'url' => route('bookings.show', $booking),
                ]);
            }
        });

        $tasks = BookingTask::with(['booking.tenant', 'unit.building', 'assignee'])
            ->whereBetween('due_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->orderBy('due_at')
            ->get();

        $tasks->each(function (BookingTask $task) use ($days) {
            $key = $task->due_at?->toDateString();
            if (! $key || ! $days->has($key)) {
                return;
            }

            $days[$key]['events']->push([
                'type' => str($task->task_type)->replace('_', ' ')->headline(),
                'tone' => in_array($task->task_type, ['cleaning', 'checkout_cleaning']) ? 'emerald' : 'amber',
                'time' => $task->due_at->format('H:i'),
                'title' => $task->title,
                'subtitle' => trim(($task->unit?->building?->name ? $task->unit->building->name.' / ' : '').($task->unit?->unit_no ?? '').($task->assignee ? ' / '.$task->assignee->full_name : '')),
                'status' => str($task->status)->replace('_', ' ')->headline(),
                'url' => route('tasks.index'),
            ]);
        });

        $pendingInvoices = Invoice::with(['tenant', 'unit.building'])
            ->whereBetween('due_date', [$start, $end])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderBy('due_date')
            ->get();

        $pendingInvoices->each(function (Invoice $invoice) use ($days) {
            $key = $invoice->due_date?->toDateString();
            if (! $key || ! $days->has($key)) {
                return;
            }

            $days[$key]['events']->push([
                'type' => 'Pending invoice',
                'tone' => 'rose',
                'time' => 'Due',
                'title' => $invoice->invoice_no.' / AED '.number_format((float) $invoice->balance_amount, 2),
                'subtitle' => ($invoice->tenant?->full_name ?: 'Tenant').' / '.($invoice->unit?->unit_no ?: 'Unit'),
                'status' => str($invoice->status)->replace('_', ' ')->headline(),
                'url' => route('invoices.show', $invoice),
            ]);
        });

        $collectionRequests = PaymentCollectionRequest::with(['tenant', 'booking.unit.building', 'assignedTo'])
            ->whereBetween('preferred_date', [$start, $end])
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled'])
            ->orderBy('preferred_date')
            ->get();

        $collectionRequests->each(function (PaymentCollectionRequest $request) use ($days) {
            $key = $request->preferred_date?->toDateString();
            if (! $key || ! $days->has($key)) {
                return;
            }

            $days[$key]['events']->push([
                'type' => 'Collection request',
                'tone' => 'violet',
                'time' => $request->preferred_time_window ?: 'Tenant request',
                'title' => str($request->collection_method)->replace('_', ' ')->headline().' / AED '.number_format((float) $request->amount, 2),
                'subtitle' => ($request->tenant?->full_name ?: 'Tenant').' / '.($request->assignedTo?->full_name ?: 'Unassigned'),
                'status' => str($request->status)->replace('_', ' ')->headline(),
                'url' => route('payment-collection-requests.index'),
            ]);
        });

        $dtcmCheckins = DtcmCheckin::with(['booking.tenant', 'booking.unit.building'])
            ->whereHas('booking', fn ($query) => $query->whereBetween('check_in_date', [$start, $end]))
            ->where('status', '!=', 'completed')
            ->get();

        $dtcmCheckins->each(function (DtcmCheckin $dtcm) use ($days) {
            $key = $dtcm->booking?->check_in_date?->toDateString();
            if (! $key || ! $days->has($key)) {
                return;
            }

            $days[$key]['events']->push([
                'type' => 'DTCM check-in',
                'tone' => 'cyan',
                'time' => 'Authority',
                'title' => $dtcm->booking?->booking_no ?: 'DTCM pending',
                'subtitle' => ($dtcm->booking?->tenant?->full_name ?: 'Guest').' / '.($dtcm->booking?->unit?->unit_no ?: 'Unit'),
                'status' => str($dtcm->status)->headline(),
                'url' => route('dtcm-checkins.index'),
            ]);
        });

        $utilityAccounts = UtilityAccount::with('unit.building')
            ->whereBetween('next_due_date', [$start, $end])
            ->where('paid_by_company', true)
            ->get();

        $utilityAccounts->each(function (UtilityAccount $account) use ($days) {
            $key = $account->next_due_date?->toDateString();
            if (! $key || ! $days->has($key)) {
                return;
            }

            $days[$key]['events']->push([
                'type' => 'Utility due',
                'tone' => 'amber',
                'time' => 'Due',
                'title' => str($account->provider_type)->headline().' / AED '.number_format((float) $account->estimated_amount, 2),
                'subtitle' => ($account->provider_name ?: 'Provider').' / '.($account->unit?->building?->name ?: 'Building').' '.$account->unit?->unit_no,
                'status' => str($account->status ?: 'active')->headline(),
                'url' => route('utilities.index'),
            ]);
        });

        $days = $days->map(function (array $day) {
            $day['events'] = $day['events']->sortBy(fn ($event) => ($event['time'] === 'Due' ? '23:00' : $event['time']).$event['type'])->values();
            return $day;
        });

        $flat = $days->flatMap(fn ($day) => $day['events']);

        return [
            'start' => $start,
            'end' => $end,
            'preset' => $preset,
            'days' => $days,
            'stats' => [
                'check_ins' => $flat->where('type', 'Check-in')->count(),
                'check_outs' => $flat->where('type', 'Check-out')->count(),
                'tasks' => $tasks->count(),
                'pending_invoices' => $pendingInvoices->count(),
                'collections' => $collectionRequests->count(),
                'utilities' => $utilityAccounts->count(),
            ],
        ];
    }

    private function bookings(Carbon $start, Carbon $end)
    {
        return Booking::with(['tenant', 'unit.building'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('check_in_date', [$start, $end])
                    ->orWhereBetween('check_out_date', [$start, $end]);
            })
            ->whereNotIn('booking_status', ['cancelled'])
            ->orderBy('check_in_date')
            ->get();
    }

    private function resolveRange(Request $request): array
    {
        $preset = $request->string('preset', '7_days')->toString();
        $start = Carbon::parse($request->input('start', now()->toDateString()))->startOfDay();

        $end = match ($preset) {
            '2_days' => $start->copy()->addDay(),
            '14_days' => $start->copy()->addDays(13),
            'month' => $start->copy()->endOfMonth(),
            'custom' => Carbon::parse($request->input('end', $start->copy()->addDays(6)->toDateString()))->endOfDay(),
            default => $start->copy()->addDays(6),
        };

        if ($end->lt($start)) {
            $end = $start->copy();
        }

        if ($end->diffInDays($start) > 62) {
            $end = $start->copy()->addDays(62);
        }

        return [$start, $end->endOfDay(), $preset];
    }
}
