<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class SoftwareUpdateController extends Controller
{
    public function index(): View
    {
        return view('software-updates.index', [
            'enabled' => config('erp.web_updater_enabled'),
            'latestLog' => rescue(fn () => $this->latestLog(), null, report: true),
            'productionLogs' => rescue(fn () => $this->productionLogs(), [], report: true),
            'phpBinary' => PHP_BINARY,
            'composerBinary' => config('erp.composer_binary'),
            'gitBinary' => config('erp.git_binary'),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        abort_unless(config('erp.web_updater_enabled'), 403, 'Software updater is disabled.');
        abort_unless($request->user()?->hasRole('Super Admin'), 403, 'Only Super Admin can run software updates.');
        @set_time_limit(900);

        $validated = $request->validate([
            'git_pull' => ['nullable', 'boolean'],
            'composer_install' => ['nullable', 'boolean'],
            'npm_build' => ['nullable', 'boolean'],
            'migrate' => ['nullable', 'boolean'],
            'clear_cache' => ['nullable', 'boolean'],
            'build_cache' => ['nullable', 'boolean'],
        ]);

        $steps = $this->steps($validated);
        if ($steps === []) {
            return back()->withErrors(['software_updates' => 'Select at least one update step.']);
        }

        $logPath = storage_path('logs/software-updates/update-'.now()->format('Ymd-His').'.log');
        File::ensureDirectoryExists(dirname($logPath));
        File::put($logPath, "Pattern RMS software update\nStarted: ".now()->toDateTimeString()."\nUser: {$request->user()->email}\n\n");

        $failed = false;
        foreach ($steps as $label => $command) {
            $result = $this->runStep($label, $command, $logPath);
            if (! $result) {
                $failed = true;
                break;
            }
        }

        File::append($logPath, "\nFinished: ".now()->toDateTimeString()."\nStatus: ".($failed ? 'FAILED' : 'SUCCESS')."\n");

        return back()->with(
            $failed ? 'warning' : 'status',
            $failed
                ? 'Update stopped because one step failed. Open the log below and fix that item before running again.'
                : 'Software update completed successfully. No business records were changed.'
        );
    }

    private function steps(array $validated): array
    {
        $php = PHP_BINARY;
        $steps = [];

        if (! empty($validated['git_pull'])) {
            $steps['Download latest code'] = [config('erp.git_binary'), 'pull', '--ff-only'];
        }

        if (! empty($validated['composer_install'])) {
            $steps['Install PHP dependencies'] = [config('erp.composer_binary'), 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'];
        }

        if (! empty($validated['npm_build'])) {
            $steps['Build frontend assets'] = $this->frontendBuildCommand();
        }

        if (! empty($validated['clear_cache'])) {
            $steps['Clear cached files'] = [$php, 'artisan', 'optimize:clear'];
        }

        if (! empty($validated['migrate'])) {
            $steps['Run safe database migrations'] = [$php, 'artisan', 'migrate', '--force'];
        }

        if (! empty($validated['build_cache'])) {
            $steps['Rebuild Laravel cache'] = [$php, 'artisan', 'optimize'];
        }

        return $steps;
    }

    private function runStep(string $label, array $command, string $logPath): bool
    {
        if (($command[0] ?? null) === '__skip__') {
            File::append($logPath, "===== {$label} =====\nSKIPPED: {$command[1]}\n\nExit code: 0\n\n");

            return true;
        }

        File::append($logPath, "===== {$label} =====\n$ ".implode(' ', array_map('escapeshellarg', $command))."\n");

        try {
            $process = new Process($command, base_path());
            $process->setEnv($this->processEnvironment());
            $process->setTimeout(600);
            $process->run(function (string $type, string $buffer) use ($logPath): void {
                File::append($logPath, $buffer);
            });

            File::append($logPath, "\nExit code: {$process->getExitCode()}\n\n");

            return $process->isSuccessful();
        } catch (\Throwable $exception) {
            File::append($logPath, "\nERROR: {$exception->getMessage()}\n\n");

            return false;
        }
    }

    private function frontendBuildCommand(): array
    {
        $npm = trim((string) config('erp.npm_binary'));

        if ($npm === '' || strtolower($npm) === 'false' || strtolower($npm) === 'none') {
            return ['__skip__', 'NPM_BINARY is disabled. Using committed public/build assets.'];
        }

        return [$npm, 'run', 'build'];
    }

    public function downloadLog(string $type)
    {
        abort_unless(request()->user()?->hasRole('Super Admin') || request()->user()?->can('users.manage'), 403);

        $log = collect($this->productionLogs())->firstWhere('type', $type);
        abort_unless($log && is_file($log['path']), 404);

        return response()->download($log['path'], $log['filename']);
    }

    public function copyLog(string $type): RedirectResponse
    {
        abort_unless(request()->user()?->hasRole('Super Admin') || request()->user()?->can('users.manage'), 403);

        $log = collect($this->productionLogs())->firstWhere('type', $type);
        abort_unless($log && is_file($log['path']), 404);

        $sharePath = storage_path('app/support-logs/'.now()->format('Ymd-His').'-'.$log['filename']);
        File::ensureDirectoryExists(dirname($sharePath));
        File::put($sharePath, Str::limit(File::get($log['path']), 120000, "\n... log truncated ..."));

        return back()->with('status', 'Log copy created at '.$sharePath);
    }

    private function processEnvironment(): array
    {
        $home = env('HOME') ?: dirname(base_path()) ?: base_path();
        $composerHome = trim((string) config('erp.composer_home')) ?: storage_path('app/composer');
        File::ensureDirectoryExists($composerHome);

        return [
            'HOME' => $home,
            'COMPOSER_HOME' => $composerHome,
        ];
    }

    private function latestLog(): ?array
    {
        $directory = storage_path('logs/software-updates');
        if (! is_dir($directory)) {
            return null;
        }

        $files = collect(File::files($directory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        $file = $files->first();
        $content = rescue(
            fn () => $this->safeLogText(File::get($file->getPathname())),
            'The latest update log could not be read.',
            report: true
        );

        return [
            'name' => $file->getFilename(),
            'updated_at' => date('M d, Y H:i:s', $file->getMTime()),
            'content' => Str::limit($content, 120000, "\n... log truncated ..."),
        ];
    }

    private function productionLogs(): array
    {
        $latestUpdate = $this->latestLogPath();

        return collect([
            [
                'type' => 'laravel',
                'label' => 'Laravel application log',
                'path' => storage_path('logs/laravel.log'),
                'filename' => 'laravel.log',
            ],
            [
                'type' => 'software-update',
                'label' => 'Latest software update log',
                'path' => $latestUpdate,
                'filename' => $latestUpdate ? basename($latestUpdate) : 'software-update.log',
            ],
            [
                'type' => 'ttlock-callback',
                'label' => 'TTLock callback log',
                'path' => storage_path('logs/ttlock/callback.log'),
                'filename' => 'ttlock-callback.log',
            ],
        ])
            ->filter(fn (array $log): bool => $log['path'] && is_file($log['path']))
            ->map(function (array $log): array {
                $log['updated_at'] = rescue(fn () => date('M d, Y H:i:s', filemtime($log['path'])), 'Not available', report: true);
                $log['size'] = rescue(fn () => number_format(filesize($log['path']) / 1024, 1).' KB', 'Not available', report: true);

                return $log;
            })
            ->values()
            ->all();
    }

    private function latestLogPath(): ?string
    {
        $directory = storage_path('logs/software-updates');
        if (! is_dir($directory)) {
            return null;
        }

        $file = collect(File::files($directory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->first();

        return $file?->getPathname();
    }

    private function safeLogText(string $content): string
    {
        $content = str_replace("\0", '', $content);

        $isUtf8 = function_exists('mb_check_encoding')
            ? mb_check_encoding($content, 'UTF-8')
            : preg_match('//u', $content) === 1;

        if ($isUtf8) {
            return $content;
        }

        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $content);

        return is_string($converted) ? $converted : '[Log contains unsupported characters and could not be displayed.]';
    }
}
