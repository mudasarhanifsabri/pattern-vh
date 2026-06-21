<?php

namespace App\Support;

use App\Models\Owner;
use Illuminate\Support\Collection;

class OwnerStatementPdf
{
    private array $commands = [];

    public function make(Owner $owner, array $statement, $from, $to): string
    {
        $this->commands = [];
        $unitTitle = $owner->units
            ->map(fn ($unit) => trim(($unit->building?->name ? $unit->building->name.' ' : '').$unit->unit_no))
            ->filter()
            ->take(3)
            ->implode(', ');

        $title = ($unitTitle ?: 'Owner Portfolio').' Statement of Accounts';
        $openingBalance = 0.0;
        $billedAmount = (float) $statement['gross'];
        $amountPaid = (float) $statement['management_fee'] + (float) $statement['expenses'];
        $balanceDue = (float) $statement['net'];

        $this->text(38, 800, 'PATTERN VACATION HOMES RENTAL', 13, 'bold')
            ->text(38, 782, 'Al Barsha Dubai', 9)
            ->text(38, 768, 'U.A.E TRN 101001557300003', 9)
            ->text(38, 754, '04 329 9693', 9)
            ->text(38, 740, 'customerservice@pattern.ae', 9)
            ->text(38, 726, 'www.pattern.ae', 9)
            ->text(38, 686, 'To', 10, 'bold')
            ->text(38, 666, $this->shorten($title, 58), 13, 'bold')
            ->text(38, 646, $this->shorten($owner->full_name, 38).'    '.$from->format('j F Y').' To '.$to->format('j F Y'), 10);

        $this->text(38, 612, 'Accounts Summary', 12, 'bold')
            ->summaryLine(38, 590, 'Opening Balance', $openingBalance)
            ->summaryLine(38, 572, 'Billed Amount', $billedAmount)
            ->summaryLine(38, 554, 'Amount Paid', $amountPaid)
            ->summaryLine(38, 536, 'Balance Due', $balanceDue);

        $this->line(38, 512, 555, 512, '000000')
            ->text(38, 498, 'Date', 8, 'bold')
            ->text(108, 498, 'Transactoins', 8, 'bold')
            ->text(190, 498, 'Details', 8, 'bold')
            ->text(398, 498, 'Amount', 8, 'bold')
            ->text(470, 498, 'Payments', 8, 'bold')
            ->text(530, 498, 'Balance', 8, 'bold')
            ->line(38, 488, 555, 488, '000000');

        $ledgerRows = $this->ledgerRows(collect($statement['rows']), $openingBalance);
        $y = 468;
        foreach ($ledgerRows->take(13) as $row) {
            $this->text(38, $y, $row['date'], 7)
                ->text(108, $y, $this->shorten($row['transaction'], 18), 7)
                ->text(190, $y, $this->shorten($row['details'], 42), 7)
                ->text(398, $y, $row['amount'], 7)
                ->text(470, $y, $row['payments'], 7)
                ->text(530, $y, $row['balance'], 7);
            $y -= 23;
        }

        if ($ledgerRows->count() > 13) {
            $this->text(38, $y, '+ '.($ledgerRows->count() - 13).' more rows. Use CSV export for full transaction detail.', 8, 'bold');
            $y -= 20;
        }

        $this->line(398, 116, 555, 116, '000000')
            ->text(438, 96, 'Balance Due', 10, 'bold')
            ->text(500, 96, $this->money($balanceDue), 10, 'bold')
            ->text(38, 52, 'Generated from Pattern RMS confirmed bookings, approved payments, management fees, and owner expenses.', 7)
            ->text(38, 38, 'Statement generated '.now()->format('d M Y H:i'), 7);

        return $this->output();
    }

    private function summaryLine(float $x, float $y, string $label, float $amount): self
    {
        return $this->text($x, $y, $label, 10)
            ->text($x + 140, $y, $this->money($amount), 10, 'bold');
    }

    private function ledgerRows(Collection $rows, float $openingBalance): Collection
    {
        $balance = $openingBalance;
        $ledger = collect([
            [
                'date' => '***Opening',
                'transaction' => 'Balance***',
                'details' => '',
                'amount' => $this->money($openingBalance),
                'payments' => $this->money(0),
                'balance' => $this->money($balance),
            ],
        ]);

        foreach ($rows as $row) {
            if ((float) $row['gross'] > 0) {
                $balance += (float) $row['gross'];
                $ledger->push([
                    'date' => $row['date']->format('j M Y'),
                    'transaction' => 'Bill',
                    'details' => $row['description'],
                    'amount' => $this->money((float) $row['gross']),
                    'payments' => $this->money(0),
                    'balance' => $this->money($balance),
                ]);
            }

            if ((float) $row['management_fee'] > 0) {
                $balance -= (float) $row['management_fee'];
                $ledger->push([
                    'date' => $row['date']->format('j M Y'),
                    'transaction' => 'Journal',
                    'details' => 'Management Fee',
                    'amount' => $this->money(-1 * (float) $row['management_fee']),
                    'payments' => $this->money(0),
                    'balance' => $this->money($balance),
                ]);
            }

            if ((float) $row['owner_expense'] > 0) {
                $balance -= (float) $row['owner_expense'];
                $ledger->push([
                    'date' => $row['date']->format('j M Y'),
                    'transaction' => 'Payment Made',
                    'details' => $row['description'],
                    'amount' => $this->money(0),
                    'payments' => $this->money((float) $row['owner_expense']),
                    'balance' => $this->money($balance),
                ]);
            }
        }

        return $ledger;
    }

    private function text(float $x, float $y, string $text, int $size = 10, string $font = 'regular'): self
    {
        $this->commands[] = sprintf(
            'BT /%s %d Tf 0 0 0 rg %.2F %.2F Td (%s) Tj ET',
            $font === 'bold' ? 'F2' : 'F1',
            $size,
            $x,
            $y,
            $this->escape($text),
        );

        return $this;
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $color = '000000'): self
    {
        $rgb = $this->rgb($color);
        $this->commands[] = sprintf('%s RG %.2F %.2F m %.2F %.2F l S', $rgb, $x1, $y1, $x2, $y2);

        return $this;
    }

    private function output(): string
    {
        $content = implode("\n", $this->commands);
        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n",
            "6 0 obj << /Length ".strlen($content)." >> stream\n{$content}\nendstream endobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function money(float $amount): string
    {
        return 'AED '.number_format($amount, 2);
    }

    private function shorten(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        return strlen($value) > $limit ? substr($value, 0, $limit - 3).'...' : $value;
    }

    private function rgb(string $hex): string
    {
        return sprintf('%.3F %.3F %.3F', hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255);
    }

    private function escape(string $text): string
    {
        $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
