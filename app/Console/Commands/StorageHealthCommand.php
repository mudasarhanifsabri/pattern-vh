<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('storage:health {--disk= : Disk to test. Defaults to the configured filesystem disk.}')]
#[Description('Test the configured storage disk with upload, exists, and delete checks.')]
class StorageHealthCommand extends Command
{
    public function handle(): int
    {
        $diskName = $this->option('disk') ?: config('filesystems.default');
        $diskConfig = config("filesystems.disks.{$diskName}", []);

        $this->components->info("Testing storage disk [{$diskName}]");

        if (($diskConfig['driver'] ?? null) === 's3') {
            $this->line('Bucket: '.($diskConfig['bucket'] ?: 'not configured'));
            $this->line('Region: '.($diskConfig['region'] ?: 'not configured'));
        }

        $path = 'health-check/'.now()->format('Y/m/d-His').'-storage-test.txt';

        try {
            $disk = Storage::disk($diskName);
            $stored = $disk->put($path, 'Pattern RMS storage health check');

            if (! $stored) {
                $this->components->error("Upload returned false for [{$path}].");

                return self::FAILURE;
            }

            if (! $disk->exists($path)) {
                $this->components->error("Upload finished but file was not found at [{$path}].");

                return self::FAILURE;
            }

            $disk->delete($path);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            $previous = $exception->getPrevious();
            while ($previous) {
                $this->line('<comment>Caused by:</comment> '.$previous->getMessage());
                $previous = $previous->getPrevious();
            }

            return self::FAILURE;
        }

        $this->components->info('Storage upload/read/delete works.');

        return self::SUCCESS;
    }
}
