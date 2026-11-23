<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ProfilePhotoService
{
    private const DISK = 'material_public';

    public function replace(User $user, UploadedFile $photo): string
    {
        $disk = Storage::disk(self::DISK);

        try {
            $path = $photo->storePublicly('profile-photos', self::DISK);
        } catch (FilesystemException $exception) {
            report($exception);

            throw new HttpException(
                500,
                'Foto profil gagal disimpan. Periksa ruang dan izin storage.',
                $exception
            );
        }

        if (!is_string($path) || $path === '') {
            throw new HttpException(
                500,
                'Foto profil gagal disimpan. Periksa ruang dan izin storage.'
            );
        }

        try {
            $user->profile_photo_path = $path;
            if (!$user->saveOrFail()) {
                throw new RuntimeException(
                    'Data foto profil gagal disimpan ke database.'
                );
            }
        } catch (Throwable $exception) {
            try {
                if (!$disk->delete($path)) {
                    report(new RuntimeException(
                        'File foto baru gagal dibersihkan setelah penyimpanan database gagal.'
                    ));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return $disk->url($path);
    }

    public function remove(User $user): void
    {
        $user->profile_photo_path = null;
        $user->saveOrFail();
    }
}
