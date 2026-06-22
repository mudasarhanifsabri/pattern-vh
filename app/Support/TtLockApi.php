<?php

namespace App\Support;

use App\Models\TtLock;
use App\Models\TtLockSetting;
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
                ->post($this->url('/lock/list'), [
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
            ->post($this->url('/oauth2/token'), [
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

    private function url(string $path): string
    {
        return rtrim((string) config('ttlock.base_url'), '/').$path;
    }

    private function milliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
