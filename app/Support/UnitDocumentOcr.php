<?php

namespace App\Support;

use Aws\Exception\AwsException;
use Aws\Textract\TextractClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UnitDocumentOcr
{
    public function extract(UploadedFile $file, string $documentType): array
    {
        if (! config('ocr.enabled')) {
            return $this->response(false, 'OCR is disabled. Set OCR_ENABLED=true in .env.', [], '');
        }

        try {
            $rawText = $this->detectDocumentText(file_get_contents($file->getRealPath()) ?: '');
        } catch (\Throwable $exception) {
            report($exception);

            return $this->response(false, $this->failureMessage($exception), [], '');
        }

        $fields = $documentType === 'title_deed'
            ? $this->titleDeedFields($rawText)
            : $this->dtcmFields($rawText);

        return $this->response(
            (bool) array_filter($fields),
            array_filter($fields) ? 'Document scanned. Please review extracted unit fields before saving.' : 'No reliable unit document data found. Please fill manually.',
            $fields,
            $rawText
        );
    }

    private function titleDeedFields(string $text): array
    {
        return [
            'title_deed_no' => $this->extractReference($text, [
                'title deed no', 'title deed number', 'title deed', 'certificate no', 'certificate number',
                'document no', 'document number', 'registration no', 'registration number',
            ]),
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

    private function extractReference(string $text, array $labels): ?string
    {
        $normalized = $this->oneLine($text);
        $labelPattern = collect($labels)->map(fn (string $label) => preg_quote($label, '/'))->implode('|');

        if (preg_match('/(?:'.$labelPattern.')\s*[:#\-]?\s*([A-Z0-9][A-Z0-9\/\-. ]{3,40})/iu', $normalized, $match)) {
            return $this->cleanReference($match[1]);
        }

        if (preg_match('/\b(?:DTCM|HH|TD|DEED)[\-\/ ]?[A-Z0-9]{4,24}\b/i', $normalized, $match)) {
            return $this->cleanReference($match[0]);
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

        return null;
    }

    private function firstDate(string $value): ?string
    {
        if (preg_match('/\b(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2})\b/', $value, $match)) {
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
        $value = preg_replace('/\b(issue|issued|expiry|expiration|date|valid|owner|property|unit)\b.*$/i', '', $value) ?: $value;

        return Str::of($value)
            ->replaceMatches('/[^A-Z0-9\/\-.]/i', '')
            ->trim('/-. ')
            ->upper()
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

    private function response(bool $ok, string $message, array $fields, string $rawText): array
    {
        return compact('ok', 'message', 'fields') + ['raw_text' => $rawText];
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
}
