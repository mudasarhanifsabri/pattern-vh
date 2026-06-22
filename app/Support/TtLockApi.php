<?php

namespace App\Support;

use App\Models\TtLock;
use App\Models\TtLockEvent;
use App\Models\TtLockSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TtLockApi
{
    public function test(TtLockSetting $setting): array
    {
        $token = $this->token($setting);

        return [
            'ok' => true,
            'message' => 'TTLock connection successful. Token received.',
            'data' => $token,
        ];
    }

    public function syncLocks(TtLockSetting $setting): array
    {
        $token = $this->validAccessToken($setting);
        $synced = 0;
        $pageNo = 1;
        $pageSize = 100;

        do {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->apiUrl('/lock/list'), [
                    'clientId' => $setting->client_id,
                    'accessToken' => $token,
                    'pageNo' => $pageNo,
                    'pageSize' => $pageSize,
                    'date' => $this->milliseconds(),
                ]);

            $payload = $this->payloadOrFail($response->json(), 'Could not fetch TTLock lock list.');
            $locks = collect($payload['list'] ?? []);

            foreach ($locks as $lock) {
                if (empty($lock['lockId'])) {
                    continue;
                }

                TtLock::updateOrCreate(
                    ['lock_id' => (string) $lock['lockId']],
                    [
                        'tt_lock_setting_id' => $setting->id,
                        'lock_name' => $lock['lockName'] ?? $lock['lockAlias'] ?? 'TTLock '.$lock['lockId'],
                        'lock_alias' => $lock['lockAlias'] ?? null,
                        'gateway_id' => isset($lock['gatewayId']) ? (string) $lock['gatewayId'] : null,
                        'mac_address' => $lock['lockMac'] ?? $lock['mac'] ?? null,
                        'battery_level' => $this->battery($lock),
                        'status' => 'active',
                        'last_synced_at' => now(),
                        'notes' => 'Synced from TTLock API.',
                    ],
                );

                $synced++;
            }

            $pages = (int) ($payload['pages'] ?? $pageNo);
            $pageNo++;
        } while ($pageNo <= $pages);

        $setting->forceFill([
            'last_tested_at' => now(),
            'last_error' => null,
        ])->save();

        return [
            'synced' => $synced,
        ];
    }

    public function syncHistory(TtLockSetting $setting, int $days = 30): array
    {
        $token = $this->validAccessToken($setting);
        $synced = 0;
        $locksChecked = 0;
        $startDate = now()->subDays($days);
        $endDate = now();

        $locks = TtLock::query()
            ->with('unit')
            ->where('tt_lock_setting_id', $setting->id)
            ->whereNotNull('lock_id')
            ->get();

        foreach ($locks as $lock) {
            $locksChecked++;
            $pageNo = 1;
            $pageSize = 100;

            do {
                $response = Http::asForm()
                    ->timeout(30)
                    ->post($this->apiUrl('/lockRecord/list'), [
                        'clientId' => $setting->client_id,
                        'accessToken' => $token,
                        'lockId' => $lock->lock_id,
                        'startDate' => $this->dateMilliseconds($startDate),
                        'endDate' => $this->dateMilliseconds($endDate),
                        'pageNo' => $pageNo,
                        'pageSize' => $pageSize,
                        'date' => $this->milliseconds(),
                    ]);

                $payload = $this->payloadOrFail($response->json(), 'Could not fetch TTLock history.');
                $records = collect($payload['list'] ?? []);

                foreach ($records as $record) {
                    $this->persistRecord($lock, (array) $record);
                    $synced++;
                }

                $pages = (int) ($payload['pages'] ?? $pageNo);
                $pageNo++;
            } while ($pageNo <= $pages);
        }

        $setting->forceFill([
            'last_tested_at' => now(),
            'last_error' => null,
        ])->save();

        return [
            'synced' => $synced,
            'locks' => $locksChecked,
            'days' => $days,
        ];
    }

    public function token(TtLockSetting $setting): array
    {
        $payload = $this->requestToken($setting, $setting->password);

        if (empty($payload['access_token']) && ! preg_match('/^[a-f0-9]{32}$/i', $setting->password)) {
            $payload = $this->requestToken($setting, md5($setting->password));
        }

        if (empty($payload['access_token'])) {
            throw new \RuntimeException($payload['errMsg'] ?? $payload['errmsg'] ?? $payload['message'] ?? 'TTLock did not return an access token.');
        }

        $expiresIn = (int) ($payload['expires_in'] ?? $payload['expiresIn'] ?? 0);

        $setting->forceFill([
            'access_token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'] ?? $setting->refresh_token,
            'token_expires_at' => $expiresIn > 0 ? now()->addSeconds(max($expiresIn - 300, 60)) : now()->addHours(1),
            'last_tested_at' => now(),
            'last_error' => null,
        ])->save();

        return $payload;
    }

    private function validAccessToken(TtLockSetting $setting): string
    {
        if ($setting->access_token && $setting->token_expires_at?->isFuture()) {
            return $setting->access_token;
        }

        return $this->token($setting)['access_token'];
    }

    private function requestToken(TtLockSetting $setting, string $password): array
    {
        $response = Http::asForm()
            ->timeout(30)
            ->post($this->oauthUrl('/oauth2/token'), [
                'clientId' => $setting->client_id,
                'clientSecret' => $setting->client_secret,
                'username' => $setting->username,
                'password' => $password,
                'date' => $this->milliseconds(),
            ]);

        return $response->json() ?: [
            'message' => trim($response->body()) ?: 'Empty TTLock response.',
        ];
    }

    private function payloadOrFail(?array $payload, string $fallback): array
    {
        if (! is_array($payload)) {
            throw new \RuntimeException($fallback);
        }

        if (isset($payload['errcode']) && (int) $payload['errcode'] !== 0) {
            throw new \RuntimeException($payload['errmsg'] ?? $payload['errMsg'] ?? $fallback);
        }

        return $payload;
    }

    private function battery(array $lock): ?int
    {
        $value = $lock['electricQuantity'] ?? $lock['battery'] ?? $lock['batteryLevel'] ?? null;

        return $value === null ? null : max(0, min(100, (int) $value));
    }

    private function persistRecord(TtLock $lock, array $record): void
    {
        $eventAt = $this->recordDate($record);
        $eventType = $this->recordEventType($record);
        $keyboardPwd = $record['keyboardPwd'] ?? $record['keyboardPwdName'] ?? null;

        TtLockEvent::updateOrCreate(
            [
                'lock_id' => (string) $lock->lock_id,
                'event_type' => $eventType,
                'event_at' => $eventAt?->format('Y-m-d H:i:s'),
                'keyboard_pwd' => $keyboardPwd ? (string) $keyboardPwd : null,
            ],
            [
                'tt_lock_id' => $lock->id,
                'unit_id' => $lock->unit?->id,
                'lock_name' => $record['lockName'] ?? $lock->lock_name,
                'operator_name' => $record['username'] ?? $record['nickName'] ?? $record['senderUsername'] ?? null,
                'keyboard_pwd' => $keyboardPwd ? (string) $keyboardPwd : null,
                'record_id' => isset($record['recordId']) ? (string) $record['recordId'] : null,
                'source' => 'api_sync',
                'payload' => $record,
            ],
        );
    }

    private function recordEventType(array $record): string
    {
        $type = (int) ($record['recordType'] ?? $record['recordTypeFromLock'] ?? 0);

        return [
            1 => 'app_unlock',
            2 => 'manual_lock',
            3 => 'gateway_unlock',
            4 => 'passcode_unlock',
            5 => 'passcode_lock',
            6 => 'passcode_deleted',
            7 => 'ic_card_unlock',
            8 => 'fingerprint_unlock',
            9 => 'wristband_unlock',
            10 => 'mechanical_key_unlock',
            11 => 'app_lock',
            12 => 'gateway_lock',
            29 => 'unexpected_unlock',
            30 => 'door_magnet_close',
            31 => 'door_magnet_open',
            32 => 'open_from_inside',
            44 => 'tamper_alert',
            45 => 'auto_lock',
            48 => 'invalid_passcode_attempts',
        ][$type] ?? 'record_type_'.$type;
    }

    private function recordDate(array $record): ?Carbon
    {
        $value = $record['lockDate'] ?? $record['serverDate'] ?? null;

        if (! $value) {
            return null;
        }

        return Carbon::createFromTimestampMs((int) $value);
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) config('ttlock.api_url'), '/').$path;
    }

    private function oauthUrl(string $path): string
    {
        return rtrim((string) config('ttlock.oauth_url'), '/').$path;
    }

    private function milliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    private function dateMilliseconds(Carbon $date): int
    {
        return $date->getTimestampMs();
    }
}
