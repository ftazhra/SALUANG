<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SchoolAccessService
{
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
        $first = trim((string) $first);
        $second = trim((string) $second);

        return $first !== ''
            && $second !== ''
            && mb_strtolower($first) === mb_strtolower($second);
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

        return $users->whereNotNull('sekolah')
            ->whereRaw('LOWER(TRIM(sekolah)) = ?', [
                mb_strtolower($school)
            ]);
    }
}
