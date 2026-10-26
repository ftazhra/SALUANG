<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Chapter;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class DiscussionController extends Controller
{
    /**
     * Menggunakan materi Guru yang dipublikasikan sebagai sumber validasi.
     * Format disimpan tetap "bab-N" dan "N.M" agar data Forum lama kompatibel.
     *
     * @return array<string, list<string>>
     */
    private function publishedForumOptions(): array
    {
        $chapters = Chapter::query()
            ->where('is_published', true)
            ->with(['subchapters' => function ($query) {
                $query->where('is_published', true);
            }])
            ->get();
        $options = [];
        foreach ($chapters as $chapter) {
            $chapterNumber = (string) $chapter->nomor_bab;
            $chapterCode = 'bab-' . $chapterNumber;
            $options[$chapterCode] = [];
            foreach ($chapter->subchapters as $subchapter) {
                $options[$chapterCode][] = $chapterNumber . '.' . (string) $subchapter->nomor_subbab;
            }
        }
        return $options;
    }
    /** @param array<string, mixed> $data
     *  @param array<string, list<string>> $options
     */
    private function validateChapterPair(array $data, array $options): void
    {
        if (!isset($data['chapter'], $data['subchapter'])) {
            return;
        }
        if (!in_array($data['subchapter'], $options[$data['chapter']] ?? [], true)) {
            throw ValidationException::withMessages([
                'subchapter' => 'Subbab tidak sesuai dengan Bab yang dipilih.',
            ]);
        }
    }
    /*
    |--------------------------------------------------------------------------
    | 02. MEMERIKSA AKSES SISWA
    |--------------------------------------------------------------------------
    */
    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()
                && $request->user()->role === 'guru',
            403,
            'Forum Diskusi hanya tersedia untuk Guru.'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | 03. MENAMPILKAN DAFTAR DISKUSI
    |--------------------------------------------------------------------------
    |
    | Mengambil diskusi dari database MySQL.
    | Mendukung filter Bab, Subbab, pencarian,
    | dan status sudah/belum terjawab.
    |
    */
    public function index(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        $forumOptions = $this->publishedForumOptions();
        $filters = $request->validate([
            'chapter' => [
                'nullable',
                Rule::in(array_keys($forumOptions)),
            ],
            'subchapter' => [
                'nullable',
                Rule::in(array_values(array_unique(array_merge([], ...array_values($forumOptions))))),
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
        $this->validateChapterPair($filters, $forumOptions);
        $query = Discussion::query()
            ->with('user:id,name')
            ->withCount('replies');
        // 03.1. Filter Bab
        if (!empty($filters['chapter'])) {
            $query->where(
                'chapter',
                $filters['chapter']
            );
        }
        // 03.2. Filter Subbab
        if (!empty($filters['subchapter'])) {
            $query->where(
                'subchapter',
                $filters['subchapter']
            );
        }
        // 03.3. Pencarian judul dan pertanyaan
        if (!empty($filters['search'])) {
            $keyword = $filters['search'];
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where(
                        'title',
                        'like',
                        '%' . $keyword . '%'
                    )
                    ->orWhere(
                        'body',
                        'like',
                        '%' . $keyword . '%'
                    );
            });
        }
        // 03.4. Filter status tanggapan
        $status = $filters['status'] ?? 'all';
        if ($status === 'answered') {
            $query->has('replies');
        }
        if ($status === 'unanswered') {
            $query->doesntHave('replies');
        }
        // 03.5. Diskusi terbaru terlebih dahulu
        $discussions = $query
            ->latest()
            ->paginate(10);
        $userId = $request->user()->id;
        $discussions->getCollection()->each(function ($discussion) use ($userId) {
            $discussion->setAttribute(
                'is_owner',
                (int) $discussion->user_id === (int) $userId
            );
        });
        return response()->json($discussions);
    }
    /*
    |--------------------------------------------------------------------------
    | 04. MEMBUAT DAN MENYIMPAN DISKUSI BARU
    |--------------------------------------------------------------------------
    |
    | Setiap pertanyaan terhubung dengan akun
    | siswa yang sedang login.
    |
    */
    public function store(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        // 04.1. Validasi data pertanyaan
        $forumOptions = $this->publishedForumOptions();
        $data = $request->validate([
            'chapter' => [
                'required',
                Rule::in(array_keys($forumOptions)),
            ],
            'subchapter' => [
                'required',
                Rule::in(array_values(array_unique(array_merge([], ...array_values($forumOptions))))),
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
        $this->validateChapterPair($data, $forumOptions);
        // 04.2. Simpan ke tabel discussions
        $discussion = $request->user()
            ->discussions()
            ->create($data);
        // 04.3. Sertakan nama pembuat
        $discussion->load('user:id,name');
        $discussion->loadCount('replies');
        // 04.4. Kirim respons ke JavaScript
        return response()->json([
            'message' => 'Diskusi berhasil dibuat.',
            'discussion' => $discussion,
        ], 201);
    }
    /*
    |--------------------------------------------------------------------------
    | 05. MENAMPILKAN DETAIL DISKUSI
    |--------------------------------------------------------------------------
    |
    | Mengambil pertanyaan lengkap beserta
    | seluruh tanggapan dari database.
    |
    */
    public function show(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);
        // 05.1. Ambil pembuat dan seluruh tanggapan
        $discussion->load([
            'user:id,name,profile_photo_path',
            'replies' => function ($query) {
                $query
                    ->with('user:id,name,profile_photo_path')
                    ->oldest();
            },
        ]);
        // Penulis pertanyaan utama: foto profil atau fallback inisial di frontend.
        if ($discussion->user) {
            $path = $discussion->user->profile_photo_path;
            $discussion->user->setAttribute(
                'forum_avatar_url',
                is_string($path) && $path !== ''
                    ? asset('storage/' . ltrim($path, '/'))
                    : null
            );
        }
        // Sertakan URL foto untuk avatar, bukan path penyimpanan internal.
        $discussion->replies->each(function ($reply) {
            $author = $reply->user;
            if (!$author) {
                return;
            }
            $path = $author->profile_photo_path;
            $author->setAttribute(
                'forum_avatar_url',
                is_string($path) && $path !== ''
                    ? asset('storage/' . ltrim($path, '/'))
                    : null
            );
        });
        // 05.2. Hitung jumlah tanggapan
        $discussion->loadCount('replies');
        $discussion->setAttribute(
            'is_owner',
            (int) $discussion->user_id === (int) $request->user()->id
        );
        return response()->json([
            'discussion' => $discussion,
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 06. MENYIMPAN TANGGAPAN DISKUSI
    |--------------------------------------------------------------------------
    |
    | Tanggapan disimpan pada tabel
    | discussion_replies.
    |
    */
    public function reply(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);
        // 06.1. Validasi isi tanggapan
        $data = $request->validate([
            'body' => [
                'required',
                'string',
                'min:2',
                'max:5000',
            ],
        ]);
        // 06.2. Simpan tanggapan ke database
        $reply = $discussion
            ->replies()
            ->create([
                'user_id' => $request->user()->id,
                'body' => $data['body'],
            ]);
        // 06.3. Sertakan nama pembuat tanggapan
        $reply->load('user:id,name');
        return response()->json([
            'message' => 'Tanggapan berhasil dikirim.',
            'reply' => $reply,
        ], 201);
    }
    /*
    |--------------------------------------------------------------------------
    | 07. MENGEDIT DISKUSI MILIK SENDIRI
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);
        // Hanya pemilik diskusi yang boleh mengedit.
        abort_unless(
            (int) $discussion->user_id === (int) $request->user()->id,
            403,
            'Guru hanya boleh mengedit diskusi miliknya sendiri.'
        );
        // Validasi isi diskusi yang diperbarui.
        $forumOptions = $this->publishedForumOptions();
        $data = $request->validate([
            'chapter' => [
                'required',
                Rule::in(array_keys($forumOptions)),
            ],
            'subchapter' => [
                'required',
                Rule::in(array_values(array_unique(array_merge([], ...array_values($forumOptions))))),
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
        $this->validateChapterPair($data, $forumOptions);
        // Simpan perubahan ke tabel discussions.
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
    | 08. MENGHAPUS DISKUSI MILIK SENDIRI
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Request $request,
        Discussion $discussion
    ): JsonResponse {
        $this->ensureGuru($request);
        // Hanya pemilik diskusi yang boleh menghapus.
        abort_unless(
            (int) $discussion->user_id === (int) $request->user()->id,
            403,
            'Guru hanya boleh menghapus diskusi miliknya sendiri.'
        );
        // Hapus diskusi dari database.
        // Balasan ikut terhapus melalui cascade foreign key.
        $discussion->delete();
        return response()->json([
            'message' => 'Diskusi berhasil dihapus.',
        ]);
    }
}
