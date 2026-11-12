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
                ids.set(
                    row.dataset.opSubchapter,
                    row.querySelector('.op-progress-subchapter-title')?.textContent?.trim() || 'Subbab'
                );
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
            const rows = [...card.querySelectorAll('[data-op-subchapter]')];
            const selectedRows = subchapterSelect.value
                ? rows.filter(row => row.dataset.opSubchapter === subchapterSelect.value)
                : rows;
            const matchSubchapter = selectedRows.length > 0;
            const visible = matchChapter && matchSubchapter;
            card.hidden = !visible;
            if (visible) {
                matched++;
                const materialFinished = selectedRows.filter(
                    row => row.dataset.opMaterialDone === 'true'
                ).length;
                const quizFinished = selectedRows.filter(
                    row => row.dataset.opQuizDone === 'true'
                ).length;
                const finished = materialFinished + quizFinished;
                const total = selectedRows.length * 2;
                const percentage = total > 0 ? Math.round((finished / total) * 100) : 0;
                const progressTitle = card.querySelector('[data-op-progress-title]');
                const progressPercentage = card.querySelector('[data-op-progress-percentage]');
                const progressBar = card.querySelector('[data-op-progressbar]');
                const progressMeta = card.querySelector('[data-op-progress-meta]');
                const selectedTitle = subchapterSelect.value
                    ? selectedRows[0].querySelector('.op-progress-subchapter-title')?.textContent?.trim()
                    : null;

                progressTitle.textContent = selectedTitle || card.dataset.opChapterTitle;
                progressPercentage.textContent = `${percentage}%`;
                progressBar.setAttribute('aria-valuenow', String(percentage));
                progressBar.setAttribute('aria-label', `Progres ${progressTitle.textContent}`);
                progressBar.querySelector('div').style.width = `${percentage}%`;
                progressMeta.textContent =
                    `${finished} dari ${total} aktivitas selesai (Materi + Kuis) · ` +
                    (finished === total ? 'Selesai' : finished > 0 ? 'Sedang Berlangsung' : 'Belum Dimulai');
            }

            rows.forEach(row => {
                row.hidden = Boolean(subchapterSelect.value)
                    && row.dataset.opSubchapter !== subchapterSelect.value;
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
