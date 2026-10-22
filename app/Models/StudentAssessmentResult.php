<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentResult extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 01. KONFIGURASI TABEL
    |--------------------------------------------------------------------------
    */

    protected $table = 'student_assessment_results';

    /*
    |--------------------------------------------------------------------------
    | 02. KOLOM YANG BOLEH DIISI
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'assessment_type',
        'chapter_id',
        'total_questions',
        'correct_answers',
        'score',
        'completed_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | 03. KONVERSI TIPE DATA
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'user_id' => 'integer',
        'chapter_id' => 'integer',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'score' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | 04. RELASI KE SISWA
    |--------------------------------------------------------------------------
    */

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 05. RELASI KE BAB
    |--------------------------------------------------------------------------
    */

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(
            Chapter::class,
            'chapter_id'
        );
    }
}
