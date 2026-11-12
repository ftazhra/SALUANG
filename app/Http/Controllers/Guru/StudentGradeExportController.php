<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Services\StudentGradeService;
use Illuminate\Http\Request;
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
    public function excel(Request $request, StudentGradeService $grades): StreamedResponse
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
        $overview = $grades->overview((int) $request->user()->id, $filters);
        $students = $overview['data'];
        /*
        |--------------------------------------------------------------------------
        | 02. SIAPKAN WORKBOOK
        |--------------------------------------------------------------------------
        */
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai Siswa');
        $sheet->setCellValue('A1', 'SALUANG — REKAP NILAI SISWA');
        $sheet->mergeCells('A1:F1');
        $headers = [
            'Nama Siswa',
            'NISN',
            'Kelas',
            'Nilai Akhir',
            'Ketuntasan',
            'Detail',
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
            $sheet->setCellValueExplicit(
                "A{$row}",
                (string) ($student['name'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValueExplicit(
                "B{$row}",
                (string) ($student['nisn'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValueExplicit(
                "C{$row}",
                (string) ($student['class'] ?? ''),
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValue("D{$row}", $student['final_score']);
            $sheet->setCellValue(
                "E{$row}",
                match ($student['completion_status']) {
                    'tuntas' => 'Tuntas',
                    'belum_tuntas' => 'Belum Tuntas',
                    default => 'Belum Dinilai',
                }
            );
            $sheet->setCellValue(
                "F{$row}",
                $student['quiz_completed_count'] > 0 || $student['evaluation_completed']
                    ? 'Riwayat tersedia'
                    : 'Belum ada riwayat'
            );
            $row++;
        }
        /*
        |--------------------------------------------------------------------------
        | 04. FORMAT TAMPILAN
        |--------------------------------------------------------------------------
        */
        $sheet->getStyle('A1:F1')
            ->getFont()
            ->setBold(true)
            ->setSize(15);
        $sheet->getStyle('A3:F3')
            ->getFont()
            ->setBold(true);
        $sheet->getStyle('A3:F3')
            ->getFill()
            ->setFillType(
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFE5F3EA');
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(true);
        }
        $sheet->freezePane('A4');
        $lastRow = max(3, $row - 1);
        $sheet->setAutoFilter("A3:F{$lastRow}");
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
}