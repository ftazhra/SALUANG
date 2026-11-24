
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
    const topbarAvatar = byId('gdTopbarAvatar');
    const photoButton = byId('gpProfilePhotoButton');
    const photoInput = byId('gpProfilePhotoInput');
    const savePhotoButton = byId('gpProfileSavePhotoButton');
    const cancelPhotoButton = byId('gpProfileCancelPhotoButton');
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

    function showToast(message, error = false) {
        const existing = document.getElementById('gpAppToast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'gpAppToast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.textContent = message;
        toast.style.position = 'fixed';
        toast.style.right = '20px';
        toast.style.bottom = '20px';
        toast.style.zIndex = '3200';
        toast.style.maxWidth = '360px';
        toast.style.padding = '12px 16px';
        toast.style.borderRadius = '12px';
        toast.style.background = error ? '#fff0ef' : '#eaf7ef';
        toast.style.border = error ? '1px solid #f0c5c5' : '1px solid #c8e7d4';
        toast.style.color = error ? '#a52f2f' : '#17643e';
        toast.style.boxShadow = '0 8px 24px rgba(7, 84, 119, 0.12)';
        toast.style.fontSize = '14px';
        toast.style.fontWeight = '600';
        document.body.appendChild(toast);

        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => toast.remove(), 3500);
    }

    function showConfirmDialog({ title, message, confirmText = 'Ya, Hapus', cancelText = 'Batal', onConfirm }) {
        const wrapper = document.createElement('div');
        wrapper.id = 'gpConfirmDialog';
        wrapper.style.position = 'fixed';
        wrapper.style.inset = '0';
        wrapper.style.zIndex = '3300';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.justifyContent = 'center';
        wrapper.style.background = 'rgba(7, 41, 55, 0.52)';

        const dialog = document.createElement('div');
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'gpConfirmTitle');
        dialog.style.width = 'min(92vw, 420px)';
        dialog.style.background = '#fff';
        dialog.style.borderRadius = '16px';
        dialog.style.padding = '24px';
        dialog.style.boxShadow = '0 16px 48px rgba(7, 41, 55, 0.18)';

        const icon = document.createElement('div');
        icon.innerHTML = '<i class="bi bi-exclamation-triangle" style="font-size: 28px; color: #d44949;"></i>';
        icon.style.display = 'grid';
        icon.style.placeItems = 'center';
        icon.style.width = '58px';
        icon.style.height = '58px';
        icon.style.margin = '0 auto 16px';
        icon.style.borderRadius = '50%';
        icon.style.background = '#fff0e9';

        const heading = document.createElement('h2');
        heading.id = 'gpConfirmTitle';
        heading.textContent = title;
        heading.style.margin = '0 0 12px';
        heading.style.textAlign = 'center';
        heading.style.color = '#075477';
        heading.style.fontSize = '20px';

        const description = document.createElement('p');
        description.textContent = message;
        description.style.margin = '0';
        description.style.textAlign = 'center';
        description.style.color = '#638a96';
        description.style.lineHeight = '1.7';
        description.style.fontSize = '14px';

        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.justifyContent = 'flex-end';
        actions.style.gap = '10px';
        actions.style.marginTop = '22px';

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.textContent = cancelText;
        cancelButton.className = 'gm-btn gm-btn-outline';
        cancelButton.addEventListener('click', () => wrapper.remove());

        const confirmButton = document.createElement('button');
        confirmButton.type = 'button';
        confirmButton.textContent = confirmText;
        confirmButton.className = 'gm-btn gm-btn-danger';
        confirmButton.addEventListener('click', async () => {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Memproses...';
            try {
                if (typeof onConfirm === 'function') {
                    await onConfirm();
                }
            } finally {
                wrapper.remove();
            }
        });

        actions.append(cancelButton, confirmButton);
        dialog.append(icon, heading, description, actions);
        wrapper.appendChild(dialog);
        document.body.appendChild(wrapper);
        cancelButton.focus();

        wrapper.addEventListener('click', event => {
            if (event.target === wrapper) wrapper.remove();
        });
        document.addEventListener('keydown', function onKeydown(event) {
            if (event.key === 'Escape' && document.body.contains(wrapper)) {
                wrapper.remove();
                document.removeEventListener('keydown', onKeydown);
            }
        }, { once: true });
    }

    function getErrorMessage(result, fallback) {
        const firstError = result?.errors
            ? Object.values(result.errors).flat()[0]
            : null;

        return firstError || result?.message || fallback;
    }


    // Avatar topbar dan halaman Profil selalu menunjukkan foto yang sama.
    function initialFromName(name) {
        return Array.from(String(name || '').trim())[0]?.toLocaleUpperCase('id-ID') || 'G';
    }

    function setAvatarInitial(name) {
        const initial = initialFromName(name);
        const largeInitial = document.createElement('span');
        largeInitial.id = 'gpProfileAvatarInitial';
        largeInitial.textContent = initial;
        avatar.replaceChildren(largeInitial);
        if (topbarAvatar) {
            topbarAvatar.dataset.initial = initial;
            topbarAvatar.textContent = initial;
        }
    }

    function setAvatarPhoto(url) {
        if (typeof url !== 'string' || !url.trim()) {
            throw new Error('URL foto dari server kosong.');
        }
        // Tempatkan gambar ke avatar besar dan kecil; jika gagal, inisial tetap terlihat.
        const large = document.createElement('img');
        large.id = 'gpProfileAvatarImage';
        large.alt = 'Foto profil Guru';
        large.src = url;
        large.addEventListener('error', () => {
            setAvatarInitial(displayName?.textContent || 'G');
            showMessage(photoFilename, 'Foto gagal dimuat dari penyimpanan publik.', true);
        }, { once: true });
        avatar.replaceChildren(large);

        if (topbarAvatar) {
            const small = document.createElement('img');
            small.alt = 'Foto profil Guru';
            small.src = url;
            small.addEventListener('error', () => {
                topbarAvatar.textContent = topbarAvatar.dataset.initial || 'G';
            }, { once: true });
            topbarAvatar.replaceChildren(small);
        }
    }

    // Bila foto lama hilang dari storage, tampilkan inisial nama.
    topbarAvatar?.querySelector('img')?.addEventListener('error', () => {
        topbarAvatar.textContent = topbarAvatar.dataset.initial || 'G';
    }, { once: true });
    avatar?.querySelector('img')?.addEventListener('error', () => {
        setAvatarInitial(displayName?.textContent || 'G');
    }, { once: true });

    let savedPhotoUrl = avatar.querySelector('img')?.src || null;
    let previewPhotoUrl = null;

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
            setAvatarPhoto(savedPhotoUrl);
        } else {
            setAvatarInitial(displayName?.textContent || 'G');
        }
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
            const fallback = response.redirected
                ? 'Sesi berakhir atau halaman dialihkan. Muat ulang halaman lalu login kembali.'
                : response.status === 419
                    ? 'Sesi berakhir. Muat ulang halaman lalu coba kembali.'
                    : response.status === 413
                        ? 'Server menolak foto. Gunakan ukuran maksimal 2 MB; batas hosting mungkin lebih rendah.'
                        : `Permintaan gagal (HTTP ${response.status}).`;
            throw new Error(getErrorMessage(result, fallback));
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
            if (!avatar.querySelector('img')) setAvatarInitial(data.name);

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

            const topbarRole = byId('gdTopbarRole');
            if (topbarRole) {
                const subject = String(data.mata_pelajaran ?? '').trim();
                topbarRole.textContent = subject ? `Guru ${subject}` : 'Guru';
            }

        } catch (error) {
            showMessage(editMessage, error.message, true);
        } finally {
            saveButton.disabled = false;
        }
    });

    /* 07. PILIH DAN SIMPAN FOTO PROFIL */
    photoButton.disabled = false;
    photoButton.removeAttribute('title');

    photoButton.addEventListener('click', () => {
        photoInput.click();
    });

    photoInput.addEventListener('change', () => {
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
            resetPhotoSelection();
            showMessage(photoFilename, 'Gunakan JPG, PNG, atau WebP maksimal 2 MB.', true);
            return;
        }

        revokePreview();
        previewPhotoUrl = URL.createObjectURL(file);
        setAvatarPhoto(previewPhotoUrl);
        savePhotoButton.hidden = false;
        savePhotoButton.disabled = false;
        cancelPhotoButton.hidden = false;
        deletePhotoButton.disabled = true;
        showMessage(photoFilename, 'Pratinjau siap. Pilih Simpan Foto untuk menerapkan perubahan.');
    });

    savePhotoButton.addEventListener('click', async () => {
        const file = photoInput.files?.[0];
        if (!file || savePhotoButton.disabled) return;

        savePhotoButton.disabled = true;
        photoButton.disabled = true;
        showMessage(photoFilename, 'Menyimpan foto...');

        try {
            const formData = new FormData();
            formData.append('photo', file);

            const result = await requestApi(
                `${API}/photo`,
                'POST',
                formData,
                true
            );

            setAvatarPhoto(result.photo_url);
            savedPhotoUrl = result.photo_url;
            revokePreview();
            photoInput.value = '';
            savePhotoButton.hidden = true;
            cancelPhotoButton.hidden = true;

            deletePhotoButton.hidden = false;
            deletePhotoButton.disabled = false;

            showMessage(photoFilename, result.message || 'Foto profil berhasil diperbarui.');

        } catch (error) {
            showMessage(photoFilename, error.message, true);
        } finally {
            photoButton.disabled = false;
            if (!savePhotoButton.hidden) savePhotoButton.disabled = false;
        }
    });

    cancelPhotoButton.addEventListener('click', resetPhotoSelection);

    /* 08. HAPUS FOTO PROFIL */
    if (!deletePhotoButton.hidden) {
        deletePhotoButton.disabled = false;
    }

    deletePhotoButton.addEventListener('click', async () => {
        showConfirmDialog({
            title: 'Hapus Foto Profil?',
            message: 'Apakah Anda yakin ingin menghapus foto profil ini?',
            confirmText: 'Ya, Hapus',
            onConfirm: async () => {
                deletePhotoButton.disabled = true;
                photoFilename.textContent = 'Menghapus foto...';

                try {
                    await requestApi(
                        `${API}/photo`,
                        'DELETE'
                    );

                    setAvatarInitial(displayName.textContent.trim());
                    savedPhotoUrl = null;
                    revokePreview();

                    deletePhotoButton.hidden = true;
                    photoFilename.textContent = 'Foto profil berhasil dihapus.';
                } catch (error) {
                    photoFilename.textContent = error.message;
                    deletePhotoButton.disabled = false;
                    throw error;
                }
            }
        });
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

            showToast('Kata sandi berhasil diperbarui.');

        } catch (error) {
            showMessage(passwordMessage, error.message, true);
        } finally {
            passwordSave.disabled = false;
        }
    });
});
