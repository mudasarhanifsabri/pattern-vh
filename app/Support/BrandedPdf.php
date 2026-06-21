<?php

namespace App\Support;

class BrandedPdf
{
    private array $commands = [];

    private array $objects = [];

    private ?array $logo = null;

    public function __construct(private string $title, private string $subtitle)
    {
        $this->logo = $this->loadLogo();
        $this->header();
    }

    public function text(float $x, float $y, string $text, int $size = 10, string $font = 'regular', string $color = '0B1F3F'): self
    {
        $this->commands[] = sprintf(
            "BT /%s %d Tf %s rg %.2F %.2F Td (%s) Tj ET",
            $font === 'bold' ? 'F2' : 'F1',
            $size,
            $this->rgb($color),
            $x,
            $y,
            $this->escape($text),
        );

        return $this;
    }

    public function rect(float $x, float $y, float $w, float $h, string $fill = 'FFFFFF', ?string $stroke = null): self
    {
        $operator = $stroke ? 'B' : 'f';
        $strokeCommand = $stroke ? ' '.$this->rgb($stroke).' RG' : '';
        $this->commands[] = sprintf("%s rg%s %.2F %.2F %.2F %.2F re %s", $this->rgb($fill), $strokeCommand, $x, $y, $w, $h, $operator);

        return $this;
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $color = 'E2E8F0'): self
    {
        $this->commands[] = sprintf("%s RG %.2F %.2F m %.2F %.2F l S", $this->rgb($color), $x1, $y1, $x2, $y2);

        return $this;
    }

    public function labelValue(float $x, float $y, string $label, string $value, float $w = 170): self
    {
        $this->rect($x, $y - 8, $w, 42, 'F8FAFC', 'E2E8F0');
        $this->text($x + 12, $y + 17, strtoupper($label), 7, 'bold', '64748B');
        $this->text($x + 12, $y + 2, $value, 10, 'bold', '071A3B');

        return $this;
    }

    public function table(float $x, float $y, array $rows, float $w = 500): self
    {
        $this->rect($x, $y - (count($rows) * 28) - 12, $w, (count($rows) * 28) + 22, 'FFFFFF', 'E2E8F0');
        $this->rect($x, $y - 12, $w, 22, 'EFF6FF');
        $this->text($x + 14, $y - 4, 'Description', 8, 'bold', '2563EB');
        $this->text($x + $w - 110, $y - 4, 'Amount', 8, 'bold', '2563EB');

        $cursor = $y - 36;
        foreach ($rows as $row) {
            $this->text($x + 14, $cursor, $row[0], 10, 'regular', '0F172A');
            $this->text($x + $w - 110, $cursor, $row[1], 10, 'bold', '0F172A');
            $this->line($x + 12, $cursor - 9, $x + $w - 12, $cursor - 9);
            $cursor -= 28;
        }

        return $this;
    }

    public function totalBox(float $x, float $y, string $label, string $amount, string $fill = '061A38'): self
    {
        $this->rect($x, $y, 185, 54, $fill);
        $this->text($x + 16, $y + 33, strtoupper($label), 8, 'bold', 'BFDBFE');
        $this->text($x + 16, $y + 14, $amount, 17, 'bold', 'FFFFFF');

        return $this;
    }

    public function output(): string
    {
        $this->footer();

        $content = implode("\n", $this->commands);
        $this->objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> ".($this->logo ? '/XObject << /Logo 7 0 R >>' : '')." >> /Contents 6 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n",
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n",
            "6 0 obj << /Length ".strlen($content)." >> stream\n{$content}\nendstream endobj\n",
        ];

        if ($this->logo) {
            $this->objects[] = "7 0 obj << /Type /XObject /Subtype /Image /Width {$this->logo['width']} /Height {$this->logo['height']} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($this->logo['data'])." >> stream\n{$this->logo['data']}\nendstream endobj\n";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($this->objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($this->objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($this->objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer << /Size ".(count($this->objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function header(): void
    {
        $this->rect(0, 742, 595, 100, '061A38');
        $this->rect(34, 690, 527, 92, 'FFFFFF', 'E2E8F0');

        if ($this->logo) {
            $this->commands[] = 'q 170 0 0 50 52 714 cm /Logo Do Q';
        } else {
            $this->text(52, 738, 'PATTERN', 22, 'bold', '061A38');
        }

        $this->text(52, 702, 'Pattern Vacation Homes Rental', 8, 'bold', '2563EB');
        $this->text(300, 738, $this->title, 23, 'bold', '071A3B');
        $this->text(302, 718, $this->subtitle, 10, 'regular', '64748B');
    }

    private function footer(): void
    {
        $this->line(34, 42, 561, 42);
        $this->text(34, 25, 'Pattern Vacation Homes Rental - Dubai operations suite', 8, 'regular', '64748B');
        $this->text(455, 25, 'Generated '.now()->format('M d, Y H:i'), 8, 'regular', '64748B');
    }

    private function loadLogo(): ?array
    {
        $path = public_path('brand/pattern-logo.jpeg');
        if (! is_file($path)) {
            return null;
        }

        $size = @getimagesize($path);
        if (! $size) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1], 'data' => file_get_contents($path)];
    }

    private function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        return sprintf('%.3F %.3F %.3F', hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255);
    }

    private function escape(string $text): string
    {
        $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
