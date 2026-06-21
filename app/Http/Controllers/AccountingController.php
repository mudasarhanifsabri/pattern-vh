<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Unit;

class AccountingController extends Controller
{
    public function __invoke()
    {
        return view('accounting.index', [
            'stats' => [
                'revenue' => Payment::where('status', 'approved')->sum('amount'),
                'open_balance' => Invoice::where('balance_amount', '>', 0)->sum('balance_amount'),
                'expenses' => Expense::sum('amount'),
                'owner_units' => Unit::whereHas('owners')->count(),
            ],
            'recentExpenses' => Expense::with(['owner', 'unit.building'])->latest('incurred_on')->limit(6)->get(),
            'ownerCount' => Owner::count(),
            'activeBookings' => Booking::whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])->count(),
        ]);
    }
}
