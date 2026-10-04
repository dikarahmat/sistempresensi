<div id="smart-page-loader" class="loader-overlay" style="display: none;">
    <div class="app-loader" role="status" aria-label="Memuat"></div>
</div>

<style>
    /* Overlay loading penuh layar: HITAM transparan (alpha 0.50) sehingga halaman
       di belakang masih samar terlihat tanpa tint biru dari loader itu sendiri. */
    .loader-overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        z-index: 999999999 !important;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.50);
        pointer-events: auto;
        opacity: 1;
        transition: opacity 250ms ease;
    }

    .loader-overlay.is-hiding {
        opacity: 0;
    }

    /* Cincin spinner (hanya animasi transform -> murah). */
    .app-loader {
        width: 50px;
        padding: 8px;
        aspect-ratio: 1;
        border-radius: 50%;
        background: #ffffff;
        --_m:
            conic-gradient(#0000 10%, #000),
            linear-gradient(#000 0 0) content-box;
        -webkit-mask: var(--_m);
        mask: var(--_m);
        -webkit-mask-composite: source-out;
        mask-composite: subtract;
        animation: app-spin 1s infinite linear;
    }

    /* Varian kecil (dalam tombol/area sempit): lebar & padding dikecilkan. */
    .app-loader--sm {
        width: 22px;
        padding: 3px;
    }

    @keyframes app-spin {
        to { transform: rotate(1turn); }
    }

    /* Reduced motion: animasi diperlambat sangat pelan, indikator TETAP tampil. */
    @media (prefers-reduced-motion: reduce) {
        .app-loader {
            animation-duration: 5s;
        }
    }
</style>

<script>
(function() {
    'use strict';

    let loaderTimeout = null;
    let fadeTimeout = null;
    let safetyTimeout = null;
    const DELAY_MS = 100;
    const FADE_MS = 250;
    // Jaringan/macet total: paksa sembunyikan agar overlay tidak pernah menutupi halaman.
    const SAFETY_MS = 15000;

    function getLoader() {
        return document.getElementById('smart-page-loader');
    }

    function showLoaderWithDelay() {
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
        }
        loaderTimeout = setTimeout(function() {
            const loader = getLoader();
            if (loader) {
                if (fadeTimeout) {
                    clearTimeout(fadeTimeout);
                    fadeTimeout = null;
                }
                loader.classList.remove('is-hiding');
                loader.style.display = 'flex';

                if (safetyTimeout) {
                    clearTimeout(safetyTimeout);
                }
                safetyTimeout = setTimeout(hideLoader, SAFETY_MS);
            }
        }, DELAY_MS);
    }

    function hideLoader() {
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
            loaderTimeout = null;
        }
        if (safetyTimeout) {
            clearTimeout(safetyTimeout);
            safetyTimeout = null;
        }
        const loader = getLoader();
        if (!loader || loader.style.display === 'none') {
            return;
        }
        // Fade out halus 250ms sebelum overlay dilepas.
        loader.classList.add('is-hiding');
        if (fadeTimeout) {
            clearTimeout(fadeTimeout);
        }
        fadeTimeout = setTimeout(function() {
            loader.style.display = 'none';
            loader.classList.remove('is-hiding');
            fadeTimeout = null;
        }, FADE_MS);
    }

    function isDownloadDestination(href) {
        try {
            if (!href) return false;
            const url = new URL(href, window.location.href);
            const path = url.pathname.toLowerCase();
            const downloadRoute = /(?:^|\/)(?:export|download|template|print-?cards?|generate-qr|import)(?:[-/._]|$)/i.test(path);
            const fileExtension = /\.(?:pdf|xlsx?|csv|zip|docx?|png|jpe?g|webp|svg)(?:$|\/)/i.test(path);
            const downloadParameter = ['download', 'attachment', 'export', 'pdf'].some(function(key) {
                return url.searchParams.has(key);
            });

            return downloadRoute || fileExtension || downloadParameter;
        } catch (error) {
            return false;
        }
    }

    window.addEventListener('load', hideLoader);
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            hideLoader();
        }
    });

    document.addEventListener('click', function(event) {
        const target = event.target;
        const anchor = target instanceof Element ? target.closest('a') : null;
        if (!anchor) return;

        const href = anchor.getAttribute('href');
        const linkTarget = anchor.getAttribute('target');
        const normalizedHref = href ? href.trim().toLowerCase() : '';

        if (!normalizedHref || normalizedHref.includes('#') || normalizedHref.startsWith('javascript:') || linkTarget === '_blank') {
            return;
        }

        if (anchor.hasAttribute('download') || anchor.hasAttribute('data-no-loader') || anchor.hasAttribute('data-download') || anchor.hasAttribute('data-import') || anchor.closest('[data-download]') || anchor.closest('[data-import]') || isDownloadDestination(href) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }

        const sidebar = anchor.closest('.app-sidebar-drawer');
        if (sidebar) {
            try {
                const sidebarState = window.Alpine && window.Alpine.$data(document.body);
                if (sidebarState && typeof sidebarState.sidebarOpen !== 'undefined') {
                    sidebarState.sidebarOpen = false;
                } else {
                    sidebar.classList.remove('mobile-sidebar-active');
                }
            } catch (error) {
                sidebar.classList.remove('mobile-sidebar-active');
            }
        }

        showLoaderWithDelay();
    });

    document.addEventListener('submit', function(event) {
        const form = event.target;
        const submitter = event.submitter;
        const action = form instanceof HTMLFormElement ? (form.getAttribute('action') || window.location.href) : '';
        if (form instanceof HTMLFormElement) {
            if (form.hasAttribute('download') || form.hasAttribute('data-no-loader') || form.hasAttribute('data-download') || form.hasAttribute('data-import-form') || form.hasAttribute('data-import') || isDownloadDestination(action)) {
                return;
            }
            if (submitter && (submitter.hasAttribute('download') || submitter.hasAttribute('data-no-loader') || submitter.hasAttribute('data-download') || submitter.hasAttribute('data-import') || submitter.closest('[data-download]') || submitter.closest('[data-import]') || isDownloadDestination(submitter.getAttribute('formaction')))) {
                return;
            }
        }
        showLoaderWithDelay();
    });

    if (document.readyState !== 'complete') {
        showLoaderWithDelay();
    }

    window.showSmartLoader = showLoaderWithDelay;
    window.hideSmartLoader = hideLoader;
})();
</script>
