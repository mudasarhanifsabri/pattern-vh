<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class ReferenceNumber
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function next(
        string $modelClass,
        string $column,
        string $prefix,
        string $dateFormat = 'Ymd',
        int $width = 4,
        bool $withTrashed = false
    ): string {
        $next = max(
            self::countExisting($modelClass, $column, $prefix, $withTrashed) + 1,
            self::highestSequence($modelClass, $column, $prefix, $withTrashed) + 1,
        );

        do {
            $candidate = $prefix.'-'.now()->format($dateFormat).'-'.str_pad((string) $next, $width, '0', STR_PAD_LEFT);
            $next++;
        } while (self::query($modelClass, $withTrashed)->where($column, $candidate)->exists());

        return $candidate;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function countExisting(string $modelClass, string $column, string $prefix, bool $withTrashed): int
    {
        return self::query($modelClass, $withTrashed)
            ->where($column, 'like', $prefix.'-%')
            ->count();
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function highestSequence(string $modelClass, string $column, string $prefix, bool $withTrashed): int
    {
        $pattern = '/^'.preg_quote($prefix, '/').'-\d{6,8}-(\d+)$/';

        return self::query($modelClass, $withTrashed)
            ->where($column, 'like', $prefix.'-%')
            ->pluck($column)
            ->reduce(function (int $highest, ?string $reference) use ($pattern): int {
                if (! $reference || ! preg_match($pattern, $reference, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private static function query(string $modelClass, bool $withTrashed)
    {
        return $withTrashed ? $modelClass::withTrashed() : $modelClass::query();
    }
}
