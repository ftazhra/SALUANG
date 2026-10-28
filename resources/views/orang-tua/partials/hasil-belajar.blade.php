<article class="parent-card parent-results-page" id="parentResultsPage">
    <div class="pr-head">
        <div>
            <h2>Hasil Belajar Anak</h2>
            <p>Lihat hasil kuis dan evaluasi anak berdasarkan Bab dan Subbab.</p>
        </div>
        <button type="button" class="parent-outline-button" data-parent-page="beranda">Kembali ke Beranda</button>
    </div>

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
            <thead><tr><th>Nama</th><th>Jenis</th><th>Materi</th><th>Nilai</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody id="prRows">
                <tr><td colspan="6" class="pr-empty">Memuat hasil belajar...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pr-detail" id="prDetail" hidden aria-live="polite"></div>
</article>
<script type="application/json" id="prChaptersJson">@json($chapters ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="prResultsJson">@json($learningResults ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="application/json" id="prApprovedJson">@json(!empty($child))</script>
