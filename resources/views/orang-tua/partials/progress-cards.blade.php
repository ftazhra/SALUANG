@forelse(($chapters ?? []) as $chapter)
    <div class="parent-chapter-card">
        <div class="parent-chapter-header">
            <strong>{{ $chapter['title'] }}</strong><span>{{ $chapter['percentage'] }}%</span></div>
        <div class="parent-progress-track">
            <div class="parent-progress-fill" style="width:{{ max(0, min(100, $chapter['percentage'])) }}%"></div>
        </div>
        <p class="parent-chapter-meta">{{ $chapter['finished'] }} dari {{ $chapter['total'] }} materi selesai</p>
        @foreach ($chapter['subchapters'] as $subchapter)
            <div class="parent-subchapter-row"><span>{{ $subchapter['title'] }}</span><b
                    class="{{ $subchapter['done'] ? 'finished' : '' }}">{{ $subchapter['done'] ? 'Selesai' : 'Belum selesai' }}</b>
            </div>
        @endforeach
    </div>
@empty
    <div class="parent-empty">
        {{ empty($child) ? 'Hubungan akun anak belum disetujui.' : 'Belum ada progres Bab VI yang tercatat.' }}</div>
@endforelse
