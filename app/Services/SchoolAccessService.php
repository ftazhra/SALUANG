<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SchoolAccessService
{
    private const SMPN21_SCHOOL_IDENTITY = 'smpn21_banjarmasin';

    // Memeriksa kesamaan sekolah dua pengguna.
    public function sameSchool(User $first, User $second): bool
    {
        return $this->sameSchoolNames(
            $first->sekolah,
            $second->sekolah
        );
    }

    // Membandingkan nama sekolah.
    public function sameSchoolNames(?string $first, ?string $second): bool
    {
        $first = $this->schoolIdentity($first);
        $second = $this->schoolIdentity($second);

        return $first !== ''
            && $second !== ''
            && $first === $second;
    }

    // Membatasi pengguna berdasarkan sekolah yang sama.
    public function constrainUsersToSchool(
        Builder $users,
        User $reference
    ): Builder {
        $school = trim((string) $reference->sekolah);

        if ($school === '') {
            return $users->whereRaw('1 = 0');
        }

        if ($this->schoolIdentity($school) === self::SMPN21_SCHOOL_IDENTITY) {
            return $users->whereNotNull('sekolah')
                ->whereRaw(
                    "LOWER(REPLACE(REPLACE(REPLACE(TRIM(sekolah), '.', ''), ' ', ''), '-', '')) IN (?, ?, ?)",
                    [
                        'smpnegeri21banjarmasin',
                        'smpn21banjarmasin',
                        'smpn21',
                    ]
                );
        }

        return $users->whereNotNull('sekolah')
            ->whereRaw('LOWER(TRIM(sekolah)) = ?', [
                mb_strtolower($school)
            ]);
    }

    private function schoolIdentity(?string $school): string
    {
        $normalized = mb_strtolower(trim((string) $school));
        $compact = preg_replace('/[^a-z0-9]/u', '', $normalized);

        if (in_array($compact, [
            'smpnegeri21banjarmasin',
            'smpn21banjarmasin',
            'smpn21',
        ], true)) {
            return self::SMPN21_SCHOOL_IDENTITY;
        }

        return preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
    }
}
