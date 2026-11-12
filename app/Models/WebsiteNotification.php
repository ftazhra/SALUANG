<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteNotification extends Model
{
    protected $fillable = [
        'recipient_id',
        'recipient_role',
        'event_key',
        'event_type',
        'source_type',
        'source_id',
        'student_id',
        'title',
        'message',
        'target_url',
        'read_at',
    ];

    protected $casts = [
        'recipient_id' => 'integer',
        'source_id' => 'integer',
        'student_id' => 'integer',
        'read_at' => 'datetime',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
