<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentTeacherMessage extends Model
{
    protected $table = 'parent_teacher_messages';

    protected $fillable = [
        'parent_id',
        'student_id',
        'teacher_id',
        'sender_id',
        'client_message_id',
        'body',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'student_id' => 'integer',
        'teacher_id' => 'integer',
        'sender_id' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}