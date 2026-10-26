document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const modal = document.getElementById('sdLogoutModal');
    const dialog = modal?.querySelector('.sd-logout-dialog');
    const cancel = document.getElementById('sdLogoutCancel');
    const close = document.getElementById('sdLogoutClose');
    const confirm = document.getElementById('sdLogoutConfirm');
    if (!modal || !dialog || !cancel || !close || !confirm) {
        return;
    }
    let pendingForm = null;
    let previousFocus = null;
    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('sd-logout-open');
        pendingForm = null;
        if (previousFocus?.isConnected) {
            previousFocus.focus();
        }
    }
    // Semua formulir logout memakai satu konfirmasi.
    document.querySelectorAll('[data-sd-logout-form]')
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                pendingForm = form;
                previousFocus = document.activeElement;
                modal.hidden = false;
                document.body.classList.add('sd-logout-open');
                cancel.focus();
            });
        });
    cancel.addEventListener('click', closeModal);
    close.addEventListener('click', closeModal);
    modal.querySelector('.sd-logout-backdrop')
        .addEventListener('click', closeModal);
    document.addEventListener('keydown', function (event) {
        if (modal.hidden) return;
        if (event.key === 'Escape') {
            closeModal();
        }
        if (event.key === 'Tab') {
            const controls = [close, cancel, confirm]
                .filter(button => !button.disabled);
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    confirm.addEventListener('click', function () {
        if (!pendingForm) return;
        const form = pendingForm;
        confirm.disabled = true;
        confirm.textContent = 'Keluar...';
        // Mengirim form POST Laravel yang asli beserta token CSRF.
        form.submit();
    });
});