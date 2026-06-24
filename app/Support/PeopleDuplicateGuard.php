<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PeopleDuplicateGuard
{
    public function findDuplicate(string $modelClass, array $data, ?int $ignoreId = null): ?Model
    {
        $email = $this->normalizeEmail($data['email'] ?? null);
        $identityNo = $this->normalizeIdentityNo($data['identity_no'] ?? null);
        $mobileNo = $this->normalizePhone($data['mobile_no'] ?? null);
        $fullName = $this->normalizeName($data['full_name'] ?? null);

        if (! $email && ! $identityNo && (! $mobileNo || ! $fullName)) {
            return null;
        }

        return $modelClass::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where(function (Builder $query) use ($email, $identityNo, $mobileNo, $fullName): void {
                if ($email) {
                    $query->orWhereRaw('LOWER(email) = ?', [$email]);
                }

                if ($identityNo) {
                    $query->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(identity_no, '-', ''), ' ', ''), '.', '')) = ?", [$identityNo]);
                }

                if ($mobileNo && $fullName) {
                    $query->orWhere(function (Builder $query) use ($mobileNo, $fullName): void {
                        $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(mobile_no, '+', ''), '-', ''), ' ', ''), '.', '') = ?", [$mobileNo])
                            ->whereRaw('LOWER(TRIM(full_name)) = ?', [$fullName]);
                    });
                }
            })
            ->oldest('id')
            ->first();
    }

    public function lockKey(string $modelClass, array $data): string
    {
        $parts = array_filter([
            class_basename($modelClass),
            $this->normalizeEmail($data['email'] ?? null),
            $this->normalizeIdentityNo($data['identity_no'] ?? null),
            $this->normalizePhone($data['mobile_no'] ?? null),
            $this->normalizeName($data['full_name'] ?? null),
        ]);

        return 'people-create:'.sha1(implode('|', $parts));
    }

    public function withCreateLock(string $modelClass, array $data, callable $callback): mixed
    {
        return Cache::lock($this->lockKey($modelClass, $data), 20)->block(8, $callback);
    }

    private function normalizeEmail(?string $value): ?string
    {
        return filled($value) ? Str::of($value)->lower()->trim()->toString() : null;
    }

    private function normalizeIdentityNo(?string $value): ?string
    {
        return filled($value)
            ? Str::of($value)->lower()->replace(['-', ' ', '.'], '')->trim()->toString()
            : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        return filled($value)
            ? preg_replace('/\D+/', '', $value)
            : null;
    }

    private function normalizeName(?string $value): ?string
    {
        return filled($value) ? Str::of($value)->squish()->lower()->toString() : null;
    }
}
