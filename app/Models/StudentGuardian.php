<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentGuardian extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 01. NAMA TABEL
    |--------------------------------------------------------------------------
    */
    protected $table = 'student_guardians';
    /*
    |--------------------------------------------------------------------------
    | 02. KOLOM YANG BOLEH DIISI
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'student_id',
        'guardian_id',
        'relationship',
        'status',
        'verified_at',
    ];
    /*
    |--------------------------------------------------------------------------
    | 03. KONVERSI TIPE DATA
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'student_id' => 'integer',
        'guardian_id' => 'integer',
        'verified_at' => 'datetime',
    ];
    /*
    |--------------------------------------------------------------------------
    | 04. RELASI KE AKUN SISWA
    |--------------------------------------------------------------------------
    */
    public function student(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'student_id'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | 05. RELASI KE AKUN ORANG TUA
    |--------------------------------------------------------------------------
    */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'guardian_id'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | 06. PEMERIKSAAN STATUS HUBUNGAN
    |--------------------------------------------------------------------------
    */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
