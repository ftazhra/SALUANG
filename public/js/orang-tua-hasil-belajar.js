document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const root = document.getElementById('parentResultsPage');
    if (!root) return;
    const read = id => { try { return JSON.parse(document.getElementById(id)?.textContent || 'null'); } catch { return null; } };
    const chapters = read('prChaptersJson') || [];
    const rows = read('prResultsJson') || [];
    const finalGrades = read('prFinalGradesJson') || [];
    const approved = read('prApprovedJson') === true;
    const chapterSelect = root.querySelector('#prChapter');
    const subSelect = root.querySelector('#prSubchapter');
    const tbody = root.querySelector('#prRows');
    const detail = root.querySelector('#prDetail');
    const finalGradeRows = root.querySelector('#prFinalGradeRows');
    let activeType = 'semua';
    let openResultKey = null;

    // Tiap kuis Subbab dan Evaluasi Akhir hanya menampilkan hasil terbaru.
    // Attempt lama tetap tersimpan di database; ini hanya ringkasan tampilan.
    function latestResults(list) {
        const latest = new Map();
        const scoreDate = item => {
            const value = item.date || item.completed_at || '';
            const timestamp = Date.parse(value);
            return Number.isFinite(timestamp) ? timestamp : 0;
        };
        list.forEach(item => {
            const key = item.type === 'evaluasi'
                ? `evaluasi:${item.teacher_id ?? 'unverified'}`
                : item.type === 'kuis'
                    ? `kuis:${item.teacher_id ?? 'unknown'}:${item.subchapter_id ?? item.chapter_id ?? item.id}`
                    : `lainnya:${item.id}`;
            const old = latest.get(key);
            if (!old || scoreDate(item) > scoreDate(old) ||
                (scoreDate(item) === scoreDate(old) && Number(item.id) > Number(old.id))) {
                latest.set(key, item);
            }
        });
        return [...latest.values()].sort((a, b) => scoreDate(b) - scoreDate(a));
    }

    const visibleRows = latestResults(rows);

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

    function renderFinalGrades() {
        if (!finalGradeRows) return;
        finalGradeRows.replaceChildren();
        if (!approved || !finalGrades.length) {
            const message = document.createElement('p');
            message.className = 'pr-final-grade-empty';
            message.textContent = !approved
                ? 'Nilai akhir tersedia setelah hubungan akun disetujui.'
                : 'Belum ada nilai akhir per Guru yang dapat dihitung.';
            finalGradeRows.appendChild(message);
            return;
        }

        finalGrades.forEach(grade => {
            const card = document.createElement('article');
            card.className = 'pr-final-grade-item';

            const content = document.createElement('div');
            content.className = 'pr-final-grade-content';

            const teacher = document.createElement('strong');
            teacher.textContent = grade.teacher_name || 'Guru';

            const meta = document.createElement('small');
            meta.className = 'pr-final-grade-meta';
            meta.textContent = `Kuis ${grade.quiz_completed_count}/${grade.quiz_required_count} · Evaluasi ${grade.evaluation_completed ? 'selesai' : 'belum selesai'}`;

            const scoreBox = document.createElement('div');
            scoreBox.className = 'pr-final-grade-value-wrap';

            const scoreLabel = document.createElement('span');
            scoreLabel.className = 'pr-final-grade-label';
            scoreLabel.textContent = 'Nilai Akhir';

            const scoreValue = document.createElement('strong');
            scoreValue.textContent = grade.final_score === null
                ? 'Belum Dinilai'
                : Number(grade.final_score).toLocaleString('id-ID', {
                    maximumFractionDigits: 2
                });

            const status = document.createElement('span');
            const statusText = grade.final_score === null
                ? 'Belum Dinilai'
                : (grade.completion_status === 'tuntas' ? 'Tuntas' : 'Belum Tuntas');
            status.className = 'pr-final-grade-status' + (grade.final_score === null ? ' is-pending' : (grade.completion_status === 'tuntas' ? '' : ' is-not-done'));
            status.textContent = statusText;

            scoreBox.append(scoreLabel, scoreValue, status);
            content.append(teacher, meta);
            card.append(content, scoreBox);
            finalGradeRows.appendChild(card);
        });
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

        const fields = result.type === 'evaluasi'
            ? [
                ['Atribusi Guru', result.attribution_status === 'unverified'
                    ? 'Belum terverifikasi'
                    : (result.teacher_name || 'Guru')],
                ['Lingkup', 'Evaluasi Akhir · 15 soal'],
                ['Jenis Penilaian', typeText],
                ['Nilai', scoreText],
                ['Status', result.status],
                ['Tanggal', result.date || 'Tidak tercatat']
            ]
            : [
                ['Bab', result.chapter_title],
                ['Subbab', result.subchapter_title || '-'],
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
            openResultKey = null;
            updateDetailButtons();
        });

        actions.appendChild(close);

        detail.append(header, grid, actions);
        detail.hidden = false;
    }

    function updateDetailButtons() {
        root.querySelectorAll('[data-pr-detail]').forEach(button => {
            const expanded = button.dataset.prDetail === openResultKey;
            button.setAttribute('aria-expanded', String(expanded));
            const icon = button.querySelector('i');
            if (icon) icon.className = `bi ${expanded ? 'bi-eye-slash' : 'bi-eye'}`;
            const label = button.querySelector('span');
            if (label) label.textContent = expanded ? 'Tutup' : 'Lihat';
            button.setAttribute('aria-label', expanded ? 'Tutup detail hasil belajar' : 'Lihat detail hasil belajar');
            button.title = expanded ? 'Tutup detail' : 'Lihat detail';
        });
    }

    function render() {
        detail.hidden = true;
        openResultKey = null;
        tbody.replaceChildren();
        // Evaluasi Akhir tidak terkait ke Subbab, jadi hanya muncul saat
        // filter materi = Semua Bab + Semua Subbab.
        const filtered = visibleRows.filter(item => {
            if (activeType !== 'semua' && item.type !== activeType) return false;
            if (item.type === 'evaluasi') {
                return chapterSelect.value === 'all' && subSelect.value === 'all';
            }
            return (chapterSelect.value === 'all' || String(item.chapter_id) === chapterSelect.value) &&
                (subSelect.value === 'all' || String(item.subchapter_id) === subSelect.value);
        });
        if (!approved || !filtered.length) {
            const tr = document.createElement('tr');
            const cell = td(!approved ? 'Data hasil belajar belum dapat ditampilkan. Hubungan akun Orang Tua dan Siswa perlu disetujui terlebih dahulu.' : 'Belum ada hasil belajar sesuai filter.', 'pr-empty');
            cell.colSpan = 6; tr.appendChild(cell); tbody.appendChild(tr); return;
        }
        filtered.forEach(item => {
            const tr = document.createElement('tr');
            tr.appendChild(td(
                item.type === 'evaluasi'
                    ? `Evaluasi Akhir · ${item.teacher_name || 'Guru'}`
                    : (item.subchapter_title || item.chapter_title),
                'pr-material'
            ));
            tr.appendChild(td(
                item.type === 'kuis' ? 'Kuis' : item.type === 'evaluasi' ? 'Evaluasi' : 'Penilaian',
                'pr-type'
            ));
            tr.appendChild(td(item.name, 'pr-name'));
            tr.appendChild(td(
                Number(item.score).toLocaleString('id-ID', { maximumFractionDigits: 1 }),
                'pr-score'
            ));
            const statusCell = document.createElement('td');
            statusCell.className = 'pr-status';
            const badge = document.createElement('span'); badge.className = 'pr-pill' + (item.status !== 'Tuntas' ? ' not-done' : '');
            badge.textContent = item.status; statusCell.appendChild(badge); tr.appendChild(statusCell);
            const actionCell = document.createElement('td');
            const button = document.createElement('button');
            const key = `${item.type}:${item.id}`;
            button.type = 'button';
            button.className = 'pr-detail-button pr-detail-toggle';
            button.dataset.prDetail = key;
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-controls', 'prDetail');
            button.setAttribute('aria-label', 'Lihat detail hasil belajar');
            button.title = 'Lihat detail';
            const icon = document.createElement('i');
            icon.className = 'bi bi-eye';
            icon.setAttribute('aria-hidden', 'true');
            const label = document.createElement('span');
            label.textContent = 'Lihat';
            button.append(icon, label);
            button.addEventListener('click', () => {
                if (openResultKey === key) {
                    detail.hidden = true;
                    openResultKey = null;
                    updateDetailButtons();
                    return;
                }
                openResultKey = key;
                openDetail(item);
                updateDetailButtons();
            });
            actionCell.appendChild(button); tr.appendChild(actionCell);
            tbody.appendChild(tr);
        });
    }
    function syncFilterControls() {
        const evaluationOnly = activeType === 'evaluasi';
        if (evaluationOnly) {
            chapterSelect.value = 'all';
            subchapters();
        }
        chapterSelect.disabled = evaluationOnly;
        subSelect.disabled = evaluationOnly || chapterSelect.value === 'all';
        chapterSelect.title = evaluationOnly
            ? 'Evaluasi Akhir berlaku secara umum dan tidak difilter per Bab' : '';
        subSelect.title = evaluationOnly
            ? 'Evaluasi Akhir tidak terikat pada Subbab' : '';
    }
    chapterSelect.addEventListener('change', () => { subchapters(); syncFilterControls(); render(); });
    subSelect.addEventListener('change', render);
    root.querySelectorAll('[data-pr-type]').forEach(btn => btn.addEventListener('click', () => {
        activeType = btn.dataset.prType;
        syncFilterControls();
        root.querySelectorAll('[data-pr-type]').forEach(b => { const selected = b === btn; b.classList.toggle('active', selected); b.setAttribute('aria-pressed', String(selected)); });
        render();
    }));
    syncFilterControls();
    renderFinalGrades();
    render();
});
