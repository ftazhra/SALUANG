<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscussionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. KONFIGURASI FORUM
    |--------------------------------------------------------------------------
    */

    // Sementara mengikuti struktur Forum Siswa.
    private const CHAPTER = 'bab-1';

    private const SUBCHAPTERS = [
        '1.1',
        '1.2',
        '1.3',
    ];

    /*
    |--------------------------------------------------------------------------
    | 02. AKSES KHUSUS GURU
    |--------------------------------------------------------------------------
    */

    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Forum ini hanya tersedia untuk Guru.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 03. DAFTAR DISKUSI
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $filters = $request->validate([
            'chapter' => [
                'nullable',
                Rule::in([self::CHAPTER]),
            ],
            'subchapter' => [
                'nullable',
                Rule::in(self::SUBCHAPTERS),
            ],
            'search' => [
                'nullable',
                'string',
                'max:200',
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'all',
                    'answered',
                    'unanswered',
                ]),
            ],
        ]);

        $query = Discussion::query()
            ->with('user:id,name')
            ->withCount('replies');

        if (!empty($filters['chapter'])) {
            $query->where('chapter', $filters['chapter']);
        }

        if (!empty($filters['subchapter'])) {
            $query->where('subchapter', $filters['subchapter']);
        }

        if (!empty($filters['search'])) {
            $keyword = $filters['search'];

            $query->where(function ($builder) use ($keyword) {
                $builder->where(
                    'title',
                    'like',
                    '%' . $keyword . '%'
                )->orWhere(
                    'body',
                    'like',
                    '%' . $keyword . '%'
                );
            });
        }

        $status = $filters['status'] ?? 'all';

        if ($status === 'answered') {
            $query->has('replies');
        }

        if ($status === 'unanswered') {
            $query->doesntHave('replies');
        }

        $discussions = $query
            ->latest()
            ->paginate(10);

        $userId = $request->user()->id;

        $discussions->getCollection()->each(
            function ($discussion) use ($userId) {
                $discussion->setAttribute(
                    'is_owner',
                    (int) $discussion->user_id === (int) $userId
                );
            }
        );

        return response()->json($discussions);
    }

    /*
    |--------------------------------------------------------------------------
    | 04. TAMBAH DISKUSI
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
    {
        $this->ensureGuru($request);

        $data = $request->validate([
            'chapter' => [
                'required',
                Rule::in([self::CHAPTER]),
            ],
            'subchapter' => [
                'required',
                Rule::in(self::SUBCHAPTERS),
            ],
            'title' => [
                'required',
                'string',
                'min:5',
                'max:200',
            ],
            'body' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ]);

        $discussion = $request->user()
            ->discussions()
            ->create($data);

        $discussion->load('user:id,name');
        $discussion->loadCount('replies');

        return response()->json([
            'message' => 'Diskusi berhasil dibuat.',
            'discussion' => $discussion,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 05. DETAIL DISKUSI
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);

        $discussion->load([
            'user:id,name',
            'replies' => function ($query) {
                $query->with('user:id,name')->oldest();
            },
        ]);

        $discussion->loadCount('replies');

        $discussion->setAttribute(
            'is_owner',
            (int) $discussion->user_id ===
                (int) $request->user()->id
        );

        return response()->json([
            'discussion' => $discussion,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 06. TANGGAPI DISKUSI
    |--------------------------------------------------------------------------
    */

    public function reply(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);

        $data = $request->validate([
            'body' => [
                'required',
                'string',
                'min:2',
                'max:5000',
            ],
        ]);

        $reply = $discussion->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $reply->load('user:id,name');

        return response()->json([
            'message' => 'Tanggapan Guru berhasil dikirim.',
            'reply' => $reply,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | 07. EDIT DISKUSI MILIK SENDIRI
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);

        abort_unless(
            (int) $discussion->user_id ===
                (int) $request->user()->id,
            403,
            'Guru hanya boleh mengedit diskusi miliknya sendiri.'
        );

        $data = $request->validate([
            'chapter' => [
                'required',
                Rule::in([self::CHAPTER]),
            ],
            'subchapter' => [
                'required',
                Rule::in(self::SUBCHAPTERS),
            ],
            'title' => [
                'required',
                'string',
                'min:5',
                'max:200',
            ],
            'body' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
        ]);

        $discussion->update($data);

        $discussion->load('user:id,name');
        $discussion->loadCount('replies');

        return response()->json([
            'message' => 'Diskusi berhasil diperbarui.',
            'discussion' => $discussion,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 08. HAPUS DISKUSI MILIK SENDIRI
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);

        abort_unless(
            (int) $discussion->user_id ===
                (int) $request->user()->id,
            403,
            'Guru hanya boleh menghapus diskusi miliknya sendiri.'
        );

        $discussion->delete();

        return response()->json([
            'message' => 'Diskusi berhasil dihapus.',
        ]);
    }
}