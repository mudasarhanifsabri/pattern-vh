<?php

namespace Tests\Unit;

use App\Support\IdentityDocumentOcr;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class IdentityDocumentOcrParserTest extends TestCase
{
    public function test_passport_pdf_text_ignores_cover_paragraph_and_extracts_passport_fields(): void
    {
        $fields = $this->normalize(<<<'TEXT'
            The President Islamic Republic of Pakistan
            requests and requires in the name of the President all those to whom it may concern
            to allow the bearer to pass freely without let or hindrance and to afford the bearer such assistance and protection as may be necessary
            Passport No
            CJ5023062
            Surname
            SABRI
            Given Names
            MUDASAR HANIF
            Nationality
            PAKISTANI
            Date of Birth
            11 FEB 1995
            Date of Issue
            26 SEP 2023
            Date of Expiry
            24 SEP 2028
            TEXT);

        $this->assertSame('passport', $fields['identity_type']);
        $this->assertSame('CJ5023062', $fields['identity_no']);
        $this->assertSame('Mudasar Hanif Sabri', $fields['full_name']);
        $this->assertSame('Pakistani', $fields['nationality']);
        $this->assertSame('1995-02-11', $fields['date_of_birth']);
        $this->assertSame('2023-09-26', $fields['identity_issue_date']);
        $this->assertSame('2028-09-24', $fields['identity_expiry_date']);
    }

    public function test_emirates_id_text_extracts_identity_number_and_name(): void
    {
        $fields = $this->normalize(<<<'TEXT'
            UNITED ARAB EMIRATES
            Resident Identity Card
            ID Number
            784-1981-7063585-2
            Name: Shabina Naz Abdullah
            Date of Birth:
            10/06/1981
            Nationality: Pakistan
            Issuing Date
            23/10/2025
            Expiry Date
            22/10/2027
            TEXT);

        $this->assertSame('emirates_id', $fields['identity_type']);
        $this->assertSame('784-1981-7063585-2', $fields['identity_no']);
        $this->assertSame('Shabina Naz Abdullah', $fields['full_name']);
        $this->assertSame('Pakistani', $fields['nationality']);
        $this->assertSame('1981-06-10', $fields['date_of_birth']);
        $this->assertSame('2025-10-23', $fields['identity_issue_date']);
        $this->assertSame('2027-10-22', $fields['identity_expiry_date']);
    }

    private function normalize(string $rawText): array
    {
        $method = new ReflectionMethod(IdentityDocumentOcr::class, 'normalize');
        $method->setAccessible(true);

        return $method->invoke(new IdentityDocumentOcr(), [], $rawText);
    }
}
