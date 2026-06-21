<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ErpStoragePath
{
    public static function documentPath(string $module, string $ownerName, string $folder, UploadedFile $file, ?string $filename = null): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $module = Str::of($module)->trim()->headline()->replace(' ', '-')->lower();
        $name = Str::of($ownerName)->trim()->slug('-')->whenEmpty(fn () => 'unknown');
        $folder = Str::of($folder)->trim('/')->slug('-');
        $filename = $filename
            ? Str::of(pathinfo($filename, PATHINFO_FILENAME))->slug('-').'.'.$file->getClientOriginalExtension()
            : Str::uuid().'.'.$file->getClientOriginalExtension();

        return "{$year}/{$month}/{$module}/{$name}/{$folder}/{$filename}";
    }
}
