<?php

namespace App\Support;

use Aws\Textract\TextractClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IdentityDocumentOcr
{
    public function extract(UploadedFile $file): array
    {
        if (! config('ocr.enabled')) {
            return [
                'ok' => false,
                'message' => 'OCR is disabled. Set OCR_ENABLED=true in .env.',
                'fields' => [],
                'raw_text' => '',
            ];
        }

        $bytes = file_get_contents($file->getRealPath());
        $textract = $this->textract();
        $identityFields = [];
        $rawText = '';

        try {
            $result = $textract->analyzeID([
                'DocumentPages' => [
                    ['Bytes' => $bytes],
                ],
            ]);

            foreach (($result['IdentityDocuments'] ?? []) as $document) {
                foreach (($document['IdentityDocumentFields'] ?? []) as $field) {
                    $type = strtoupper((string) data_get($field, 'Type.Text'));
                    $value = trim((string) data_get($field, 'ValueDetection.Text'));
                    if ($type && $value) {
                        $identityFields[$type] = $value;
                    }
                }
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            $textResult = $textract->detectDocumentText([
                'Document' => ['Bytes' => $bytes],
            ]);

            $rawText = collect($textResult['Blocks'] ?? [])
                ->where('BlockType', 'LINE')
                ->pluck('Text')
                ->filter()
                ->implode("\n");
        } catch (\Throwable $exception) {
            report($exception);
        }

        $fields = $this->normalize($identityFields, $rawText);

        return [
            'ok' => (bool) array_filter($fields),
            'message' => array_filter($fields) ? 'Document scanned. Please review before saving.' : 'No reliable identity data found. Please fill manually.',
            'fields' => $fields,
            'raw_text' => $rawText,
            'provider_fields' => $identityFields,
        ];
    }

    private function normalize(array $identityFields, string $rawText): array
    {
        $documentNumber = $identityFields['DOCUMENT_NUMBER']
            ?? $identityFields['ID_NUMBER']
            ?? $identityFields['PASSPORT_NUMBER']
            ?? $this->extractIdentityNumber($rawText);

        $identityType = $this->detectIdentityType($rawText, $documentNumber);
        $fullName = $this->extractName($identityFields, $rawText);

        return [
            'identity_type' => $identityType,
            'identity_no' => $documentNumber ? $this->cleanDocumentNumber($documentNumber, $identityType) : null,
            'full_name' => $fullName,
            'date_of_birth' => $this->normalizeDate($identityFields['DATE_OF_BIRTH'] ?? $this->extractDateNear($rawText, ['date of birth', 'birth', 'dob'])),
            'identity_expiry_date' => $this->normalizeDate($identityFields['EXPIRATION_DATE'] ?? $identityFields['EXPIRY_DATE'] ?? $this->extractDateNear($rawText, ['expiry', 'expiration', 'valid until'])),
        ];
    }

    private function extractName(array $identityFields, string $rawText): ?string
    {
        $name = $identityFields['NAME']
            ?? trim(implode(' ', array_filter([
                $identityFields['FIRST_NAME'] ?? null,
                $identityFields['MIDDLE_NAME'] ?? null,
                $identityFields['LAST_NAME'] ?? null,
            ])));

        if ($name) {
            return $this->cleanName($name);
        }

        if (preg_match('/(?:name|full name|surname)\s*[:\-]?\s*([A-Z][A-Z\s]{4,})/i', $rawText, $match)) {
            return $this->cleanName($match[1]);
        }

        return null;
    }

    private function extractIdentityNumber(string $rawText): ?string
    {
        if (preg_match('/\b784[-\s]?\d{4}[-\s]?\d{7}[-\s]?\d\b/', $rawText, $match)) {
            return $match[0];
        }

        if (preg_match('/(?:passport|document|id)\s*(?:no|number|#)?\s*[:\-]?\s*([A-Z0-9]{6,15})/i', $rawText, $match)) {
            return $match[1];
        }

        return null;
    }

    private function detectIdentityType(string $rawText, ?string $documentNumber): ?string
    {
        if ($documentNumber && preg_match('/^784/', preg_replace('/\D/', '', $documentNumber))) {
            return 'emirates_id';
        }

        if (Str::contains(Str::lower($rawText), ['emirates id', 'identity card', 'united arab emirates'])) {
            return 'emirates_id';
        }

        if (Str::contains(Str::lower($rawText), ['passport'])) {
            return 'passport';
        }

        return null;
    }

    private function extractDateNear(string $rawText, array $labels): ?string
    {
        $lines = preg_split('/\R+/', $rawText) ?: [];
        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), $labels)) {
                continue;
            }

            $window = trim($line.' '.($lines[$index + 1] ?? ''));
            if (preg_match('/\b(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2})\b/', $window, $match)) {
                return $match[1];
            }
        }

        return null;
    }

    private function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim(str_replace(['.', '\\'], ['/', '/'], $value));
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/y', 'd-m-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
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

    private function cleanDocumentNumber(string $value, ?string $identityType): string
    {
        $value = trim($value);

        if ($identityType === 'emirates_id') {
            $digits = preg_replace('/\D/', '', $value);
            if (strlen($digits) === 15) {
                return substr($digits, 0, 3).'-'.substr($digits, 3, 4).'-'.substr($digits, 7, 7).'-'.substr($digits, 14, 1);
            }
        }

        return strtoupper(preg_replace('/[^A-Z0-9\-]/i', '', $value));
    }

    private function cleanName(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[^A-Z\s.\'-]/i', ' ')
            ->squish()
            ->title()
            ->toString();
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
