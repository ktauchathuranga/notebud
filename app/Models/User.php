<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
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
}
