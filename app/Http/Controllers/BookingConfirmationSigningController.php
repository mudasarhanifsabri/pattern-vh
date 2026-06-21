<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\ActivityLogger;
use App\Support\BookingConfirmationPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingConfirmationSigningController extends Controller
{
    public function send(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['in:email,whatsapp,sms,portal'],
        ]);

        $token = $booking->confirmation_token ?: Str::random(48);
        $booking->forceFill([
            'confirmation_token' => $token,
            'confirmation_delivery_channels' => $validated['channels'],
            'confirmation_link_sent_at' => now(),
        ])->save();

        $link = route('booking-confirmations.sign', [$booking, $token]);

        foreach ($validated['channels'] as $channel) {
            $recipient = match ($channel) {
                'email' => $booking->tenant->email,
                'whatsapp', 'sms' => $booking->tenant->mobile_no,
                default => 'tenant portal',
            };

            $booking->notificationLogs()->create([
                'channel' => $channel,
                'recipient' => $recipient,
                'subject' => 'Booking confirmation signing link',
                'message' => "Open and sign booking confirmation {$booking->booking_no}: {$link}",
                'status' => 'sent',
                'payload' => ['signing_link' => $link],
                'sent_at' => now(),
            ]);
        }

        ActivityLogger::log('bookings.confirmation_link_sent', "Sent booking confirmation signing link for {$booking->booking_no}.", $booking, [
            'channels' => $validated['channels'],
        ]);

        return back()->with('status', 'Booking confirmation signing link marked sent.');
    }

    public function show(Booking $booking, string $token)
    {
        abort_unless(hash_equals((string) $booking->confirmation_token, $token), 403);

        return view('bookings.sign-confirmation', [
            'booking' => $booking->load(['unit.building', 'tenant', 'agent']),
            'token' => $token,
        ]);
    }

    public function sign(Request $request, Booking $booking, string $token)
    {
        abort_unless(hash_equals((string) $booking->confirmation_token, $token), 403);

        $validated = $request->validate([
            'signed_by' => ['required', 'string', 'max:191'],
            'signature_text' => ['required', 'string', 'max:191'],
            'signature_data' => ['required', 'string', 'starts_with:data:image/png;base64,'],
            'accepted_terms' => ['accepted'],
        ]);

        $booking->forceFill([
            'confirmation_signed_at' => now(),
            'confirmation_signed_by' => $validated['signed_by'],
            'confirmation_signature_text' => $validated['signature_text'],
            'confirmation_signature_data' => $validated['signature_data'],
            'confirmation_signature_mime' => 'image/png',
            'confirmation_signed_ip' => $request->ip(),
            'confirmation_signed_user_agent' => substr((string) $request->userAgent(), 0, 500),
        ])->save();

        $booking->notificationLogs()->create([
            'channel' => 'portal',
            'recipient' => $booking->tenant->email ?: $booking->tenant->mobile_no,
            'subject' => 'Booking confirmation signed',
            'message' => "Booking {$booking->booking_no} was signed by {$validated['signed_by']}.",
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        ActivityLogger::log('bookings.confirmation_signed', "Booking confirmation signed for {$booking->booking_no}.", $booking);

        return redirect()->route('booking-confirmations.sign', [$booking, $token])->with('status', 'Booking confirmation signed successfully.');
    }

    public function pdf(Booking $booking, string $token, BookingConfirmationPdf $pdf)
    {
        abort_unless(hash_equals((string) $booking->confirmation_token, $token), 403);

        return response($pdf->make($booking), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$booking->booking_no.'-confirmation.pdf"',
        ]);
    }
}
