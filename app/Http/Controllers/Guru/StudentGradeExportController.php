<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\StudentAssessmentResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentGradeExportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. UNDUH LAPORAN EXCEL
    |--------------------------------------------------------------------------
    */

    public function excel(Request $request): StreamedResponse
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Hanya Guru yang boleh mengunduh nilai siswa.'
        );

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

        $query = User::query()
            ->where('role', 'siswa')
            ->with('assessmentResults');

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

        if (!empty($filters['class'])) {
            if ($hasKelas) {
                $query->where('kelas', $filters['class']);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $students = $query->orderBy('name')->get();

        // Ketuntasan belum dikonfigurasi di SALUANG.
        if (!empty($filters['completion']) &&
            $filters['completion'] !== 'belum_dinilai') {
            $students = $students->take(0);
        }

        /*
        |--------------------------------------------------------------------------
        | 02. SIAPKAN WORKBOOK
        |--------------------------------------------------------------------------
        */

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Rekap Nilai Siswa');

        $sheet->setCellValue('A1', 'SALUANG — REKAP NILAI SISWA');
        $sheet->mergeCells('A1:G1');

        $headers = [
            'Nama Siswa',
            'NISN',
            'Kelas',
            'Kuis',
            'Evaluasi',
            'Nilai Akhir',
            'Ketuntasan',
        ];

        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                $index + 1
            );

            $sheet->setCellValue(
                $column . '3',
                $header
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 03. ISI DATA ASLI DARI DATABASE
        |--------------------------------------------------------------------------
        */

        $row = 4;

        foreach ($students as $student) {
            $results = $student->assessmentResults;

            $quiz = $this->latestResult($results, 'quiz');
            $evaluation = $this->latestResult(
                $results,
                'evaluation'
            );

            $sheet->setCellValue("A{$row}", $student->name);

            // NISN disimpan sebagai teks agar angka nol awal aman.
            $sheet->setCellValueExplicit(
                "B{$row}",
                $hasNisn ? (string) ($student->nisn ?? '') : '',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            $sheet->setCellValue(
                "C{$row}",
                $hasKelas ? ($student->kelas ?? '') : ''
            );

            if ($quiz !== null) {
                $sheet->setCellValue(
                    "D{$row}",
                    (float) $quiz->score
                );
            }

            if ($evaluation !== null) {
                $sheet->setCellValue(
                    "E{$row}",
                    (float) $evaluation->score
                );
            }

            // Belum ada rumus Nilai Akhir.
            $sheet->setCellValue("F{$row}", '');

            $sheet->setCellValue(
                "G{$row}",
                'Belum Dinilai'
            );

            $row++;
        }

        /*
        |--------------------------------------------------------------------------
        | 04. FORMAT TAMPILAN
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle('A1:G1')
            ->getFont()
            ->setBold(true)
            ->setSize(15);

        $sheet->getStyle('A3:G3')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A3:G3')
            ->getFill()
            ->setFillType(
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFE5F3EA');

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }

        $sheet->freezePane('A4');

        $lastRow = max(3, $row - 1);

        $sheet->setAutoFilter("A3:G{$lastRow}");

        /*
        |--------------------------------------------------------------------------
        | 05. KIRIM FILE XLSX
        |--------------------------------------------------------------------------
        */

        $filename = 'SALUANG_Nilai_Siswa_' .
            now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 06. HASIL TERBARU BERDASARKAN JENIS
    |--------------------------------------------------------------------------
    */

    private function latestResult(
        Collection $results,
        string $type
    ): ?StudentAssessmentResult {
        return $results
            ->where('assessment_type', $type)
            ->sortByDesc('completed_at')
            ->first();
    }
}