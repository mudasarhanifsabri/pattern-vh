<?php

namespace App\Support;

use Aws\Textract\TextractClient;
use Aws\Exception\AwsException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class IdentityDocumentOcr
{
    private const TEXT_FIELD_LABELS = [
        'name',
        'full name',
        'surname',
        'date of birth',
        'birth',
        'dob',
        'nationality',
        'nationalite',
        'issuing date',
        'issue date',
        'date of issue',
        'expiry date',
        'expiry',
        'expiration date',
        'expiration',
        'valid until',
        'date of expiry',
        'sex',
        'gender',
        'id number',
        'identity number',
        'document number',
        'passport number',
        'passport no',
        'signature',
    ];

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
        $usedTextFallback = false;
        $startedAt = microtime(true);
        $mode = config('ocr.textract_mode', 'detect_text');
        $isPdf = strtolower((string) $file->getClientOriginalExtension()) === 'pdf'
            || $file->getMimeType() === 'application/pdf';

        if ($mode === 'detect_text' || $isPdf) {
            try {
                $rawText = $this->detectDocumentText($textract, $bytes);
                $usedTextFallback = (bool) $rawText;
            } catch (\Throwable $exception) {
                report($exception);

                return $this->failure($exception);
            }
        } else {
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

                $rawText = implode("\n", array_filter($identityFields));
            } catch (\Throwable $exception) {
                report($exception);

                return $this->failure($exception);
            }

            if (config('ocr.text_fallback') || empty($identityFields)) {
                try {
                    $detectedText = $this->detectDocumentText($textract, $bytes);

                    if ($detectedText) {
                        $rawText = trim($rawText."\n".$detectedText);
                        $usedTextFallback = true;
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        }

        $fields = $this->normalize($identityFields, $rawText);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        return [
            'ok' => (bool) array_filter($fields),
            'message' => array_filter($fields) ? 'Document scanned. Please review before saving.' : 'No reliable identity data found. Please fill manually.',
            'fields' => $fields,
            'raw_text' => $rawText,
            'provider_fields' => $identityFields,
            'meta' => [
                'duration_ms' => $durationMs,
                'text_fallback_used' => $usedTextFallback,
                'pdf_text_detection_used' => $isPdf,
            ],
        ];
    }

    private function failure(\Throwable $exception): array
    {
        return [
            'ok' => false,
            'message' => $this->failureMessage($exception),
            'fields' => [],
            'raw_text' => '',
        ];
    }

    private function failureMessage(\Throwable $exception): string
    {
        if ($exception instanceof AwsException) {
            $awsMessage = trim($exception->getAwsErrorMessage() ?: $exception->getMessage());

            if ($exception->getAwsErrorCode() === 'UnrecognizedClientException') {
                return 'AWS Textract rejected the access key. Please check OCR_AWS_ACCESS_KEY_ID and OCR_AWS_SECRET_ACCESS_KEY.';
            }

            if (Str::contains(Str::lower($awsMessage), ['security token', 'access key'])) {
                return 'AWS Textract credentials are not valid. Please update OCR AWS keys in settings.';
            }

            if (Str::contains(Str::lower($awsMessage), ['unsupported document', 'request has unsupported document format'])) {
                return 'Textract could not read this file format. Please try a clearer PDF or upload a JPG, PNG, or WEBP image.';
            }

            return 'AWS Textract error: '.$awsMessage;
        }

        return 'OCR scan failed: '.$exception->getMessage();
    }

    private function normalize(array $identityFields, string $rawText): array
    {
        $mrzFields = $this->extractMrzFields($rawText);
        $identityType = $this->detectIdentityType($rawText, $mrzFields['identity_no'] ?? null) ?? ($mrzFields['identity_type'] ?? null);
        $documentNumber = $identityFields['DOCUMENT_NUMBER']
            ?? $identityFields['ID_NUMBER']
            ?? ($identityType === 'passport' ? ($mrzFields['identity_no'] ?? null) : null)
            ?? $identityFields['PASSPORT_NUMBER']
            ?? ($identityType === 'passport' ? $this->extractPassportNumber($rawText) : null)
            ?? $this->extractIdentityNumber($rawText)
            ?? ($mrzFields['identity_no'] ?? null);

        $fullName = $identityType === 'passport'
            ? (($mrzFields['full_name'] ?? null) ?: $this->extractPassportName($rawText) ?: $this->extractName($identityFields, $rawText))
            : ($this->extractName($identityFields, $rawText) ?? ($mrzFields['full_name'] ?? null));
        $nationality = $this->normalizeNationality(
            $identityFields['NATIONALITY']
                ?? $identityFields['NATIONALITY_CODE']
                ?? $identityFields['COUNTRY']
                ?? $identityFields['COUNTRY_OF_ISSUE']
                ?? $identityFields['ISSUING_COUNTRY']
                ?? ($mrzFields['nationality'] ?? null)
                ?? $this->extractNationality($rawText)
        );

        return [
            'identity_type' => $identityType,
            'identity_no' => $documentNumber ? $this->cleanDocumentNumber($documentNumber, $identityType) : null,
            'full_name' => $fullName,
            'nationality' => $nationality,
            'date_of_birth' => $this->normalizeDate($identityFields['DATE_OF_BIRTH'] ?? $this->extractDateNear($rawText, ['date of birth', 'birth', 'dob']) ?? ($mrzFields['date_of_birth'] ?? null)),
            'identity_issue_date' => $this->normalizeDate($identityFields['ISSUE_DATE'] ?? $identityFields['ISSUING_DATE'] ?? $this->extractDateNear($rawText, ['issuing date', 'issue date', 'date of issue'])),
            'identity_expiry_date' => $this->normalizeDate($identityFields['EXPIRATION_DATE'] ?? $identityFields['EXPIRY_DATE'] ?? $this->extractDateNear($rawText, ['expiry date', 'expiry', 'expiration date', 'expiration', 'valid until', 'date of expiry']) ?? ($mrzFields['identity_expiry_date'] ?? null)),
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
            return $this->validName($this->cleanName($name));
        }

        $name = $this->extractTextAfterLabel($rawText, ['full name', 'name', 'surname']);
        if ($name) {
            return $this->validName($this->cleanName($name));
        }

        return null;
    }

    private function extractPassportName(string $rawText): ?string
    {
        $lines = collect(preg_split('/\R+/', $rawText) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values();

        $surname = null;
        $givenNames = null;

        foreach ($lines as $index => $line) {
            $lower = Str::lower($line);

            if (! $surname && Str::contains($lower, ['surname', 'last name'])) {
                $surname = $this->extractValueFromLineWindow($lines, $index, ['surname', 'last name']);
            }

            if (! $givenNames && Str::contains($lower, ['given name', 'given names', 'first name'])) {
                $givenNames = $this->extractValueFromLineWindow($lines, $index, ['given names', 'given name', 'first name']);
            }
        }

        $combined = trim(($givenNames ?: '').' '.($surname ?: ''));
        if ($combined) {
            return $this->validName($this->cleanName($combined));
        }

        return null;
    }

    private function extractNationality(string $rawText): ?string
    {
        $lines = preg_split('/\R+/', $rawText) ?: [];

        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), ['nationality', 'nationalite', 'الجنسية'])) {
                continue;
            }

            $window = trim($line.' '.($lines[$index + 1] ?? ''));
            $nationality = $this->extractTextAfterLabel($window, ['nationality', 'nationalite', 'الجنسية'], ['issuing date', 'issue date', 'date of issue', 'expiry date', 'expiration date']);
            if ($nationality) {
                return $nationality;
            }

            if (! empty($lines[$index + 1]) && preg_match('/^[A-Z][A-Z\s]{2,}$/i', trim($lines[$index + 1]))) {
                return trim($lines[$index + 1]);
            }
        }

        return null;
    }

    private function extractIdentityNumber(string $rawText): ?string
    {
        if (preg_match('/\b784[-\s]?\d{4}[-\s]?\d{7}[-\s]?\d\b/', $rawText, $match)) {
            return $match[0];
        }

        if (preg_match('/(?:passport|document|id|identity)\s*(?:no|number|#)\s*[:\-]?\s*([A-Z0-9]{6,15})/i', $rawText, $match)) {
            return $match[1];
        }

        return null;
    }

    private function extractPassportNumber(string $rawText): ?string
    {
        $lines = preg_split('/\R+/', $rawText) ?: [];

        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), ['passport no', 'passport number', 'passport n0'])) {
                continue;
            }

            $window = implode(' ', array_slice($lines, $index, 4));
            if (preg_match('/\b([A-Z]{1,3}\s?\d{5,9}|[A-Z]\d{6,9})\b/i', $window, $match)) {
                return strtoupper(str_replace(' ', '', $match[1]));
            }
        }

        if (preg_match('/\b([A-Z]{1,3}\s?\d{6,9})\b/i', $rawText, $match)) {
            return strtoupper(str_replace(' ', '', $match[1]));
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
        $value = $this->extractTextAfterLabel($rawText, $labels, self::TEXT_FIELD_LABELS);
        $date = $this->extractFirstDate($value ?: '');
        if ($date) {
            return $date;
        }

        $lines = preg_split('/\R+/', $rawText) ?: [];
        foreach ($lines as $index => $line) {
            if (! Str::contains(Str::lower($line), $labels)) {
                continue;
            }

            $window = trim(implode(' ', array_slice($lines, $index, 5)));
            $value = $this->extractTextAfterLabel($window, $labels, self::TEXT_FIELD_LABELS);
            $date = $this->extractFirstDate($value ?: $window);
            if ($date) {
                return $date;
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
        $date = $this->extractFirstDate($value);
        if ($date) {
            $value = $date;
        }

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
        $value = $this->stripTrailingLabels($value);

        return Str::of($value)
            ->replaceMatches('/[^A-Z\s.\'-]/i', ' ')
            ->squish()
            ->title()
            ->toString();
    }

    private function validName(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $lower = Str::lower($value);
        if (Str::length($value) > 80 || Str::contains($lower, [
            'president',
            'republic',
            'concern',
            'bearer',
            'hindrance',
            'protection',
            'director',
            'immigration',
            'passport',
            'government',
        ])) {
            return null;
        }

        return preg_match('/^[A-Z][A-Z\s.\'-]{2,}$/i', $value) ? $value : null;
    }

    private function extractValueFromLineWindow(\Illuminate\Support\Collection $lines, int $index, array $labels): ?string
    {
        $line = (string) $lines[$index];
        $value = $this->extractTextAfterLabel($line, $labels, self::TEXT_FIELD_LABELS);
        if ($this->validName($this->cleanName((string) $value))) {
            return $value;
        }

        for ($offset = 1; $offset <= 2; $offset++) {
            $candidate = trim((string) ($lines[$index + $offset] ?? ''));
            if ($this->validName($this->cleanName($candidate))) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeNationality(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = $this->stripTrailingLabels($value);

        $value = Str::of($value)
            ->replaceMatches('/[^A-Z\s]/i', ' ')
            ->squish()
            ->upper()
            ->toString();

        if (! $value) {
            return null;
        }

        $map = [
            'ARE' => 'Emirati',
            'UAE' => 'Emirati',
            'UNITED ARAB EMIRATES' => 'Emirati',
            'EMIRATI' => 'Emirati',
            'IND' => 'Indian',
            'INDIA' => 'Indian',
            'INDIAN' => 'Indian',
            'PAK' => 'Pakistani',
            'PAKISTAN' => 'Pakistani',
            'PAKISTANI' => 'Pakistani',
            'PHL' => 'Filipino',
            'PHILIPPINES' => 'Filipino',
            'FILIPINO' => 'Filipino',
            'GBR' => 'British',
            'UNITED KINGDOM' => 'British',
            'BRITISH' => 'British',
            'USA' => 'American',
            'UNITED STATES' => 'American',
            'AMERICAN' => 'American',
            'EGY' => 'Egyptian',
            'EGYPT' => 'Egyptian',
            'EGYPTIAN' => 'Egyptian',
            'JOR' => 'Jordanian',
            'JORDAN' => 'Jordanian',
            'JORDANIAN' => 'Jordanian',
            'LBN' => 'Lebanese',
            'LEBANON' => 'Lebanese',
            'LEBANESE' => 'Lebanese',
        ];

        return $map[$value] ?? Str::of($value)->lower()->title()->toString();
    }

    private function extractTextAfterLabel(string $rawText, array $labels, ?array $stopLabels = null): ?string
    {
        $text = Str::of($rawText)
            ->replaceMatches('/\s+/u', ' ')
            ->squish()
            ->toString();

        if (! $text) {
            return null;
        }

        $labelPattern = $this->labelsPattern($labels);
        $stopPattern = $this->labelsPattern(array_values(array_unique(array_diff($stopLabels ?? self::TEXT_FIELD_LABELS, $labels))));

        if (preg_match('/(?:^|\s)(?:'.$labelPattern.')\s*[:\-]?\s*(.+?)(?=\s+(?:'.$stopPattern.')\b|$)/iu', $text, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function stripTrailingLabels(string $value): string
    {
        return trim(preg_replace('/\b(?:'.$this->labelsPattern(self::TEXT_FIELD_LABELS).')\b.*$/iu', '', $value) ?? $value);
    }

    private function extractFirstDate(string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/\b(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{4}[\/\-.]\d{1,2}[\/\-.]\d{1,2})\b/', $value, $match)) {
            return $match[1];
        }

        if (preg_match('/\b(\d{1,2}\s+[A-Z]{3,9}\s+\d{2,4}|[A-Z]{3,9}\s+\d{1,2},?\s+\d{2,4})\b/i', $value, $match)) {
            return $match[1];
        }

        return null;
    }

    private function extractMrzFields(string $rawText): array
    {
        $lines = collect(preg_split('/\R+/', $rawText) ?: [])
            ->map(fn (string $line) => strtoupper(preg_replace('/[^A-Z0-9<]/', '', $line) ?? ''))
            ->filter(fn (string $line) => strlen($line) >= 25 && Str::contains($line, '<'))
            ->values();

        for ($index = 0; $index < $lines->count() - 1; $index++) {
            $line1 = $lines[$index];
            $line2 = $lines[$index + 1];

            if (! str_starts_with($line1, 'P<') || strlen($line2) < 30) {
                continue;
            }

            $names = explode('<<', substr($line1, 5), 2);
            $surname = str_replace('<', ' ', $names[0] ?? '');
            $givenNames = str_replace('<', ' ', $names[1] ?? '');
            $passportNo = rtrim(substr($line2, 0, 9), '<');
            $nationality = substr($line2, 10, 3);
            $birth = substr($line2, 13, 6);
            $expiry = substr($line2, 21, 6);

            return array_filter([
                'identity_type' => 'passport',
                'identity_no' => $passportNo ?: null,
                'full_name' => $this->cleanName(trim($givenNames.' '.$surname)),
                'nationality' => $nationality,
                'date_of_birth' => $this->normalizeMrzDate($birth),
                'identity_expiry_date' => $this->normalizeMrzDate($expiry, futurePreferred: true),
            ]);
        }

        return [];
    }

    private function normalizeMrzDate(string $value, bool $futurePreferred = false): ?string
    {
        if (! preg_match('/^\d{6}$/', $value)) {
            return null;
        }

        $year = (int) substr($value, 0, 2);
        $month = substr($value, 2, 2);
        $day = substr($value, 4, 2);
        $century = $futurePreferred || $year <= (int) now()->format('y') + 10 ? 2000 : 1900;

        try {
            return Carbon::create($century + $year, (int) $month, (int) $day)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function labelsPattern(array $labels): string
    {
        return collect($labels)
            ->filter()
            ->map(fn (string $label) => preg_quote($label, '/'))
            ->implode('|');
    }

    private function detectDocumentText(TextractClient $textract, string $bytes): string
    {
        $textResult = $textract->detectDocumentText([
            'Document' => ['Bytes' => $bytes],
        ]);

        return collect($textResult['Blocks'] ?? [])
            ->where('BlockType', 'LINE')
            ->pluck('Text')
            ->filter()
            ->implode("\n");
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
