<?php

use App\Models\File;
use App\Models\User;
use App\Support\StorageQuota;

test('default quota bytes returns config value', function () {
    config()->set('filesystems.storage_quota.default_bytes', 50 * 1024 * 1024);

    expect(StorageQuota::defaultQuotaBytes())->toBe(50 * 1024 * 1024);
});

test('grace bytes returns config value', function () {
    config()->set('filesystems.storage_quota.grace_bytes', 2 * 1024 * 1024);

    expect(StorageQuota::graceBytes())->toBe(2 * 1024 * 1024);
});

test('effective quota uses user override when set', function () {
    $user = User::factory()->create(['storage_quota_bytes' => 100 * 1024 * 1024]);

    expect(StorageQuota::effectiveQuotaBytes($user))->toBe(100 * 1024 * 1024);
});

test('effective quota uses default when no override', function () {
    config()->set('filesystems.storage_quota.default_bytes', 20 * 1024 * 1024);

    $user = User::factory()->create(['storage_quota_bytes' => null]);

    expect(StorageQuota::effectiveQuotaBytes($user))->toBe(20 * 1024 * 1024);
});

test('limit bytes includes grace', function () {
    config()->set('filesystems.storage_quota.default_bytes', 20 * 1024 * 1024);
    config()->set('filesystems.storage_quota.grace_bytes', 1024 * 1024);

    $user = User::factory()->create(['storage_quota_bytes' => null]);

    expect(StorageQuota::limitBytes($user))->toBe(21 * 1024 * 1024);
});

test('can accept returns true when under limit', function () {
    config()->set('filesystems.storage_quota.default_bytes', 20 * 1024 * 1024);
    config()->set('filesystems.storage_quota.grace_bytes', 1024 * 1024);

    $user = User::factory()->create();

    expect(StorageQuota::canAccept($user, 1024))->toBeTrue();
});

test('can accept returns false when over limit', function () {
    config()->set('filesystems.storage_quota.default_bytes', 1024);
    config()->set('filesystems.storage_quota.grace_bytes', 0);

    $user = User::factory()->create();

    File::factory()->create([
        'user_id' => $user->id,
        'size' => 1024,
    ]);

    expect(StorageQuota::canAccept($user, 1))->toBeFalse();
});

test('used bytes sums only user files', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    File::factory()->create([
        'user_id' => $user->id,
        'size' => 500,
    ]);

    File::factory()->create([
        'user_id' => $other->id,
        'size' => 1000,
    ]);

    expect(StorageQuota::usedBytes($user))->toBe(500);
});

test('remaining bytes calculates correctly', function () {
    config()->set('filesystems.storage_quota.default_bytes', 2048);
    config()->set('filesystems.storage_quota.grace_bytes', 0);

    $user = User::factory()->create();

    File::factory()->create([
        'user_id' => $user->id,
        'size' => 500,
    ]);

    expect(StorageQuota::remainingBytes($user))->toBe(1548);
});

test('format bytes outputs correct units', function () {
    expect(StorageQuota::formatBytes(0))->toBe('0 B');
    expect(StorageQuota::formatBytes(512))->toBe('512 B');
    expect(StorageQuota::formatBytes(1024))->toBe('1.00 KB');
    expect(StorageQuota::formatBytes(1024 * 1024))->toBe('1.00 MB');
    expect(StorageQuota::formatBytes(1024 * 1024 * 1024))->toBe('1.00 GB');
});
