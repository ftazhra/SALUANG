/* =====================================================
   SALUANG — INTERAKSI HALAMAN PROFIL SISWA
   ===================================================== */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const $ = (id) => document.getElementById(id);
    async function readJsonResponse(response, fallback) {
        const contentType = response.headers.get('content-type') || '';
        let result = null;
        if (contentType.includes('application/json')) {
            try {
                result = await response.json();
            } catch {
                result = null;
            }
        }
        if (!response.ok) {
            const firstError = result?.errors
                ? Object.values(result.errors).flat()[0]
                : null;
            throw new Error(
                firstError
                || result?.message
                || (response.redirected
                    ? 'Sesi berakhir atau halaman dialihkan. Muat ulang halaman lalu login kembali.'
                    : response.status === 419
                        ? 'Sesi berakhir. Muat ulang halaman lalu coba kembali.'
                        : response.status === 413
                            ? 'Server menolak foto. Gunakan ukuran maksimal 2 MB; batas hosting mungkin lebih rendah.'
                            : `${fallback} (HTTP ${response.status}).`)
            );
        }
        if (!result || typeof result !== 'object') {
            throw new Error(
                response.redirected
                    ? 'Sesi berakhir atau halaman dialihkan. Muat ulang halaman lalu login kembali.'
                    : `Server tidak mengembalikan respons JSON yang valid (HTTP ${response.status}).`
            );
        }
        return result;
    }
    const profilePage = $('profilePage');
    if (!profilePage) {
        return;
    }
    /* 01. EDIT INFORMASI PRIBADI */
    const information = $('profileInformation');
    const editForm = $('profileEditForm');
    const editButton = $('profileEditButton');
    function closeEditProfile() {
        editForm.hidden = true;
        information.hidden = false;
        editButton.hidden = false;
        editForm.reset();
        $('profileEditMessage').textContent = '';
    }
    editButton.addEventListener('click', function () {
        information.hidden = true;
        editForm.hidden = false;
        editButton.hidden = true;
        $('profileEditMessage').textContent = '';
    });
    $('profileCancelEdit').addEventListener(
        'click',
        closeEditProfile
    );
    editForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const message = $('profileEditMessage');
        const submit = editForm.querySelector('[type="submit"]');
        const payload = {
            name: $('profileEditName').value.trim(),
            username: $('profileEditUsername').value.trim(),
            email: $('profileEditEmail').value.trim() || null,
            kelas: $('profileEditClass').value.trim() || null,
            sekolah: $('profileEditSchool').value.trim() || null,
            nomor_telepon: $('profileEditPhone').value.trim() || null,
        };
        submit.disabled = true;
        message.classList.remove('is-error');
        message.textContent = 'Menyimpan perubahan...';
        try {
            const response = await fetch('/siswa/profil', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content || '',
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(
                    Object.values(result.errors || {}).flat()[0]
                    || result.message
                    || 'Informasi profil gagal disimpan.'
                );
            }

            const student = result.student;
            const display = (value) => value || 'Belum diisi';
            $('profileDisplayName').textContent = display(student.name);
            $('profileInfoName').textContent = display(student.name);
            $('profileInfoUsername').textContent = display(student.username);
            $('profileInfoEmail').textContent = display(student.email);
            $('profileInfoNisn').textContent = display(student.nisn);
            $('profileInfoClass').textContent = display(student.kelas);
            $('profileInfoSchool').textContent = display(student.sekolah);
            $('profileInfoPhone').textContent = display(student.nomor_telepon);
            $('profileDisplaySchoolClass').textContent =
                `${student.sekolah || 'Sekolah belum diisi'} · ${student.kelas || 'Kelas belum diisi'}`;
            message.textContent = result.message;
            closeEditProfile();
            $('profileEditName').value = student.name || '';
            $('profileEditUsername').value = student.username || '';
            $('profileEditEmail').value = student.email || '';
            $('profileEditClass').value = student.kelas || '';
            $('profileEditSchool').value = student.sekolah || '';
            $('profileEditPhone').value = student.nomor_telepon || '';
            $('profileEditMessage').textContent = result.message;
        } catch (error) {
            message.classList.add('is-error');
            message.textContent = error.message;
        } finally {
            submit.disabled = false;
        }
    });
    /* 02. UBAH KATA SANDI */
    const passwordButton = $('profilePasswordButton');
    const passwordForm = $('profilePasswordForm');
    function closePasswordForm() {
        passwordForm.hidden = true;
        passwordButton.hidden = false;
        passwordForm.reset();
        $('profilePasswordMessage').textContent = '';
    }
    passwordButton.addEventListener('click', function () {
        passwordButton.hidden = true;
        passwordForm.hidden = false;
        $('profilePasswordMessage').textContent = '';
        $('profileCurrentPassword').focus();
    });
    $('profilePasswordBack').addEventListener(
        'click',
        closePasswordForm
    );
    passwordForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const password = $('profileNewPassword').value;
        const confirmation = $('profileConfirmPassword').value;
        const message = $('profilePasswordMessage');
        if (password !== confirmation) {
            message.textContent =
                'Konfirmasi password baru tidak sama.';
            message.classList.add('is-error');
            return;
        }
        message.classList.remove('is-error');
        message.textContent =
            'Form sudah siap. Penyimpanan password ke Laravel ' +
            'akan diaktifkan pada tahap berikutnya.';
    });
    /* =====================================================
       03. PRATINJAU DAN SIMPAN FOTO PROFIL
       ===================================================== */
    const photoButton = $('profilePhotoButton');
    const photoInput = $('profilePhotoInput');
    const savePhotoButton = $('profileSavePhotoButton');
    const cancelPhotoButton = $('profileCancelPhotoButton');
    const filename = $('profilePhotoFilename');
    const deletePhotoButton = $('profileDeletePhotoButton');
    const avatar = $('profileAvatar');
    const topbarAvatar = $('sdTopbarAvatar');
    let savingPhoto = false;
    let savedPhotoUrl = avatar.querySelector('img')?.src || null;
    let previewPhotoUrl = null;

    function setInitial() {
        const name = $('profileDisplayName')?.textContent.trim() || 'Siswa';
        const initial = Array.from(name)[0]?.toLocaleUpperCase('id-ID') || 'S';
        const large = document.createElement('span');
        large.id = 'profileAvatarInitial';
        large.textContent = initial;
        avatar.replaceChildren(large);
        if (topbarAvatar) {
            topbarAvatar.dataset.initial = initial;
            topbarAvatar.replaceChildren(document.createTextNode(initial));
        }
    }

    function setPhoto(url) {
        const large = document.createElement('img');
        large.id = 'profileAvatarImage';
        large.alt = 'Foto profil siswa';
        large.src = url;
        large.addEventListener('error', () => setInitial(), { once: true });
        avatar.replaceChildren(large);

        if (topbarAvatar) {
            const small = document.createElement('img');
            small.className = 'sd-topbar-avatar-img';
            small.alt = 'Foto profil siswa';
            small.src = url;
            small.addEventListener('error', () => {
                topbarAvatar.replaceChildren(
                    document.createTextNode(topbarAvatar.dataset.initial || 'S')
                );
            }, { once: true });
            topbarAvatar.replaceChildren(small);
        }
    }

    avatar.querySelector('img')?.addEventListener('error', () => setInitial(), { once: true });
    topbarAvatar?.querySelector('img')?.addEventListener('error', () => {
        topbarAvatar.replaceChildren(document.createTextNode(topbarAvatar.dataset.initial || 'S'));
    }, { once: true });

    function revokePreview() {
        if (previewPhotoUrl) {
            URL.revokeObjectURL(previewPhotoUrl);
            previewPhotoUrl = null;
        }
    }

    function resetPhotoSelection() {
        revokePreview();
        photoInput.value = '';
        savePhotoButton.hidden = true;
        savePhotoButton.disabled = true;
        cancelPhotoButton.hidden = true;
        deletePhotoButton.disabled = false;
        if (savedPhotoUrl) {
            setPhoto(savedPhotoUrl);
        } else {
            setInitial();
        }
    }

    photoButton.addEventListener('click', function () {
        if (!savingPhoto) {
            photoInput.click();
        }
    });
    photoInput.addEventListener('change', function () {
        const file = photoInput.files?.[0];
        if (!file) return;
        // Validasi awal di browser.
        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
        if (!allowedTypes.includes(file.type)) {
            resetPhotoSelection();
            filename.textContent = 'Gunakan gambar JPG, PNG, atau WebP.';
            filename.classList.add('is-error');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            resetPhotoSelection();
            filename.textContent = 'Ukuran foto maksimal 2 MB.';
            filename.classList.add('is-error');
            return;
        }

        revokePreview();
        previewPhotoUrl = URL.createObjectURL(file);
        setPhoto(previewPhotoUrl);
        savePhotoButton.hidden = false;
        savePhotoButton.disabled = false;
        cancelPhotoButton.hidden = false;
        deletePhotoButton.disabled = true;
        filename.classList.remove('is-error');
        filename.textContent = 'Pratinjau siap. Pilih Simpan Foto untuk menerapkan perubahan.';
    });

    savePhotoButton.addEventListener('click', async function () {
        const file = photoInput.files?.[0];
        if (!file || savingPhoto) return;
        const formData = new FormData();
        formData.append('photo', file);
        savingPhoto = true;
        savePhotoButton.disabled = true;
        photoButton.disabled = true;
        filename.classList.remove('is-error');
        filename.textContent = 'Menyimpan foto...';
        try {
            const response = await fetch('/siswa/profil/foto', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content || '',
                },
                body: formData,
            });
            const result = await readJsonResponse(response, 'Foto gagal diunggah.');
            setPhoto(result.photo_url);
            savedPhotoUrl = result.photo_url;
            revokePreview();
            photoInput.value = '';
            savePhotoButton.hidden = true;
            cancelPhotoButton.hidden = true;
            filename.textContent = result.message || 'Foto profil berhasil disimpan.';
            deletePhotoButton.hidden = false;
            deletePhotoButton.disabled = false;
            filename.classList.remove('is-error');
        } catch (error) {
            filename.textContent = error.message;
            filename.classList.add('is-error');
        } finally {
            savingPhoto = false;
            photoButton.disabled = false;
            if (!savePhotoButton.hidden) savePhotoButton.disabled = false;
        }
    });
    cancelPhotoButton.addEventListener('click', resetPhotoSelection);

    /* =====================================================
       04. MENGHAPUS FOTO PROFIL
       ===================================================== */
    const deletePhotoModal = $('profileDeletePhotoModal');
    const deletePhotoCancel = $('profileDeletePhotoCancel');
    const deletePhotoConfirm = $('profileDeletePhotoConfirm');
    const deletePhotoError = $('profileDeletePhotoError');
    function closeDeletePhotoModal() {
        if (deletePhotoConfirm.disabled) return;
        deletePhotoModal.hidden = true;
        deletePhotoError.hidden = true;
        deletePhotoError.textContent = '';
    }
    deletePhotoButton.addEventListener('click', function () {
        deletePhotoError.hidden = true;
        deletePhotoModal.hidden = false;
        deletePhotoCancel.focus();
    });
    deletePhotoCancel.addEventListener(
        'click',
        closeDeletePhotoModal
    );
    deletePhotoModal.querySelector(
        '.sd-profile-photo-backdrop'
    ).addEventListener('click', closeDeletePhotoModal);
    deletePhotoModal.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDeletePhotoModal();
        }
        if (event.key === 'Tab') {
            const controls = [
                deletePhotoCancel,
                deletePhotoConfirm
            ];
            const first = controls[0];
            const last = controls[1];
            if (
                event.shiftKey &&
                document.activeElement === first
            ) {
                event.preventDefault();
                last.focus();
            } else if (
                !event.shiftKey &&
                document.activeElement === last
            ) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    deletePhotoConfirm.addEventListener('click', async function () {
        if (deletePhotoConfirm.disabled) return;
        deletePhotoConfirm.disabled = true;
        deletePhotoCancel.disabled = true;
        deletePhotoConfirm.textContent = 'Menghapus...';
        try {
            const response = await fetch('/siswa/profil/foto', {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content || ''
                }
            });
            const result = await readJsonResponse(response, 'Foto gagal dihapus.');
            // Kembalikan avatar menjadi inisial nama akun.
            setInitial();
            savedPhotoUrl = null;
            revokePreview();
            deletePhotoButton.hidden = true;
            $('profilePhotoFilename').textContent =
                'Foto profil berhasil dihapus.';
            deletePhotoModal.hidden = true;
        } catch (error) {
            deletePhotoError.textContent = error.message;
            deletePhotoError.hidden = false;
        } finally {
            deletePhotoConfirm.disabled = false;
            deletePhotoCancel.disabled = false;
            deletePhotoConfirm.textContent = 'Ya, Hapus';
        }
    });
});