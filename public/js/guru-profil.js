
/* =====================================================
   SALUANG — PROFIL GURU
   File: public/js/guru-profil.js
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const page = document.getElementById('guruProfilePage');
    if (!page) return;

    const byId = (id) => document.getElementById(id);
    const API = '/guru/profile';

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content;

    /* 01. ELEMEN PROFIL */
    const displayName = byId('gpProfileDisplayName');
    const identitySubtitle = byId('gpProfileIdentitySubtitle');

    const infoName = byId('gpProfileInfoName');
    const infoUsername = byId('gpProfileInfoUsername');
    const infoEmail = byId('gpProfileInfoEmail');
    const infoNip = byId('gpProfileInfoNip');
    const infoSubject = byId('gpProfileInfoSubject');
    const infoSchool = byId('gpProfileInfoSchool');
    const infoPhone = byId('gpProfileInfoPhone');

    const editButton = byId('gpProfileEditButton');
    const editForm = byId('gpProfileEditForm');
    const information = byId('gpProfileInformation');
    const cancelEdit = byId('gpProfileCancelEdit');
    const saveButton = byId('gpProfileSaveButton');
    const editMessage = byId('gpProfileEditMessage');

    const editName = byId('gpProfileEditName');
    const editUsername = byId('gpProfileEditUsername');
    const editEmail = byId('gpProfileEditEmail');
    const editSubject = byId('gpProfileEditSubject');
    const editSchool = byId('gpProfileEditSchool');
    const editPhone = byId('gpProfileEditPhone');

    /* 02. ELEMEN FOTO */
    const avatar = byId('gpProfileAvatar');
    const photoButton = byId('gpProfilePhotoButton');
    const photoInput = byId('gpProfilePhotoInput');
    const deletePhotoButton = byId('gpProfileDeletePhotoButton');
    const photoFilename = byId('gpProfilePhotoFilename');

    /* 03. ELEMEN PASSWORD */
    const passwordButton = byId('gpProfilePasswordButton');
    const passwordForm = byId('gpProfilePasswordForm');
    const passwordBack = byId('gpProfilePasswordBack');
    const passwordSave = byId('gpProfileSavePassword');
    const passwordMessage = byId('gpProfilePasswordMessage');

    const currentPassword = byId('gpProfileCurrentPassword');
    const newPassword = byId('gpProfileNewPassword');
    const confirmPassword = byId('gpProfileConfirmPassword');

    function valueOrDash(value) {
        const text = String(value ?? '').trim();
        return text || 'Belum diisi';
    }

    function showMessage(element, message, error = false) {
        if (!element) return;

        element.textContent = message;
        element.classList.toggle('is-error', error);
    }

    function getErrorMessage(result, fallback) {
        const firstError = result?.errors
            ? Object.values(result.errors).flat()[0]
            : null;

        return firstError || result?.message || fallback;
    }

    /* 04. REQUEST KE LARAVEL */
    async function requestApi(url, method, data, isFile = false) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken || ''
        };

        if (!isFile) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers,
            body: isFile ? data : JSON.stringify(data ?? {})
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                getErrorMessage(
                    result,
                    `Permintaan gagal (HTTP ${response.status}).`
                )
            );
        }

        return result;
    }

    /* 05. BUKA / TUTUP EDIT PROFIL */
    editButton.addEventListener('click', () => {
        information.hidden = true;
        editForm.hidden = false;

        showMessage(editMessage, '');
        editName.focus();
    });

    cancelEdit.addEventListener('click', () => {
        editForm.hidden = true;
        information.hidden = false;

        showMessage(editMessage, '');
    });

    /* 06. SIMPAN INFORMASI PROFIL */
    saveButton.disabled = false;
    saveButton.removeAttribute('title');

    editForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        saveButton.disabled = true;
        showMessage(editMessage, 'Menyimpan perubahan...');

        try {
            const result = await requestApi(API, 'PATCH', {
                name: editName.value.trim(),
                username: editUsername.value.trim(),
                email: editEmail.value.trim() || null,
                mata_pelajaran: editSubject.value.trim() || null,
                sekolah: editSchool.value.trim() || null,
                nomor_telepon: editPhone.value.trim() || null
            });

            const data = result.data || {};

            infoName.textContent = valueOrDash(data.name);
            infoUsername.textContent = valueOrDash(data.username);
            infoEmail.textContent = valueOrDash(data.email);
            infoNip.textContent = valueOrDash(data.nip_nipk);
            infoSubject.textContent = valueOrDash(data.mata_pelajaran);
            infoSchool.textContent = valueOrDash(data.sekolah);
            infoPhone.textContent = valueOrDash(data.nomor_telepon);

            displayName.textContent = valueOrDash(data.name);

            identitySubtitle.textContent =
                `${valueOrDash(data.sekolah)} · ` +
                `${valueOrDash(data.mata_pelajaran)}`;

            editForm.hidden = true;
            information.hidden = false;

            showMessage(editMessage, '');

            // Perbarui bagian topbar setelah data tersimpan.
            document.querySelectorAll('.gd-user-info strong')
                .forEach((element) => {
                    element.textContent = valueOrDash(data.name);
                });

        } catch (error) {
            showMessage(editMessage, error.message, true);
        } finally {
            saveButton.disabled = false;
        }
    });

    /* 07. UNGGAH FOTO PROFIL */
    photoButton.disabled = false;
    photoButton.removeAttribute('title');

    photoButton.addEventListener('click', () => {
        photoInput.click();
    });

    photoInput.addEventListener('change', async () => {
        const file = photoInput.files?.[0];
        if (!file) return;

        const acceptedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (
            !acceptedTypes.includes(file.type) ||
            file.size > 2 * 1024 * 1024
        ) {
            photoFilename.textContent =
                'Gunakan JPG, PNG, atau WebP maksimal 2 MB.';

            photoInput.value = '';
            return;
        }

        photoButton.disabled = true;
        photoFilename.textContent = 'Mengunggah foto...';

        try {
            const formData = new FormData();
            formData.append('photo', file);

            const result = await requestApi(
                `${API}/photo`,
                'POST',
                formData,
                true
            );

            const image = document.createElement('img');

            image.id = 'gpProfileAvatarImage';
            image.alt = 'Foto profil Guru';
            image.src = result.photo_url;

            avatar.replaceChildren(image);

            deletePhotoButton.hidden = false;
            deletePhotoButton.disabled = false;

            photoFilename.textContent =
                'Foto profil berhasil diperbarui.';

        } catch (error) {
            photoFilename.textContent = error.message;
        } finally {
            photoButton.disabled = false;
            photoInput.value = '';
        }
    });

    /* 08. HAPUS FOTO PROFIL */
    if (!deletePhotoButton.hidden) {
        deletePhotoButton.disabled = false;
    }

    deletePhotoButton.addEventListener('click', async () => {
        const confirmed = window.confirm(
            'Yakin ingin menghapus foto profil?'
        );

        if (!confirmed) return;

        deletePhotoButton.disabled = true;
        photoFilename.textContent = 'Menghapus foto...';

        try {
            await requestApi(
                `${API}/photo`,
                'DELETE'
            );

            const initial = document.createElement('span');
            initial.id = 'gpProfileAvatarInitial';

            const name = displayName.textContent.trim();
            initial.textContent =
                Array.from(name)[0]?.toUpperCase() || 'G';

            avatar.replaceChildren(initial);

            deletePhotoButton.hidden = true;
            photoFilename.textContent =
                'Foto profil berhasil dihapus.';

        } catch (error) {
            photoFilename.textContent = error.message;
            deletePhotoButton.disabled = false;
        }
    });

    /* 09. BUKA / TUTUP FORM PASSWORD */
    passwordButton.addEventListener('click', () => {
        passwordForm.hidden = false;
        passwordButton.hidden = true;

        showMessage(passwordMessage, '');
        currentPassword.focus();
    });

    passwordBack.addEventListener('click', () => {
        passwordForm.hidden = true;
        passwordButton.hidden = false;

        passwordForm.reset();
        showMessage(passwordMessage, '');
    });

    /* 10. UBAH KATA SANDI */
    passwordSave.disabled = false;
    passwordSave.removeAttribute('title');

    passwordForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (newPassword.value !== confirmPassword.value) {
            showMessage(
                passwordMessage,
                'Konfirmasi password baru tidak cocok.',
                true
            );
            return;
        }

        passwordSave.disabled = true;
        showMessage(passwordMessage, 'Memperbarui kata sandi...');

        try {
            await requestApi(
                `${API}/password`,
                'PATCH',
                {
                    current_password: currentPassword.value,
                    password: newPassword.value,
                    password_confirmation: confirmPassword.value
                }
            );

            passwordForm.reset();
            passwordForm.hidden = true;
            passwordButton.hidden = false;

            showMessage(passwordMessage, '');

            window.alert('Kata sandi berhasil diperbarui.');

        } catch (error) {
            showMessage(passwordMessage, error.message, true);
        } finally {
            passwordSave.disabled = false;
        }
    });
});
