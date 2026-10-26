/* =====================================================
   SALUANG — INTERAKSI HALAMAN PROFIL SISWA
   Tahap desain: belum menyimpan perubahan ke server.
   ===================================================== */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const $ = (id) => document.getElementById(id);
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
    editForm.addEventListener('submit', function (event) {
        event.preventDefault();
        $('profileEditMessage').textContent =
            'Tampilan Edit Profil sudah siap. ' +
            'Penyimpanan ke database akan diaktifkan pada tahap berikutnya.';
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
       03. UPLOAD FOTO PROFIL KE DATABASE
       ===================================================== */
    const photoButton = $('profilePhotoButton');
    const photoInput = $('profilePhotoInput');
    const filename = $('profilePhotoFilename');
    let uploadingPhoto = false;
    photoButton.addEventListener('click', function () {
        if (!uploadingPhoto) {
            photoInput.click();
        }
    });
    photoInput.addEventListener('change', async function () {
        const file = photoInput.files?.[0];
        if (!file) return;
        // Validasi awal di browser.
        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
        if (!allowedTypes.includes(file.type)) {
            filename.textContent =
                'Gunakan gambar JPG, PNG, atau WebP.';
            photoInput.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            filename.textContent =
                'Ukuran foto maksimal 2 MB.';
            photoInput.value = '';
            return;
        }
        const formData = new FormData();
        formData.append('photo', file);
        uploadingPhoto = true;
        photoButton.disabled = true;
        filename.textContent = 'Mengunggah foto...';
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
            const contentType =
                response.headers.get('content-type') || '';
            const result = contentType.includes('application/json')
                ? await response.json()
                : {};
            if (!response.ok) {
                const validationMessage =
                    result.errors?.photo?.[0];
                throw new Error(
                    validationMessage ||
                    result.message ||
                    'Foto gagal diunggah.'
                );
            }
            const avatar = $('profileAvatar');
            let image = $('profileAvatarImage');
            // Buat elemen gambar jika sebelumnya hanya ada inisial.
            if (!image) {
                image = document.createElement('img');
                image.id = 'profileAvatarImage';
            }
            image.alt = 'Foto profil siswa';
            image.src = result.photo_url;
            avatar.replaceChildren(image);
            filename.textContent =
                'Foto profil berhasil disimpan.';
            $('profileDeletePhotoButton').hidden = false;
        } catch (error) {
            filename.textContent = error.message;
        } finally {
            uploadingPhoto = false;
            photoButton.disabled = false;
            photoInput.value = '';
        }
    });
    /* =====================================================
       04. MENGHAPUS FOTO PROFIL
       ===================================================== */
    const deletePhotoButton = $('profileDeletePhotoButton');
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
            const result = await response.json();
            if (!response.ok) {
                throw new Error(
                    result.message || 'Foto gagal dihapus.'
                );
            }
            // Kembalikan avatar menjadi inisial nama akun.
            const avatar = $('profileAvatar');
            const profileName = $('profileDisplayName')
                ?.textContent.trim() || 'Siswa';
            const initial = document.createElement('span');
            initial.id = 'profileAvatarInitial';
            initial.textContent =
                Array.from(profileName)[0]?.toUpperCase() || 'S';
            avatar.replaceChildren(initial);
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