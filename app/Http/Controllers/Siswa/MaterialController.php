<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->role === 'siswa',
            403,
            'Hanya Siswa yang dapat mengakses materi.'
        );

        $chapters = Chapter::query()
            ->where('is_published', true)
            ->whereHas('guru', function ($query) {
                $query->where('role', 'guru');
            })
            ->with([
                'subchapters' => function ($query) {
                    $query->where('is_published', true)
                        ->orderBy('nomor_subbab')
                        ->with([
                            'media',
                            'contentBlocks.media',
                        ]);
                }
            ])
            ->orderBy('nomor_bab')
            ->get([
                'id',
                'user_id',
                'nomor_bab',
                'judul',
                'deskripsi',
                'gambar_pengenalan_path',
                'gambar_pengenalan_judul',
                'gambar_pengenalan_keterangan',
                'tujuan_pembelajaran',
                'pertanyaan_pemantik',
            ]);

        $chapters->each(function (Chapter $chapter) {
            $chapter->subchapters->each(function ($subchapter) {
                $subchapter->media->each(function ($media) {
                    $media->makeHidden([
                        'storage_path',
                        'library_path',
                        'youtube_video_id',
                    ]);
                });
                $subchapter->contentBlocks->each(function ($block) {
                    $block->media?->makeHidden([
                        'storage_path',
                        'library_path',
                        'youtube_video_id',
                    ]);
                });
                $blocks = $subchapter->materialBlocks();
                $subchapter->makeHidden('contentBlocks');
                $subchapter->setAttribute(
                    'content_blocks',
                    $blocks
                );
            });
        });

        return response()->json([
            'chapters' => $chapters,
        ]);
    }
}