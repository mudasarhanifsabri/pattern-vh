<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Support\OwnerBalanceCalculator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerBalanceController extends Controller
{
    public function __invoke(Request $request, OwnerBalanceCalculator $calculator)
    {
        $search = trim($request->string('search')->toString());
        $groupBy = $request->string('group_by')->toString() === 'unit' ? 'unit' : 'owner';
        $owners = Owner::query()
            ->with(['units.building'])
            ->withCount('units')
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile_no', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->get();
        $rows = $groupBy === 'unit'
            ? $owners->flatMap(fn (Owner $owner) => $owner->units->map(fn ($unit): array => [
                'owner' => $owner,
                'unit' => $unit,
                'balance' => $calculator->calculate($owner, $unit->id),
            ]))->values()
            : $owners->map(fn (Owner $owner): array => [
                'owner' => $owner,
                'unit' => null,
                'balance' => $calculator->calculate($owner),
            ]);

        if ($request->boolean('export')) {
            return $this->export($rows);
        }

        return view('owner-balances.index', [
            'rows' => $rows,
            'totalPayable' => $rows->sum(fn ($row) => max($row['balance'], 0)),
            'totalReceivable' => abs($rows->sum(fn ($row) => min($row['balance'], 0))),
            'groupBy' => $groupBy,
        ]);
    }

    private function export($rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Owner', 'Email', 'Building', 'Unit', 'Units', 'Payable to Owner', 'Owner Owes Pattern', 'Net Balance']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['owner']->full_name,
                    $row['owner']->email,
                    $row['unit']?->building?->name,
                    $row['unit']?->unit_no,
                    $row['owner']->units_count,
                    max($row['balance'], 0),
                    abs(min($row['balance'], 0)),
                    $row['balance'],
                ]);
            }
            fclose($handle);
        }, 'owner-balances-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
