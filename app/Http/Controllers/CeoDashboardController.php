<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\OwnerPayoutTransfer;
use App\Models\Payment;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CeoDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        [$from, $to] = $this->range($request);
        $invoices = Invoice::query()->whereNotIn('status', ['cancelled', 'draft'])->whereBetween('invoice_date', [$from, $to]);
        $rentRevenue = (float) (clone $invoices)->sum('rent_amount');
        $serviceRevenue = (float) (clone $invoices)->selectRaw('COALESCE(SUM(dtcm_fee + cleaning_fee + agency_fee), 0) total')->value('total');
        $vatLiability = (float) (clone $invoices)->sum('vat_amount');
        $depositLiability = (float) (clone $invoices)->sum('deposit_amount');
        $expenses = (float) Expense::whereBetween('incurred_on', [$from, $to])->sum('amount');
        $collections = (float) Payment::where('status', 'approved')->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount');
        $revenue = $rentRevenue + $serviceRevenue;
        $unitCount = max(Unit::count(), 1);
        $occupied = Unit::where('availability_status', 'occupied')->count();

        $months = collect(range(5, 0))->map(function (int $back): array {
            $month = now()->subMonths($back);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $invoiceQuery = Invoice::whereNotIn('status', ['cancelled', 'draft'])->whereBetween('invoice_date', [$start, $end]);
            $income = (float) (clone $invoiceQuery)->selectRaw('COALESCE(SUM(rent_amount + dtcm_fee + cleaning_fee + agency_fee), 0) total')->value('total');
            $cost = (float) Expense::whereBetween('incurred_on', [$start, $end])->sum('amount');

            return ['label' => $month->format('M'), 'revenue' => $income, 'expenses' => $cost, 'profit' => $income - $cost];
        });

        return view('ceo.dashboard', [
            'from' => $from,
            'to' => $to,
            'metrics' => compact('rentRevenue', 'serviceRevenue', 'revenue', 'expenses', 'collections', 'vatLiability', 'depositLiability') + [
                'netProfit' => $revenue - $expenses,
                'margin' => $revenue > 0 ? round((($revenue - $expenses) / $revenue) * 100, 1) : 0,
                'outstanding' => (float) Invoice::where('balance_amount', '>', 0)->whereNotIn('status', ['cancelled'])->sum('balance_amount'),
                'occupancy' => round(($occupied / $unitCount) * 100, 1),
            ],
            'months' => $months,
            'maxChart' => max((float) $months->max(fn ($row) => max($row['revenue'], $row['expenses'])), 1),
            'alerts' => [
                ['label' => 'Overdue invoices', 'value' => Invoice::where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->count(), 'route' => 'invoices.index', 'tone' => 'amber'],
                ['label' => 'Urgent tasks', 'value' => BookingTask::where('priority', 'urgent')->whereNotIn('status', ['completed', 'cancelled'])->count(), 'route' => 'tasks.index', 'tone' => 'rose'],
                ['label' => 'Payouts transferred', 'value' => OwnerPayoutTransfer::whereBetween('transferred_at', [$from, $to])->count(), 'route' => 'owner-payouts.index', 'tone' => 'blue'],
                ['label' => 'Active bookings', 'value' => Booking::whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])->count(), 'route' => 'bookings.index', 'tone' => 'emerald'],
            ],
            'recentExpenses' => Expense::with(['owner', 'unit.building'])->latest('incurred_on')->limit(6)->get(),
            'upcomingPayouts' => OwnerPayoutTransfer::with('owner')->latest()->limit(6)->get(),
        ]);
    }

    private function range(Request $request): array
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();

        return [$from, $to];
    }
}
