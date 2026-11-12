<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentQuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'subchapter_id',
        'status',
        'answers',
        'review',
        'score',
        'correct_answers',
        'total_questions',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'review' => 'array',
        'score' => 'decimal:2',
        'correct_answers' => 'integer',
        'total_questions' => 'integer',
        'completed_at' => 'datetime',
    ];
}
