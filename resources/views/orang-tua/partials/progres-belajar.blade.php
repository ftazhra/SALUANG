<article class="parent-card parent-panel op-progress" id="opProgressRoot">
    <div class="op-progress-heading">
        <div>
            <h2>Progres Belajar Anak</h2>
            <p>Lihat perkembangan materi anak berdasarkan Bab dan Subbab.</p>
        </div>
        <button type="button" class="parent-outline-button" data-parent-page="beranda">Kembali ke Beranda</button>
    </div>

    <div class="op-progress-filters">
        <label class="op-progress-filter">
            <span>Bab</span>
            <select id="opProgressChapter" aria-label="Filter Bab">
                <option value="">Semua Bab</option>
                @foreach ($chapters ?? [] as $chapter)
                    <option value="{{ $chapter['id'] }}">{{ $chapter['title'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="op-progress-filter">
            <span>Subbab</span>
            <select id="opProgressSubchapter" aria-label="Filter Subbab">
                <option value="">Semua Subbab</option>
            </select>
        </label>
    </div>

    @if (!$child)
        <div class="parent-empty op-progress-empty">Hubungan akun Orang Tua dan Siswa belum disetujui. Progres anak akan
            terlihat setelah hubungan disetujui.</div>
    @else
        <div id="opProgressCards" class="op-progress-cards">
            @forelse (($chapters ?? []) as $chapter)
                <div class="op-progress-card" data-op-chapter="{{ $chapter['id'] }}">
                    <div class="op-progress-card-title">
                        <strong>{{ $chapter['title'] }}</strong>
                        <span>{{ (int) $chapter['percentage'] }}%</span>
                    </div>
                    <div class="op-progress-bar" role="progressbar" aria-label="Progres {{ $chapter['title'] }}"
                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $chapter['percentage'] }}">
                        <div style="width: {{ max(0, min(100, (int) $chapter['percentage'])) }}%"></div>
                    </div>
                    <p class="op-progress-card-meta">
                        {{ $chapter['finished'] }} dari {{ $chapter['total'] }} materi selesai ·
                        {{ $chapter['finished'] >= $chapter['total'] && $chapter['total'] > 0 ? 'Selesai' : ($chapter['finished'] > 0 ? 'Sedang Berlangsung' : 'Belum Dimulai') }}
                    </p>
                    <div class="op-progress-subchapters">
                        @foreach ($chapter['subchapters'] ?? [] as $subchapter)
                            <div class="op-progress-subchapter" data-op-subchapter="{{ $subchapter['id'] }}"
                                data-op-chapter-ref="{{ $chapter['id'] }}">
                                <span>{{ $subchapter['title'] }}</span>
                                <b
                                    class="{{ $subchapter['done'] ? 'is-finished' : '' }}">{{ $subchapter['done'] ? 'Selesai' : 'Belum selesai' }}</b>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="parent-empty">Belum ada materi Bab VI yang dapat ditampilkan. Periksa apakah materi sudah
                    dipublikasikan Guru.</div>
            @endforelse
        </div>
        <div id="opProgressNoMatch" class="parent-empty op-progress-empty" hidden>Tidak ada progres sesuai filter yang
            dipilih.</div>
    @endif
</article>
