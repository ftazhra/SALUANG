<article class="parent-card parent-activity-page" id="parentActivityPage">
    <div class="pa-head">
        <div>
            <h2>Aktivitas Anak</h2>
            <p>Pantau aktivitas belajar anak berdasarkan Bab dan Subbab.</p>
        </div>
        <button type="button" class="parent-outline-button" data-parent-page="beranda">Kembali ke Beranda</button>
    </div>

    <div class="pa-select-grid">
        <label class="pa-field" for="paChapter">
            <strong>Bab</strong>
            <select id="paChapter">
                <option value="all">Semua Bab</option>
                @foreach ($chapters ?? [] as $chapter)
                    <option value="{{ $chapter['id'] }}">{{ $chapter['title'] }}</option>
                @endforeach
            </select>
        </label>
        <label class="pa-field" for="paSubchapter">
            <strong>Subbab</strong>
            <select id="paSubchapter">
                <option value="all">Semua Subbab</option>
            </select>
        </label>
    </div>

    <div class="pa-filters" role="group" aria-label="Filter jenis aktivitas">
        <button type="button" class="pa-filter active" data-pa-type="semua" aria-pressed="true">Semua</button>
        <button type="button" class="pa-filter" data-pa-type="materi" aria-pressed="false">Materi</button>
        <button type="button" class="pa-filter" data-pa-type="kuis" aria-pressed="false">Kuis</button>
        <button type="button" class="pa-filter" data-pa-type="evaluasi" aria-pressed="false">Evaluasi</button>
        <button type="button" class="pa-filter" data-pa-type="diskusi" aria-pressed="false">Diskusi</button>
    </div>

    @if (empty($child))
        <div class="pa-empty" role="status">Aktivitas anak belum dapat ditampilkan. Hubungan akun Orang Tua dengan
            Siswa perlu disetujui terlebih dahulu.</div>
    @else
        <div class="pa-list" id="paActivityList" aria-live="polite"></div>
        <div class="pa-empty" id="paActivityEmpty" role="status" hidden>Belum ada aktivitas sesuai filter.</div>
        <div class="pa-bottom" id="paActivityCount" aria-live="polite"></div>
    @endif
</article>
<script type="application/json" id="paChaptersJson">@json($chapters ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="paActivitiesJson">@json($activityHistory ?? $activities ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
