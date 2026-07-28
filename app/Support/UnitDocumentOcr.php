<?php

namespace App\Support;

use Aws\Exception\AwsException;
use Aws\Textract\TextractClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class UnitDocumentOcr
{
    public function extract(UploadedFile $file, string $documentType): array
    {
        if (! config('ocr.enabled')) {
            return $this->response(false, 'OCR is disabled. Set OCR_ENABLED=true in .env.', [], '');
        }

        try {
            $rawText = $this->isPdf($file) ? $this->extractPdfText($file) : '';

            if (! $rawText) {
                if ($this->isPdf($file)) {
                    return $this->response(false, 'This PDF does not contain readable text. Please upload a clear JPG/PNG image of the title deed or a searchable PDF.', [], '');
                }

                $rawText = $this->detectDocumentText(file_get_contents($file->getRealPath()) ?: '');
            }
        } catch (\Throwable $exception) {
            report($exception);

            return $this->response(false, $this->failureMessage($exception), [], '');
        }

        $fields = $this->parseText($rawText, $documentType);

        return $this->response(
            (bool) array_filter($fields),
            array_filter($fields) ? 'Document scanned. Please review extracted unit fields before saving.' : 'No reliable unit document data found. Please fill manually.',
            $fields,
            $rawText
        );
    }

    public function parseText(string $rawText, string $documentType): array
    {
        return $documentType === 'title_deed'
            ? $this->titleDeedFields($rawText)
            : $this->dtcmFields($rawText);
    }

    private function titleDeedFields(string $text): array
    {
        return [
            'unit_no' => $this->extractReverseTableValue($text, 'Property No')
                ?: $this->extractDubaiSeparatedLabelValue($text, 'Property No')
                ?: $this->extractReference($text, ['property no', 'unit no', 'unit number', 'premise no', 'premises no']),
            'floor' => $this->extractReverseTableValue($text, 'Floor No')
                ?: $this->extractDubaiSeparatedLabelValue($text, 'Floor No')
                ?: $this->extractReference($text, ['floor no', 'floor number', 'floor']),
            'size_sqft' => $this->extractDecimalNear($text, ['area sq feet', 'area sq ft', 'area sqft', 'total area sq feet'])
                ?: $this->extractReverseTableValue($text, 'Area Sq Feet'),
            'parking_no' => $this->extractReverseTableValue($text, 'Parkings')
                ?: $this->extractDubaiSeparatedLabelValue($text, 'Parkings')
                ?: $this->extractReference($text, ['parkings', 'parking no', 'parking number', 'parking bay']),
            'title_deed_no' => $this->extractTitleDeedNumber($text),
            'title_deed_issue_date' => $this->extractDateNear($text, [
                'issue date', 'issued date', 'date of issue', 'registration date', 'certificate date',
            ]),
            'title_deed_expiry_date' => $this->extractDateNear($text, [
                'expiry date', 'expiration date', 'valid until', 'validity date',
            ]),
        ];
    }

    private function dtcmFields(string $text): array
    {
        return [
            'dtcm_permit_no' => $this->extractReference($text, [
                'dtcm permit no', 'dtcm permit number', 'unit permit no', 'unit permit number',
                'holiday home permit no', 'permit no', 'permit number', 'license no', 'licence no',
            ]),
            'dtcm_permit_expiry_date' => $this->extractDateNear($text, [
                'expiry date', 'expiration date', 'permit expiry', 'valid until', 'valid to', 'end date',
            ]),
        ];
    }

    private function extractReference(string $text, array $labels, bool $allowCodeFallback = true): ?string
    {
        $normalized = $this->oneLine($text);
        $labelPattern = collect($labels)->map(fn (string $label) => preg_quote($label, '/'))->implode('|');

        if (preg_match('/(?:'.$labelPattern.')\s*[:#\-]?\s*([A-Z0-9][A-Z0-9\/\-. ]{0,40})/iu', $normalized, $match)) {
            return $this->cleanReference($match[1]);
        }

        if ($allowCodeFallback && preg_match('/\b(?:DTCM|HH|TD|DEED)[\-\/ ]?[A-Z0-9]{4,24}\b/i', $normalized, $match)) {
            return $this->cleanReference($match[0]);
        }

        return null;
    }

    private function extractTitleDeedNumber(string $text): ?string
    {
        return $this->extractReference($text, [
            'title deed no', 'title deed number', 'title deed certificate no', 'title deed certificate number',
            'certificate no', 'certificate number',
            'document no', 'document number',
        ], false) ?: $this->extractReference($text, [
            'registration no.', 'registration no', 'registration number',
        ], false) ?: $this->lastRegistrationNumber($text);
    }

    private function extractTextNear(string $text, array $labels, int $maxLength = 80): ?string
    {
        $normalized = $this->oneLine($text);
        $labelPattern = collect($labels)->map(fn (string $label) => preg_quote($label, '/'))->implode('|');

        if (! preg_match('/(?:'.$labelPattern.')\s*[:#\-]?\s*([A-Z][A-Z0-9&.,\'’()\- ]{1,'.$maxLength.'})/iu', $normalized, $match)) {
            return null;
        }

        $value = preg_replace('/\b(plot no|municipality no|building no|building name|property no|floor no|parkings|suite area|area sq)\b.*$/iu', '', $match[1]) ?: $match[1];

        return Str::of($value)
            ->replaceMatches('/\s+/u', ' ')
            ->trim(" \t\n\r\0\x0B:-")
            ->title()
            ->toString();
    }

    private function extractReverseTableValue(string $text, string $label): ?string
    {
        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), Str::lower($label))) {
                continue;
            }

            $labelOrder = [
                'Property Type',
                'Community',
                'Plot No',
                'Building No',
                'Building Name',
                'Common Area',
                'Area Sq Meter',
                'Parkings',
                'Floor No',
                'Property No',
            ];

            $labelCount = collect($labelOrder)
                ->filter(fn (string $knownLabel) => Str::contains(Str::lower($line), Str::lower($knownLabel)))
                ->count();

            if ($labelCount < 3) {
                continue;
            }

            $previous = array_slice($lines, max(0, $index - 12), min(12, $index));

            $position = array_search($label, $labelOrder, true);
            if ($position === false) {
                return null;
            }

            $values = array_values(array_filter($previous, fn (string $value) => ! Str::contains($value, ':')));
            $values = array_slice($values, -count($labelOrder));
            $valueIndex = count($values) - count($labelOrder) + $position;
            $value = $values[$valueIndex] ?? null;

            return $value ? $this->cleanReverseValue($value) : null;
        }

        return null;
    }

    private function extractDubaiSeparatedLabelValue(string $text, string $label): ?string
    {
        $lines = collect(preg_split('/\R+/', $text) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();

        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), Str::lower($label))) {
                continue;
            }

            if (! preg_match('/^'.preg_quote($label, '/').'\s*:?\s*$/i', $line)) {
                continue;
            }

            $nearbyLabels = collect(array_slice($lines, $index - 4, 10))
                ->filter(fn (string $nearby) => Str::contains(Str::lower($nearby), [
                    'common area', 'area sq meter', 'parkings', 'floor no', 'property no', 'building name', 'building no',
                ]))
                ->count();

            if ($nearbyLabels < 4) {
                continue;
            }

            $value = $lines[$index - 5] ?? null;

            return $value ? $this->cleanReverseValue($value) : null;
        }

        return null;
    }

    private function extractDecimalNear(string $text, array $labels): ?string
    {
        $normalized = $this->oneLine($text);
        $labelPattern = collect($labels)->map(fn (string $label) => preg_quote($label, '/'))->implode('|');

        if (preg_match('/(?:'.$labelPattern.')\s*[:#\-]?\s*([0-9][0-9,]*(?:\.[0-9]+)?)/iu', $normalized, $match)) {
            return str_replace(',', '', $match[1]);
        }

        $labelNeedles = collect($labels)->map(fn (string $label) => Str::lower($label))->all();
        foreach (preg_split('/\R+/', $text) ?: [] as $line) {
            if (Str::contains(Str::lower($line), $labelNeedles) && preg_match('/([0-9][0-9,]*(?:\.[0-9]+)?)/', $line, $match)) {
                return str_replace(',', '', $match[1]);
            }
        }

        return null;
    }

    private function lastRegistrationNumber(string $text): ?string
    {
        if (preg_match_all('/\b\d{4,9}\/20\d{2}\b/', $text, $matches) && ! empty($matches[0])) {
            return $matches[0][0];
        }

        return null;
    }

    private function extractDateNear(string $text, array $labels): ?string
    {
        $lines = preg_split('/\R+/', $text) ?: [];
        $labelNeedles = collect($labels)->map(fn (string $label) => Str::lower($label))->all();

        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), $labelNeedles)) {
                continue;
            }

            $window = implode(' ', array_slice($lines, $index, 4));
            $date = $this->firstDate($window);
            if ($date) {
                return $date;
            }
        }

        $normalized = $this->oneLine($text);
        $labelPattern = collect($labels)->map(fn (string $label) => preg_quote($label, '/'))->implode('|');

        if (preg_match('/(?:'.$labelPattern.')\s*[:\-]?\s*(.{0,60})/iu', $normalized, $match)) {
            return $this->firstDate($match[1]);
        }

        if (preg_match('/(.{0,30})(?:'.$labelPattern.')/iu', $normalized, $match)) {
            return $this->firstDate($match[1]);
        }

        return null;
    }

    private function firstDate(string $value): ?string
    {
        if (preg_match('/(?<!\d)(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2})(?!\d)/', $value, $match)) {
            return $this->normalizeDate($match[1]);
        }

        if (preg_match('/\b(\d{1,2}\s+[A-Z]{3,9}\s+\d{2,4}|[A-Z]{3,9}\s+\d{1,2},?\s+\d{2,4})\b/i', $value, $match)) {
            return $this->normalizeDate($match[1]);
        }

        return null;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim(str_replace(['.', '\\'], ['/', '/'], $value));

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/y', 'd-m-y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanReference(string $value): string
    {
        $value = preg_replace('/\b(issue|issued|expiry|expiration|date|valid|owner|property type|community|plot|municipality|building|floor|parkings|suite|area)\b.*$/i', '', $value) ?: $value;

        return Str::of($value)
            ->replaceMatches('/[^A-Z0-9\/\-.]/i', '')
            ->trim('/-. ')
            ->upper()
            ->toString();
    }

    private function cleanReverseValue(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[^\pL\pN\/\-. ]/u', '')
            ->squish()
            ->toString();
    }

    private function oneLine(string $text): string
    {
        return Str::of($text)->replaceMatches('/\s+/u', ' ')->squish()->toString();
    }

    private function detectDocumentText(string $bytes): string
    {
        $result = $this->textract()->detectDocumentText([
            'Document' => ['Bytes' => $bytes],
        ]);

        return collect($result['Blocks'] ?? [])
            ->where('BlockType', 'LINE')
            ->pluck('Text')
            ->filter()
            ->implode("\n");
    }

    private function extractPdfText(UploadedFile $file): string
    {
        if (! class_exists(PdfParser::class)) {
            return '';
        }

        return Str::of($this->sanitizeUtf8((new PdfParser())->parseFile($file->getRealPath())->getText()))
            ->replace("\r", "\n")
            ->replaceMatches("/\n{3,}/", "\n\n")
            ->trim()
            ->toString();
    }

    private function isPdf(UploadedFile $file): bool
    {
        return strtolower((string) $file->getClientOriginalExtension()) === 'pdf'
            || $file->getMimeType() === 'application/pdf';
    }

    private function response(bool $ok, string $message, array $fields, string $rawText): array
    {
        $fields = collect($fields)
            ->map(fn ($value) => is_string($value) ? $this->sanitizeUtf8($value) : $value)
            ->all();

        return compact('ok', 'message', 'fields') + [
            'raw_text' => '',
        ];
    }

    private function failureMessage(\Throwable $exception): string
    {
        if ($exception instanceof AwsException) {
            return 'AWS Textract error: '.trim($exception->getAwsErrorMessage() ?: $exception->getMessage());
        }

        return 'OCR scan failed: '.$exception->getMessage();
    }

    private function textract(): TextractClient
    {
        return new TextractClient([
            'version' => 'latest',
            'region' => config('ocr.aws.region'),
            'credentials' => [
                'key' => config('ocr.aws.key'),
                'secret' => config('ocr.aws.secret'),
            ],
        ]);
    }

    private function sanitizeUtf8(string $value): string
    {
        if (function_exists('mb_scrub')) {
            $value = mb_scrub($value, 'UTF-8');
        } else {
            $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?: '';
    }
}
