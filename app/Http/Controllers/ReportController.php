<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $owner = $this->ownerFor($request);

        if ($owner) {
            return $this->ownerIndex($request, $owner, $from, $to);
        }

        abort_if($request->user()->can('portal.owner'), 403);

        $invoiceQuery = Invoice::whereNotIn('status', ['cancelled', 'draft'])->whereBetween('invoice_date', [$from, $to]);
        $rent = (float) (clone $invoiceQuery)->sum('rent_amount');
        $fees = (float) (clone $invoiceQuery)->selectRaw('COALESCE(SUM(dtcm_fee + cleaning_fee + agency_fee), 0) total')->value('total');
        $vat = (float) (clone $invoiceQuery)->sum('vat_amount');
        $deposits = (float) (clone $invoiceQuery)->sum('deposit_amount');
        $expenses = (float) Expense::whereBetween('incurred_on', [$from, $to])->sum('amount');
        $collections = (float) Payment::where('status', 'approved')->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount');
        $revenue = $rent + $fees;

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['name' => 'Operating revenue', 'type' => 'profit_loss', 'value' => $revenue, 'note' => 'Rent and non-refundable fees'],
                ['name' => 'Operating expenses', 'type' => 'expenses', 'value' => $expenses, 'note' => 'Recorded expenses in period'],
                ['name' => 'Net profit / loss', 'type' => 'profit_loss', 'value' => $revenue - $expenses, 'note' => $revenue > 0 ? round((($revenue - $expenses) / $revenue) * 100, 1).'% margin' : 'No revenue'],
                ['name' => 'Cash collected', 'type' => 'payments', 'value' => $collections, 'note' => 'Approved payments'],
            ],
            'profitLoss' => compact('rent', 'fees', 'revenue', 'expenses', 'vat', 'deposits') + ['net' => $revenue - $expenses],
            'expenseBreakdown' => Expense::query()->whereBetween('incurred_on', [$from, $to])->selectRaw('type, SUM(amount) total')->groupBy('type')->orderByDesc('total')->get(),
            'invoiceStatus' => Invoice::query()->selectRaw('status, COUNT(*) total, SUM(balance_amount) balance')->groupBy('status')->get(),
            'ownerReport' => false,
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:bookings,invoices,payments,expenses,profit_loss'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        [$from, $to] = $this->range($request);
        $type = $validated['type'];
        $owner = $this->ownerFor($request);

        abort_if($request->user()->can('portal.owner') && ! $owner, 403);

        return response()->streamDownload(function () use ($type, $from, $to, $owner): void {
            $handle = fopen('php://output', 'w');
            if ($owner) {
                match ($type) {
                    'bookings' => $this->ownerBookingCsv($handle, $owner, $from, $to),
                    'invoices' => $this->ownerInvoiceCsv($handle, $owner, $from, $to),
                    'payments' => $this->ownerPaymentCsv($handle, $owner, $from, $to),
                    'expenses' => $this->ownerExpenseCsv($handle, $owner, $from, $to),
                    'profit_loss' => $this->ownerProfitLossCsv($handle, $owner, $from, $to),
                };
            } else {
                match ($type) {
                    'bookings' => $this->bookingCsv($handle, $from, $to),
                    'invoices' => $this->invoiceCsv($handle, $from, $to),
                    'payments' => $this->paymentCsv($handle, $from, $to),
                    'expenses' => $this->expenseCsv($handle, $from, $to),
                    'profit_loss' => $this->profitLossCsv($handle, $from, $to),
                };
            }
            fclose($handle);
        }, "pattern-{$type}-{$from->format('Ymd')}-{$to->format('Ymd')}.csv", ['Content-Type' => 'text/csv']);
    }

    private function ownerIndex(Request $request, Owner $owner, Carbon $from, Carbon $to)
    {
        $rent = $this->ownerRentCollections($owner, $from, $to);
        $expenses = $this->ownerExpenseQuery($owner, $from, $to)->sum('amount');
        $revenue = $rent;

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['name' => 'Rent collected', 'type' => 'profit_loss', 'value' => $revenue, 'note' => 'Approved rent payments for your properties'],
                ['name' => 'Owner expenses', 'type' => 'expenses', 'value' => $expenses, 'note' => 'Expenses assigned to your properties'],
                ['name' => 'Net owner income', 'type' => 'profit_loss', 'value' => $revenue - $expenses, 'note' => $revenue > 0 ? round((($revenue - $expenses) / $revenue) * 100, 1).'% margin' : 'No rent collected'],
                ['name' => 'Cash collected', 'type' => 'payments', 'value' => $revenue, 'note' => 'Rent portion only'],
            ],
            'profitLoss' => [
                'rent' => $rent,
                'fees' => 0,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'vat' => 0,
                'deposits' => 0,
                'net' => $revenue - $expenses,
            ],
            'expenseBreakdown' => $this->ownerExpenseQuery($owner, $from, $to)->selectRaw('type, SUM(amount) total')->groupBy('type')->orderByDesc('total')->get(),
            'invoiceStatus' => $this->ownerInvoiceQuery($owner)->selectRaw('status, COUNT(*) total, SUM(balance_amount) balance')->groupBy('status')->get(),
            'ownerReport' => true,
            'owner' => $owner,
        ]);
    }

    private function ownerFor(Request $request): ?Owner
    {
        if (! $request->user()?->can('portal.owner')) {
            return null;
        }

        return Owner::with('units.building')
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }

    private function ownerInvoiceQuery(Owner $owner)
    {
        $unitIds = $owner->units->pluck('id');

        return Invoice::query()
            ->whereIn('unit_id', $unitIds)
            ->whereNotIn('status', ['cancelled', 'draft']);
    }

    private function ownerPaymentQuery(Owner $owner, Carbon $from, Carbon $to)
    {
        $unitIds = $owner->units->pluck('id');

        return Payment::query()
            ->with(['invoice.unit.building'])
            ->where('status', 'approved')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereHas('invoice', fn ($query) => $query
                ->whereIn('unit_id', $unitIds)
                ->whereNotIn('status', ['cancelled', 'draft']));
    }

    private function ownerExpenseQuery(Owner $owner, Carbon $from, Carbon $to)
    {
        $unitIds = $owner->units->pluck('id');

        return Expense::query()
            ->whereBetween('incurred_on', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) use ($owner, $unitIds): void {
                $query->where('owner_id', $owner->id)
                    ->orWhereIn('unit_id', $unitIds);
            });
    }

    private function ownerRentCollections(Owner $owner, Carbon $from, Carbon $to): float
    {
        $shareByUnit = $owner->units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);

        return (float) $this->ownerPaymentQuery($owner, $from, $to)
            ->get()
            ->sum(function (Payment $payment) use ($shareByUnit): float {
                $invoice = $payment->invoice;
                if (! $invoice || (float) $invoice->total_amount <= 0) {
                    return 0;
                }

                $rentPortion = (float) $payment->amount * ((float) $invoice->rent_amount / (float) $invoice->total_amount);
                $ownerShare = ($shareByUnit[$invoice->unit_id] ?? 100) / 100;

                return $rentPortion * $ownerShare;
            });
    }

    private function profitLossCsv($handle, Carbon $from, Carbon $to): void
    {
        $invoices = Invoice::whereNotIn('status', ['cancelled', 'draft'])->whereBetween('invoice_date', [$from, $to]);
        $rent = (float) (clone $invoices)->sum('rent_amount');
        $dtcm = (float) (clone $invoices)->sum('dtcm_fee');
        $cleaning = (float) (clone $invoices)->sum('cleaning_fee');
        $agency = (float) (clone $invoices)->sum('agency_fee');
        $expenses = (float) Expense::whereBetween('incurred_on', [$from, $to])->sum('amount');
        fputcsv($handle, ['Pattern Vacation Homes - Profit & Loss']);
        fputcsv($handle, ['Period', $from->format('Y-m-d').' to '.$to->format('Y-m-d')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Account', 'AED']);
        foreach ([['Rent revenue', $rent], ['DTCM fees', $dtcm], ['Cleaning fees', $cleaning], ['Agency fees', $agency], ['Total operating revenue', $rent + $dtcm + $cleaning + $agency], ['Operating expenses', -$expenses], ['Net profit / loss', $rent + $dtcm + $cleaning + $agency - $expenses], ['VAT payable (not revenue)', (float) (clone $invoices)->sum('vat_amount')], ['Refundable deposits (liability)', (float) (clone $invoices)->sum('deposit_amount')]] as $row) fputcsv($handle, $row);
    }

    private function bookingCsv($handle, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Booking', 'Tenant', 'Unit', 'Check in', 'Check out', 'Rent', 'VAT', 'Status']);
        Booking::with(['tenant', 'unit.building'])->whereBetween('check_in_date', [$from, $to])->orderByDesc('check_in_date')->each(fn (Booking $booking) => fputcsv($handle, [$booking->booking_no, $booking->tenant?->full_name, $booking->unit?->building?->name.' '.$booking->unit?->unit_no, $booking->check_in_date?->format('Y-m-d'), $booking->check_out_date?->format('Y-m-d'), $booking->rent_amount, $booking->vat_amount, $booking->booking_status]));
    }

    private function invoiceCsv($handle, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Invoice', 'Tenant', 'Rent', 'VAT', 'Total', 'Paid', 'Balance', 'Status', 'Due']);
        Invoice::with('tenant')->whereBetween('invoice_date', [$from, $to])->orderByDesc('invoice_date')->each(fn (Invoice $invoice) => fputcsv($handle, [$invoice->invoice_no, $invoice->tenant?->full_name, $invoice->rent_amount, $invoice->vat_amount, $invoice->total_amount, $invoice->paid_amount, $invoice->balance_amount, $invoice->status, $invoice->due_date?->format('Y-m-d')]));
    }

    private function paymentCsv($handle, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Payment', 'Invoice', 'Method', 'Amount', 'Status', 'Paid at']);
        Payment::with('invoice')->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->orderByDesc('paid_at')->each(fn (Payment $payment) => fputcsv($handle, [$payment->payment_no, $payment->invoice?->invoice_no, $payment->method, $payment->amount, $payment->status, $payment->paid_at?->format('Y-m-d H:i')]));
    }

    private function expenseCsv($handle, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Expense', 'Name', 'Type', 'Target', 'Owner', 'Unit', 'Date', 'Amount']);
        Expense::with(['owner', 'unit.building'])->whereBetween('incurred_on', [$from, $to])->orderByDesc('incurred_on')->each(fn (Expense $expense) => fputcsv($handle, [$expense->expense_no, $expense->name, $expense->type, $expense->expense_to_role, $expense->owner?->full_name, $expense->unit?->unit_no, $expense->incurred_on?->format('Y-m-d'), $expense->amount]));
    }

    private function ownerProfitLossCsv($handle, Owner $owner, Carbon $from, Carbon $to): void
    {
        $rent = $this->ownerRentCollections($owner, $from, $to);
        $expenses = (float) $this->ownerExpenseQuery($owner, $from, $to)->sum('amount');

        fputcsv($handle, ['Pattern Vacation Homes - Owner Income']);
        fputcsv($handle, ['Owner', $owner->full_name]);
        fputcsv($handle, ['Period', $from->format('Y-m-d').' to '.$to->format('Y-m-d')]);
        fputcsv($handle, []);
        fputcsv($handle, ['Account', 'AED']);
        fputcsv($handle, ['Collected rent income', $rent]);
        fputcsv($handle, ['Owner expenses', -$expenses]);
        fputcsv($handle, ['Net owner income', $rent - $expenses]);
    }

    private function ownerBookingCsv($handle, Owner $owner, Carbon $from, Carbon $to): void
    {
        $unitIds = $owner->units->pluck('id');

        fputcsv($handle, ['Booking', 'Tenant', 'Unit', 'Check in', 'Check out', 'Rent', 'Status']);
        Booking::with(['tenant', 'unit.building'])
            ->whereIn('unit_id', $unitIds)
            ->whereBetween('check_in_date', [$from, $to])
            ->orderByDesc('check_in_date')
            ->each(fn (Booking $booking) => fputcsv($handle, [$booking->booking_no, $booking->tenant?->full_name, $booking->unit?->building?->name.' '.$booking->unit?->unit_no, $booking->check_in_date?->format('Y-m-d'), $booking->check_out_date?->format('Y-m-d'), $booking->rent_amount, $booking->booking_status]));
    }

    private function ownerInvoiceCsv($handle, Owner $owner, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Invoice', 'Tenant', 'Unit', 'Rent', 'Paid', 'Balance', 'Status', 'Due']);
        $this->ownerInvoiceQuery($owner)
            ->with(['tenant', 'unit.building'])
            ->whereBetween('invoice_date', [$from, $to])
            ->orderByDesc('invoice_date')
            ->each(fn (Invoice $invoice) => fputcsv($handle, [$invoice->invoice_no, $invoice->tenant?->full_name, $invoice->unit?->building?->name.' '.$invoice->unit?->unit_no, $invoice->rent_amount, $invoice->paid_amount, $invoice->balance_amount, $invoice->status, $invoice->due_date?->format('Y-m-d')]));
    }

    private function ownerPaymentCsv($handle, Owner $owner, Carbon $from, Carbon $to): void
    {
        $shareByUnit = $owner->units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);

        fputcsv($handle, ['Payment', 'Invoice', 'Unit', 'Rent Portion', 'Owner Share', 'Status', 'Paid at']);
        $this->ownerPaymentQuery($owner, $from, $to)
            ->orderByDesc('paid_at')
            ->get()
            ->each(function (Payment $payment) use ($handle, $shareByUnit): void {
                $invoice = $payment->invoice;
                $rentPortion = $invoice && (float) $invoice->total_amount > 0
                    ? (float) $payment->amount * ((float) $invoice->rent_amount / (float) $invoice->total_amount)
                    : 0;
                $ownerShare = $invoice ? $rentPortion * (($shareByUnit[$invoice->unit_id] ?? 100) / 100) : 0;

                fputcsv($handle, [$payment->payment_no, $invoice?->invoice_no, $invoice?->unit?->building?->name.' '.$invoice?->unit?->unit_no, $rentPortion, $ownerShare, $payment->status, $payment->paid_at?->format('Y-m-d H:i')]);
            });
    }

    private function ownerExpenseCsv($handle, Owner $owner, Carbon $from, Carbon $to): void
    {
        fputcsv($handle, ['Expense', 'Name', 'Type', 'Unit', 'Date', 'Amount']);
        $this->ownerExpenseQuery($owner, $from, $to)
            ->with('unit.building')
            ->orderByDesc('incurred_on')
            ->each(fn (Expense $expense) => fputcsv($handle, [$expense->expense_no, $expense->name, $expense->type, $expense->unit?->building?->name.' '.$expense->unit?->unit_no, $expense->incurred_on?->format('Y-m-d'), $expense->amount]));
    }

    private function range(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfYear();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }
}
