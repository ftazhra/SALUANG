
/* =====================================================
   SALUANG — NAVIGASI PANDUAN PENGGUNAAN
   ===================================================== */

document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const guideSection = document.getElementById('panduan');
    const guideHome = document.getElementById('saluangGuideHome');

    const guidePages = document.querySelectorAll(
        '[data-guide-page]'
    );

    const openButtons = document.querySelectorAll(
        '[data-guide-open]'
    );

    const backButtons = document.querySelectorAll(
        '[data-guide-back]'
    );

    if (!guideSection || !guideHome || !guidePages.length) {
        return;
    }

    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    );

    function scrollToGuide() {
        guideSection.scrollIntoView({
            behavior: prefersReducedMotion.matches
                ? 'auto'
                : 'smooth',
            block: 'start'
        });
    }

    /* 01. MEMBUKA PANDUAN SESUAI PERAN */

    function openGuide(role) {
        const selectedPage = document.querySelector(
            '[data-guide-page="' + role + '"]'
        );

        if (!selectedPage) {
            return;
        }

        guideHome.hidden = true;

        guidePages.forEach(function (page) {
            page.hidden = page !== selectedPage;
        });

        scrollToGuide();
    }

    /* 02. KEMBALI KE PILIHAN PERAN */

    function closeGuide() {
        guidePages.forEach(function (page) {
            page.hidden = true;
        });

        guideHome.hidden = false;

        scrollToGuide();
    }

    /* 03. EVENT TOMBOL LIHAT PANDUAN */

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openGuide(button.dataset.guideOpen);
        });
    });

    /* 04. EVENT TOMBOL KEMBALI */

    backButtons.forEach(function (button) {
        button.addEventListener('click', closeGuide);
    });

    /* 05. KONDISI AWAL */

    guidePages.forEach(function (page) {
        page.hidden = true;
    });

    guideHome.hidden = false;
});