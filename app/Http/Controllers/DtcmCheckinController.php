<?php

namespace App\Http\Controllers;

use App\Models\DtcmCheckin;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class DtcmCheckinController extends Controller
{
    public function index()
    {
        return view('dtcm-checkins.index', [
            'checkins' => DtcmCheckin::query()
                ->with(['booking.tenant', 'booking.unit.building'])
                ->when(request('status'), fn ($query, string $status) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function complete(Request $request, DtcmCheckin $dtcmCheckin)
    {
        $validated = $request->validate([
            'portal_reference' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $dtcmCheckin->update([
            'status' => 'registered',
            'portal_reference' => $validated['portal_reference'] ?? $dtcmCheckin->portal_reference,
            'notes' => $validated['notes'] ?? $dtcmCheckin->notes,
            'submitted_at' => now(),
        ]);

        $dtcmCheckin->booking()->update(['booking_status' => 'checked_in']);

        ActivityLogger::log('dtcm_checkins.registered', "DTCM check-in completed for booking {$dtcmCheckin->booking->booking_no}.", $dtcmCheckin);

        return back()->with('status', 'DTCM guest registration completed. Booking is now checked in.');
    }
}
