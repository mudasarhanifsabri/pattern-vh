<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\OwnerPayoutTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceSheetController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to, $rows] = $this->sheet($request);

        return view('finance-sheet.index', compact('from', 'to', 'rows'));
    }

    public function pdf(Request $request)
    {
        [$from, $to, $rows] = $this->sheet($request);
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        // A3 landscape keeps the complete accounting column set on one printable sheet.
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A3-L',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 7,
            'margin_bottom' => 7,
        ]);
        $pdf->SetTitle('Finance Sheet');
        $pdf->WriteHTML(view('pdfs.finance-sheet', compact('from', 'to', 'rows'))->render());

        return response($pdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="finance-sheet-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf"',
        ]);
    }

    public function excel(Request $request): StreamedResponse
    {
        [$from, $to, $rows] = $this->sheet($request);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->headings());

            foreach ($rows as $row) {
                fputcsv($handle, $this->exportValues($row));
            }

            fclose($handle);
        }, 'finance-sheet-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function sheet(Request $request): array
    {
        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $invoices = $this->invoicesForPeriod($from, $to);
        $expenses = Expense::query()
            ->whereIn('unit_id', $invoices->pluck('unit_id')->filter()->unique())
            ->where('expense_to_role', 'owner')
            ->with('owner')
            ->get();
        $transferByPayment = OwnerPayoutTransfer::query()
            ->whereIn('payment_id', $invoices->flatMap(fn (Invoice $invoice) => $invoice->payments->pluck('id'))->filter()->unique())
            ->get()
            ->groupBy('payment_id');

        $rows = $invoices
            ->flatMap(fn (Invoice $invoice) => $this->rowsForInvoice($invoice, $expenses, $transferByPayment))
            ->sortBy(fn (array $row) => $row['check_out']->format('Y-m-d').' '.$row['invoice_no'].' '.$row['owner_name'])
            ->values();

        return [$from, $to, $rows];
    }

    private function invoicesForPeriod($from, $to): Collection
    {
        return Invoice::query()
            ->with(['booking.unit.building', 'booking.unit.owners', 'tenant', 'payments', 'extensionRequest'])
            ->where(function ($query) use ($from, $to): void {
                $query->where(function ($query) use ($from, $to): void {
                    $query->whereHas('extensionRequest')
                        ->whereDate('period_end', '>=', $from->toDateString())
                        ->whereDate('period_end', '<=', $to->toDateString());
                })->orWhere(function ($query) use ($from, $to): void {
                    $query->whereDoesntHave('extensionRequest')
                        ->whereHas('booking', fn ($bookingQuery) => $bookingQuery
                            ->whereDate('check_out_date', '>=', $from->toDateString())
                            ->whereDate('check_out_date', '<=', $to->toDateString()));
                });
            })
            ->get();
    }

    private function rowsForInvoice(Invoice $invoice, Collection $expenses, Collection $transferByPayment): Collection
    {
        $unit = $invoice->booking?->unit;
        $owners = $unit?->owners->isNotEmpty() ? $unit->owners : collect([null]);

        return $owners->map(function (?Owner $owner) use ($invoice, $unit, $expenses, $transferByPayment): array {
            $ownerPercent = (float) ($owner?->pivot?->share_percent ?? 0);
            $ownerRent = (float) $invoice->rent_amount * ($ownerPercent / 100);
            $managementFee = $ownerRent * ((float) ($unit?->management_fee_percent ?? 0) / 100);
            $periodExpenses = $this->periodExpenses($expenses, $owner, $invoice);
            $deductions = $this->classifyExpenses($periodExpenses);
            $totalDeduction = array_sum($deductions);
            $eNet = $ownerRent - $managementFee;
            $amountToOwner = max(0, $eNet - $totalDeduction);
            $approvedPayments = $invoice->payments->where('status', 'approved');
            $transfers = $approvedPayments
                ->flatMap(fn ($payment) => $transferByPayment->get($payment->id, collect()))
                ->where('owner_id', $owner?->id);
            $transfer = $transfers->sortByDesc('transferred_at')->first();
            $securityDepositPaid = max(0, min(
                (float) $invoice->deposit_amount,
                (float) $invoice->paid_amount - ((float) $invoice->total_amount - (float) $invoice->deposit_amount)
            ));

            return [
                'owner_payment' => $approvedPayments->pluck('payment_no')->filter()->implode(' / ') ?: '-',
                'status' => $transfer?->transferred_at ? 'Transferred' : ((float) $invoice->paid_amount > 0 ? 'Ready' : 'Awaiting tenant payment'),
                'unit' => $unit?->unit_no ?? '-',
                'type' => $unit?->unit_type ?? '-',
                'area' => $unit?->building?->area ?? '-',
                'building_name' => $unit?->building?->name ?? '-',
                'payment_mode' => $approvedPayments->pluck('method')->map(fn ($method) => str($method)->replace('_', ' ')->headline()->toString())->unique()->implode(' / ') ?: '-',
                'tenant_name' => $invoice->tenant?->full_name ?? '-',
                'check_in' => $invoice->stay_check_in_date,
                'check_out' => $invoice->stay_check_out_date,
                'original_rent' => (float) $invoice->rent_amount,
                'vat' => (float) $invoice->vat_amount,
                'including_vat' => (float) $invoice->rent_amount + (float) $invoice->vat_amount,
                'housekeeping' => (float) $invoice->cleaning_fee,
                'tourism' => (float) $invoice->dtcm_fee,
                'security_deposit' => (float) $invoice->deposit_amount,
                'agency_fee' => (float) $invoice->agency_fee,
                'grand_total' => (float) $invoice->total_amount,
                'tenant_transferred' => (float) $invoice->paid_amount,
                'balance' => (float) $invoice->balance_amount,
                'deposit' => $securityDepositPaid,
                'owner_percent' => $ownerPercent,
                'pattern_rent_profit' => $managementFee,
                'owner_name' => $owner?->full_name ?? 'No owner linked',
                'dewa' => $deductions['dewa'],
                'gas' => $deductions['gas'],
                'ac' => $deductions['ac'],
                'cleaning_profit' => (float) $invoice->cleaning_fee - $deductions['cleaning'],
                'e_net' => $eNet,
                'maintenance' => $deductions['maintenance'],
                'others' => $deductions['others'],
                'remarks' => $this->remarks($invoice, $periodExpenses),
                'furniture_balance' => $deductions['furniture'],
                'total_deduction' => $totalDeduction,
                'transfer_to_owner' => $transfer?->reference_no ?: ($transfer?->transferred_at?->format('Y-m-d') ?? '-'),
                'amount_to_owner' => $transfer?->net_payout ?? $amountToOwner,
                'invoice_no' => $invoice->invoice_no,
            ];
        });
    }

    private function periodExpenses(Collection $expenses, ?Owner $owner, Invoice $invoice): Collection
    {
        if (! $owner || ! $invoice->stay_check_in_date || ! $invoice->stay_check_out_date) {
            return collect();
        }

        return $expenses->filter(fn (Expense $expense) => (int) $expense->owner_id === (int) $owner->id
            && (int) $expense->unit_id === (int) $invoice->unit_id
            && $expense->incurred_on?->betweenIncluded($invoice->stay_check_in_date, $invoice->stay_check_out_date));
    }

    private function classifyExpenses(Collection $expenses): array
    {
        $totals = array_fill_keys(['dewa', 'gas', 'ac', 'cleaning', 'maintenance', 'others', 'furniture'], 0.0);

        foreach ($expenses as $expense) {
            $text = strtolower($expense->type.' '.$expense->name.' '.$expense->notes);
            $bucket = str_contains($text, 'dewa') || str_contains($text, 'electric') ? 'dewa'
                : (str_contains($text, 'gas') ? 'gas'
                    : (str_contains($text, 'air condition') || str_contains($text, ' hvac') || str_contains($text, ' ac ') ? 'ac'
                        : (str_contains($text, 'furniture') ? 'furniture'
                            : ($expense->type === 'cleaning' ? 'cleaning'
                                : (in_array($expense->type, ['maintenance', 'repair'], true) ? 'maintenance' : 'others')))));
            $totals[$bucket] += (float) $expense->amount;
        }

        return $totals;
    }

    private function remarks(Invoice $invoice, Collection $expenses): string
    {
        return collect([$invoice->notes])
            ->merge($expenses->map(fn (Expense $expense) => $expense->name.($expense->notes ? ': '.$expense->notes : '')))
            ->filter()
            ->implode(' | ') ?: '-';
    }

    private function headings(): array
    {
        return ['Owner Payment', 'Status', 'Unit', 'Type', 'Area', 'Building Name', 'Mode of Payment', 'Tenant Name', 'Check-In', 'Check-Out', 'Original Rent', 'VAT', 'Incl. VAT', 'Housekeeping', 'Tourism', 'Security Deposit', 'Agency Fee', 'Grand Total', 'Tenant Transferred', 'Balance', 'Deposit', 'Owner %', 'Pattern Rent Profit', 'Owner Name', 'DEWA', 'Gas', 'AC', 'Cleaning Profit', 'e-Net', 'Maintenance', 'Others', 'Remarks', 'Furniture Balance', 'Total Deduction', 'Transfer To Owner', 'Amount to Owner'];
    }

    private function exportValues(array $row): array
    {
        return [
            $row['owner_payment'], $row['status'], $row['unit'], $row['type'], $row['area'], $row['building_name'], $row['payment_mode'], $row['tenant_name'], $row['check_in']?->format('Y-m-d'), $row['check_out']?->format('Y-m-d'), $row['original_rent'], $row['vat'], $row['including_vat'], $row['housekeeping'], $row['tourism'], $row['security_deposit'], $row['agency_fee'], $row['grand_total'], $row['tenant_transferred'], $row['balance'], $row['deposit'], $row['owner_percent'], $row['pattern_rent_profit'], $row['owner_name'], $row['dewa'], $row['gas'], $row['ac'], $row['cleaning_profit'], $row['e_net'], $row['maintenance'], $row['others'], $row['remarks'], $row['furniture_balance'], $row['total_deduction'], $row['transfer_to_owner'], $row['amount_to_owner'],
        ];
    }
}
