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
                throw new Error('Server tidak mengembalikan respons JSON yang valid.');
            }
            if (!response.ok) {
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
    photoButton.addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', async () => {
        const file = photoInput.files?.[0];
        if (!file) return;
        const status = byId('parentProfilePhotoMessage');
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            status.textContent = 'Gunakan JPG, PNG, atau WebP maksimal 2 MB.';
            photoInput.value = '';
            return;
        }
        photoButton.disabled = true;
        status.textContent = 'Mengunggah foto...';
        try {
            const data = new FormData();
            data.append('photo', file);
            const result = await request('/orang-tua/profile/photo', 'POST', data, true);
            setPhoto(result.photo_url);
            byId('parentProfileDeletePhotoButton').hidden = false;
            status.textContent = result.message;
        } catch (error) {
            status.textContent = error.message;
        } finally {
            photoButton.disabled = false;
            photoInput.value = '';
        }
    });

    byId('parentProfileDeletePhotoButton').addEventListener('click', async event => {
        if (!window.confirm('Hapus foto profil Orang Tua?')) return;
        const button = event.currentTarget;
        const status = byId('parentProfilePhotoMessage');
        button.disabled = true;
        try {
            const result = await request('/orang-tua/profile/photo', 'DELETE');
            setInitial(byId('parentProfileDisplayName').textContent);
            button.hidden = true;
            status.textContent = result.message;
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });
});
