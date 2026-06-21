<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class SoftwareUpdateController extends Controller
{
    public function index(): View
    {
        return view('software-updates.index', [
            'enabled' => config('erp.web_updater_enabled'),
            'latestLog' => $this->latestLog(),
            'phpBinary' => PHP_BINARY,
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
            $steps['Download latest code'] = ['git', 'pull', '--ff-only'];
        }

        if (! empty($validated['composer_install'])) {
            $steps['Install PHP dependencies'] = [config('erp.composer_binary'), 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'];
        }

        if (! empty($validated['npm_build'])) {
            $steps['Build frontend assets'] = [config('erp.npm_binary'), 'run', 'build'];
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
        File::append($logPath, "===== {$label} =====\n$ ".implode(' ', array_map('escapeshellarg', $command))."\n");

        try {
            $process = new Process($command, base_path());
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

        return [
            'name' => $file->getFilename(),
            'updated_at' => date('M d, Y H:i:s', $file->getMTime()),
            'content' => File::get($file->getPathname()),
        ];
    }
}
