document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('parentActivityPage');
    if (!root) return;
    function read(id) {
        try { return JSON.parse(document.getElementById(id)?.textContent || '[]'); }
        catch (_) { return []; }
    }
    const chapters = read('paChaptersJson');
    const activities = read('paActivitiesJson');
    const chapter = document.getElementById('paChapter');
    const subchapter = document.getElementById('paSubchapter');
    const list = document.getElementById('paActivityList');
    const empty = document.getElementById('paActivityEmpty');
    const counter = document.getElementById('paActivityCount');
    let selectedType = 'semua';
    const icons = { materi: 'bi-book', kuis: 'bi-clipboard-check', evaluasi: 'bi-journal-check', diskusi: 'bi-chat-dots' };
    const displayDate = date => {
        if (!date) return '';
        const parsed = new Date(date);
        return isNaN(parsed.getTime()) ? '' : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(parsed);
    };
    const insert = (tag, value, className) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        node.textContent = String(value ?? '');
        return node;
    };
    function draw() {
        if (!list) return;
        const matches = activities.filter(item =>
            (chapter.value === 'all' || String(item.chapter_id) === chapter.value) &&
            (subchapter.value === 'all' || String(item.subchapter_id) === subchapter.value) &&
            (selectedType === 'semua' || item.type === selectedType)
        ).sort((a, b) => String(b.date || '').localeCompare(String(a.date || '')));
        list.replaceChildren();
        matches.forEach(item => {
            const row = insert('div', '', 'pa-entry');
            const iconWrap = insert('span', '', 'pa-entry-icon');
            const icon = document.createElement('i');
            icon.className = 'bi ' + (icons[item.type] || 'bi-activity');
            iconWrap.appendChild(icon);
            const main = insert('div', '', 'pa-entry-main');
            main.append(insert('strong', item.title || 'Aktivitas belajar', 'pa-entry-title'));
            const parts = [item.chapter_title, item.subchapter_title, item.detail].filter(Boolean);
            main.append(insert('span', parts.join(' · '), 'pa-entry-detail'));
            row.append(iconWrap, main, insert('time', displayDate(item.date), 'pa-entry-date'));
            list.append(row);
        });
        if (empty) empty.hidden = matches.length !== 0;
        if (counter) counter.textContent = matches.length ? `${matches.length} aktivitas ditemukan` : '';
    }
    chapter?.addEventListener('change', () => {
        subchapter.replaceChildren(new Option('Semua Subbab', 'all'));
        const chosen = chapters.find(item => String(item.id) === chapter.value);
        (chosen?.subchapters || []).forEach(item => subchapter.add(new Option(item.title || 'Subbab', String(item.id))));
        draw();
    });
    subchapter?.addEventListener('change', draw);
    root.querySelectorAll('[data-pa-type]').forEach(button => button.addEventListener('click', () => {
        selectedType = button.dataset.paType;
        root.querySelectorAll('[data-pa-type]').forEach(other => {
            const active = other === button;
            other.classList.toggle('active', active);
            other.setAttribute('aria-pressed', String(active));
        });
        draw();
    }));
    draw();
});
