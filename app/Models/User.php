<?php

namespace App\Models;

use App\Support\StorageQuota;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
        'avatar_path',
        'storage_quota_bytes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'storage_quota_bytes' => 'integer',
            'last_login_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        return Str::upper(Str::substr($this->username, 0, 2));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function avatarDisk(): string
    {
        return (string) config('filesystems.avatars', 'public');
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk($this->avatarDisk())->url($this->avatar_path);
    }

    public function effectiveStorageQuotaBytes(): int
    {
        return StorageQuota::effectiveQuotaBytes($this);
    }

    public function storageLimitBytes(): int
    {
        return StorageQuota::limitBytes($this);
    }

    public function usedStorageBytes(): int
    {
        return StorageQuota::usedBytes($this);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function sharedByMe(): HasMany
    {
        return $this->hasMany(Share::class, 'shared_by');
    }

    public function sharedWithMe(): HasMany
    {
        return $this->hasMany(Share::class, 'shared_with');
    }

    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(RecoveryCode::class);
    }
}
