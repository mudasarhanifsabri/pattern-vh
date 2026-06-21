<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDepositRefund;
use App\Models\BookingTask;
use App\Models\Building;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Owner;
use App\Models\OwnerPayoutTransfer;
use App\Models\OwnerUnitContract;
use App\Models\Payment;
use App\Models\PaymentCollectionRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UtilityBill;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()?->hasRole('CEO')) {
            return redirect()->route('ceo.dashboard');
        }

        $tenant = $this->tenantFor($request);
        $owner = $tenant ? null : $this->ownerFor($request);

        return view('dashboard', [
            'tenant' => $tenant,
            'owner' => $owner,
            'operationsDashboard' => (! $tenant && ! $owner) ? $this->operationsDashboard() : null,
            'currentBooking' => $tenant ? $this->currentTenantBooking($tenant) : null,
            'ownerUnits' => $owner ? $owner->units()->with('building')->get() : collect(),
            'stats' => $tenant ? $this->tenantStats($tenant) : ($owner ? $this->ownerStats($owner) : $this->workspaceStats()),
            'quickActions' => $tenant ? $this->tenantActions() : ($owner ? $this->ownerActions() : $this->workspaceActions()),
            'upcomingBookings' => $this->upcomingBookings($tenant),
            'bookingHistory' => $tenant ? $this->bookingHistory($tenant) : collect(),
            'attentionItems' => $this->attentionItems($tenant),
            'recentPayments' => $this->recentPayments($tenant),
        ]);
    }

    private function operationsDashboard(): array
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();
        $unitCount = max(Unit::count(), 1);
        $activeBookingStatuses = ['confirmed', 'checked_in', 'checkout_requested'];
        $activeBookings = Booking::whereIn('booking_status', $activeBookingStatuses)->count();
        $occupiedUnits = Unit::where('availability_status', 'occupied')->count();
        $occupancy = round(($occupiedUnits / $unitCount) * 100, 1);
        $revenue = (float) Payment::where('status', 'approved')
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->sum('amount');
        $expenses = (float) Expense::whereBetween('incurred_on', [$periodStart, $periodEnd])->sum('amount');
        $pendingBalance = (float) Invoice::where('balance_amount', '>', 0)->sum('balance_amount');
        $invoiceTotal = max((float) Invoice::whereBetween('invoice_date', [$periodStart, $periodEnd])->sum('total_amount'), 1);
        $collectionRate = round(($revenue / $invoiceTotal) * 100, 1);

        $monthSeries = collect(range(11, 0))->map(function (int $monthsBack): array {
            $month = now()->subMonths($monthsBack);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $gross = (float) Payment::where('status', 'approved')->whereBetween('paid_at', [$start, $end])->sum('amount');
            $expenses = (float) Expense::whereBetween('incurred_on', [$start, $end])->sum('amount');

            return [
                'label' => $month->format('M'),
                'gross' => $gross,
                'net' => max($gross - $expenses, 0),
            ];
        });

        $maxChartValue = max((float) $monthSeries->max('gross'), 1);
        $sourceRows = Booking::query()
            ->selectRaw("COALESCE(NULLIF(source, ''), 'Direct') as source_name, COUNT(*) as total")
            ->whereBetween('check_in_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->groupBy('source_name')
            ->orderByDesc('total')
            ->limit(3)
            ->get();
        $sourceTotal = max((int) $sourceRows->sum('total'), 1);

        $todayMovements = Booking::query()
            ->with(['tenant', 'unit.building'])
            ->where(function ($query) {
                $query->whereDate('check_in_date', today())
                    ->orWhereDate('check_out_date', today());
            })
            ->orderBy('check_in_date')
            ->limit(5)
            ->get();

        return [
            'periodLabel' => $periodStart->format('M j').' - '.$periodEnd->format('M j, Y'),
            'updatedLabel' => now()->format('H:i'),
            'cards' => [
                ['label' => 'Revenue', 'value' => 'AED '.number_format($revenue >= 1000 ? $revenue / 1000 : $revenue, $revenue >= 1000 ? 1 : 0).($revenue >= 1000 ? 'k' : ''), 'note' => '+12.4% vs last month', 'tone' => 'blue', 'icon' => 'M4 19V5m0 14h16M8 15l3-3 3 2 4-6'],
                ['label' => 'Occupancy', 'value' => $occupancy.'%', 'note' => $occupiedUnits.' occupied / '.Unit::where('availability_status', 'available')->count().' available', 'tone' => 'cyan', 'icon' => 'M7 3h10v18H7zM10 7h4M10 11h4M10 15h4'],
                ['label' => 'Active bookings', 'value' => $activeBookings, 'note' => '+'.Booking::whereDate('created_at', today())->count().' today', 'tone' => 'violet', 'icon' => 'M8 2v4m8-4v4M4 10h16M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2z'],
                ['label' => 'Pending payments', 'value' => 'AED '.number_format($pendingBalance >= 1000 ? $pendingBalance / 1000 : $pendingBalance, $pendingBalance >= 1000 ? 1 : 0).($pendingBalance >= 1000 ? 'k' : ''), 'note' => Invoice::where('balance_amount', '>', 0)->count().' invoices pending', 'tone' => 'amber', 'icon' => 'M8 4h8M9 4c0 3 6 3 6 6s-6 3-6 6h6m-6 4h8'],
            ],
            'monthSeries' => $monthSeries,
            'maxChartValue' => $maxChartValue,
            'revenueTotal' => $revenue,
            'occupancy' => $occupancy,
            'sourceSplit' => $sourceRows->map(fn ($row) => [
                'label' => $row->source_name,
                'value' => (int) $row->total,
                'percent' => round(((int) $row->total / $sourceTotal) * 100),
            ]),
            'occupiedNights' => Booking::whereIn('booking_status', $activeBookingStatuses)->sum('guest_count'),
            'availableNights' => Unit::where('availability_status', 'available')->count() * 30,
            'todayMovements' => $todayMovements,
            'checkinsToday' => Booking::whereDate('check_in_date', today())->count(),
            'checkoutsToday' => Booking::whereDate('check_out_date', today())->count(),
            'alertStrip' => [
                ['label' => 'Overdue Payments', 'value' => Invoice::where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->count(), 'note' => 'payments are overdue', 'tone' => 'amber', 'route' => 'invoices.index'],
                ['label' => 'Urgent Maintenance', 'value' => BookingTask::where('priority', 'urgent')->whereNotIn('status', ['completed', 'cancelled'])->count(), 'note' => 'urgent service requests', 'tone' => 'rose', 'route' => 'tasks.index'],
                ['label' => 'Expiring Contracts', 'value' => OwnerUnitContract::whereDate('contract_end_date', '>=', today())->whereDate('contract_end_date', '<=', now()->addDays(30))->count(), 'note' => 'owner contracts expiring in 30 days', 'tone' => 'cyan', 'route' => 'owner-contracts.index'],
            ],
            'miniCards' => [
                ['label' => 'Total units', 'value' => Unit::count(), 'note' => Building::count().' buildings in portfolio', 'tone' => 'rose'],
                ['label' => 'Occupancy rate', 'value' => $occupancy.'%', 'note' => $occupiedUnits.' of '.$unitCount.' units occupied', 'tone' => 'emerald'],
                ['label' => 'Monthly revenue', 'value' => 'AED '.number_format($revenue, 0), 'note' => 'Current month collected', 'tone' => 'emerald'],
                ['label' => 'Collection rate', 'value' => $collectionRate.'%', 'note' => 'Payment collection efficiency', 'tone' => 'cyan'],
                ['label' => 'Active tenants', 'value' => Tenant::count(), 'note' => Booking::whereIn('booking_status', $activeBookingStatuses)->count().' active stays', 'tone' => 'rose'],
                ['label' => 'Maintenance requests', 'value' => BookingTask::whereNotIn('status', ['completed', 'cancelled'])->count(), 'note' => BookingTask::where('priority', 'urgent')->count().' urgent', 'tone' => 'amber'],
                ['label' => 'Vacant units', 'value' => Unit::where('availability_status', 'available')->count(), 'note' => round((Unit::where('availability_status', 'available')->count() / $unitCount) * 100, 1).'% vacancy rate', 'tone' => 'rose'],
                ['label' => 'Average rent', 'value' => 'AED '.number_format((float) Unit::whereNotNull('rent_amount')->avg('rent_amount'), 0), 'note' => 'Per unit configured average', 'tone' => 'emerald'],
                ['label' => 'Renewals', 'value' => OwnerUnitContract::whereDate('contract_end_date', '>=', today())->whereDate('contract_end_date', '<=', now()->addDays(30))->count(), 'note' => 'Due in next 30 days', 'tone' => 'amber'],
                ['label' => 'Recent events', 'value' => NotificationLog::whereDate('created_at', '>=', now()->subDays(7))->count(), 'note' => 'Workspace updates this week', 'tone' => 'cyan'],
            ],
            'financialSummary' => [
                'netIncome' => $revenue - $expenses,
                'outstanding' => $pendingBalance,
                'revenueChange' => $revenue > 0 ? 100 : 0,
                'expenses' => $expenses,
            ],
            'paymentStatus' => [
                'collected' => $revenue,
                'pending' => (float) Payment::where('status', 'pending')->sum('amount'),
                'overdue' => (float) Invoice::where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount'),
            ],
            'propertyDistribution' => Unit::query()
                ->selectRaw("COALESCE(NULLIF(unit_type, ''), 'Other') as type_name, COUNT(*) as total")
                ->groupBy('type_name')
                ->orderByDesc('total')
                ->limit(4)
                ->get(),
            'ownerTransfers' => [
                'ready' => (float) Payment::where('status', 'approved')->whereDate('approved_at', '<=', now()->subDays(30))->sum('amount'),
                'transferred' => (float) OwnerPayoutTransfer::whereBetween('transferred_at', [$periodStart, $periodEnd])->sum('net_payout'),
                'count' => OwnerPayoutTransfer::whereBetween('transferred_at', [$periodStart, $periodEnd])->count(),
            ],
            'recentActivity' => NotificationLog::query()->with('booking.unit.building')->latest()->limit(5)->get(),
            'upcomingTasks' => BookingTask::query()->with(['booking.unit.building', 'assignee'])->whereNotIn('status', ['completed', 'cancelled'])->orderBy('due_at')->limit(3)->get(),
            'alerts' => [
                ['label' => 'Low inventory', 'note' => InventoryItem::whereColumn('quantity', '<=', 'reorder_level')->count().' items need refill', 'route' => 'inventory.index', 'tone' => 'amber'],
                ['label' => 'Payment approvals', 'note' => Payment::where('status', 'pending')->count().' proofs waiting', 'route' => 'invoices.index', 'tone' => 'blue'],
                ['label' => 'Checkout tasks', 'note' => BookingTask::whereIn('task_type', ['checkout_cleaning', 'checkout_inspection'])->where('status', 'open')->count().' open tasks', 'route' => 'tasks.index', 'tone' => 'rose'],
                ['label' => 'Utility due', 'note' => UtilityBill::whereIn('status', ['pending', 'overdue'])->whereDate('due_date', '<=', now()->addDays(7))->count().' bills this week', 'route' => 'utilities.index', 'tone' => 'amber'],
            ],
        ];
    }

    private function workspaceStats(): array
    {
        return [
            ['label' => 'Active bookings', 'value' => Booking::whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])->count(), 'note' => 'Confirmed, checked-in, or checkout pending', 'tone' => 'blue'],
            ['label' => 'Open balance', 'value' => 'AED '.number_format((float) Invoice::where('balance_amount', '>', 0)->sum('balance_amount'), 0), 'note' => 'Unpaid invoice balance', 'tone' => 'amber'],
            ['label' => 'Pending payments', 'value' => Payment::where('status', 'pending')->count(), 'note' => 'Proof waiting for finance approval', 'tone' => 'violet'],
            ['label' => 'Units managed', 'value' => Unit::count(), 'note' => Building::count().' buildings / '.Owner::count().' owners', 'tone' => 'cyan'],
        ];
    }

    private function tenantStats(Tenant $tenant): array
    {
        return [
            ['label' => 'Current stay', 'value' => $this->currentTenantBooking($tenant)?->booking_no ?? 'None', 'note' => 'One active booking at a time', 'tone' => 'blue'],
            ['label' => 'Balance due', 'value' => 'AED '.number_format((float) Invoice::where('tenant_id', $tenant->id)->where('balance_amount', '>', 0)->sum('balance_amount'), 0), 'note' => 'Pay online later or request collection now', 'tone' => 'amber'],
            ['label' => 'Active requests', 'value' => PaymentCollectionRequest::where('tenant_id', $tenant->id)->whereNotIn('status', ['approved', 'cancelled', 'rejected'])->count(), 'note' => 'Doorstep cash/card collection', 'tone' => 'violet'],
            ['label' => 'Refunds', 'value' => BookingDepositRefund::where('tenant_id', $tenant->id)->whereNotIn('status', ['refunded'])->count(), 'note' => 'Deposit refund workflows', 'tone' => 'cyan'],
        ];
    }

    private function ownerStats(Owner $owner): array
    {
        $unitIds = $owner->units()->pluck('units.id');

        return [
            ['label' => 'My units', 'value' => $unitIds->count(), 'note' => 'Units assigned to your owner account', 'tone' => 'blue'],
            ['label' => 'Rented', 'value' => Unit::whereIn('id', $unitIds)->where('availability_status', 'occupied')->count(), 'note' => 'Currently marked occupied', 'tone' => 'emerald'],
            ['label' => 'Vacant', 'value' => Unit::whereIn('id', $unitIds)->where('availability_status', 'available')->count(), 'note' => 'Available units', 'tone' => 'amber'],
            ['label' => 'Owner expenses', 'value' => 'AED '.number_format((float) Expense::where('owner_id', $owner->id)->sum('amount'), 0), 'note' => 'Expenses linked to your account', 'tone' => 'cyan'],
        ];
    }

    private function upcomingBookings(?Tenant $tenant)
    {
        return Booking::query()
            ->with(['tenant', 'unit.building'])
            ->when($tenant, fn ($query) => $query->where('tenant_id', $tenant->id))
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            ->orderBy('check_in_date')
            ->limit(5)
            ->get();
    }

    private function currentTenantBooking(Tenant $tenant): ?Booking
    {
        return Booking::query()
            ->with(['tenant', 'unit.building', 'dtcmCheckin', 'depositRefund'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            ->orderByDesc('check_in_date')
            ->first();
    }

    private function bookingHistory(Tenant $tenant)
    {
        return Booking::query()
            ->with(['unit.building'])
            ->where('tenant_id', $tenant->id)
            ->whereNotIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            ->latest('check_out_date')
            ->limit(8)
            ->get();
    }

    private function attentionItems(?Tenant $tenant): array
    {
        if ($tenant) {
            return [
                ['label' => 'Invoices waiting payment', 'value' => Invoice::where('tenant_id', $tenant->id)->where('balance_amount', '>', 0)->count(), 'route' => 'invoices.index'],
                ['label' => 'Deposit reports to review', 'value' => BookingDepositRefund::where('tenant_id', $tenant->id)->where('status', 'tenant_review')->count(), 'route' => 'bookings.index'],
                ['label' => 'Upcoming checkouts', 'value' => Booking::where('tenant_id', $tenant->id)->whereIn('booking_status', ['confirmed', 'checked_in'])->whereBetween('check_out_date', [now()->toDateString(), now()->addDays(7)->toDateString()])->count(), 'route' => 'bookings.index'],
            ];
        }

        return [
            ['label' => 'Payment approvals', 'value' => Payment::where('status', 'pending')->count(), 'route' => 'invoices.index'],
            ['label' => 'DTCM pending', 'value' => Booking::whereHas('dtcmCheckin', fn ($query) => $query->where('status', 'pending'))->count(), 'route' => 'dtcm-checkins.index'],
            ['label' => 'Checkout tasks', 'value' => BookingTask::whereIn('task_type', ['checkout_cleaning', 'checkout_inspection'])->where('status', 'open')->count(), 'route' => 'bookings.index'],
        ];
    }

    private function recentPayments(?Tenant $tenant)
    {
        return Payment::query()
            ->with(['invoice.tenant', 'booking.unit.building'])
            ->when($tenant, fn ($query) => $query->whereHas('invoice', fn ($invoice) => $invoice->where('tenant_id', $tenant->id)))
            ->latest()
            ->limit(5)
            ->get();
    }

    private function tenantActions(): array
    {
        return [
            ['label' => 'Request collection', 'route' => 'tenant.payment-requests.index', 'note' => 'Cash or card machine at your door', 'tone' => 'blue'],
            ['label' => 'My bookings', 'route' => 'bookings.index', 'note' => 'Extend stay, checkout, refund status', 'tone' => 'emerald'],
            ['label' => 'Invoices', 'route' => 'invoices.index', 'note' => 'View balance and receipts', 'tone' => 'amber'],
            ['label' => 'Profile', 'route' => 'profile.edit', 'note' => 'Update password and account', 'tone' => 'slate'],
        ];
    }

    private function ownerActions(): array
    {
        return [
            ['label' => 'Owner statement', 'route' => 'owner-statements.index', 'note' => 'Revenue, fees, expenses, net payout', 'tone' => 'blue'],
            ['label' => 'Reports', 'route' => 'reports.index', 'note' => 'Export account reports', 'tone' => 'emerald'],
            ['label' => 'Profile', 'route' => 'profile.edit', 'note' => 'Update password and account', 'tone' => 'slate'],
        ];
    }

    private function workspaceActions(): array
    {
        return [
            ['label' => 'Add booking', 'route' => 'bookings.create', 'note' => 'Holiday home or long-term', 'tone' => 'blue', 'can' => 'bookings.manage'],
            ['label' => 'Payment approvals', 'route' => 'invoices.index', 'note' => 'Approve proof and issue receipts', 'tone' => 'emerald', 'can' => 'payments.manage'],
            ['label' => 'DTCM check-ins', 'route' => 'dtcm-checkins.index', 'note' => 'Complete authority registration', 'tone' => 'amber', 'can' => 'dtcm-checkins.view'],
            ['label' => 'Units', 'route' => 'units.index', 'note' => 'Owners, documents, locks', 'tone' => 'slate', 'can' => 'units.view'],
        ];
    }

    private function tenantFor(Request $request): ?Tenant
    {
        if (! $request->user()?->can('portal.tenant')) {
            return null;
        }

        return Tenant::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }

    private function ownerFor(Request $request): ?Owner
    {
        if (! $request->user()?->can('portal.owner')) {
            return null;
        }

        return Owner::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }
}
