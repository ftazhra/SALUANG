<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationQuestion extends Model
{
    /*
    |--------------------------------------------------------------------------
    | KOLOM YANG BOLEH DISIMPAN
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'nomor_soal',
        'tipe_soal',
        'pertanyaan',
        'konten_soal',
        'kunci_jawaban',
        'pembahasan',
        'is_published',
    ];

    /*
    |--------------------------------------------------------------------------
    | KONVERSI TIPE DATA
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'nomor_soal' => 'integer',
        'konten_soal' => 'array',
        'kunci_jawaban' => 'array',
        'is_published' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI KE GURU
    |--------------------------------------------------------------------------
    */

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
