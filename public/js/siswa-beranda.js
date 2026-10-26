/* Beranda Siswa: ringkasan, progres, dan aktivitas terbaru. */
(function () {
    'use strict';
    const get = id => document.getElementById(id);
    function setText(element, value) {
        if (element) element.textContent = String(value ?? '');
    }
    function el(tag, className = '', text = null) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== null && text !== undefined) {
            node.textContent = String(text);
        }
        return node;
    }
    function renderSummary({ publishedChapters = [], dashboardProgress = null } = {}) {
        const chapter = publishedChapters.find(item =>
            dashboardProgress?.chapter?.id
            && Number(item.id) === Number(dashboardProgress.chapter.id)
        ) || publishedChapters[0] || null;
        const title = chapter
            ? String(chapter.judul || '').trim() || 'Bab Tanpa Judul'
            : 'Belum ada materi yang dipublikasikan Guru.';
        setText(get('sdHomeLessonTitle'), title);
        setText(get('sdAchievementMaterialTitle'), title);
        setText(
            document.querySelector('#homePage .sd-home-lesson .sd-pill'),
            chapter ? `BAB ${chapter.nomor_bab}` : 'MATERI PEMBELAJARAN'
        );
    }
    function showMaterialError(message) {
        setText(get('sdHomeLessonTitle'), message);
        setText(get('sdAchievementMaterialTitle'), message);
    }
    async function refreshProgress() {
        const response = await fetch('/siswa/progress', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || 'Progres belajar gagal dimuat.');
        }
        setText(get('sdProgressPercentage'), `${result.percentage}%`);
        setText(
            get('sdCompletedMaterials'),
            `${result.completed_materials} dari ${result.total_materials} materi selesai`
        );
        setText(get('sdCompletedQuizzes'), result.completed_quizzes);
        setText(get('sdCompletedEvaluations'), result.completed_evaluations);
        setText(document.querySelector('.sd-status'), result.status);
        document.querySelectorAll('.sd-progress').forEach(progress => {
            const fill = progress.querySelector('span');
            if (fill) fill.style.width = `${result.percentage}%`;
            progress.setAttribute('aria-valuenow', String(result.percentage));
        });
        const activities = get('sdActivityList');
        const emptyState = get('sdActivityEmpty');
        if (activities) {
            activities.replaceChildren();
            (result.activities || []).forEach(activity => {
                const item = el('li');
                item.appendChild(el('strong', '', activity.title));
                item.appendChild(el(
                    'div',
                    '',
                    activity.description || 'Aktivitas belajar'
                ));
                if (activity.occurred_at) {
                    const date = new Date(activity.occurred_at);
                    const time = el(
                        'time',
                        '',
                        Number.isNaN(date.getTime())
                            ? activity.occurred_at
                            : new Intl.DateTimeFormat('id-ID', {
                                dateStyle: 'medium',
                                timeStyle: 'short'
                            }).format(date)
                    );
                    item.appendChild(time);
                }
                activities.appendChild(item);
            });
        }
        if (emptyState) emptyState.hidden = Boolean(result.activities?.length);
        return result;
    }
    window.SaluangStudentHome = {
        refreshProgress,
        renderSummary,
        showMaterialError,
    };
})();
