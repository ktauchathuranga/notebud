<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Share extends Model
{
    protected $fillable = [
        'shared_by',
        'shared_with',
        'shareable_type',
        'shareable_id',
        'status',
        'message',
    ];

    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }
}
