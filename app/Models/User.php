<?php
namespace App\Models;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    /*
    |--------------------------------------------------------------------------
    | 01. ATRIBUT YANG BOLEH DIISI
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'role',
        'nisn',
        'nip_nipk',
        'kelas',
        'sekolah',
        'mata_pelajaran',
        'nomor_telepon',
        'hubungan_anak',
        'foto_profil',
        'profile_photo_path',
        'is_approved',
        'identity_verified_at',
        'email_verified_at',
        'remember_token',
    ];
    /*
    |--------------------------------------------------------------------------
    | 02. ATRIBUT YANG DISEMBUNYIKAN
    |--------------------------------------------------------------------------
    */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /*
    |--------------------------------------------------------------------------
    | 03. KONVERSI TIPE DATA
    |--------------------------------------------------------------------------
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | 04. RELASI FORUM DISKUSI SALUANG
    |--------------------------------------------------------------------------
    */
    // Diskusi yang dibuat pengguna.
    public function discussions(): HasMany
    {
        return $this->hasMany(
            Discussion::class,
            'user_id'
        );
    }
    // Tanggapan diskusi yang dikirim pengguna.
    public function discussionReplies(): HasMany
    {
        return $this->hasMany(
            DiscussionReply::class,
            'user_id'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | 05. RELASI HASIL PENILAIAN SISWA
    |--------------------------------------------------------------------------
    */
    // Riwayat hasil Kuis dan Evaluasi siswa.
    public function assessmentResults(): HasMany
    {
        return $this->hasMany(
            StudentAssessmentResult::class,
            'user_id'
        );
    }
}