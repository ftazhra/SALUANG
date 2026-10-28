<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EvaluationResultService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
class StudentGradePdfController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. UNDUH LAPORAN NILAI PDF
    |--------------------------------------------------------------------------
    */
    public function download(Request $request, EvaluationResultService $evaluationResults)
    {
        // Hanya Guru yang boleh mengunduh laporan.
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Hanya Guru yang dapat mengunduh laporan nilai.'
        );
        /*
        |--------------------------------------------------------------------------
        | 02. VALIDASI FILTER
        |--------------------------------------------------------------------------
        */
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'class' => ['nullable', 'string', 'max:100'],
            'completion' => [
                'nullable',
                'in:tuntas,belum_tuntas,belum_dinilai',
            ],
        ]);
        $hasNisn = Schema::hasColumn('users', 'nisn');
        $hasKelas = Schema::hasColumn('users', 'kelas');
        $teacherId = (int) $request->user()->id;
        $quizResults = $evaluationResults->latestQuizByStudentForTeacher($teacherId);
        $evaluationAttempts = $evaluationResults->latestByStudentForTeacher($teacherId);
        $studentIds = array_values(array_unique([
            ...array_keys($quizResults),
            ...array_keys($evaluationAttempts),
        ]));
        /*
        |--------------------------------------------------------------------------
        | 03. AMBIL DATA SISWA DARI DATABASE
        |--------------------------------------------------------------------------
        */
        $query = User::query()
            ->where('role', 'siswa')
            ->whereIn('id', $studentIds);
        // Filter nama siswa atau NISN.
        if (!empty($filters['search'])) {
            $keyword = $filters['search'];
            $query->where(function ($builder) use (
                $keyword,
                $hasNisn
            ) {
                $builder->where(
                    'name',
                    'like',
                    "%{$keyword}%"
                );
                if ($hasNisn) {
                    $builder->orWhere(
                        'nisn',
                        'like',
                        "%{$keyword}%"
                    );
                }
            });
        }
        // Filter kelas.
        if (!empty($filters['class'])) {
            if ($hasKelas) {
                $query->where(
                    'kelas',
                    $filters['class']
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        // Ketuntasan belum dikonfigurasi.
        if (
            !empty($filters['completion']) &&
            $filters['completion'] !== 'belum_dinilai'
        ) {
            $query->whereRaw('1 = 0');
        }
        $students = $query
            ->orderBy('name')
            ->get();
        /*
        |--------------------------------------------------------------------------
        | 04. SIAPKAN DATA LAPORAN
        |--------------------------------------------------------------------------
        */
        $rows = [];
        foreach ($students as $student) {
            $quiz = $quizResults[$student->id] ?? null;
            $evaluation = $evaluationAttempts[$student->id] ?? null;
            $rows[] = [
                'name' => $student->name,
                'nisn' => $hasNisn
                    ? $student->nisn
                    : null,
                'class' => $hasKelas
                    ? $student->kelas
                    : null,
                'quiz' => $quiz !== null
                    ? (float) $quiz['score']
                    : null,
                'evaluation' => $evaluation !== null
                    ? (float) $evaluation['score']
                    : null,
            ];
        }
        /*
        |--------------------------------------------------------------------------
        | 05. MEMBUAT TAMPILAN LAPORAN PDF
        |--------------------------------------------------------------------------
        */
        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 28px;
                }
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 10px;
                    color: #234758;
                }
                h1 {
                    text-align: center;
                    color: #075477;
                    font-size: 18px;
                    margin-bottom: 6px;
                }
                .subtitle {
                    text-align: center;
                    color: #66818b;
                    margin-bottom: 22px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th,
                td {
                    border: 1px solid #cdded8;
                    padding: 8px 6px;
                    text-align: left;
                    word-wrap: break-word;
                }
                th {
                    background: #e5f3ea;
                    color: #075477;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background: #f8fbf9;
                }
                .note {
                    margin-top: 18px;
                    font-size: 9px;
                    color: #6c818a;
                    line-height: 1.6;
                }
            </style>
        </head>
        <body>
            <h1>SALUANG — REKAP NILAI SISWA</h1>
            <div class="subtitle">
                Laporan Hasil Belajar Siswa
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Siswa</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>Kuis</th>
                        <th>Evaluasi</th>
                        <th>Nilai Akhir</th>
                        <th>Ketuntasan</th>
                    </tr>
                </thead>
                <tbody>';
        /*
        |--------------------------------------------------------------------------
        | 06. MASUKKAN DATA SISWA KE TABEL PDF
        |--------------------------------------------------------------------------
        */
        foreach ($rows as $index => $row) {
            $number = $index + 1;
            // Escape teks agar aman ditampilkan sebagai HTML.
            $name = e((string) ($row['name'] ?? ''));
            $nisn = e((string) ($row['nisn'] ?? '—'));
            $class = e((string) ($row['class'] ?? '—'));
            $quiz = $row['quiz'] === null
                ? '—'
                : number_format(
                    $row['quiz'],
                    2,
                    ',',
                    '.'
                );
            $evaluation = $row['evaluation'] === null
                ? '—'
                : number_format(
                    $row['evaluation'],
                    2,
                    ',',
                    '.'
                );
            $html .= "
                <tr>
                    <td>{$number}</td>
                    <td>{$name}</td>
                    <td>{$nisn}</td>
                    <td>{$class}</td>
                    <td>{$quiz}</td>
                    <td>{$evaluation}</td>
                    <td>—</td>
                    <td>Belum Dinilai</td>
                </tr>
            ";
        }
        /*
        |--------------------------------------------------------------------------
        | 07. JIKA DATA SISWA KOSONG
        |--------------------------------------------------------------------------
        */
        if (count($rows) === 0) {
            $html .= '
                <tr>
                    <td colspan="8"
                        style="text-align:center;">
                        Tidak ada data siswa yang sesuai.
                    </td>
                </tr>
            ';
        }
        /*
        |--------------------------------------------------------------------------
        | 08. TUTUP HTML LAPORAN
        |--------------------------------------------------------------------------
        */
        $html .= '
                </tbody>
            </table>
            <p class="note">
                Catatan: Nilai Akhir dan Ketuntasan belum
                ditentukan karena aturan penilaian SALUANG
                masih dalam tahap konfigurasi.
                Nilai yang belum tersedia ditampilkan
                dengan tanda garis (—).
            </p>
        </body>
        </html>';
        /*
        |--------------------------------------------------------------------------
        | 09. GENERATE DAN UNDUH FILE PDF
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape');
        $filename = 'SALUANG_Nilai_Siswa_' .
            now()->format('Ymd_His') .
            '.pdf';
        return $pdf->download($filename);
    }
}
