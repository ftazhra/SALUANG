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
    const chatInput = byId('gpMessageInput');
    const sendButton = byId('gpSendMessage');
    let selectedClass = null;
    let selectedId = null;
    let contacts = [];
    let searchTimer = null;
    let activeRequest = null;
    let requestVersion = 0;
    let chatRequestVersion = 0;
    let pendingMessage = null;
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
        chatRequestVersion += 1;
        pendingMessage = null;
        selectedRelationshipId.value = '';
        contactDetail.hidden = true;
        whatsappLink.hidden = true;
        whatsappLink.removeAttribute('href');
        chatSubtitle.textContent =
            'Pilih narahubung untuk melihat detail kontak.';
        chatInput.disabled = true;
        sendButton.disabled = true;
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
        pendingMessage = null;
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
        chatInput.disabled = false;
        sendButton.disabled = false;
        loadConversation();
        renderContacts();
    }
    /* 05. TAMPILKAN DAFTAR KONTAK */
    function renderContacts() {

        // Tempatkan daftar kontak tepat di bawah kelas yang aktif.
        const activeClassButton = classButtons.find(
            button => button.dataset.gpClass === selectedClass
        );

        if (activeClassButton) {
            activeClassButton.after(contactList);
            contactList.hidden = false;
        } else {
            document.getElementById('gpClassList').after(contactList);
            contactList.hidden = true;
        }

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

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'gp-contact-item';

            if (Number(contact.relationship_id) === selectedId) {
                button.classList.add('active');
            }

            const studentLabel = document.createElement('strong');
            studentLabel.textContent = displayValue(student.name);

            button.appendChild(studentLabel);

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

            renderContacts();

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
            // Tampilkan perubahan dropdown terlebih dahulu.
            renderContacts();

            // Ambil kontak setelah tampilan diperbarui.
            requestAnimationFrame(() => {
                loadContacts();
            });
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
    function newMessageId() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, character => {
            const random = Math.floor(Math.random() * 16);
            return (character === 'x' ? random : (random & 3) | 8).toString(16);
        });
    }
    async function loadConversation() {
        if (!selectedId) return;
        const selectedRelationship = selectedId;
        const version = ++chatRequestVersion;
        chatMessages.replaceChildren(showMessage('Memuat riwayat percakapan...'));
        try {
            const response = await fetch(`${API}/${encodeURIComponent(selectedRelationship)}/messages`, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.message || `Gagal memuat pesan (HTTP ${response.status}).`);
            if (version !== chatRequestVersion || selectedRelationship !== selectedId) return;
            const rows = Array.isArray(result.messages) ? result.messages : [];
            chatMessages.replaceChildren();
            if (!rows.length) {
                chatMessages.append(showMessage('Belum ada pesan. Mulai percakapan dengan Orang Tua.'));
                return;
            }

            rows.forEach(message => {
                const row = document.createElement('div');

                const isGuru = message.is_mine === true;

                row.className = 'gp-chat-row ' +
                    (isGuru ? 'is-guru' : 'is-orang-tua');

                const bubble = document.createElement('div');
                bubble.className = 'gp-chat-bubble';

                const body = document.createElement('p');
                body.className = 'gp-chat-text';
                body.textContent = String(message.body ?? '');

                const meta = document.createElement('small');
                meta.className = 'gp-chat-meta';

                const rawDate = String(message.created_at ?? '');
                const date = new Date(rawDate);

                const formattedDate = !Number.isNaN(date.getTime())
                    ? date.toLocaleString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })
                    : rawDate;

                meta.textContent =
                    `${String(message.sender_name ?? 'Pengguna')} · ${formattedDate}`;

                bubble.append(body, meta);
                row.appendChild(bubble);
                chatMessages.appendChild(row);
            });

            chatMessages.scrollTop = chatMessages.scrollHeight;
        } catch (error) {
            if (version !== chatRequestVersion) return;
            chatMessages.replaceChildren(showMessage(error.message));
        }
    }
    /* 09. KIRIM BALASAN CHAT INTERNAL */
    chatForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = chatInput.value.trim();
        if (!selectedId || !body) return;
        if (!pendingMessage || pendingMessage.body !== body || pendingMessage.relationshipId !== selectedId) {
            pendingMessage = { body, relationshipId: selectedId, id: newMessageId() };
        }
        sendButton.disabled = true;
        try {
            const response = await fetch(`${API}/${encodeURIComponent(selectedId)}/messages`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ body, client_message_id: pendingMessage.id })
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || `Gagal mengirim pesan (HTTP ${response.status}).`);
            pendingMessage = null;
            chatInput.value = '';
            await loadConversation();
        } catch (error) {
            chatMessages.replaceChildren(showMessage(error.message));
        } finally {
            if (selectedId) sendButton.disabled = false;
        }
    });
    /* 10. TAMPILAN AWAL */
    resetContactDetail(
        'Pilih kelas, kemudian pilih narahubung orang tua.'
    );
    renderContacts();
});
