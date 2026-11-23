<article class="parent-card parent-results-page" id="parentResultsPage">
    <div class="pr-head">
        <div>
            <h2>Hasil Belajar Anak</h2>
            <p>Lihat hasil kuis berdasarkan materi dan Evaluasi Akhir berdasarkan Guru.</p>
        </div>
    </div>

    <section class="pr-final-grade-summary" aria-labelledby="prFinalGradeTitle">
        <h3 id="prFinalGradeTitle">Nilai Akhir per Guru</h3>
        <p>Kuis dan Evaluasi Akhir masing-masing berbobot 50%; KKM 70.</p>
        <div id="prFinalGradeRows" class="pr-final-grade-list" aria-live="polite"></div>
    </section>

    <div class="pr-select-grid">
        <label class="pr-field" for="prChapter"><strong>Bab</strong>
            <select id="prChapter"><option value="all">Semua Bab</option>
                @foreach(($chapters ?? []) as $chapter)
                    <option value="{{ $chapter['id'] }}">{{ $chapter['title'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="pr-field" for="prSubchapter"><strong>Subbab</strong>
            <select id="prSubchapter"><option value="all">Semua Subbab</option></select>
        </label>
    </div>

    <div class="pr-filters" role="group" aria-label="Filter jenis hasil belajar">
        <button type="button" class="pr-filter active" data-pr-type="semua" aria-pressed="true">Semua</button>
        <button type="button" class="pr-filter" data-pr-type="kuis" aria-pressed="false">Kuis</button>
        <button type="button" class="pr-filter" data-pr-type="evaluasi" aria-pressed="false">Evaluasi</button>
    </div>

    <div class="pr-table-scroll">
        <table class="pr-table">
            <thead><tr><th>Materi</th><th>Jenis</th><th>Nama</th><th>Nilai</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody id="prRows">
                <tr><td colspan="6" class="pr-empty">Memuat hasil belajar...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pr-detail" id="prDetail" hidden aria-live="polite"></div>

    @php
        $parentTeacherNotes = $teacherNotes ?? [
            'assessments' => [],
            'general' => [],
            'unavailable' => [],
        ];
        $hasParentTeacherNotes = collect($parentTeacherNotes)->flatten(1)->isNotEmpty();
    @endphp
    <section class="pr-teacher-notes" aria-labelledby="prTeacherNotesTitle">
        <h3 id="prTeacherNotesTitle">Catatan Guru</h3>
        @if (empty($child))
            <p class="pr-teacher-notes-empty">Catatan tersedia setelah hubungan akun Orang Tua dan Siswa disetujui.</p>
        @elseif (!$hasParentTeacherNotes)
            <p class="pr-teacher-notes-empty">Belum ada catatan dari Guru.</p>
        @else
            @foreach (($parentTeacherNotes['assessments'] ?? []) as $assessment)
                <section class="pr-teacher-note-group">
                    <h4>{{ $assessment['type_label'] ?? 'Catatan penilaian' }}</h4>
                    <p class="pr-teacher-note-context">
                        {{ $assessment['chapter_title'] ?? 'Evaluasi Akhir' }}
                        @if (!empty($assessment['subchapter_title']))
                            · {{ $assessment['subchapter_title'] }}
                        @endif
                    </p>
                    @foreach (($assessment['notes'] ?? []) as $note)
                        <article class="pr-teacher-note">
                            <p>{{ $note['body'] }}</p>
                            <small>
                                {{ $note['teacher_name'] ?? $assessment['teacher_name'] ?? 'Guru' }}
                                · {{ \Illuminate\Support\Carbon::parse($note['date'])->format('d/m/Y H:i') }}
                            </small>
                        </article>
                    @endforeach
                </section>
            @endforeach

            @foreach (($parentTeacherNotes['general'] ?? []) as $note)
                <article class="pr-teacher-note">
                    <h4>Catatan umum</h4>
                    <p>{{ $note['body'] }}</p>
                    <small>
                        {{ $note['teacher_name'] ?? 'Guru' }}
                        · {{ \Illuminate\Support\Carbon::parse($note['date'])->format('d/m/Y H:i') }}
                    </small>
                </article>
            @endforeach

            @foreach (($parentTeacherNotes['unavailable'] ?? []) as $note)
                <article class="pr-teacher-note">
                    <h4>Catatan penilaian sebelumnya</h4>
                    <p>{{ $note['body'] }}</p>
                    <small>
                        {{ $note['teacher_name'] ?? 'Guru' }}
                        · {{ \Illuminate\Support\Carbon::parse($note['date'])->format('d/m/Y H:i') }}
                    </small>
                </article>
            @endforeach
        @endif
    </section>
</article>
<script type="application/json" id="prChaptersJson">@json($chapters ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="prResultsJson">@json($learningResults ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="prFinalGradesJson">@json($finalGrades ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="prApprovedJson">@json(!empty($child))</script>
