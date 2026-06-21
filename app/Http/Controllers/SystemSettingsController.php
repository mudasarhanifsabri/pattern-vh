<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    private array $keys = [
        'APP_URL', 'QUEUE_CONNECTION', 'FILESYSTEM_DISK',
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_DEFAULT_REGION', 'AWS_BUCKET', 'AWS_ENDPOINT', 'AWS_USE_PATH_STYLE_ENDPOINT', 'AWS_THROW',
    ];

    public function index()
    {
        return view('settings.index', [
            'values' => $this->envValues(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate(collect($this->keys)->mapWithKeys(fn (string $key): array => [
            $key => ['nullable', 'string', 'max:1000'],
        ])->all());

        $this->writeEnv($validated);

        return back()->with('status', 'Settings saved. Run php artisan optimize:clear if cached config is enabled.');
    }

    public function test(Request $request)
    {
        $type = $request->validate(['type' => ['required', 'in:database,s3,mail,queue']])['type'];

        try {
            match ($type) {
                'database' => DB::select('select 1'),
                's3' => Storage::disk('s3')->exists('__pattern_connection_test__'),
                'mail' => Mail::raw('Pattern RMS mail test at '.now()->toDateTimeString(), fn ($message) => $message->to(config('mail.from.address'))->subject('Pattern RMS mail test')),
                'queue' => DB::table('jobs')->limit(1)->count(),
            };

            return back()->with('status', ucfirst($type).' connection test passed.');
        } catch (\Throwable $exception) {
            return back()->withErrors(['settings' => ucfirst($type).' test failed: '.$exception->getMessage()]);
        }
    }

    private function envValues(): array
    {
        $env = $this->parseEnv();

        return collect($this->keys)->mapWithKeys(fn (string $key): array => [$key => $env[$key] ?? env($key, '')])->all();
    }

    private function statuses(): array
    {
        return [
            'Database' => $this->check(fn () => DB::select('select 1')),
            'Queue' => config('queue.default') === 'sync'
                ? ['ok' => false, 'message' => 'Sync queue is slow for emails. Use database and run php artisan queue:work.']
                : ['ok' => true, 'message' => config('queue.default').' queue configured. Run php artisan queue:work.'],
            'Storage' => $this->check(fn () => Storage::disk(config('filesystems.default'))->exists('__pattern_connection_test__')),
            'Mail' => ['ok' => (bool) config('mail.mailers.smtp.host'), 'message' => config('mail.mailers.smtp.host') ?: 'SMTP host missing'],
        ];
    }

    private function check(callable $callback): array
    {
        try {
            $callback();

            return ['ok' => true, 'message' => 'Connected'];
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }
    }

    private function parseEnv(): array
    {
        $path = base_path('.env');
        if (! file_exists($path)) {
            return [];
        }

        return collect(file($path, FILE_IGNORE_NEW_LINES))
            ->filter(fn ($line): bool => str_contains($line, '=') && ! str_starts_with(trim($line), '#'))
            ->mapWithKeys(function (string $line): array {
                [$key, $value] = explode('=', $line, 2);

                return [trim($key) => trim($value, "\"'")];
            })
            ->all();
    }

    private function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $content = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            if (! in_array($key, $this->keys, true)) {
                continue;
            }

            $encoded = $this->encodeEnv((string) $value);
            if (preg_match("/^{$key}=.*$/m", $content)) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}={$encoded}", $content);
            } else {
                $content .= PHP_EOL."{$key}={$encoded}";
            }
        }

        file_put_contents($path, $content);
    }

    private function encodeEnv(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return preg_match('/\s|#|"|\'/', $value) ? '"'.str_replace('"', '\"', $value).'"' : $value;
    }
}
