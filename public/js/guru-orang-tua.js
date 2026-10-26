/* =====================================================
   SALUANG — HUBUNGI ORANG TUA GURU
   File: public/js/guru-orang-tua.js
   ===================================================== */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    /* 01. ELEMEN HALAMAN */
    const page = document.getElementById('guruParentPage');
    if (!page) return;
    const byId = (id) => document.getElementById(id);
    const API = '/guru/parent-contacts';
    const classButtons = Array.from(
        page.querySelectorAll('[data-gp-class]')
    );
    const searchInput = byId('gpSearchContact');
    const contactList = byId('gpContactList');
    const contactCount = byId('gpContactCount');
    const contactDetail = byId('gpContactDetail');
    const guardianName = byId('gpGuardianName');
    const guardianRelation = byId('gpGuardianRelation');
    const guardianPhone = byId('gpGuardianPhone');
    const relationship = byId('gpRelationship');
    const studentName = byId('gpStudentName');
    const studentClass = byId('gpStudentClass');
    const studentNisn = byId('gpStudentNisn');
    const whatsappLink = byId('gpWhatsappLink');
    const chatSubtitle = byId('gpChatSubtitle');
    const chatMessages = byId('gpChatMessages');
    const selectedRelationshipId = byId('gpSelectedRelationshipId');
    const chatForm = byId('gpChatForm');
    let selectedClass = null;
    let selectedId = null;
    let contacts = [];
    let searchTimer = null;
    let activeRequest = null;
    let requestVersion = 0;
    /* 02. HELPER */
    function displayValue(value) {
        const text = String(value ?? '').trim();
        return text || '—';
    }
    function setText(element, value) {
        if (element) {
            element.textContent = displayValue(value);
        }
    }
    function showMessage(message) {
        const placeholder = document.createElement('div');
        placeholder.className = 'gp-empty';
        const icon = document.createElement('i');
        icon.className = 'bi bi-info-circle';
        const text = document.createElement('p');
        text.textContent = message;
        placeholder.append(icon, text);
        return placeholder;
    }
    function resetContactDetail(message) {
        selectedId = null;
        selectedRelationshipId.value = '';
        contactDetail.hidden = true;
        whatsappLink.hidden = true;
        whatsappLink.removeAttribute('href');
        chatSubtitle.textContent =
            'Pilih narahubung untuk melihat detail kontak.';
        chatMessages.replaceChildren(
            showMessage(message)
        );
    }
    /* 03. NORMALISASI NOMOR WHATSAPP */
    function whatsappNumber(phone) {
        const raw = String(phone ?? '').trim();
        if (!raw || /[a-z]/i.test(raw)) return null;
        const compact = raw.replace(/[\s().-]/g, '');
        if (!/^\+?\d+$/.test(compact)) return null;
        let number = compact.replace(/^\+/, '');
        if (number.startsWith('0')) {
            number = '62' + number.substring(1);
        } else if (number.startsWith('8')) {
            number = '62' + number;
        }
        if (!/^628\d{7,12}$/.test(number)) {
            return null;
        }
        return number;
    }
    /* 04. TAMPILKAN KONTAK TERPILIH */
    function selectContact(contact) {
        if (!selectedClass) return;
        const student = contact.student || {};
        const guardian = contact.guardian || {};
        // Perlindungan tambahan agar kelas tidak tercampur.
        if (String(student.class ?? '').trim() !== selectedClass) {
            return;
        }
        selectedId = Number(contact.relationship_id);
        selectedRelationshipId.value = String(selectedId);
        contactDetail.hidden = false;
        setText(guardianName, guardian.name);
        setText(guardianRelation, guardian.relationship);
        setText(relationship, guardian.relationship);
        setText(guardianPhone, guardian.phone);
        setText(studentName, student.name);
        setText(studentClass, student.class);
        setText(studentNisn, student.nisn);
        whatsappLink.hidden = true;
        whatsappLink.removeAttribute('href');
        const number = whatsappNumber(guardian.phone);
        if (number) {
            whatsappLink.href = `https://wa.me/${number}`;
            whatsappLink.hidden = false;
        }
        setText(
            chatSubtitle,
            `Narahubung: ${displayValue(guardian.name)} — ` +
            `${displayValue(guardian.relationship)}`
        );
        chatMessages.replaceChildren(
            showMessage(
                'Kontak berhasil dipilih. Riwayat chat internal ' +
                'akan tersedia setelah API pesan dibuat.'
            )
        );
        renderContacts();
    }
    /* 05. TAMPILKAN DAFTAR KONTAK */
    function renderContacts() {
        contactList.replaceChildren();
        contactCount.textContent = `${contacts.length} kontak`;
        if (!selectedClass) {
            contactList.replaceChildren();
            return;
        }
        if (contacts.length === 0) {
            contactList.appendChild(
                showMessage(
                    `Belum ada narahubung terverifikasi di ${selectedClass} ` +
                    'yang sesuai pencarian.'
                )
            );
            return;
        }
        contacts.forEach((contact) => {
            const student = contact.student || {};
            const guardian = contact.guardian || {};
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'gp-contact-item';
            if (Number(contact.relationship_id) === selectedId) {
                button.classList.add('active');
            }
            const studentLabel = document.createElement('strong');
            studentLabel.textContent = displayValue(student.name);
            const classLabel = document.createElement('small');
            classLabel.textContent =
                `Kelas: ${displayValue(student.class)}`;
            const guardianLabel = document.createElement('small');
            guardianLabel.textContent =
                `Narahubung: ${displayValue(guardian.name)}`;
            const relationLabel = document.createElement('span');
            relationLabel.className = 'gp-relation';
            relationLabel.textContent =
                displayValue(guardian.relationship);
            button.append(
                studentLabel,
                classLabel,
                guardianLabel,
                relationLabel
            );
            button.addEventListener('click', () => {
                selectContact(contact);
            });
            contactList.appendChild(button);
        });
    }
    /* 06. AMBIL KONTAK BERDASARKAN KELAS */
    async function loadContacts() {
        requestVersion += 1;
        const currentVersion = requestVersion;
        if (activeRequest) {
            activeRequest.abort();
            activeRequest = null;
        }
        if (!selectedClass) {
            contacts = [];
            contactCount.textContent = '0 kontak';
            contactList.replaceChildren();
            return;
        }
        const controller = new AbortController();
        activeRequest = controller;
        const requestedClass = selectedClass;
        const params = new URLSearchParams();
        params.set('class', requestedClass);
        const search = searchInput.value.trim();
        if (search) {
            params.set('search', search);
        }
        contactList.replaceChildren(
            showMessage(`Memuat narahubung ${requestedClass}...`)
        );
        try {
            const response = await fetch(
                `${API}?${params.toString()}`,
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    signal: controller.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );
            if (!response.ok) {
                throw new Error(
                    `Gagal memuat kontak (HTTP ${response.status}).`
                );
            }
            const result = await response.json();
            // Abaikan hasil permintaan lama.
            if (
                currentVersion !== requestVersion ||
                requestedClass !== selectedClass
            ) {
                return;
            }
            const returnedContacts = Array.isArray(result.data)
                ? result.data
                : [];
            // Filter tambahan. Filter utama tetap ada di Controller.
            contacts = returnedContacts.filter((contact) =>
                String(contact.student?.class ?? '').trim() === selectedClass
            );
            const stillSelected = contacts.find(
                (contact) =>
                    Number(contact.relationship_id) === selectedId
            );
            if (stillSelected) {
                selectContact(stillSelected);
            } else {
                resetContactDetail(
                    `Pilih narahubung dari ${selectedClass}.`
                );
                renderContacts();
            }
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (currentVersion !== requestVersion) return;
            contacts = [];
            resetContactDetail(
                'Data narahubung belum dapat ditampilkan.'
            );
            contactCount.textContent = '0 kontak';
            contactList.replaceChildren(
                showMessage(error.message)
            );
        } finally {
            if (activeRequest === controller) {
                activeRequest = null;
            }
        }
    }
    /* 07. PILIH KELAS VII A - VII E */
    classButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const className = button.dataset.gpClass;
            // Klik kelas aktif sekali lagi = tutup kelas.
            selectedClass =
                selectedClass === className ? null : className;
            clearTimeout(searchTimer);
            searchInput.value = '';
            contacts = [];
            resetContactDetail(
                'Pilih narahubung untuk melihat detail kontak.'
            );
            classButtons.forEach((classButton) => {
                const active =
                    classButton.dataset.gpClass === selectedClass;
                classButton.classList.toggle('active', active);
                classButton.setAttribute(
                    'aria-expanded',
                    String(active)
                );
            });
            loadContacts();
        });
    });
    /* 08. PENCARIAN DALAM KELAS TERPILIH */
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        if (!selectedClass) {
            contactList.replaceChildren(
                showMessage(
                    'Pilih kelas terlebih dahulu sebelum mencari.'
                )
            );
            return;
        }
        searchTimer = setTimeout(() => {
            loadContacts();
        }, 350);
    });
    /* 09. CHAT INTERNAL BELUM DIAKTIFKAN */
    chatForm.addEventListener('submit', (event) => {
        event.preventDefault();
    });
    /* 10. TAMPILAN AWAL */
    resetContactDetail(
        'Pilih kelas, kemudian pilih narahubung orang tua.'
    );
    renderContacts();
});
