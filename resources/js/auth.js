document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("[data-sl-form]");

    if (!form) return;

    const formType = form.dataset.slForm;

    const roleRadios = form.querySelectorAll(
        'input[name="role"]'
    );

    function selectedRole() {
        const checked = form.querySelector(
            'input[name="role"]:checked'
        );

        return checked ? checked.value : "siswa";
    }

    function showField(field, visible) {
        if (!field) return;

        field.hidden = !visible;

        field.querySelectorAll("input").forEach(function (input) {
            input.disabled = !visible;
        });
    }

    function updateLogin(role) {
        const label = document.getElementById(
            "sl-identifier-label"
        );

        const input = document.getElementById(
            "identifier"
        );

        const childField = document.getElementById(
            "sl-child-field"
        );

        const childInput = document.getElementById(
            "nisn_anak"
        );

        const labels = {
            siswa: {
                label: "NISN",
                placeholder: "Masukkan NISN"
            },
            guru: {
                label: "NIPK",
                placeholder: "Masukkan NIPK"
            },
            orang_tua: {
                label: "No. HP/Username",
                placeholder: "Masukkan No. HP atau username"
            }
        };

        if (label) {
            label.textContent = labels[role].label;
        }

        if (input) {
            input.placeholder = labels[role].placeholder;
        }

        const parent = role === "orang_tua";

        showField(childField, parent);

        if (childInput) {
            childInput.required = parent;
        }
    }

    function updateRegister(role) {
        const studentFields = form.querySelectorAll(
            '[data-sl-only="siswa"]'
        );

        const parentFields = form.querySelectorAll(
            '[data-sl-only="orang_tua"]'
        );

        studentFields.forEach(function (field) {
            showField(field, role === "siswa");
        });

        parentFields.forEach(function (field) {
            showField(field, role === "orang_tua");
        });

        const nisn = document.getElementById("nisn");
        const phone = document.getElementById("nomor_telepon");
        const childNisn = document.getElementById(
            "register_nisn_anak"
        );

        if (nisn) {
            nisn.required = role === "siswa";
        }

        if (phone) {
            phone.required = role === "orang_tua";
        }

        if (childNisn) {
            childNisn.required = role === "orang_tua";
        }

        const guruMessage = document.getElementById(
            "sl-guru-message"
        );

        const submit = document.getElementById(
            "sl-register-button"
        );

        if (guruMessage) {
            guruMessage.hidden = role !== "guru";
        }

        if (submit) {
            submit.disabled = role === "guru";

            submit.textContent = role === "guru"
                ? "Pendaftaran Guru Memerlukan Verifikasi"
                : "Daftar";
        }
    }

    function updateRole() {
        const role = selectedRole();

        if (formType === "login") {
            updateLogin(role);
        }

        if (formType === "register") {
            updateRegister(role);
        }
    }

    roleRadios.forEach(function (radio) {
        radio.addEventListener("change", updateRole);
    });

    updateRole();

    // Tampilkan / sembunyikan kata sandi
    document.querySelectorAll("[data-sl-eye]").forEach(
        function (button) {
            button.addEventListener("click", function () {
                const input = document.getElementById(
                    button.dataset.slEye
                );

                if (!input) return;

                const visible = input.type === "password";

                input.type = visible ? "text" : "password";

                button.setAttribute(
                    "aria-label",
                    visible
                        ? "Sembunyikan kata sandi"
                        : "Tampilkan kata sandi"
                );
            });
        }
    );
});