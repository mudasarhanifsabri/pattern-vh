<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Building;
use App\Models\Unit;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityCalendarController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->filled('start')
            ? Carbon::parse($request->input('start'))->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $days = max(7, min(31, (int) $request->integer('days', 14)));
        $end = $start->copy()->addDays($days - 1);

        $units = Unit::query()
            ->with('building')
            ->when($request->integer('building_id'), fn ($query, int $id) => $query->where('building_id', $id))
            ->when($request->integer('unit_id'), fn ($query, int $id) => $query->where('id', $id))
            ->orderBy('building_id')
            ->orderBy('unit_no')
            ->get();

        $bookings = Booking::query()
            ->with(['tenant', 'unit'])
            ->whereIn('booking_status', ['confirmed', 'checked_in', 'checkout_requested'])
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->input('source')))
            ->whereDate('check_in_date', '<=', $end)
            ->whereDate('check_out_date', '>=', $start)
            ->get()
            ->groupBy('unit_id');

        return view('availability-calendar.index', [
            'start' => $start,
            'end' => $end,
            'days' => collect(CarbonPeriod::create($start, $end))->values(),
            'units' => $units,
            'bookingsByUnit' => $bookings,
            'buildings' => Building::query()->orderBy('name')->get(),
            'allUnits' => Unit::query()->with('building')->orderBy('unit_no')->get(),
            'sources' => Booking::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source'),
        ]);
    }
}
