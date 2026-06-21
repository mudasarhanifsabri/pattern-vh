<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #071a3b; font-size: 11px; background: #f3f6fb; }
        .header { background: #fff; border: 1px solid #dbe4f0; border-radius: 18px; padding: 18px; margin-bottom: 12px; }
        .eyebrow { color: #2563eb; font-size: 9px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; }
        h1 { margin: 5px 0 4px; font-size: 26px; }
        .muted { color: #64748b; }
        .stats { width: 100%; border-spacing: 8px; margin-bottom: 12px; }
        .stat { background: #fff; border: 1px solid #dbe4f0; border-radius: 16px; padding: 12px; }
        .stat-label { color: #94a3b8; font-size: 8px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .stat-value { margin-top: 6px; font-size: 22px; font-weight: 900; }
        .day { background: #fff; border: 1px solid #dbe4f0; border-radius: 16px; margin-bottom: 10px; overflow: hidden; page-break-inside: avoid; }
        .day-head { padding: 11px 13px; border-bottom: 1px solid #e2e8f0; }
        .day-title { font-size: 15px; font-weight: 900; }
        .event { margin: 9px 12px; border-radius: 12px; padding: 10px; border-left: 4px solid #2563eb; background: #f8fafc; }
        .event.blue { border-left-color: #2563eb; background: #eff6ff; }
        .event.emerald { border-left-color: #059669; background: #ecfdf5; }
        .event.amber { border-left-color: #d97706; background: #fffbeb; }
        .event.rose { border-left-color: #e11d48; background: #fff1f2; }
        .event.violet { border-left-color: #7c3aed; background: #f5f3ff; }
        .event.cyan { border-left-color: #0891b2; background: #ecfeff; }
        .event.slate { border-left-color: #64748b; background: #f8fafc; }
        .event-type { color: #475569; font-size: 8px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; }
        .event-title { margin-top: 4px; font-size: 12px; font-weight: 900; }
        .event-sub { margin-top: 3px; color: #64748b; }
        .status { float: right; border-radius: 999px; background: #fff; padding: 4px 8px; font-size: 8px; font-weight: 900; color: #334155; }
        .empty { padding: 18px; color: #94a3b8; text-align: center; }
        .footer { margin-top: 10px; color: #94a3b8; font-size: 9px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="eyebrow">Pattern Vacation Homes Rental</div>
        <h1>Operations Planning Sheet</h1>
        <div class="muted">{{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }} / Generated {{ now()->format('M d, Y H:i') }}</div>
    </div>

    <table class="stats">
        <tr>
            @foreach([
                ['Check-ins', $stats['check_ins']],
                ['Check-outs', $stats['check_outs']],
                ['Tasks', $stats['tasks']],
                ['Pending invoices', $stats['pending_invoices']],
                ['Collections', $stats['collections']],
                ['Utilities', $stats['utilities']],
            ] as [$label, $value])
                <td class="stat">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-value">{{ $value }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    @foreach($days as $day)
        <section class="day">
            <div class="day-head">
                <span class="day-title">{{ $day['date']->format('D, M d, Y') }}</span>
                <span class="status">{{ $day['events']->count() }} items</span>
            </div>
            @forelse($day['events'] as $event)
                <div class="event {{ $event['tone'] }}">
                    <span class="status">{{ $event['status'] }}</span>
                    <div class="event-type">{{ $event['type'] }} / {{ $event['time'] }}</div>
                    <div class="event-title">{{ $event['title'] }}</div>
                    <div class="event-sub">{{ $event['subtitle'] }}</div>
                </div>
            @empty
                <div class="empty">No planned work.</div>
            @endforelse
        </section>
    @endforeach

    <div class="footer">Secure operations workspace</div>
</body>
</html>
