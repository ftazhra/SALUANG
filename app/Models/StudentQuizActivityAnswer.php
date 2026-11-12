<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentQuizActivityAnswer extends Model
{
    protected $table = 'student_quiz_activity_answers';

    protected $fillable = [
        'user_id',
        'subchapter_id',
        'quiz_question_id',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];
}
