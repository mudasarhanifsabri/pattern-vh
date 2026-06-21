<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingDepositRefund;
use Illuminate\Http\Request;

class SecurityDepositController extends Controller
{
    public function index(Request $request)
    {
        $refunds = BookingDepositRefund::query()
            ->with(['booking.unit.building', 'tenant'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $activeBookings = Booking::query()
            ->with(['tenant', 'unit.building', 'depositRefund'])
            ->where('deposit_amount', '>', 0)
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested', 'checked_out'])
            ->latest('check_in_date')
            ->limit(10)
            ->get();

        return view('security-deposits.index', [
            'refunds' => $refunds,
            'activeBookings' => $activeBookings,
            'stats' => [
                'held' => Booking::where('deposit_amount', '>', 0)->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])->sum('deposit_amount'),
                'pending_review' => BookingDepositRefund::whereIn('status', ['pending_inspection', 'tenant_review'])->sum('deposit_amount'),
                'damage' => BookingDepositRefund::sum('damage_amount'),
                'refunded' => BookingDepositRefund::where('status', 'refunded')->sum('refund_amount'),
            ],
        ]);
    }
}
