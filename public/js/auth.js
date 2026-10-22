
document.addEventListener('DOMContentLoaded', function () {

    // ===================================================
    // SALUANG - LOGIN DAN DAFTAR
    // ===================================================

    const form = document.querySelector(
        '[data-sl-form], [data-auth-form], #saluang-login-form'
    );

    if (!form) return;

    const formType =
        form.dataset.slForm ||
        form.dataset.authForm ||
        (form.id === 'saluang-login-form' ? 'login' : '');

    const roles = form.querySelectorAll('input[name="role"]');

    // ===================================================
    // FUNGSI UMUM
    // ===================================================

    function getSelectedRole() {
        const selected = form.querySelector(
            'input[name="role"]:checked'
        );

        return selected ? selected.value : 'siswa';
    }

    function findElement(...selectors) {
        for (const selector of selectors) {
            const element = form.querySelector(selector);

            if (element) return element;
        }

        return null;
    }

    function toggleField(field, visible) {
        if (!field) return;

        field.hidden = !visible;

        field.querySelectorAll('input, select, textarea').forEach(
            function (input) {
                input.disabled = !visible;
            }
        );
    }

    // ===================================================
    // LOGIN SESUAI FIGMA
    // ===================================================

    function updateLogin(role) {

        const identifierLabel = findElement(
            '#sl-identifier-label',
            '#identifier-label'
        );

        const identifier = findElement('#identifier');

        const childField = findElement(
            '#sl-child-field',
            '#nisn-anak-field',
            '#child-login-field'
        );

        const childNisn = findElement('#nisn_anak');

        const roleConfig = {
            siswa: {
                label: 'NISN',
                placeholder: 'Masukkan NISN'
            },

            guru: {
                label: 'NIPK',
                placeholder: 'Masukkan NIPK'
            },

            orang_tua: {
                label: 'No. HP/Username',
                placeholder: 'Masukkan No. HP atau username'
            }
        };

        const config = roleConfig[role] || roleConfig.siswa;

        if (identifierLabel) {
            identifierLabel.textContent = config.label;
        }

        if (identifier) {
            identifier.placeholder = config.placeholder;
        }

        // NISN Anak hanya tampil saat Orang Tua dipilih.
        const isParent = role === 'orang_tua';

        toggleField(childField, isParent);

        if (childNisn) {
            childNisn.required = isParent;
        }
    }

    // ===================================================
    // PENDAFTARAN SESUAI FIGMA
    // ===================================================

    function updateRegister(role) {

        // Kolom khusus Siswa, Guru, dan Orang Tua.
        const roleFields = form.querySelectorAll(
            '[data-sl-only], [data-register-role], ' +
            '[data-student-field], [data-guardian-field]'
        );

        roleFields.forEach(function (field) {

            let fieldRole =
                field.dataset.slOnly ||
                field.dataset.registerRole;

            if (field.hasAttribute('data-student-field')) {
                fieldRole = 'siswa';
            }

            if (field.hasAttribute('data-guardian-field')) {
                fieldRole = 'orang_tua';
            }

            toggleField(field, fieldRole === role);
        });

        // Kolom dan validasi sesuai masing-masing peran.
        const nisn = findElement('#nisn');
        const nipk = findElement('#nip_nipk');
        const phone = findElement('#nomor_telepon');

        const childNisn = findElement(
            '#register_nisn_anak',
            '#register-nisn-anak'
        );

        if (nisn) {
            nisn.required = role === 'siswa';
        }

        if (nipk) {
            nipk.required = role === 'guru';
        }

        if (phone) {
            phone.required = role === 'orang_tua';
        }

        if (childNisn) {
            childNisn.required = role === 'orang_tua';
        }

        // Formulir Guru dapat dikirim.
        // Persetujuan akun tetap ditentukan oleh backend.
        const submitButton = findElement(
            '#sl-register-button',
            '#register-submit'
        );

        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Daftar';
        }

        const guruNotice = findElement(
            '#sl-guru-message',
            '#guru-notice'
        );

        if (guruNotice) {
            guruNotice.hidden = true;
        }
    }

    // ===================================================
    // PERGANTIAN PILIHAN PERAN
    // ===================================================

    function updateRole() {
        const role = getSelectedRole();

        if (formType === 'login') {
            updateLogin(role);
        }

        if (formType === 'register') {
            updateRegister(role);
        }
    }

    roles.forEach(function (radio) {
        radio.addEventListener('change', updateRole);
    });

    // Jalankan saat halaman pertama kali terbuka.
    updateRole();

    // ===================================================
    // TAMPILKAN / SEMBUNYIKAN KATA SANDI
    // ===================================================

    form.querySelectorAll(
        '[data-sl-eye], [data-password-toggle]'
    ).forEach(function (button) {

        button.addEventListener('click', function () {

            const targetId =
                button.dataset.slEye ||
                button.dataset.passwordToggle;

            if (!targetId) return;

            const password = document.getElementById(
                targetId
            );

            if (!password) return;

            const showPassword =
                password.type === 'password';

            password.type = showPassword
                ? 'text'
                : 'password';

            button.setAttribute(
                'aria-label',
                showPassword
                    ? 'Sembunyikan kata sandi'
                    : 'Tampilkan kata sandi'
            );

            button.setAttribute(
                'aria-pressed',
                String(showPassword)
            );
        });
    });

});