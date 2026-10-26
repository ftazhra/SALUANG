<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentQuizAnswer extends Model
{
    protected $table = 'student_quiz_answers';

    protected $fillable = [
        'user_id',
        'subchapter_id',
        'quiz_question_id',
        'answer',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
