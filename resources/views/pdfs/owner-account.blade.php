<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; color: #0f172a; font-size: 9px; }
        h1 { margin: 0; color: #071a3b; font-size: 20px; }
        .subtitle { margin-top: 4px; color: #64748b; font-size: 9px; }
        .meta { margin: 14px 0 10px; padding: 9px 10px; background: #eff6ff; color: #1e3a8a; }
        .summary { width: 100%; margin: 0 0 12px; border-collapse: separate; border-spacing: 6px 0; }
        .summary td { width: 33.33%; padding: 9px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .summary small { display: block; color: #64748b; text-transform: uppercase; }
        .summary strong { display: block; margin-top: 3px; font-size: 14px; color: #071a3b; }
        table.ledger { width: 100%; border-collapse: collapse; }
        .ledger th { padding: 7px 5px; background: #071a3b; color: #fff; text-align: left; font-size: 7px; text-transform: uppercase; }
        .ledger td { padding: 7px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .ledger tr:nth-child(even) td { background: #f8fafc; }
        .number { text-align: right; white-space: nowrap; }
        .debit { color: #be123c; }
        .credit { color: #047857; }
        .muted { color: #64748b; font-size: 8px; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ $owner->full_name }} - Statement of Account</h1>
    <div class="subtitle">Generated {{ now()->format('M d, Y H:i') }} | {{ $selectedUnit ? $selectedUnit->building?->name.' / '.$selectedUnit->unit_no : 'All owner units' }}</div>

    @if($filters['from'] || $filters['to'] || $filters['type'] || $filters['search'])
        <div class="meta">
            Filters:
            {{ $filters['from'] ? 'From '.$filters['from']->format('M d, Y') : '' }}
            {{ $filters['to'] ? ' To '.$filters['to']->format('M d, Y') : '' }}
            {{ $filters['type'] ? ' | Type: '.$filters['type'] : '' }}
            {{ $filters['search'] ? ' | Search: '.$filters['search'] : '' }}
        </div>
    @endif

    <table class="summary"><tr>
        <td><small>Credits</small><strong>AED {{ number_format((float)$totals['credits'], 2) }}</strong></td>
        <td><small>Debits</small><strong>AED {{ number_format((float)$totals['debits'], 2) }}</strong></td>
        <td><small>Balance</small><strong>AED {{ number_format((float)$totals['balance'], 2) }}</strong></td>
    </tr></table>

    <table class="ledger">
        <thead><tr><th>Date</th><th>Invoice period</th><th>Type</th><th>Description / unit</th><th>Reference</th><th class="number">Debit</th><th class="number">Credit</th><th class="number">Balance</th></tr></thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td class="nowrap">{{ $entry['date']->format('M d, Y') }}</td>
                    <td class="nowrap">{{ $entry['period'] ?: '-' }}</td>
                    <td>{{ $entry['type_label'] }}<div class="muted">{{ $entry['source'] }}</div></td>
                    <td>{{ $entry['description'] }}@if($entry['unit'])<div class="muted">{{ $entry['unit']->building?->name }} / {{ $entry['unit']->unit_no }}</div>@endif</td>
                    <td>{{ $entry['reference'] ?: '-' }}</td>
                    <td class="number debit">{{ $entry['debit'] ? number_format($entry['debit'], 2) : '-' }}</td>
                    <td class="number credit">{{ $entry['credit'] ? number_format($entry['credit'], 2) : '-' }}</td>
                    <td class="number">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="padding:20px;text-align:center;">No account entries match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
