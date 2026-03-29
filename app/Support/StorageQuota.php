<?php

namespace App\Support;

use App\Models\File;
use App\Models\User;

class StorageQuota
{
    public static function defaultQuotaBytes(): int
    {
        return (int) config('filesystems.storage_quota.default_bytes', 20 * 1024 * 1024);
    }

    public static function graceBytes(): int
    {
        return (int) config('filesystems.storage_quota.grace_bytes', 1024 * 1024);
    }

    public static function effectiveQuotaBytes(User $user): int
    {
        return (int) ($user->storage_quota_bytes ?? self::defaultQuotaBytes());
    }

    public static function limitBytes(User $user): int
    {
        return self::effectiveQuotaBytes($user) + self::graceBytes();
    }

    public static function usedBytes(User $user): int
    {
        return (int) File::query()->where('user_id', $user->id)->sum('size');
    }

    public static function canAccept(User $user, int $incomingBytes, ?int $usedBytes = null): bool
    {
        $used = $usedBytes ?? self::usedBytes($user);

        return ($used + $incomingBytes) <= self::limitBytes($user);
    }

    public static function remainingBytes(User $user, ?int $usedBytes = null): int
    {
        $used = $usedBytes ?? self::usedBytes($user);

        return max(self::limitBytes($user) - $used, 0);
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max($bytes, 0);

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        return number_format($value, $i === 0 ? 0 : 2).' '.$units[$i];
    }
}
