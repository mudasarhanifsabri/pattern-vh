<?php

namespace App\Http\Controllers;

use App\Models\TtLock;
use App\Models\TtLockEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TtLockCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $request->all();
        $lockId = $this->value($payload, ['lockId', 'lock_id', 'lockID']);
        $recordId = $this->value($payload, ['recordId', 'record_id', 'recordID']);
        $lock = $lockId ? TtLock::query()->with('unit')->where('lock_id', (string) $lockId)->first() : null;

        if ($payload) {
            TtLockEvent::updateOrCreate(
                [
                    'record_id' => $recordId ? (string) $recordId : null,
                    'lock_id' => $lockId ? (string) $lockId : null,
                    'event_at' => $this->eventAt($payload)?->format('Y-m-d H:i:s'),
                ],
                [
                    'tt_lock_id' => $lock?->id,
                    'unit_id' => $lock?->unit?->id,
                    'lock_name' => $this->value($payload, ['lockName', 'lock_name']) ?: $lock?->lock_name,
                    'event_type' => $this->eventType($payload),
                    'operator_name' => $this->value($payload, ['username', 'operator', 'operatorName', 'senderUsername']),
                    'keyboard_pwd' => $this->value($payload, ['keyboardPwd', 'keyboard_pwd', 'password']),
                    'source' => 'callback',
                    'payload' => $payload,
                ],
            );
        }

        File::ensureDirectoryExists(storage_path('logs/ttlock'));
        File::append(
            storage_path('logs/ttlock/callback.log'),
            now()->toDateTimeString().' '.$request->ip().' '.json_encode($payload).PHP_EOL,
        );

        return response()->json(['success' => true]);
    }

    private function value(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                return $payload[$key];
            }
        }

        return null;
    }

    private function eventAt(array $payload): ?\Carbon\Carbon
    {
        $value = $this->value($payload, ['date', 'recordDate', 'eventDate', 'unlockDate', 'time']);

        if (! $value) {
            return now();
        }

        if (is_numeric($value)) {
            $value = (int) $value;

            return $value > 9999999999
                ? \Carbon\Carbon::createFromTimestampMs($value)
                : \Carbon\Carbon::createFromTimestamp($value);
        }

        try {
            return \Carbon\Carbon::parse((string) $value);
        } catch (\Throwable) {
            return now();
        }
    }

    private function eventType(array $payload): string
    {
        $type = $this->value($payload, ['recordType', 'event', 'type', 'eventType']);

        return $type ? str((string) $type)->replace(['_', '-'], ' ')->lower()->slug('_')->toString() : 'unlock';
    }
}
