document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const root = document.getElementById('parentResultsPage');
    if (!root) return;
    const read = id => { try { return JSON.parse(document.getElementById(id)?.textContent || 'null'); } catch { return null; } };
    const chapters = read('prChaptersJson') || [];
    const rows = read('prResultsJson') || [];
    const approved = read('prApprovedJson') === true;
    const chapterSelect = root.querySelector('#prChapter');
    const subSelect = root.querySelector('#prSubchapter');
    const tbody = root.querySelector('#prRows');
    const detail = root.querySelector('#prDetail');
    let activeType = 'semua';

    function subchapters() {
        const chapter = chapters.find(item => String(item.id) === chapterSelect.value);
        subSelect.replaceChildren(new Option('Semua Subbab', 'all'));
        (chapter?.subchapters || []).forEach(s => subSelect.add(new Option(s.title, String(s.id))));
    }
    function td(value, className = '') {
        const cell = document.createElement('td');
        cell.textContent = String(value ?? '-');
        if (className) cell.className = className;
        return cell;
    }

    function openDetail(result) {
        detail.replaceChildren();

        // Header detail
        const header = document.createElement('div');
        header.className = 'pr-detail-header';

        const headingGroup = document.createElement('div');
        headingGroup.className = 'pr-detail-heading';

        const eyebrow = document.createElement('span');
        eyebrow.className = 'pr-detail-eyebrow';
        eyebrow.textContent = 'DETAIL HASIL BELAJAR';

        const heading = document.createElement('h3');
        heading.textContent = result.name || 'Hasil Belajar';

        headingGroup.append(eyebrow, heading);

        const statusBadge = document.createElement('span');
        const isComplete = result.status === 'Tuntas';

        statusBadge.className =
            'pr-pill' + (isComplete ? '' : ' not-done');

        statusBadge.textContent = result.status || '-';

        header.append(headingGroup, statusBadge);

        // Informasi detail dua kolom
        const grid = document.createElement('div');
        grid.className = 'pr-detail-grid';

        const score = Number(result.score);
        const scoreText = Number.isFinite(score)
            ? score.toLocaleString('id-ID', {
                maximumFractionDigits: 1
            })
            : '-';

        const typeText =
            result.type === 'kuis' ? 'Kuis' :
                result.type === 'evaluasi' ? 'Evaluasi' :
                    'Penilaian';

        const fields = [
            ['Bab', result.chapter_title],
            [
                'Subbab',
                result.subchapter_title || 'Evaluasi tingkat bab'
            ],
            ['Jenis Penilaian', typeText],
            ['Nilai Akhir', scoreText],
            ['Status', result.status],
            ['Tanggal', result.date || 'Tidak tercatat']
        ];

        fields.forEach(([label, value]) => {
            const item = document.createElement('div');
            item.className = 'pr-detail-item';

            const fieldLabel = document.createElement('span');
            fieldLabel.className = 'pr-detail-label';
            fieldLabel.textContent = label;

            const fieldValue = document.createElement('strong');
            fieldValue.className = 'pr-detail-value';
            fieldValue.textContent = String(value ?? '-');

            if (label === 'Nilai Akhir') {
                item.classList.add('pr-detail-score');
            }

            item.append(fieldLabel, fieldValue);
            grid.appendChild(item);
        });

        // Tombol tutup
        const actions = document.createElement('div');
        actions.className = 'pr-detail-actions';

        const close = document.createElement('button');
        close.className = 'pr-detail-button';
        close.type = 'button';
        close.textContent = 'Tutup Detail';

        close.addEventListener('click', () => {
            detail.hidden = true;
        });

        actions.appendChild(close);

        detail.append(header, grid, actions);
        detail.hidden = false;
    }

    function render() {
        detail.hidden = true;
        tbody.replaceChildren();
        const filtered = rows.filter(item =>
            (chapterSelect.value === 'all' || String(item.chapter_id) === chapterSelect.value) &&
            (subSelect.value === 'all' || String(item.subchapter_id) === subSelect.value) &&
            (activeType === 'semua' || item.type === activeType)
        );
        if (!approved || !filtered.length) {
            const tr = document.createElement('tr');
            const cell = td(!approved ? 'Data hasil belajar belum dapat ditampilkan. Hubungan akun Orang Tua dan Siswa perlu disetujui terlebih dahulu.' : 'Belum ada hasil belajar sesuai filter.', 'pr-empty');
            cell.colSpan = 6; tr.appendChild(cell); tbody.appendChild(tr); return;
        }
        filtered.forEach(item => {
            const tr = document.createElement('tr');
            tr.appendChild(td(item.name));
            tr.appendChild(td(item.type === 'kuis' ? 'Kuis' : item.type === 'evaluasi' ? 'Evaluasi' : 'Penilaian'));
            tr.appendChild(td(item.subchapter_title || item.chapter_title));
            tr.appendChild(td(Number(item.score).toLocaleString('id-ID', { maximumFractionDigits: 1 })));
            const statusCell = document.createElement('td');
            const badge = document.createElement('span'); badge.className = 'pr-pill' + (item.status !== 'Tuntas' ? ' not-done' : '');
            badge.textContent = item.status; statusCell.appendChild(badge); tr.appendChild(statusCell);
            const actionCell = document.createElement('td');
            const button = document.createElement('button'); button.type = 'button'; button.className = 'pr-detail-button'; button.textContent = 'Lihat Detail';
            button.addEventListener('click', () => openDetail(item)); actionCell.appendChild(button); tr.appendChild(actionCell);
            tbody.appendChild(tr);
        });
    }
    chapterSelect.addEventListener('change', () => { subchapters(); render(); });
    subSelect.addEventListener('change', render);
    root.querySelectorAll('[data-pr-type]').forEach(btn => btn.addEventListener('click', () => {
        activeType = btn.dataset.prType;
        root.querySelectorAll('[data-pr-type]').forEach(b => { const selected = b === btn; b.classList.toggle('active', selected); b.setAttribute('aria-pressed', String(selected)); });
        render();
    }));
    render();
});
