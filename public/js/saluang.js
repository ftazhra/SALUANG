document.addEventListener("DOMContentLoaded", () => {
    const nav = document.querySelector(".main-nav");
    const links = [...document.querySelectorAll(".main-nav a[href^='#']")];
    const indicator = nav?.querySelector(".nav-indicator");
    if (!nav || !indicator || !links.length) return;
    const sections = links
        .map(link => ({
            link,
            section: document.getElementById(link.hash.slice(1))
        }))
        .filter(item => item.section);
    const reducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    );
    function activate(link) {
        links.forEach(item => {
            const active = item === link;
            item.classList.toggle("active", active);
            if (active) {
                item.setAttribute("aria-current", "location");
            } else {
                item.removeAttribute("aria-current");
            }
        });
        // Pindahkan satu garis yang sama secara halus.
        indicator.style.width = `${link.offsetWidth}px`;
        indicator.style.transform = `translateX(${link.offsetLeft}px)`;
    }
    function updateFromScroll() {
        if (!sections.length) return;
        const headerHeight =
            document.querySelector(".site-header")?.offsetHeight || 80;
        const checkpoint = window.scrollY + headerHeight + 110;
        let current = sections[0];
        for (const item of sections) {
            if (item.section.offsetTop <= checkpoint) {
                current = item;
            }
        }
        if (
            window.scrollY + window.innerHeight >=
            document.documentElement.scrollHeight - 5
        ) {
            current = sections[sections.length - 1];
        }
        activate(current.link);
    }
    let scrollingToTarget = false;
    links.forEach(link => {
        link.addEventListener("click", event => {
            const target = document.querySelector(link.hash);
            if (!target) return;
            event.preventDefault();
            // Garis langsung mulai meluncur.
            activate(link);
            // Mencegah garis bolak-balik selama animasi scroll.
            scrollingToTarget = true;
            target.scrollIntoView({
                behavior: reducedMotion.matches ? "auto" : "smooth",
                block: "start"
            });
            history.replaceState(null, "", link.hash);
            // Setelah navigasi, izinkan pembaruan dari scroll kembali.
            // Waktu ini hanya fallback; scrollend menangani penyelesaian.
            if (!("onscrollend" in window)) {
                window.setTimeout(() => {
                    scrollingToTarget = false;
                    updateFromScroll();
                }, 1000);
            }
        });
    });
    let ticking = false;
    window.addEventListener("scroll", () => {
        if (scrollingToTarget || ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            updateFromScroll();
            ticking = false;
        });
    }, { passive: true });
    window.addEventListener("scrollend", () => {
        if (scrollingToTarget) {
            scrollingToTarget = false;
            updateFromScroll();
        }
    });
    window.addEventListener("resize", () => {
        const active = links.find(link => link.classList.contains("active"));
        if (active) activate(active);
    });
    // Atur garis pertama tanpa animasi saat halaman baru dimuat.
    indicator.style.transition = "none";
    updateFromScroll();
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            indicator.style.transition = "";
        });
    });
    // Animasi kartu tetap berjalan seperti sebelumnya.
    const cards = document.querySelectorAll(
        ".benefit-card, .feature-row, .guide-card, .about-grid, .cta-banner"
    );
    if (
        "IntersectionObserver" in window &&
        !reducedMotion.matches
    ) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        cards.forEach(card => {
            card.classList.add("reveal-on-scroll");
            observer.observe(card);
        });
    }
});
/* =====================================================
   FITUR SALUANG — PENANDA PILIHAN AKTIF
   ===================================================== */
document.addEventListener('DOMContentLoaded', function () {
    const featureLinks = document.querySelectorAll(
        '#fitur .feature-links a'
    );
    const backLinks = document.querySelectorAll(
        '#fitur .feature-row a[href="#fitur"]'
    );
    featureLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            featureLinks.forEach(function (item) {
                item.classList.remove('is-active');
                item.removeAttribute('aria-current');
            });
            link.classList.add('is-active');
            link.setAttribute('aria-current', 'location');
        });
    });
    backLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            featureLinks.forEach(function (item) {
                item.classList.remove('is-active');
                item.removeAttribute('aria-current');
            });
        });
    });
});