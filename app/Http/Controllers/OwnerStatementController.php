<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Owner;
use App\Support\OwnerStatementPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerStatementController extends Controller
{
    public function index(Request $request)
    {
        $owner = $this->ownerFor($request);
        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $statement = $owner ? $this->buildStatement($owner, $from, $to) : null;

        if ($owner && $request->boolean('export')) {
            return $this->export($owner, $statement, $from, $to);
        }

        return view('owner-statements.index', [
            'owners' => Owner::orderBy('full_name')->get(),
            'owner' => $owner,
            'from' => $from,
            'to' => $to,
            'statement' => $statement,
        ]);
    }

    public function pdf(Request $request, OwnerStatementPdf $pdf)
    {
        $owner = $this->ownerFor($request);
        abort_unless($owner, 404);

        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();
        $statement = $this->buildStatement($owner, $from, $to);
        $filename = 'owner-statement-'.$owner->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return response($pdf->make($owner, $statement, $from, $to), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function preview(Request $request)
    {
        $owner = $this->ownerFor($request);
        abort_unless($owner, 404);

        $from = $request->date('from') ?: now()->startOfMonth();
        $to = $request->date('to') ?: now()->endOfMonth();

        return view('owner-statements.pdf-preview', [
            'owner' => $owner,
            'from' => $from,
            'to' => $to,
            'pdfUrl' => route('owner-statements.pdf', $request->query()),
            'backUrl' => route('owner-statements.index', $request->query()),
        ]);
    }

    private function ownerFor(Request $request): ?Owner
    {
        if ($request->user()->can('owner-statements.manage') && $request->filled('owner_id')) {
            return Owner::with('units.building')->find($request->integer('owner_id'));
        }

        if ($request->user()->can('portal.owner')) {
            return Owner::with('units.building')
                ->where('user_id', $request->user()->id)
                ->orWhere('email', $request->user()->email)
                ->first();
        }

        return Owner::with('units.building')->first();
    }

    private function buildStatement(Owner $owner, $from, $to): array
    {
        $unitIds = $owner->units->pluck('id');
        $shareByUnit = $owner->units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->pivot->share_percent ?? 100)]);
        $managementByUnit = $owner->units->mapWithKeys(fn ($unit) => [$unit->id => (float) ($unit->management_fee_percent ?? 0)]);

        $bookings = Booking::query()
            ->with('unit.building')
            ->whereIn('unit_id', $unitIds)
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested', 'checked_out'])
            ->whereBetween('check_in_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $revenueRows = $bookings->map(function (Booking $booking) use ($shareByUnit, $managementByUnit): array {
            $share = $shareByUnit[$booking->unit_id] ?? 100;
            $gross = (float) $booking->rent_amount * ($share / 100);
            $management = $gross * (($managementByUnit[$booking->unit_id] ?? 0) / 100);

            return [
                'date' => $booking->check_in_date,
                'description' => $booking->booking_no.' / '.$booking->unit->building->name.' '.$booking->unit->unit_no,
                'gross' => $gross,
                'management_fee' => $management,
                'owner_expense' => 0,
                'net' => $gross - $management,
            ];
        });

        $expenses = Expense::query()
            ->with('unit.building')
            ->where('owner_id', $owner->id)
            ->whereBetween('incurred_on', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn (Expense $expense): array => [
                'date' => $expense->incurred_on,
                'description' => $expense->name.' / '.($expense->unit?->unit_no ? $expense->unit->building->name.' '.$expense->unit->unit_no : str($expense->type)->headline()),
                'gross' => 0,
                'management_fee' => 0,
                'owner_expense' => (float) $expense->amount,
                'net' => -1 * (float) $expense->amount,
            ]);

        $rows = $revenueRows->concat($expenses)->sortBy('date')->values();

        return [
            'rows' => $rows,
            'gross' => $rows->sum('gross'),
            'management_fee' => $rows->sum('management_fee'),
            'expenses' => $rows->sum('owner_expense'),
            'net' => $rows->sum('net'),
        ];
    }

    private function export(Owner $owner, array $statement, $from, $to): StreamedResponse
    {
        return response()->streamDownload(function () use ($owner, $statement): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Owner', $owner->full_name]);
            fputcsv($handle, ['Date', 'Description', 'Gross', 'Management Fee', 'Owner Expense', 'Net']);
            foreach ($statement['rows'] as $row) {
                fputcsv($handle, [$row['date']->format('Y-m-d'), $row['description'], $row['gross'], $row['management_fee'], $row['owner_expense'], $row['net']]);
            }
            fputcsv($handle, ['Totals', '', $statement['gross'], $statement['management_fee'], $statement['expenses'], $statement['net']]);
            fclose($handle);
        }, 'owner-statement-'.$owner->id.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
