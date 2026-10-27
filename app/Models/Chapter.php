<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'gambar_pengenalan_path',
        'gambar_pengenalan_judul',
        'gambar_pengenalan_keterangan',
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
    protected $hidden = [
        'gambar_pengenalan_path',
    ];
    protected $appends = [
        'gambar_pengenalan_url',
    ];
    public function getGambarPengenalanUrlAttribute(): ?string
    {
        $path = $this->getAttribute('gambar_pengenalan_path');

        return is_string($path) && $path !== ''
            ? Storage::disk('public')->url($path)
            : null;
    }
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
