<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\Subchapter;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSampleSeeder extends Seeder
{
    public function run(): void
    {
        // Gunakan satu akun Guru yang tersedia.
        $guru = User::query()
            ->where('role', 'guru')
            ->orderBy('id')
            ->first();

        if (!$guru) {
            $this->command->warn(
                'Belum ada akun Guru. Seeder dibatalkan.'
            );
            return;
        }

        // Buat BAB 1 hanya jika belum ada pada akun Guru ini.
        $chapter = Chapter::firstOrCreate(
            [
                'user_id' => $guru->id,
                'nomor_bab' => 1,
            ],
            [
                'judul' => 'Karakteristik dan Klasifikasi Makhluk Hidup',
                'deskripsi' => 'Mempelajari ciri-ciri makhluk hidup, komponen biotik dan abiotik, serta keanekaragaman organisme di lingkungan lahan basah Kalimantan Selatan.',
                'is_published' => true,
            ]
        );

        // Buat Subbab 1.1 jika belum ada.
        $subchapter = Subchapter::firstOrCreate(
            [
                'chapter_id' => $chapter->id,
                'nomor_subbab' => 1,
            ],
            [
                'judul' => 'Makhluk Hidup atau Benda Mati?',
                'konten' => 'Makhluk hidup memiliki ciri seperti bernapas, tumbuh, membutuhkan energi, dan merespons rangsangan. Air sungai termasuk komponen abiotik, sedangkan ikan saluang dan tumbuhan purun termasuk komponen biotik.',
                'is_published' => true,
            ]
        );

        // SOAL 1
        QuizQuestion::firstOrCreate(
            [
                'subchapter_id' => $subchapter->id,
                'nomor_soal' => 1,
            ],
            [
                'pertanyaan' => 'Berikut ini yang termasuk komponen abiotik adalah ....',
                'pilihan_a' => 'Ikan saluang',
                'pilihan_b' => 'Tumbuhan purun',
                'pilihan_c' => 'Air sungai',
                'pilihan_d' => 'Teratai',
                'kunci_jawaban' => 'C',
                'pembahasan' => 'Air sungai termasuk komponen abiotik karena merupakan komponen tidak hidup. Ikan saluang, tumbuhan purun, dan teratai merupakan makhluk hidup.',
                'is_published' => true,
            ]
        );

        // SOAL 2
        QuizQuestion::firstOrCreate(
            [
                'subchapter_id' => $subchapter->id,
                'nomor_soal' => 2,
            ],
            [
                'pertanyaan' => 'Manakah ciri yang menunjukkan bahwa makhluk hidup berbeda dari benda mati?',
                'pilihan_a' => 'Tidak membutuhkan energi',
                'pilihan_b' => 'Bernapas dan tumbuh',
                'pilihan_c' => 'Selalu berbentuk padat',
                'pilihan_d' => 'Tidak merespons lingkungan',
                'kunci_jawaban' => 'B',
                'pembahasan' => 'Bernapas dan tumbuh merupakan ciri-ciri makhluk hidup. Makhluk hidup juga membutuhkan energi dan dapat merespons rangsangan.',
                'is_published' => true,
            ]
        );

        $this->command->info(
            'Contoh BAB 1, Subbab 1.1, dan dua soal kuis telah diperiksa dan ditambahkan jika belum ada.'
        );
    }
}