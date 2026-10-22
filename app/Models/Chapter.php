<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chapter extends Model
{
    /*
    |--------------------------------------------------------------------------
    | KOLOM YANG BOLEH DISIMPAN MELALUI ELOQUENT
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'nomor_bab',
        'judul',
        'deskripsi',
        'pengantar',
        'tujuan_pembelajaran',
        'pertanyaan_pemantik',
        'is_published',
    ];

    /*
    |--------------------------------------------------------------------------
    | KONVERSI TIPE DATA
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'nomor_bab' => 'integer',
        'tujuan_pembelajaran' => 'array',
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

    /*
    |--------------------------------------------------------------------------
    | RELASI KE SUBBAB
    |--------------------------------------------------------------------------
    */

    public function subchapters(): HasMany
    {
        return $this->hasMany(Subchapter::class)
            ->orderBy('nomor_subbab');
    }
}
