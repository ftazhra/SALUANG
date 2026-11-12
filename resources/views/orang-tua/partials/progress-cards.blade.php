@forelse(($chapters ?? []) as $chapter)
    <div class="parent-chapter-card">
        <div class="parent-chapter-header">
            <strong>{{ $chapter['title'] }}</strong><span>{{ $chapter['percentage'] }}%</span></div>
        <div class="parent-progress-track">
            <div class="parent-progress-fill" style="width:{{ max(0, min(100, $chapter['percentage'])) }}%"></div>
        </div>
        <p class="parent-chapter-meta">{{ $chapter['material_finished'] }} materi dan {{ $chapter['quiz_finished'] }} kuis selesai ·
            {{ $chapter['finished'] }} dari {{ $chapter['total'] }} aktivitas</p>
        @foreach ($chapter['subchapters'] as $subchapter)
            <div class="parent-subchapter-row">
                <span class="parent-subchapter-title">{{ $subchapter['title'] }}</span>
                <span class="parent-subchapter-state">
                    {{ (int) $subchapter['done'] + (int) $subchapter['quiz_done'] }}/2 aktivitas
                </span>
            </div>
        @endforeach
    </div>
@empty
    <div class="parent-empty">
        {{ empty($child) ? 'Hubungan akun anak belum disetujui.' : 'Belum ada progres Bab VI yang tercatat.' }}</div>
@endforelse
