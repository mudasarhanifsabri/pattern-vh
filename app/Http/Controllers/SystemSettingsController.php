<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SystemSettingsController extends Controller
{
    private const MOBILE_APP_APK_PATH = 'mobile-app/pattern-mobile.apk';
    private const MOBILE_APP_META_PATH = 'mobile-app/latest.json';

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
            'cron' => $this->cronStatus(),
            'mobileApp' => $this->mobileAppRelease(),
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

    public function uploadMobileApp(Request $request)
    {
        $validated = $request->validate([
            'apk' => ['required', 'file', 'max:204800'],
            'version' => ['nullable', 'string', 'max:50'],
            'release_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('apk');
        if (strtolower($file->getClientOriginalExtension()) !== 'apk') {
            throw ValidationException::withMessages(['apk' => 'Upload an Android APK file.']);
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory('mobile-app');
        $disk->put(self::MOBILE_APP_APK_PATH, file_get_contents($file->getRealPath()));
        $disk->put(self::MOBILE_APP_META_PATH, json_encode([
            'version' => $validated['version'] ?: now()->format('Y.m.d.Hi'),
            'release_notes' => $validated['release_notes'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'size' => $disk->size(self::MOBILE_APP_APK_PATH),
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => $request->user()?->email,
        ], JSON_PRETTY_PRINT));

        return back()->with('status', 'Android APK uploaded. Users can download the latest mobile app now.');
    }

    public function deleteMobileApp()
    {
        Storage::disk('local')->delete([self::MOBILE_APP_APK_PATH, self::MOBILE_APP_META_PATH]);

        return back()->with('status', 'Android APK removed.');
    }

    public function downloadMobileApp()
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists(self::MOBILE_APP_APK_PATH), 404);

        $release = $this->mobileAppRelease();
        $version = str($release['version'] ?: 'latest')->slug('-');
        $filename = 'pattern-mobile-'.$version.'.apk';

        return response()->download($disk->path(self::MOBILE_APP_APK_PATH), $filename, [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
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

    private function cronStatus(): array
    {
        return [
            'schedule' => $this->logHeartbeat(storage_path('logs/cron.log'), 'Laravel scheduler'),
            'queue' => $this->logHeartbeat(storage_path('logs/queue-cron.log'), 'Queue worker'),
            'jobs' => [
                'pending' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : null,
                'failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null,
            ],
        ];
    }

    private function mobileAppRelease(): array
    {
        $disk = Storage::disk('local');
        $metadata = [];

        if ($disk->exists(self::MOBILE_APP_META_PATH)) {
            $metadata = json_decode($disk->get(self::MOBILE_APP_META_PATH), true) ?: [];
        }

        $exists = $disk->exists(self::MOBILE_APP_APK_PATH);
        $size = $exists ? $disk->size(self::MOBILE_APP_APK_PATH) : null;
        $uploadedAt = $metadata['uploaded_at'] ?? null;

        return [
            'exists' => $exists,
            'version' => $metadata['version'] ?? null,
            'release_notes' => $metadata['release_notes'] ?? null,
            'original_name' => $metadata['original_name'] ?? null,
            'size' => $size,
            'size_human' => $size ? $this->formatBytes($size) : null,
            'uploaded_at' => $uploadedAt ? Carbon::parse($uploadedAt) : null,
            'uploaded_by' => $metadata['uploaded_by'] ?? null,
            'download_url' => route('mobile-app.android.download'),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    private function logHeartbeat(string $path, string $label): array
    {
        if (! file_exists($path)) {
            return [
                'ok' => false,
                'label' => $label,
                'path' => $path,
                'last_run' => null,
                'last_run_human' => 'No heartbeat log found',
                'last_line' => null,
                'message' => 'Add the cron command with log output to start tracking.',
            ];
        }

        $lastRun = Carbon::createFromTimestamp(filemtime($path));
        $minutesOld = $lastRun->diffInMinutes(now());

        return [
            'ok' => $minutesOld <= 5,
            'label' => $label,
            'path' => $path,
            'last_run' => $lastRun,
            'last_run_human' => $lastRun->diffForHumans(),
            'last_line' => $this->lastNonEmptyLine($path),
            'message' => $minutesOld <= 5 ? 'Running recently' : 'Not updated in the last 5 minutes',
        ];
    }

    private function lastNonEmptyLine(string $path): ?string
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            return null;
        }

        return str($lines[array_key_last($lines)])->limit(180)->toString();
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
