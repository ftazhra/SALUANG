<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $table = 'quiz_questions';

    protected $fillable = [
        'subchapter_id',
        'nomor_soal',
        'pertanyaan',
        'pilihan_a',
        'pilihan_b',
        'pilihan_c',
        'pilihan_d',
        'kunci_jawaban',
        'pembahasan',
        'is_published',
    ];

    protected $casts = [
        'nomor_soal' => 'integer',
        'is_published' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI KE SUBBAB
    |--------------------------------------------------------------------------
    | Setiap soal kuis hanya dimiliki oleh satu Subbab.
    */

    public function subchapter(): BelongsTo
    {
        return $this->belongsTo(Subchapter::class);
    }
}