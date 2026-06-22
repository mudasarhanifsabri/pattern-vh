<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
            'keys.p256dh' => ['nullable', 'string', 'max:1000'],
            'keys.auth' => ['nullable', 'string', 'max:1000'],
            'contentEncoding' => ['nullable', 'string', 'max:100'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint_hash' => hash('sha256', $validated['endpoint']),
            ],
            [
                'endpoint' => $validated['endpoint'],
                'public_key' => data_get($validated, 'keys.p256dh'),
                'auth_token' => data_get($validated, 'keys.auth'),
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
        ]);

        PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
