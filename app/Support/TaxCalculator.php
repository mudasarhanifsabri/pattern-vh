<?php

namespace App\Support;

class TaxCalculator
{
    public static function rentVat(float|int|string|null $rentAmount): float
    {
        return round(((float) $rentAmount) * self::rate(), 2);
    }

    public static function rate(): float
    {
        return ((float) config('erp.vat_rate', 5)) / 100;
    }

    public static function ratePercent(): float
    {
        return (float) config('erp.vat_rate', 5);
    }

    public static function bookingTotal(array $data): float
    {
        $rent = self::rentFromBookingData($data);
        $vat = self::rentVat($rent);
        $fees = collect(['deposit_amount', 'dtcm_fee', 'cleaning_fee', 'agency_fee'])
            ->sum(fn (string $field): float => (float) ($data[$field] ?? 0));

        return round($rent + $vat + $fees, 2);
    }

    public static function invoiceTotal(array $data): float
    {
        $rent = (float) ($data['rent_amount'] ?? 0);
        $vat = self::rentVat($rent);
        $fees = collect(['deposit_amount', 'dtcm_fee', 'cleaning_fee', 'agency_fee'])
            ->sum(fn (string $field): float => (float) ($data[$field] ?? 0));

        return round($rent + $vat + $fees, 2);
    }

    public static function rentFromBookingData(array $data): float
    {
        $rent = collect($data['rental_periods'] ?? [])
            ->sum(fn (array $period): float => (float) ($period['rent_amount'] ?? 0));

        return $rent > 0 ? (float) $rent : (float) ($data['rent_amount'] ?? 0);
    }
}
