<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('units')->where('availability_status', 'booked')->update(['availability_status' => 'occupied']);

        DB::table('units')
            ->where('availability_status', 'occupied')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('bookings')
                    ->whereColumn('bookings.unit_id', 'units.id')
                    ->whereNull('bookings.deleted_at')
                    ->whereIn('bookings.booking_status', ['confirmed', 'extended', 'checked_in', 'checkout_requested'])
                    ->whereDate('bookings.check_in_date', '<=', now()->toDateString())
                    ->where(function ($query): void {
                        $query->whereDate('bookings.check_out_date', '>=', now()->toDateString())
                            ->orWhereExists(function ($query): void {
                                $query->selectRaw('1')
                                    ->from('booking_extension_requests')
                                    ->whereColumn('booking_extension_requests.booking_id', 'bookings.id')
                                    ->whereIn('booking_extension_requests.status', ['approved_pending_payment', 'paid_extended'])
                                    ->whereDate('booking_extension_requests.requested_check_out_date', '>=', now()->toDateString());
                            });
                    });
            })
            ->update(['availability_status' => 'available']);
    }

    public function down(): void {}
};
