<?php

namespace Tests\Unit;

use App\Support\UnitDocumentOcr;
use PHPUnit\Framework\TestCase;

class UnitDocumentOcrTest extends TestCase
{
    public function test_dubai_title_deed_text_fills_unit_fields(): void
    {
        $fields = (new UnitDocumentOcr())->parseText(<<<'TEXT'
            Title Deed
            Issue Date 28/10/2024
            Mortgage Status: Not mortgaged
            Property Type: Flat
            Community: Al Merkadh
            Plot No: 1491
            Municipality No: 347 - 4995
            Building No: 1
            Building Name: Azizi Riviera 18
            Property No: 524
            Floor No: 5
            Parkings: B2-31
            Suite Area : 24.70
            Balcony Area : 5.11
            Area Sq Meter : 29.81
            Area Sq Feet : 320.87
            Registration No. : 285435/2024 Date 27/10/2024
            Approved Signature
            285436/2024
            DUBAI LAND DEPARTMENT
            TEXT, 'title_deed');

        $this->assertSame('524', $fields['unit_no']);
        $this->assertSame('5', $fields['floor']);
        $this->assertSame('320.87', $fields['size_sqft']);
        $this->assertSame('B2-31', $fields['parking_no']);
        $this->assertSame('285435/2024', $fields['title_deed_no']);
        $this->assertSame('2024-10-28', $fields['title_deed_issue_date']);
        $this->assertNull($fields['title_deed_expiry_date']);
    }
}
