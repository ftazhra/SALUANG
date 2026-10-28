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
        const heading = document.createElement('h3');
        heading.textContent = result.name;
        detail.appendChild(heading);
        for (const [label, value] of [
            ['Bab', result.chapter_title], ['Subbab', result.subchapter_title || 'Evaluasi tingkat bab'],
            ['Jenis', result.type === 'kuis' ? 'Kuis' : result.type === 'evaluasi' ? 'Evaluasi' : 'Penilaian'],
            ['Nilai', Number(result.score).toLocaleString('id-ID', { maximumFractionDigits: 1 })],
            ['Status', result.status], ['Tanggal', result.date || 'Tidak tercatat']
        ]) {
            const p = document.createElement('p');
            const strong = document.createElement('strong');
            strong.textContent = label + ': ';
            p.append(strong, document.createTextNode(String(value)));
            detail.appendChild(p);
        }
        const close = document.createElement('button');
        close.className = 'pr-detail-button'; close.type = 'button'; close.textContent = 'Tutup Detail';
        close.addEventListener('click', () => { detail.hidden = true; });
        detail.appendChild(close); detail.hidden = false;
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
