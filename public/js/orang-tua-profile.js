document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const page = document.querySelector('[data-parent-section="profil"]');
    if (!page) return;

    const byId = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const editForm = byId('parentProfileEditForm');
    const information = byId('parentProfileInformation');
    const passwordForm = byId('parentProfilePasswordForm');
    const avatar = byId('parentProfileAvatar');
    const topbarAvatar = byId('parentTopbarAvatar');

    function message(element, value, isError = false) {
        element.textContent = value;
        element.classList.toggle('is-error', isError);
    }

    function showToast(message, isError = false) {
        const existing = document.getElementById('parentToast');
        if (existing) existing.remove();
        const toast = document.createElement('div');
        toast.id = 'parentToast';
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
        toast.style.background = isError ? '#fff0ef' : '#eaf7ef';
        toast.style.border = isError ? '1px solid #f0c5c5' : '1px solid #c8e7d4';
        toast.style.color = isError ? '#a52f2f' : '#17643e';
        toast.style.boxShadow = '0 8px 24px rgba(7, 84, 119, 0.12)';
        toast.style.fontSize = '14px';
        toast.style.fontWeight = '600';
        document.body.appendChild(toast);
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => toast.remove(), 3500);
    }

    function showConfirmDialog({ title, message: bodyMessage, confirmText = 'Ya, Hapus', cancelText = 'Batal', onConfirm }) {
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.zIndex = '3300';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.background = 'rgba(7, 41, 55, 0.52)';

        const dialog = document.createElement('div');
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-labelledby', 'parentConfirmTitle');
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
        heading.id = 'parentConfirmTitle';
        heading.textContent = title;
        heading.style.margin = '0 0 12px';
        heading.style.textAlign = 'center';
        heading.style.color = '#075477';
        heading.style.fontSize = '20px';

        const paragraph = document.createElement('p');
        paragraph.textContent = bodyMessage;
        paragraph.style.margin = '0';
        paragraph.style.textAlign = 'center';
        paragraph.style.color = '#638a96';
        paragraph.style.fontSize = '14px';
        paragraph.style.lineHeight = '1.7';

        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.justifyContent = 'flex-end';
        actions.style.gap = '10px';
        actions.style.marginTop = '22px';

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.textContent = cancelText;
        cancelButton.className = 'gm-btn gm-btn-outline';
        cancelButton.addEventListener('click', () => overlay.remove());

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
                overlay.remove();
            }
        });

        actions.append(cancelButton, confirmButton);
        dialog.append(icon, heading, paragraph, actions);
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);
        cancelButton.focus();

        overlay.addEventListener('click', event => {
            if (event.target === overlay) overlay.remove();
        });
        document.addEventListener('keydown', function onKeydown(event) {
            if (event.key === 'Escape' && document.body.contains(overlay)) {
                overlay.remove();
                document.removeEventListener('keydown', onKeydown);
            }
        }, { once: true });
    }

    function request(url, method, data, file = false) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf
        };
        if (!file) headers['Content-Type'] = 'application/json';
        return fetch(url, {
            method,
            credentials: 'same-origin',
            headers,
            body: file ? data : JSON.stringify(data ?? {})
        }).then(async response => {
            let result;
            try {
                result = await response.json();
            } catch {
                result = null;
            }
            if (!result || typeof result !== 'object') {
                throw new Error(
                    response.redirected
                        ? 'Sesi berakhir atau halaman dialihkan. Muat ulang halaman lalu login kembali.'
                        : response.status === 419
                            ? 'Sesi berakhir. Muat ulang halaman lalu coba kembali.'
                            : response.status === 413
                                ? 'Server menolak foto. Gunakan ukuran maksimal 2 MB; batas hosting mungkin lebih rendah.'
                                : `Server tidak mengembalikan respons JSON yang valid (HTTP ${response.status}).`
                );
            }
            if (!response.ok) {
                if (response.status === 413) {
                    throw new Error('Server menolak foto. Gunakan ukuran maksimal 2 MB; batas hosting mungkin lebih rendah.');
                }
                if (response.redirected || response.status === 419) {
                    throw new Error('Sesi berakhir atau halaman dialihkan. Muat ulang halaman lalu login kembali.');
                }
                const first = result.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(first || result.message || `Permintaan gagal (HTTP ${response.status}).`);
            }
            return result;
        });
    }

    function setInitial(name) {
        const initial = Array.from(String(name || '').trim())[0]?.toLocaleUpperCase('id-ID') || 'O';
        const label = document.createElement('span');
        label.id = 'parentProfileAvatarInitial';
        label.textContent = initial;
        avatar.replaceChildren(label);
        topbarAvatar.dataset.initial = initial;
        topbarAvatar.replaceChildren(document.createTextNode(initial));
    }

    function setPhoto(url) {
        const large = document.createElement('img');
        large.id = 'parentProfileAvatarImage';
        large.alt = 'Foto profil Orang Tua';
        large.src = url;
        large.addEventListener('error', () => setInitial(byId('parentProfileDisplayName').textContent), { once: true });
        avatar.replaceChildren(large);
        const small = document.createElement('img');
        small.alt = 'Foto profil Orang Tua';
        small.src = url;
        small.addEventListener('error', () => {
            topbarAvatar.replaceChildren(document.createTextNode(topbarAvatar.dataset.initial || 'O'));
        }, { once: true });
        topbarAvatar.replaceChildren(small);
    }

    avatar.querySelector('img')?.addEventListener('error', () => {
        setInitial(byId('parentProfileDisplayName').textContent);
    }, { once: true });
    topbarAvatar.querySelector('img')?.addEventListener('error', () => {
        topbarAvatar.replaceChildren(document.createTextNode(topbarAvatar.dataset.initial || 'O'));
    }, { once: true });

    byId('parentProfileEditButton').addEventListener('click', () => {
        information.hidden = true;
        byId('parentProfileEditButton').hidden = true;
        editForm.hidden = false;
        message(byId('parentProfileEditMessage'), '');
        byId('parentProfileName').focus();
    });

    byId('parentProfileCancelEdit').addEventListener('click', () => {
        editForm.reset();
        editForm.hidden = true;
        information.hidden = false;
        byId('parentProfileEditButton').hidden = false;
        message(byId('parentProfileEditMessage'), '');
    });

    editForm.addEventListener('submit', async event => {
        event.preventDefault();
        const save = byId('parentProfileSave');
        save.disabled = true;
        message(byId('parentProfileEditMessage'), 'Menyimpan perubahan...');
        try {
            const result = await request('/orang-tua/profile', 'PATCH', Object.fromEntries(new FormData(editForm)));
            const data = result.data;
            byId('parentProfileInfoName').textContent = data.name || '-';
            byId('parentProfileInfoUsername').textContent = data.username || '-';
            byId('parentProfileInfoEmail').textContent = data.email || '-';
            byId('parentProfileInfoPhone').textContent = data.nomor_telepon || '-';
            byId('parentProfileDisplayName').textContent = data.name;
            byId('parentProfileName').value = data.name;
            byId('parentProfileUsername').value = data.username;
            byId('parentProfileEmail').value = data.email || '';
            byId('parentProfilePhone').value = data.nomor_telepon || '';
            document.querySelectorAll('.parent-user-info strong').forEach(element => { element.textContent = data.name; });
            if (!avatar.querySelector('img')) setInitial(data.name);
            editForm.hidden = true;
            information.hidden = false;
            byId('parentProfileEditButton').hidden = false;
            message(byId('parentProfileEditMessage'), '');
            message(byId('parentProfileStatus'), result.message);
        } catch (error) {
            message(byId('parentProfileEditMessage'), error.message, true);
        } finally {
            save.disabled = false;
        }
    });

    const passwordButton = byId('parentProfilePasswordButton');
    passwordButton.addEventListener('click', () => {
        passwordButton.hidden = true;
        passwordForm.hidden = false;
        message(byId('parentProfilePasswordMessage'), '');
        passwordForm.elements.current_password.focus();
    });
    byId('parentProfilePasswordCancel').addEventListener('click', () => {
        passwordForm.reset();
        passwordForm.hidden = true;
        passwordButton.hidden = false;
        message(byId('parentProfilePasswordMessage'), '');
    });
    passwordForm.addEventListener('submit', async event => {
        event.preventDefault();
        const save = byId('parentProfilePasswordSave');
        save.disabled = true;
        message(byId('parentProfilePasswordMessage'), 'Memperbarui kata sandi...');
        try {
            const result = await request('/orang-tua/profile/password', 'PATCH', Object.fromEntries(new FormData(passwordForm)));
            passwordForm.reset();
            message(byId('parentProfilePasswordMessage'), result.message);
        } catch (error) {
            message(byId('parentProfilePasswordMessage'), error.message, true);
        } finally {
            save.disabled = false;
        }
    });

    const photoInput = byId('parentProfilePhotoInput');
    const photoButton = byId('parentProfilePhotoButton');
    const savePhotoButton = byId('parentProfileSavePhotoButton');
    const cancelPhotoButton = byId('parentProfileCancelPhotoButton');
    const deletePhotoButton = byId('parentProfileDeletePhotoButton');
    const photoStatus = byId('parentProfilePhotoMessage');
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
            setPhoto(savedPhotoUrl);
        } else {
            setInitial(byId('parentProfileDisplayName').textContent);
        }
    }

    photoButton.addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', () => {
        const file = photoInput.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            resetPhotoSelection();
            message(photoStatus, 'Gunakan JPG, PNG, atau WebP maksimal 2 MB.', true);
            return;
        }

        revokePreview();
        previewPhotoUrl = URL.createObjectURL(file);
        setPhoto(previewPhotoUrl);
        savePhotoButton.hidden = false;
        savePhotoButton.disabled = false;
        cancelPhotoButton.hidden = false;
        deletePhotoButton.disabled = true;
        message(photoStatus, 'Pratinjau siap. Pilih Simpan Foto untuk menerapkan perubahan.');
    });

    savePhotoButton.addEventListener('click', async () => {
        const file = photoInput.files?.[0];
        if (!file || savePhotoButton.disabled) return;
        savePhotoButton.disabled = true;
        photoButton.disabled = true;
        message(photoStatus, 'Menyimpan foto...');
        try {
            const data = new FormData();
            data.append('photo', file);
            const result = await request('/orang-tua/profile/photo', 'POST', data, true);
            setPhoto(result.photo_url);
            savedPhotoUrl = result.photo_url;
            revokePreview();
            photoInput.value = '';
            savePhotoButton.hidden = true;
            cancelPhotoButton.hidden = true;
            deletePhotoButton.hidden = false;
            deletePhotoButton.disabled = false;
            message(photoStatus, result.message);
        } catch (error) {
            message(photoStatus, error.message, true);
        } finally {
            photoButton.disabled = false;
            if (!savePhotoButton.hidden) savePhotoButton.disabled = false;
        }
    });

    cancelPhotoButton.addEventListener('click', resetPhotoSelection);

    deletePhotoButton.addEventListener('click', async event => {
        const button = event.currentTarget;
        const status = photoStatus;
        showConfirmDialog({
            title: 'Hapus Foto Profil?',
            message: 'Apakah Anda yakin ingin menghapus foto profil ini?',
            confirmText: 'Ya, Hapus',
            onConfirm: async () => {
                button.disabled = true;
                try {
                    const result = await request('/orang-tua/profile/photo', 'DELETE');
                    setInitial(byId('parentProfileDisplayName').textContent);
                    savedPhotoUrl = null;
                    revokePreview();
                    button.hidden = true;
                    status.textContent = result.message;
                } catch (error) {
                    status.textContent = error.message;
                    button.disabled = false;
                    throw error;
                }
            }
        });
    });
});
