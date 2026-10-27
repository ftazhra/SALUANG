<?php
namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class EvaluationController extends Controller
{
    private function ensureSiswa(Request $request): int
    {
        abort_unless($request->user() && $request->user()->role === 'siswa', 403);
        return (int) $request->user()->id;
    }

    private function questions()
    {
        return DB::table('evaluation_questions')->where('is_published', true)
            ->whereBetween('nomor_soal', [1,15])->orderBy('nomor_soal')->get();
    }

    private function requireReady($questions): void
    {
        $expected = range(1,15);
        $numbers = $questions->pluck('nomor_soal')->map(fn ($n) => (int) $n)->all();
        if ($numbers !== $expected) {
            throw ValidationException::withMessages(['evaluasi' => 'Evaluasi belum siap. Guru harus memublikasikan tepat 15 soal bernomor 1–15 tanpa duplikat.']);
        }
        foreach ($questions as $q) {
            $n=(int)$q->nomor_soal;
            $expectedType=$n<=5?'pilihan_ganda':($n<=10?'drag_drop':'menghubungkan');
            if ($q->tipe_soal !== $expectedType) {
                throw ValidationException::withMessages(['evaluasi'=>'Jenis soal nomor '.$n.' belum sesuai.']);
            }
        }
    }

    /**
     * Normalize JSON columns into arrays for scoring and saved answers.
     * The database query builder returns these fields as JSON strings.
     */
    private function normalizeSnapshot($questions): array
    {
        return collect($questions)->map(function ($q) {
            $row = (array) $q;
            foreach (['konten_soal', 'kunci_jawaban'] as $field) {
                if (is_string($row[$field] ?? null)) {
                    $row[$field] = json_decode($row[$field], true) ?: [];
                }
            }
            return $row;
        })->all();
    }

    private function publicQuestions($questions): array
    {
        return $questions->map(function ($q) {
            return [
                'id' => $q->id, 'nomor_soal' => (int)$q->nomor_soal,
                'tipe_soal' => $q->tipe_soal, 'pertanyaan' => $q->pertanyaan,
                'konten_soal' => is_array($q->konten_soal) ? $q->konten_soal : (json_decode($q->konten_soal, true) ?: []),
            ];
        })->all();
    }

    private function current(int $id)
    {
        return DB::table('evaluation_attempts')->where('user_id',$id)
            ->orderByDesc('id')->first();
    }

    private function status($attempt): string
    {
        if (!$attempt) return 'none';
        if ($attempt->submitted_at) return 'completed';
        if (Carbon::parse($attempt->ends_at)->isPast()) return 'expired';
        return 'active';
    }

    /** Ambang ketuntasan Bab VI; siswa dengan nilai >= 70 dikunci. */
    private const KKM = 70;

    /** Memeriksa semua percobaan, bukan hanya percobaan terakhir. */
    private function passingAttempt(int $userId)
    {
        return DB::table('evaluation_attempts')
            ->where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->where('score', '>=', self::KKM)
            ->orderByDesc('id')
            ->first();
    }

    public function state(Request $request): JsonResponse
    {
        $id=$this->ensureSiswa($request);
        $attempt=$this->current($id);
        $status=$this->status($attempt);
        if ($status === 'expired') {
            $this->finishAttempt($attempt);
            $attempt=$this->current($id);
            $status='completed';
        }
        $questions=$this->questions();
        $passing = $this->passingAttempt($id);
        // Gunakan nilai kelulusan jika ditemukan pada percobaan sebelumnya.
        if ($passing && (!$attempt || $status !== 'active')) {
            $attempt = $passing;
            $status = 'completed';
        }
        return response()->json([
            'status'=>$status,
            'passed' => (bool) $passing,
            'can_retry' => !$passing,
            'kkm' => self::KKM,
            'ready'=>$questions->count()===15 && $questions->pluck('nomor_soal')->map(fn ($v) => (int) $v)->all()===range(1,15),
            'questions'=>$status==='active'?$this->publicQuestions($questions):[],
            'attempt'=>$attempt ? [
                'id'=>$attempt->id, 'ends_at'=>Carbon::parse($attempt->ends_at)->toIso8601String(),
                'answers'=>$status==='active'?(json_decode($attempt->answers, true) ?: []):[],
                'score'=>$status==='completed'?$attempt->score:null,
                'correct_count'=>$status==='completed'?$attempt->correct_count:null,
                'details'=>$status==='completed'?$this->buildAttemptDetails($attempt):[],
            ]:null,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $id=$this->ensureSiswa($request);
        $questions=$this->questions();
        $this->requireReady($questions);
        return DB::transaction(function () use ($id,$questions) {
            if ($this->passingAttempt($id)) {
                return response()->json([
                    'message' => 'Evaluasi sudah tuntas. Kamu tidak dapat mengerjakannya lagi.',
                    'passed' => true,
                    'kkm' => self::KKM,
                ], 409);
            }
            $current=DB::table('evaluation_attempts')->where('user_id',$id)->orderByDesc('id')->lockForUpdate()->first();
            if ($current && $this->status($current)==='active') {
                return response()->json(['message'=>'Evaluasi masih berlangsung. Muat ulang halaman untuk melanjutkan.'],409);
            }
            if ($current && $this->status($current)==='expired') {
                $this->finishAttempt($current);
                if ($this->passingAttempt($id)) {
                    return response()->json([
                        'message' => 'Evaluasi sudah tuntas dan terkunci.',
                        'passed' => true,
                    ], 409);
                }
            }
            $now=now();
            $attemptId=DB::table('evaluation_attempts')->insertGetId([
                'user_id'=>$id,'answers'=>json_encode(new \stdClass()),
                'question_snapshot'=>json_encode($this->normalizeSnapshot($questions)),
                'ends_at'=>$now->copy()->addMinutes(45),
                'created_at'=>$now,'updated_at'=>$now,
            ]);
            return response()->json(['message'=>'Evaluasi dimulai.','attempt_id'=>$attemptId,'ends_at'=>$now->copy()->addMinutes(45)->toIso8601String(), 'questions'=>$this->publicQuestions($questions)]);
        });
    }

    public function save(Request $request): JsonResponse
    {
        $id=$this->ensureSiswa($request);
        $data=$request->validate(['answers'=>['required','array']]);
        return DB::transaction(function () use ($id,$data) {
            $attempt=DB::table('evaluation_attempts')->where('user_id',$id)->orderByDesc('id')->lockForUpdate()->first();
            if (!$attempt || $attempt->submitted_at) return response()->json(['message'=>'Tidak ada evaluasi aktif.'],409);
            if (Carbon::parse($attempt->ends_at)->isPast()) {
                $this->finishAttempt($attempt);
                return response()->json(['message'=>'Waktu evaluasi habis. Jawaban terakhir sebelum batas waktu sudah dinilai.'],409);
            }
            $questions=collect(json_decode($attempt->question_snapshot,true) ?: []);
            $valid=[];
            foreach ($questions as $q) {
                $n=(string)$q['nomor_soal'];
                $answer=$data['answers'][$n] ?? null;
                if ($q['tipe_soal']==='pilihan_ganda') {
                    if (in_array($answer,['A','B','C','D'],true)) $valid[$n]=$answer;
                } elseif (is_array($answer)) {
                    $items=collect($q['konten_soal']['items'] ?? [])->pluck('id')->map(fn($v)=>(string)$v)->all();
                    $targets=collect($q['konten_soal']['targets'] ?? [])->pluck('id')->map(fn($v)=>(string)$v)->all();
                    $pairs=[];
                    foreach ($answer as $key=>$value) {
                        if (in_array((string)$key,$items,true) && is_string($value) && in_array($value,$targets,true)) $pairs[(string)$key]=$value;
                    }
                    $valid[$n]=$pairs;
                }
            }
            DB::table('evaluation_attempts')->where('id',$attempt->id)->update(['answers'=>json_encode($valid),'updated_at'=>now()]);
            return response()->json(['message'=>'Jawaban tersimpan.']);
        });
    }

    private function finishAttempt($attempt): array
    {
        if ($attempt->submitted_at) return ['score'=>$attempt->score,'correct_count'=>$attempt->correct_count];
        $questions=json_decode($attempt->question_snapshot,true) ?: [];
        $answers=json_decode($attempt->answers,true) ?: [];
        $correct=0;
        foreach ($questions as $q) {
            $n=(string)$q['nomor_soal'];
            $expected=$q['kunci_jawaban'] ?? null;
            if (is_string($expected)) $expected=json_decode($expected,true);
            $response=$answers[$n] ?? null;
            if ($q['tipe_soal']==='pilihan_ganda') {
                if ($response !== null && $response===($expected['jawaban'] ?? null)) $correct++;
            } else {
                $pairs=$expected['pairs'] ?? [];
                if (is_array($response) && count($pairs)>0 && count($response)===count($pairs) && !array_diff_assoc($pairs,$response)) $correct++;
            }
        }
        $score=count($questions)?round(100*$correct/count($questions)):0;
        DB::table('evaluation_attempts')->where('id',$attempt->id)->whereNull('submitted_at')
            ->update(['score'=>$score,'correct_count'=>$correct,'submitted_at'=>now(),'updated_at'=>now()]);
        return ['score'=>$score,'correct_count'=>$correct];
    }

    /** Rincian hasil hanya dikirim setelah evaluasi dikumpulkan. */
    private function buildAttemptDetails($attempt): array
    {
        $questions = json_decode($attempt->question_snapshot, true) ?: [];
        $answers = json_decode($attempt->answers, true) ?: [];
        $details = [];

        foreach ($questions as $question) {
            $number = (int) ($question['nomor_soal'] ?? 0);
            $type = $question['tipe_soal'] ?? '';
            $content = $question['konten_soal'] ?? [];
            $key = $question['kunci_jawaban'] ?? [];
            if (is_string($content)) $content = json_decode($content, true) ?: [];
            if (is_string($key)) $key = json_decode($key, true) ?: [];
            $answer = $answers[(string) $number] ?? null;
            $entry = [
                'nomor_soal' => $number,
                'tipe_soal' => $type,
                'pertanyaan' => $question['pertanyaan'] ?? '',
                'pembahasan' => $question['pembahasan'] ?? '',
                'pairs' => [],
            ];

            if ($type === 'pilihan_ganda') {
                $correct = $key['jawaban'] ?? null;
                $choices = [
                    'A' => $content['pilihan_a'] ?? '',
                    'B' => $content['pilihan_b'] ?? '',
                    'C' => $content['pilihan_c'] ?? '',
                    'D' => $content['pilihan_d'] ?? '',
                ];
                $entry['benar'] = is_string($answer) && $answer === $correct;
                $entry['jawaban_siswa'] = $answer ? $answer . '. ' . ($choices[$answer] ?? '') : 'Belum dijawab';
                $entry['jawaban_benar'] = $correct ? $correct . '. ' . ($choices[$correct] ?? '') : '-';
            } else {
                $studentPairs = is_array($answer) ? $answer : [];
                $correctPairs = $key['pairs'] ?? [];
                $targetLabels = collect($content['targets'] ?? [])->mapWithKeys(fn ($target) => [
                    (string) ($target['id'] ?? '') => $target['label'] ?? ''
                ])->all();
                foreach ($content['items'] ?? [] as $item) {
                    $itemId = (string) ($item['id'] ?? '');
                    $student = $studentPairs[$itemId] ?? null;
                    $expected = $correctPairs[$itemId] ?? null;
                    $entry['pairs'][] = [
                        'label' => $item['label'] ?? $itemId,
                        'jawaban_siswa' => $student !== null ? ($targetLabels[$student] ?? $student) : 'Belum dijawab',
                        'jawaban_benar' => $expected !== null ? ($targetLabels[$expected] ?? $expected) : '-',
                        'benar' => $student !== null && $student === $expected,
                    ];
                }
                $entry['benar'] = count($correctPairs) > 0
                    && count($studentPairs) === count($correctPairs)
                    && !array_diff_assoc($correctPairs, $studentPairs);
            }
            $details[] = $entry;
        }
        usort($details, fn ($a, $b) => $a['nomor_soal'] <=> $b['nomor_soal']);
        return $details;
    }

    public function finish(Request $request): JsonResponse
    {
        $id=$this->ensureSiswa($request);
        return DB::transaction(function () use ($id) {
            $attempt=DB::table('evaluation_attempts')->where('user_id',$id)->orderByDesc('id')->lockForUpdate()->first();
            if (!$attempt) return response()->json(['message'=>'Belum ada evaluasi.'],409);
            $result=$this->finishAttempt($attempt);
            $saved = DB::table('evaluation_attempts')->where('id', $attempt->id)->first();
            return response()->json(['message'=>'Evaluasi selesai.'] + $result + [
                'details' => $this->buildAttemptDetails($saved),
            ]);
        });
    }
}
