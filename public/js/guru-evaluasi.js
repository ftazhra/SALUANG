
/* =====================================================
   SALUANG — KELOLA EVALUASI GURU
   File: public/js/guru-evaluasi.js
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const page = document.getElementById('guruEvaluationPage');
    if (!page) return;

    const csrf = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content || '';

    const API = '/guru/evaluations/questions';

    const grid = document.getElementById('geQuestionGrid');
    const detail = document.getElementById('geQuestionDetail');

    const form = document.getElementById('geQuestionForm');
    const formModal = document.getElementById('geQuestionModal');
    const previewModal = document.getElementById('gePreviewModal');
    const deleteModal = document.getElementById('geDeleteModal');

    const numberInput = document.getElementById('geQuestionNumber');
    const typeInput = document.getElementById('geQuestionType');

    const multipleFields = document.getElementById(
        'geMultipleChoiceFields'
    );

    const interactiveFields = document.getElementById(
        'geInteractiveFields'
    );

    const toast = document.getElementById('geToast');

    let questions = [];
    let selectedQuestion = null;
    let pendingDelete = null;
    let toastTimer = null;

    const TYPE_LABELS = {
        pilihan_ganda: 'Pilihan Ganda A–D',
        drag_drop: 'Drag-and-Drop Gambar',
        menghubungkan: 'Menghubungkan Pasangan',
        pilih_gambar: 'Memilih Gambar'
    };

    /* =========================================
       01. HELPER
       ========================================= */

    function byId(id) {
        return document.getElementById(id);
    }

    function getTypeByNumber(number) {
        number = Number(number);

        if (number >= 1 && number <= 5) {
            return 'pilihan_ganda';
        }

        if (number >= 6 && number <= 10) {
            return 'drag_drop';
        }

        if (number >= 11 && number <= 15) {
            return 'menghubungkan';
        }

        if (number >= 16 && number <= 20) {
            return 'pilih_gambar';
        }

        return '';
    }

    function showToast(message, isError = false) {
        if (!toast) return;

        clearTimeout(toastTimer);

        toast.textContent = message;
        toast.classList.toggle('is-error', isError);
        toast.hidden = false;

        toastTimer = setTimeout(() => {
            toast.hidden = true;
        }, 3500);
    }

    function closeModals() {
        [formModal, previewModal, deleteModal].forEach(modal => {
            if (modal) modal.hidden = true;
        });
    }

    function openModal(modal) {
        closeModals();
        if (modal) modal.hidden = false;
    }

    function makeElement(tag, text = '', className = '') {
        const element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        element.textContent = String(text ?? '');

        return element;
    }

    async function requestApi(url, method = 'GET', payload = null) {
        const options = {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            }
        };

        if (payload !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(payload);
        }

        const response = await fetch(url, options);

        let result;

        try {
            result = await response.json();
        } catch (_) {
            throw new Error(
                'Server tidak mengembalikan respons JSON yang valid.'
            );
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;

            throw new Error(
                firstError ||
                result.message ||
                'Permintaan gagal. Silakan coba lagi.'
            );
        }

        return result;
    }

    function questionUrl(id) {
        return `${API}/${encodeURIComponent(id)}`;
    }


    /* =========================================
       UPLOAD GAMBAR KE LARAVEL
       ========================================= */

    async function uploadEvaluationImage(file) {
        const formData = new FormData();
        formData.append('image', file);

        const response = await fetch(
            '/guru/evaluations/upload-image',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                },
                body: formData
            }
        );

        const result = await response.json();

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;

            throw new Error(
                firstError ||
                result.message ||
                'Gagal mengunggah gambar.'
            );
        }

        return result.url;
    }

    /* =========================================
       02. MUAT SOAL DARI DATABASE
       ========================================= */

    async function loadQuestions() {
        try {
            const result = await requestApi(API);

            questions = Array.isArray(result.questions)
                ? result.questions
                : [];

            renderSummary();
            renderGrid();

            if (selectedQuestion) {
                const updated = questions.find(
                    question => question.id === selectedQuestion.id
                );

                if (updated) {
                    showQuestion(updated);
                } else {
                    selectedQuestion = null;
                    detail.hidden = true;
                }
            }
        } catch (error) {
            showToast(error.message, true);
        }
    }

    function renderSummary() {
        byId('geTotalQuestions').textContent =
            String(questions.length);

        byId('gePublishedQuestions').textContent =
            String(
                questions.filter(
                    question => Boolean(question.is_published)
                ).length
            );
    }

    /* =========================================
       03. DAFTAR NOMOR SOAL 1–20
       ========================================= */

    function renderGrid() {
        grid.replaceChildren();

        for (let number = 1; number <= 20; number++) {
            const question = questions.find(
                item => Number(item.nomor_soal) === number
            );

            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'ge-number-button';
            button.textContent = String(number);

            button.title = question
                ? question.is_published
                    ? `Soal ${number} — Dipublikasikan`
                    : `Soal ${number} — Draft`
                : `Soal ${number} — Belum dibuat`;

            if (question) {
                button.classList.add('is-filled');

                if (!question.is_published) {
                    button.classList.add('is-draft');
                }
            }

            if (
                selectedQuestion &&
                Number(selectedQuestion.nomor_soal) === number
            ) {
                button.classList.add('is-active');
            }

            button.addEventListener('click', () => {
                if (question) {
                    showQuestion(question);
                } else {
                    openQuestionForm(null, number);
                }
            });

            grid.appendChild(button);
        }
    }

    /* =========================================
       04. DETAIL SOAL
       ========================================= */

    function addDetailSection(container, heading, content) {
        const section = document.createElement('div');

        section.style.marginBottom = '15px';

        const title = makeElement('strong', heading);
        title.style.display = 'block';
        title.style.marginBottom = '7px';

        const text = makeElement('p', content);
        text.style.whiteSpace = 'pre-wrap';
        text.style.margin = '0';

        section.append(title, text);
        container.appendChild(section);
    }


    /* =========================================
       TAMPILAN VISUAL SOAL BERGAMBAR
       ========================================= */

    function renderVisualQuestion(container, question) {
        const content = question.konten_soal || {};
        const answer = question.kunci_jawaban || {};

        const items = question.tipe_soal === 'pilih_gambar'
            ? content.options || []
            : content.items || [];

        const targets = content.targets || [];

        if (question.tipe_soal !== 'pilih_gambar') {
            const heading = makeElement(
                'strong',
                'Kategori / Target Jawaban'
            );

            heading.style.display = 'block';
            heading.style.marginBottom = '12px';

            container.appendChild(heading);

            const targetList = document.createElement('div');
            targetList.className = 'ge-preview-targets';

            targets.forEach(target => {
                targetList.appendChild(
                    makeElement(
                        'span',
                        target.label,
                        'ge-preview-target'
                    )
                );
            });

            container.appendChild(targetList);
        }

        const heading = makeElement(
            'strong',
            'Gambar Soal'
        );

        heading.style.display = 'block';
        heading.style.margin = '18px 0 12px';

        container.appendChild(heading);

        const grid = document.createElement('div');
        grid.className = 'ge-preview-images';

        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'ge-preview-image-card';

            const image = document.createElement('img');
            image.src = item.image;
            image.alt = item.label || 'Gambar soal';
            image.loading = 'lazy';

            const label = makeElement(
                'strong',
                item.label || 'Tanpa nama'
            );

            let correctText = '';

            if (question.tipe_soal === 'pilih_gambar') {
                const isCorrect = answer.jawaban === item.id;

                if (isCorrect) {
                    card.classList.add('is-correct');
                }

                correctText = isCorrect
                    ? '✓ Jawaban Benar'
                    : 'Pilihan Gambar';

            } else {
                const targetId = answer.pairs?.[item.id];

                const target = targets.find(
                    value => value.id === targetId
                );

                correctText = target
                    ? `Pasangan benar: ${target.label}`
                    : 'Belum ada pasangan';
            }

            const status = makeElement(
                'span',
                correctText,
                'ge-preview-answer'
            );

            card.append(image, label, status);
            grid.appendChild(card);
        });

        container.appendChild(grid);
    }

    function renderQuestionContent(container, question) {
        container.replaceChildren();

        addDetailSection(
            container,
            'Pertanyaan',
            question.pertanyaan
        );

        if (question.tipe_soal === 'pilihan_ganda') {
            const choices = question.konten_soal || {};

            ['A', 'B', 'C', 'D'].forEach(letter => {
                addDetailSection(
                    container,
                    `Pilihan ${letter}`,
                    choices[`pilihan_${letter.toLowerCase()}`] || '-'
                );
            });

            addDetailSection(
                container,
                'Kunci Jawaban',
                question.kunci_jawaban?.jawaban || '-'
            );
        } else {
            renderVisualQuestion(container, question);
        }

        if (question.pembahasan) {
            addDetailSection(
                container,
                'Pembahasan',
                question.pembahasan
            );
        }
    }

    function showQuestion(question) {
        selectedQuestion = question;

        byId('geDetailNumber').textContent =
            `SOAL ${question.nomor_soal}`;

        byId('geDetailTitle').textContent =
            question.pertanyaan;

        byId('geDetailType').textContent =
            TYPE_LABELS[question.tipe_soal] ||
            question.tipe_soal;

        const status = byId('geDetailStatus');

        status.textContent = question.is_published
            ? 'Dipublikasikan'
            : 'Draft';

        status.classList.toggle(
            'is-published',
            Boolean(question.is_published)
        );

        renderQuestionContent(
            byId('geDetailContent'),
            question
        );

        detail.hidden = false;

        renderGrid();
    }


    /* =========================================
       EDITOR VISUAL SOAL BERGAMBAR
       ========================================= */

    const targetsSection = byId('geTargetsSection');
    const targetsList = byId('geTargetsList');
    const imagesList = byId('geImagesList');

    let visualId = 0;

    function nextVisualId(prefix) {
        visualId++;
        return `${prefix}_${visualId}`;
    }

    function makeRemoveButton(onRemove) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'ge-remove-row';
        button.innerHTML = '<i class="bi bi-trash3"></i>';
        button.setAttribute('aria-label', 'Hapus baris');
        button.addEventListener('click', onRemove);
        return button;
    }

    function refreshAnswerOptions() {
        const targets = Array.from(
            targetsList.querySelectorAll('.ge-target-row')
        ).map(row => ({
            id: row.dataset.targetId,
            label: row.querySelector('.ge-target-name').value.trim()
        }));

        imagesList.querySelectorAll('.ge-image-row').forEach(row => {
            const select = row.querySelector('.ge-image-answer');
            if (!select) return;

            const oldValue = select.value;
            select.replaceChildren();

            const placeholder = new Option(
                'Pilih jawaban yang benar', ''
            );
            select.appendChild(placeholder);

            targets.forEach(target => {
                select.add(
                    new Option(target.label || 'Tanpa nama', target.id)
                );
            });

            select.value = oldValue;
        });
    }

    function addTargetRow(target = null) {
        const row = document.createElement('div');
        row.className = 'ge-target-row';
        row.dataset.targetId = target?.id || nextVisualId('target');

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'ge-target-name';
        input.placeholder = 'Contoh: Biotik';
        input.maxLength = 255;
        input.value = target?.label || '';

        input.addEventListener('input', refreshAnswerOptions);

        const remove = makeRemoveButton(() => {
            row.remove();
            refreshAnswerOptions();
        });

        row.append(input, remove);
        targetsList.appendChild(row);

        refreshAnswerOptions();
    }

    function addImageRow(item = null, correctAnswer = '') {
        const row = document.createElement('div');
        row.className = 'ge-image-row';
        row.dataset.itemId = item?.id || nextVisualId('gambar');
        row.dataset.imageUrl = item?.image || '';

        const preview = document.createElement('div');
        preview.className = 'ge-image-preview';

        function showPreview(src) {
            preview.replaceChildren();

            if (!src) {
                const icon = document.createElement('i');
                icon.className = 'bi bi-image';
                preview.appendChild(icon);
                return;
            }

            const img = document.createElement('img');
            img.src = src;
            img.alt = 'Pratinjau gambar soal';
            preview.appendChild(img);
        }

        showPreview(row.dataset.imageUrl);

        const fields = document.createElement('div');
        fields.className = 'ge-image-fields';

        const nameLabel = document.createElement('label');
        nameLabel.textContent = 'Nama Gambar';

        const name = document.createElement('input');
        name.type = 'text';
        name.className = 'ge-image-name';
        name.placeholder = 'Contoh: Ikan saluang';
        name.maxLength = 255;
        name.value = item?.label || '';

        const fileLabel = document.createElement('label');
        fileLabel.textContent = 'Upload Gambar';

        const file = document.createElement('input');
        file.type = 'file';
        file.accept = 'image/jpeg,image/png,image/webp';
        file.className = 'ge-image-file';

        file.addEventListener('change', () => {
            const selected = file.files?.[0];
            if (!selected) return;

            if (
                !['image/jpeg', 'image/png', 'image/webp']
                    .includes(selected.type)
            ) {
                file.value = '';
                showToast(
                    'Gunakan gambar JPG, PNG, atau WebP.',
                    true
                );
                return;
            }

            if (selected.size > 2 * 1024 * 1024) {
                file.value = '';
                showToast(
                    'Ukuran gambar maksimal 2 MB.',
                    true
                );
                return;
            }

            const reader = new FileReader();

            reader.addEventListener('load', () => {
                showPreview(reader.result);
            });

            reader.readAsDataURL(selected);
        });

        const type = getTypeByNumber(numberInput.value);

        fields.append(nameLabel, name, fileLabel, file);

        if (type === 'pilih_gambar') {
            const correctLabel = document.createElement('label');
            correctLabel.className = 'ge-image-correct';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'geVisualCorrectImage';
            radio.value = row.dataset.itemId;
            radio.checked = correctAnswer === row.dataset.itemId;

            correctLabel.append(
                radio,
                document.createTextNode(' Jadikan jawaban benar')
            );

            fields.appendChild(correctLabel);
        } else {
            const answerLabel = document.createElement('label');
            answerLabel.textContent = 'Kategori / Pasangan Benar';

            const select = document.createElement('select');
            select.className = 'ge-image-answer';
            select.dataset.selectedAnswer = correctAnswer;

            fields.append(answerLabel, select);
        }

        const remove = makeRemoveButton(() => {
            row.remove();
        });

        fields.appendChild(remove);
        row.append(preview, fields);
        imagesList.appendChild(row);

        refreshAnswerOptions();

        const answer = row.querySelector('.ge-image-answer');
        if (answer) {
            answer.value = correctAnswer;
        }
    }

    function resetVisualEditor() {
        targetsList.replaceChildren();
        imagesList.replaceChildren();
    }

    function setupVisualEditor(question = null) {
        resetVisualEditor();

        const type = getTypeByNumber(numberInput.value);

        targetsSection.hidden = type === 'pilih_gambar';

        byId('geInteractiveTitle').textContent =
            TYPE_LABELS[type] || 'Pengaturan Soal Bergambar';

        byId('geItemsHeading').textContent =
            type === 'pilih_gambar'
                ? 'Gambar Pilihan Jawaban'
                : 'Daftar Gambar Soal';

        if (!['drag_drop', 'menghubungkan', 'pilih_gambar']
            .includes(type)) {
            return;
        }

        const content = question?.konten_soal || {};
        const answer = question?.kunci_jawaban || {};

        if (type !== 'pilih_gambar') {
            const targets = content.targets || [];

            if (targets.length) {
                targets.forEach(addTargetRow);
            } else {
                addTargetRow();
                addTargetRow();
            }
        }

        const items = type === 'pilih_gambar'
            ? content.options || []
            : content.items || [];

        if (items.length) {
            items.forEach(item => {
                const correct = type === 'pilih_gambar'
                    ? answer.jawaban
                    : answer.pairs?.[item.id] || '';

                addImageRow(item, correct);
            });
        } else {
            addImageRow();
            addImageRow();
        }
    }

    byId('geAddTarget').addEventListener('click', () => {
        addTargetRow();
    });

    byId('geAddImage').addEventListener('click', () => {
        addImageRow();
    });


    /* =========================================
       05. JENIS SOAL OTOMATIS
       ========================================= */

    function updateQuestionType() {
        const type = getTypeByNumber(numberInput.value);

        typeInput.value = type;

        multipleFields.hidden = type !== 'pilihan_ganda';

        interactiveFields.hidden =
            !['drag_drop', 'menghubungkan', 'pilih_gambar']
                .includes(type);
    }

    numberInput.addEventListener('change', () => {
        updateQuestionType();
        setupVisualEditor();
    });

    /* =========================================
       06. FORM TAMBAH / EDIT SOAL
       ========================================= */

    function openQuestionForm(question = null, number = null) {
        form.reset();

        byId('geQuestionId').value = question?.id || '';

        numberInput.value =
            question?.nomor_soal || number || '';

        byId('geQuestionText').value =
            question?.pertanyaan || '';

        byId('geExplanation').value =
            question?.pembahasan || '';

        byId('gePublished').checked =
            Boolean(question?.is_published);

        updateQuestionType();
        setupVisualEditor(question);

        if (question?.tipe_soal === 'pilihan_ganda') {
            const choices = question.konten_soal || {};

            ['A', 'B', 'C', 'D'].forEach(letter => {
                byId(`geOption${letter}`).value =
                    choices[`pilihan_${letter.toLowerCase()}`] || '';
            });

            byId('geCorrectChoice').value =
                question.kunci_jawaban?.jawaban || '';
        }

        if (
            question &&
            question.tipe_soal !== 'pilihan_ganda'
        ) {
            byId('geContentJson').value =
                JSON.stringify(question.konten_soal || {}, null, 2);

            byId('geAnswerJson').value =
                JSON.stringify(question.kunci_jawaban || {}, null, 2);
        } else {
            byId('geContentJson').value = '';
            byId('geAnswerJson').value = '';
        }

        byId('geQuestionModalTitle').textContent =
            question
                ? `Edit Soal ${question.nomor_soal}`
                : 'Tambah Soal Evaluasi';

        openModal(formModal);
    }

    function parseJsonInput(id, label) {
        const value = byId(id).value.trim();

        if (!value) {
            throw new Error(`${label} wajib diisi.`);
        }

        try {
            const parsed = JSON.parse(value);

            if (
                parsed === null ||
                Array.isArray(parsed) ||
                typeof parsed !== 'object'
            ) {
                throw new Error();
            }

            return parsed;
        } catch (_) {
            throw new Error(
                `${label} harus berupa objek JSON yang valid.`
            );
        }
    }


    /* =========================================
       MEMBACA FORM VISUAL TANPA JSON MANUAL
       ========================================= */

    async function buildVisualPayload(type) {
        const imageRows = Array.from(
            imagesList.querySelectorAll('.ge-image-row')
        );

        if (imageRows.length < 2) {
            throw new Error(
                'Tambahkan minimal 2 gambar untuk soal ini.'
            );
        }

        const items = [];

        for (const row of imageRows) {
            const id = row.dataset.itemId;
            const label = row.querySelector(
                '.ge-image-name'
            ).value.trim();

            const file = row.querySelector(
                '.ge-image-file'
            ).files?.[0];

            if (!label) {
                throw new Error(
                    'Setiap gambar harus memiliki nama.'
                );
            }

            let image = row.dataset.imageUrl || '';

            if (file) {
                image = await uploadEvaluationImage(file);
            }

            if (!image) {
                throw new Error(
                    `Upload gambar untuk "${label}" terlebih dahulu.`
                );
            }

            row.dataset.imageUrl = image;

            items.push({
                id,
                label,
                image
            });
        }

        if (type === 'pilih_gambar') {
            const selected = imagesList.querySelector(
                'input[name="geVisualCorrectImage"]:checked'
            );

            if (!selected) {
                throw new Error(
                    'Pilih satu gambar sebagai jawaban benar.'
                );
            }

            return {
                konten_soal: {
                    options: items
                },
                kunci_jawaban: {
                    jawaban: selected.value
                }
            };
        }

        const targets = Array.from(
            targetsList.querySelectorAll('.ge-target-row')
        ).map(row => ({
            id: row.dataset.targetId,
            label: row.querySelector(
                '.ge-target-name'
            ).value.trim()
        }));

        if (targets.length < 2) {
            throw new Error(
                'Tambahkan minimal 2 kategori atau target.'
            );
        }

        if (targets.some(target => !target.label)) {
            throw new Error(
                'Semua kategori harus memiliki nama.'
            );
        }

        const pairs = {};

        for (const row of imageRows) {
            const answer = row.querySelector(
                '.ge-image-answer'
            )?.value;

            if (!answer) {
                throw new Error(
                    'Tentukan kategori benar untuk setiap gambar.'
                );
            }

            pairs[row.dataset.itemId] = answer;
        }

        return {
            konten_soal: {
                items,
                targets
            },
            kunci_jawaban: {
                pairs
            }
        };
    }

    async function buildPayload() {
        const number = Number(numberInput.value);
        const type = getTypeByNumber(number);

        if (!type) {
            throw new Error('Pilih nomor soal 1 sampai 20.');
        }

        const payload = {
            nomor_soal: number,
            tipe_soal: type,
            pertanyaan: byId('geQuestionText').value.trim(),
            pembahasan: byId('geExplanation').value.trim(),
            is_published: byId('gePublished').checked
        };

        if (!payload.pertanyaan) {
            throw new Error('Pertanyaan wajib diisi.');
        }

        if (type === 'pilihan_ganda') {
            const choices = {};

            ['A', 'B', 'C', 'D'].forEach(letter => {
                const value =
                    byId(`geOption${letter}`).value.trim();

                if (!value) {
                    throw new Error(
                        `Pilihan ${letter} wajib diisi.`
                    );
                }

                choices[`pilihan_${letter.toLowerCase()}`] =
                    value;
            });

            const answer =
                byId('geCorrectChoice').value;

            if (!['A', 'B', 'C', 'D'].includes(answer)) {
                throw new Error(
                    'Pilih kunci jawaban A, B, C, atau D.'
                );
            }

            payload.konten_soal = choices;
            payload.kunci_jawaban = {
                jawaban: answer
            };
        } else {
            const visualData = await buildVisualPayload(type);

            payload.konten_soal = visualData.konten_soal;
            payload.kunci_jawaban = visualData.kunci_jawaban;
        }

        return payload;
    }


    form.addEventListener('submit', async event => {
        event.preventDefault();

        const id = byId('geQuestionId').value;
        const saveButton = byId('geSaveQuestion');

        if (saveButton.disabled) return;

        saveButton.disabled = true;

        try {
            const payload = await buildPayload();

            const result = await requestApi(
                id ? questionUrl(id) : API,
                id ? 'PUT' : 'POST',
                payload
            );

            closeModals();

            showToast(
                result.message || 'Soal berhasil disimpan.'
            );

            selectedQuestion = result.question || null;

            await loadQuestions();

        } catch (error) {
            showToast(error.message, true);

        } finally {
            saveButton.disabled = false;
        }
    });


    /* =========================================
       07. PRATINJAU SOAL
       ========================================= */

    function openPreview() {
        if (!selectedQuestion) return;

        byId('gePreviewTitle').textContent =
            `Pratinjau Soal ${selectedQuestion.nomor_soal}`;

        renderQuestionContent(
            byId('gePreviewContent'),
            selectedQuestion
        );

        openModal(previewModal);
    }

    /* =========================================
       08. HAPUS SOAL
       ========================================= */

    function openDeleteModal() {
        if (!selectedQuestion) return;

        pendingDelete = selectedQuestion.id;
        openModal(deleteModal);
    }

    byId('geConfirmDelete').addEventListener(
        'click',
        async event => {
            if (!pendingDelete) return;

            const button = event.currentTarget;
            button.disabled = true;

            try {
                const result = await requestApi(
                    questionUrl(pendingDelete),
                    'DELETE'
                );

                pendingDelete = null;
                selectedQuestion = null;
                detail.hidden = true;

                closeModals();

                showToast(
                    result.message || 'Soal berhasil dihapus.'
                );

                await loadQuestions();
            } catch (error) {
                showToast(error.message, true);
            } finally {
                button.disabled = false;
            }
        }
    );

    /* =========================================
       09. TOMBOL UTAMA
       ========================================= */

    byId('geAddQuestion').addEventListener(
        'click',
        () => openQuestionForm()
    );

    byId('geEditQuestion').addEventListener(
        'click',
        () => {
            if (selectedQuestion) {
                openQuestionForm(selectedQuestion);
            }
        }
    );

    byId('gePreviewQuestion').addEventListener(
        'click',
        openPreview
    );

    byId('geDeleteQuestion').addEventListener(
        'click',
        openDeleteModal
    );

    /* =========================================
       10. TUTUP MODAL
       ========================================= */

    page.querySelectorAll('[data-ge-close]')
        .forEach(button => {
            button.addEventListener('click', () => {
                closeModals();
                pendingDelete = null;
            });
        });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeModals();
            pendingDelete = null;
        }
    });

    /* =========================================
       11. MUAT DATA AWAL
       ========================================= */

    loadQuestions();
});
