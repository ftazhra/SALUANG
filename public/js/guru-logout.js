/* SALUANG — Konfirmasi Logout Guru */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const modal = document.getElementById('gdLogoutModal');
    const dialog = modal?.querySelector('.gd-logout-dialog');
    const confirmButton = document.getElementById('gdLogoutConfirm');
    const forms = document.querySelectorAll('[data-gd-logout-form]');
    if (!modal || !dialog || !confirmButton || !forms.length) return;
    let activeForm = null;
    let previousFocus = null;
    let submitting = false;
    const close = () => {
        if (submitting) return;
        modal.hidden = true;
        document.body.classList.remove('gd-logout-open');
        activeForm = null;
        if (previousFocus?.isConnected) previousFocus.focus();
    };
    forms.forEach(form => form.addEventListener('submit', event => {
        if (submitting) return;
        event.preventDefault();
        activeForm = form;
        previousFocus = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('gd-logout-open');
        dialog.focus();
    }));
    modal.querySelectorAll('[data-gd-logout-cancel]').forEach(button => {
        button.addEventListener('click', close);
    });
    confirmButton.addEventListener('click', () => {
        if (!activeForm || submitting) return;
        submitting = true;
        confirmButton.disabled = true;
        // Native submit bypasses interception and retains CSRF form token.
        HTMLFormElement.prototype.submit.call(activeForm);
    });
    document.addEventListener('keydown', event => {
        if (modal.hidden) return;
        if (event.key === 'Escape') { event.preventDefault(); close(); return; }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not(:disabled)')];
        const first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    });
});
