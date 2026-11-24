<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Services\StudentGradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
class StudentGradeExportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. UNDUH LAPORAN EXCEL
    |--------------------------------------------------------------------------
    */
    public function excel(
        Request $request,
        StudentGradeService $grades
    ): BinaryFileResponse
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
        $stage = 'load_grade_data';
        $spreadsheet = null;
        $temporaryFile = null;

        if (!app()->environment('testing')) {
            $reservedMemory = str_repeat('x', 64 * 1024);
            register_shutdown_function(function () use (&$stage, &$reservedMemory): void {
                $error = error_get_last();
                if (
                    $error === null
                    || !in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true)
                ) {
                    return;
                }

                unset($reservedMemory);
                Log::critical('Student grade Excel export ended with a PHP fatal error.', [
                    'stage' => $stage,
                    'fatal_type' => $error['type'],
                    'memory_usage_bytes' => memory_get_usage(true),
                    'memory_peak_bytes' => memory_get_peak_usage(true),
                    'memory_limit' => ini_get('memory_limit'),
                ]);
            });
        }

        try {
            $overview = $grades->overview((int) $request->user()->id, $filters);
            $students = $overview['data'];

            $stage = 'build_workbook';
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
                $sheet->setCellValue($column . '3', $header);
            }

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

            $sheet->getStyle('A1:F1')->getFont()->setBold(true)->setSize(15);
            $sheet->getStyle('A3:F3')->getFont()->setBold(true);
            $sheet->getStyle('A3:F3')
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFE5F3EA');
            foreach ([
                'A' => 32,
                'B' => 16,
                'C' => 14,
                'D' => 14,
                'E' => 18,
                'F' => 24,
            ] as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            $sheet->freezePane('A4');
            $lastRow = max(3, $row - 1);
            $sheet->setAutoFilter("A3:F{$lastRow}");

            $stage = 'check_temporary_storage';
            $temporaryDirectory = storage_path('app');
            if (!is_dir($temporaryDirectory) || !is_writable($temporaryDirectory)) {
                throw new RuntimeException(
                    'Direktori sementara ekspor tidak tersedia atau tidak dapat ditulis.'
                );
            }

            $stage = 'create_temporary_file';
            $temporaryFile = tempnam($temporaryDirectory, 'saluang-grades-');
            if ($temporaryFile === false) {
                throw new RuntimeException(
                    'File ekspor Excel sementara tidak dapat disiapkan.'
                );
            }
            if (
                realpath(dirname($temporaryFile))
                !== realpath($temporaryDirectory)
            ) {
                @unlink($temporaryFile);
                $temporaryFile = null;
                throw new RuntimeException(
                    'File ekspor sementara dibuat di luar direktori privat aplikasi.'
                );
            }

            $stage = 'write_xlsx';
            (new Xlsx($spreadsheet))->save($temporaryFile);

            $stage = 'prepare_download_response';
            $filename = 'SALUANG_Nilai_Siswa_' . now()->format('Ymd_His') . '.xlsx';
            $response = response()->download(
                $temporaryFile,
                $filename,
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'private, no-store, max-age=0',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
            $response->deleteFileAfterSend(true);
            $stage = 'response_ready';

            return $response;
        } catch (Throwable $exception) {
            Log::error('Student grade Excel export failed.', [
                'stage' => $stage,
                'exception' => get_class($exception),
                'message' => $this->safeDiagnosticMessage($exception),
                'memory_usage_bytes' => memory_get_usage(true),
                'memory_peak_bytes' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'zip_extension_loaded' => extension_loaded('zip'),
                'temporary_storage_writable' => is_writable(storage_path('app')),
            ]);

            if ($temporaryFile !== null && is_file($temporaryFile) && !@unlink($temporaryFile)) {
                Log::warning('Student grade Excel temporary file cleanup failed.', [
                    'stage' => 'cleanup_temporary_file',
                ]);
            }

            throw $exception;
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
            }
        }
    }

    private function safeDiagnosticMessage(Throwable $exception): string
    {
        $message = preg_replace(
            [
                '/[A-Z]:\\\\[^\s,;]+/i',
                '~(?:/[^\s/:]+){2,}~',
                '/\b[\w.+-]+@[\w.-]+\.[A-Z]{2,}\b/i',
                '/\b(password|token|secret|authorization)\s*[:=]\s*\S+/i',
                '/"[^"]{1,120}"/',
            ],
            ['[path]', '[path]', '[redacted]', '$1=[redacted]', '"[redacted]"'],
            $exception->getMessage()
        ) ?? 'Diagnostic message unavailable.';

        return substr($message, 0, 180);
    }
}