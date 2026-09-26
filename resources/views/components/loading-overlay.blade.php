<div id="smart-page-loader" class="loader-overlay" style="display: none;">
    <div class="loader"></div>
</div>

<style>
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
        background: rgba(15, 23, 42, 0.24);
        pointer-events: auto;
    }

    .loader {
        width: 50px;
        aspect-ratio: 1;
        --c: no-repeat radial-gradient(farthest-side, #ffffff 92%, #0000);
        background:
            var(--c) 50% 0,
            var(--c) 50% 100%,
            var(--c) 100% 50%,
            var(--c) 0 50%;
        background-size: 10px 10px;
        animation: l18 1s infinite;
        position: relative;
    }

    .loader::before {
        content: "";
        position: absolute;
        inset: 0;
        margin: 3px;
        background: repeating-conic-gradient(#0000 0 35deg, #ffffff 0 90deg);
        -webkit-mask: radial-gradient(farthest-side, #0000 calc(100% - 3px), #000 0);
        border-radius: 50%;
    }

    @keyframes l18 {
        100% { transform: rotate(.5turn); }
    }
</style>

<script>
(function() {
    'use strict';

    let loaderTimeout = null;
    const DELAY_MS = 100;

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
                loader.style.display = 'flex';
            }
        }, DELAY_MS);
    }

    function hideLoader() {
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
            loaderTimeout = null;
        }
        const loader = getLoader();
        if (loader) {
            loader.style.display = 'none';
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

        if (anchor.hasAttribute('download') || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
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
        showLoaderWithDelay();
    });

    if (document.readyState !== 'complete') {
        showLoaderWithDelay();
    }

    window.showSmartLoader = showLoaderWithDelay;
    window.hideSmartLoader = hideLoader;
})();
</script>
