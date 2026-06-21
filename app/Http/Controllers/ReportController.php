<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
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

        return response()->streamDownload(function () use ($type, $from, $to): void {
            $handle = fopen('php://output', 'w');
            match ($type) {
                'bookings' => $this->bookingCsv($handle, $from, $to),
                'invoices' => $this->invoiceCsv($handle, $from, $to),
                'payments' => $this->paymentCsv($handle, $from, $to),
                'expenses' => $this->expenseCsv($handle, $from, $to),
                'profit_loss' => $this->profitLossCsv($handle, $from, $to),
            };
            fclose($handle);
        }, "pattern-{$type}-{$from->format('Ymd')}-{$to->format('Ymd')}.csv", ['Content-Type' => 'text/csv']);
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

    private function range(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfYear();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }
}
