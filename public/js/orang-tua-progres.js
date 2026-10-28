document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const root = document.getElementById('opProgressRoot');
    if (!root) return;
    const chapterSelect = root.querySelector('#opProgressChapter');
    const subchapterSelect = root.querySelector('#opProgressSubchapter');
    const chapterCards = [...root.querySelectorAll('[data-op-chapter]')];
    const noMatch = root.querySelector('#opProgressNoMatch');
    if (!chapterSelect || !subchapterSelect) return;

    function updateSubchapterOptions() {
        const previous = subchapterSelect.value;
        const ids = new Map();
        chapterCards.forEach(card => {
            if (chapterSelect.value && card.dataset.opChapter !== chapterSelect.value) return;
            card.querySelectorAll('[data-op-subchapter]').forEach(row => {
                ids.set(row.dataset.opSubchapter, row.querySelector('span')?.textContent?.trim() || 'Subbab');
            });
        });
        subchapterSelect.replaceChildren(new Option('Semua Subbab', ''));
        ids.forEach((name, id) => subchapterSelect.add(new Option(name, id)));
        subchapterSelect.value = ids.has(previous) ? previous : '';
    }

    function updateCards() {
        let matched = 0;
        chapterCards.forEach(card => {
            const matchChapter = !chapterSelect.value || card.dataset.opChapter === chapterSelect.value;
            const matchSubchapter = !subchapterSelect.value || [...card.querySelectorAll('[data-op-subchapter]')]
                .some(row => row.dataset.opSubchapter === subchapterSelect.value);
            const visible = matchChapter && matchSubchapter;
            card.hidden = !visible;
            if (visible) matched++;
            card.querySelectorAll('[data-op-subchapter]').forEach(row => {
                row.hidden = Boolean(subchapterSelect.value) && row.dataset.opSubchapter !== subchapterSelect.value;
            });
        });
        if (noMatch) noMatch.hidden = matched !== 0 || chapterCards.length === 0;
    }

    chapterSelect.addEventListener('change', () => {
        subchapterSelect.value = '';
        updateSubchapterOptions();
        updateCards();
    });
    subchapterSelect.addEventListener('change', updateCards);
    updateSubchapterOptions();
    updateCards();
});
