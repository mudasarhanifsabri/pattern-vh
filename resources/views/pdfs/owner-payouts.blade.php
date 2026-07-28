<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; color: #10213e; font-size: 9px; }
        h1 { font-size: 18px; margin: 0; }
        p { margin: 4px 0; color: #52657f; }
        table { border-collapse: collapse; width: 100%; margin-top: 18px; }
        th { background: #edf3fb; font-size: 8px; text-align: left; }
        th, td { border: 1px solid #dbe3ee; padding: 7px 5px; vertical-align: top; }
        .summary { margin-top: 14px; }
        .summary td { width: 20%; background: #f8fafc; }
        .amount { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>Owner Payout Schedule</h1>
    <p>{{ $owner?->full_name ?: 'All owners' }} · Generated {{ now()->format('d M Y, h:i A') }}</p>

    <table class="summary">
        <tr>
            <td>Upcoming<br><strong>AED {{ number_format((float) $stats['upcoming'], 2) }}</strong></td>
            <td>Ready<br><strong>AED {{ number_format((float) $stats['ready'], 2) }}</strong></td>
            <td>Transferred<br><strong>AED {{ number_format((float) $stats['transferred'], 2) }}</strong></td>
            <td>Total<br><strong>AED {{ number_format((float) $stats['total'], 2) }}</strong></td>
            <td>Items<br><strong>{{ $stats['count'] }}</strong></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr><th>Owner</th><th>Booking / Unit</th><th>Invoice period</th><th>Payable</th><th>Rent</th><th>Mgmt fee</th><th>Expenses</th><th>Balance</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['owner']->full_name }}</td>
                    <td>{{ $row['booking']?->booking_no ?? '-' }}<br>{{ $row['unit']?->building?->name ?? '-' }} / {{ $row['unit']?->unit_no ?? '-' }}</td>
                    <td>{{ $row['period_start']?->format('d M Y') ?? '-' }}<br>to {{ $row['period_end']?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $row['payable_on']?->format('d M Y') ?? '-' }}</td>
                    <td class="amount">AED {{ number_format($row['gross_share'], 2) }}</td>
                    <td class="amount">AED {{ number_format($row['management_fee'], 2) }}</td>
                    <td class="amount">AED {{ number_format($row['owner_expenses'], 2) }}</td>
                    <td class="amount"><strong>AED {{ number_format($row['net_payout'], 2) }}</strong></td>
                    <td>{{ str($row['status'])->headline() }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No approved rent collections found for payout.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
