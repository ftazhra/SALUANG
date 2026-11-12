<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Services\StudentGradeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
class StudentGradePdfController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. UNDUH LAPORAN NILAI PDF
    |--------------------------------------------------------------------------
    */
    public function download(Request $request, StudentGradeService $grades)
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
        $rows = $grades->overview((int) $request->user()->id, $filters)['data']->all();
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
                        <th>Nama Siswa</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>Nilai Akhir</th>
                        <th>Ketuntasan</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>';
        /*
        |--------------------------------------------------------------------------
        | 06. MASUKKAN DATA SISWA KE TABEL PDF
        |--------------------------------------------------------------------------
        */
        foreach ($rows as $index => $row) {
            // Escape teks agar aman ditampilkan sebagai HTML.
            $name = e((string) ($row['name'] ?? ''));
            $nisn = e((string) ($row['nisn'] ?? '—'));
            $class = e((string) ($row['class'] ?? '—'));
            $finalScore = $row['final_score'] === null
                ? '—'
                : number_format($row['final_score'], 2, ',', '.');
            $status = match ($row['completion_status']) {
                'tuntas' => 'Tuntas',
                'belum_tuntas' => 'Belum Tuntas',
                default => 'Belum Dinilai',
            };
            $detail = $row['quiz_completed_count'] > 0 || $row['evaluation_completed']
                ? 'Riwayat tersedia'
                : 'Belum ada riwayat';
            $html .= "
                <tr>
                    <td>{$name}</td>
                    <td>{$nisn}</td>
                    <td>{$class}</td>
                    <td>{$finalScore}</td>
                    <td>{$status}</td>
                    <td>{$detail}</td>
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
                    <td colspan="6"
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
                Nilai Akhir dihitung dari rata-rata Kuis Subbab dan Evaluasi Akhir dengan bobot 50:50.
                Ketuntasan menggunakan KKM 70 dan hanya ditentukan setelah semua komponen wajib lengkap.
                Nilai yang belum tersedia ditampilkan dengan tanda garis (—).
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
