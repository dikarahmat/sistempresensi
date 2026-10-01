/**
 * ==========================================================================
 * INSTANT DOWNLOAD ENGINE WITH SOLID DUAL-LAYER SWIPE (SAPUAN PUTIH PENUH)
 * SMP PGRI Parungpanjang - Single Source of Truth for File Downloads
 * ==========================================================================
 */
(function() {
    'use strict';

    // Durasi minimal (0.4s) agar sapuan tampak menyapu mulus
    const MIN_ANIMATION_MS = 400;

    /**
     * Ekstrak nama file dari Content-Disposition header atau URL fallback
     */
    function extractFilename(disposition, urlFallback) {
        if (disposition) {
            // Cek format RFC 5987: filename*=UTF-8''...
            const utf8Match = disposition.match(/filename\*=(?:UTF-8|utf-8)''([^;]+)/i);
            if (utf8Match && utf8Match[1]) {
                try {
                    return decodeURIComponent(utf8Match[1].replace(/["']/g, '').trim());
                } catch (e) {}
            }
            // Cek format standar: filename="..." atau filename=...
            const stdMatch = disposition.match(/filename="?([^";]+)"?/i);
            if (stdMatch && stdMatch[1]) {
                return stdMatch[1].trim().replace(/["']/g, '');
            }
        }

        try {
            const u = new URL(urlFallback, window.location.href);
            const segments = u.pathname.split('/').filter(Boolean);
            const last = segments[segments.length - 1];
            if (last && last.includes('.')) {
                return decodeURIComponent(last);
            }
        } catch (e) {}

        return 'unduhan_file';
    }

    /**
     * Trigger unduhan blob ke browser secara instan tanpa membuka tab baru
     */
    function triggerBlobDownload(blob, filename) {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = filename;
        // Pastikan tidak ada target="_blank"
        a.removeAttribute('target');
        document.body.appendChild(a);
        a.click();

        setTimeout(function() {
            try {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } catch (e) {}
        }, 1200);
    }

    /**
     * Dapatkan warna background asli tombol
     */
    function getButtonColor(btn) {
        try {
            let el = btn;
            let bg = window.getComputedStyle(el).backgroundColor;
            while (el && (bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)')) {
                el = el.parentElement;
                if (!el || el === document.body) break;
                bg = window.getComputedStyle(el).backgroundColor;
            }

            if (bg && bg !== 'transparent' && bg !== 'rgba(0, 0, 0, 0)') {
                return bg;
            }
        } catch (e) {}

        return '#1e3a8a'; // Fallback warna navy asli
    }

    /**
     * Cek apakah warna merupakan warna terang/putih
     */
    function isLightColor(colorStr) {
        try {
            const m = colorStr.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
            if (m) {
                const r = parseInt(m[1], 10);
                const g = parseInt(m[2], 10);
                const b = parseInt(m[3], 10);
                const lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                return lum > 0.72;
            }
        } catch (e) {}
        return false;
    }

    /**
     * Cek apakah URL merupakan tujuan download file
     */
    function isDownloadTarget(href) {
        if (!href) return false;
        try {
            const url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) return false;

            const path = url.pathname.toLowerCase();
            const downloadPatterns = /(?:^|\/)(?:export|download|template|print-?cards?|generate-qr)(?:[-/._]|$)/i;
            const fileExts = /\.(?:pdf|xlsx?|csv|zip|docx?|png|jpe?g|webp|svg)(?:$|\?)/i;

            const hasDownloadParam = ['download', 'attachment', 'export', 'pdf'].some(function(p) {
                return url.searchParams.has(p);
            });

            return downloadPatterns.test(path) || fileExts.test(path) || hasDownloadParam;
        } catch (e) {
            return false;
        }
    }

    /**
     * Cek apakah URL/Form/Tombol merupakan tujuan import file
     */
    function isImportTarget(action, form, btn) {
        if (btn && btn.hasAttribute('data-import')) return true;
        if (form && (form.hasAttribute('data-import') || form.hasAttribute('data-import-form'))) return true;
        if (!action) return false;
        try {
            const url = new URL(action, window.location.href);
            const path = url.pathname.toLowerCase();
            return /(?:^|\/)import(?:[-/._]|$)/i.test(path);
        } catch (e) {
            return false;
        }
    }

    /**
     * Siapkan dan inisialisasi track sapuan putih dual-layer (KIRI ke KANAN) pada tombol
     */
    function setupSwipeTrack(btn) {
        if (!btn || btn.dataset.isDownloading === 'true') {
            return null; // Cegah double-click / double-submit
        }

        btn.dataset.isDownloading = 'true';
        btn.classList.add('dl-btn-relative');

        // Pastikan tidak ada border, outline, atau box-shadow berwarna selama animasi sapuan berjalan
        btn.style.setProperty('border', 'none', 'important');
        btn.style.setProperty('outline', 'none', 'important');
        btn.style.setProperty('box-shadow', 'none', 'important');

        // Pastikan konten dasar tombol terbungkus rapi di layer bawah (z-index: 1)
        Array.from(btn.childNodes).forEach(function(node) {
            if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().length > 0) {
                const span = document.createElement('span');
                span.textContent = node.textContent;
                span.style.position = 'relative';
                span.style.zIndex = '1';
                btn.replaceChild(span, node);
            } else if (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains('dl-swipe-track')) {
                node.style.position = 'relative';
                node.style.zIndex = '1';
            }
        });

        // Tentukan warna tombol dan jenis sapuan
        const btnColor = getButtonColor(btn);
        const isLight = isLightColor(btnColor);

        if (isLight) {
            btn.style.setProperty('--dl-swipe-bg', '#1e3a8a');
            btn.style.setProperty('--dl-btn-color', '#ffffff');
        } else {
            btn.style.setProperty('--dl-swipe-bg', '#ffffff');
            btn.style.setProperty('--dl-btn-color', btnColor);
        }

        // Buat atau reset track sapuan
        let track = btn.querySelector('.dl-swipe-track');
        if (!track) {
            track = document.createElement('div');
            track.className = 'dl-swipe-track';
            track.setAttribute('aria-hidden', 'true');
            if (isLight) track.classList.add('is-light-btn');

            const fill = document.createElement('div');
            fill.className = 'dl-swipe-fill';

            const content = document.createElement('div');
            content.className = 'dl-swipe-content';

            // Sinkronisasi ukuran piksel & layout tombol secara presisi
            const clientW = btn.clientWidth || btn.offsetWidth || 100;
            const clientH = btn.clientHeight || btn.offsetHeight || 38;
            content.style.width = clientW + 'px';
            content.style.height = clientH + 'px';

            const btnStyle = window.getComputedStyle(btn);
            content.style.display = btnStyle.display.includes('flex') ? btnStyle.display : 'flex';
            content.style.justifyContent = btnStyle.justifyContent;
            content.style.alignItems = btnStyle.alignItems;
            content.style.padding = btnStyle.padding;
            content.style.gap = btnStyle.gap;
            content.style.fontSize = btnStyle.fontSize;
            content.style.fontWeight = btnStyle.fontWeight;
            content.style.fontFamily = btnStyle.fontFamily;
            content.style.letterSpacing = btnStyle.letterSpacing;
            content.style.textTransform = btnStyle.textTransform;
            content.style.lineHeight = btnStyle.lineHeight;

            // Kloning konten tombol (ikon & teks) ke layer atas
            Array.from(btn.childNodes).forEach(function(node) {
                if (node.nodeType === Node.ELEMENT_NODE && node.classList.contains('dl-swipe-track')) {
                    return;
                }
                content.appendChild(node.cloneNode(true));
            });

            fill.appendChild(content);
            track.appendChild(fill);
            btn.appendChild(track);
        } else {
            if (isLight) {
                track.classList.add('is-light-btn');
            } else {
                track.classList.remove('is-light-btn');
            }
            const content = track.querySelector('.dl-swipe-content');
            if (content) {
                content.style.width = (btn.clientWidth || btn.offsetWidth || 100) + 'px';
                content.style.height = (btn.clientHeight || btn.offsetHeight || 38) + 'px';
            }
        }

        const swipeFill = track.querySelector('.dl-swipe-fill');
        swipeFill.className = 'dl-swipe-fill';
        swipeFill.style.width = '0%';

        // Aktifkan fade-in track sapuan
        requestAnimationFrame(function() {
            track.classList.add('active');
        });

        const startTime = Date.now();

        function cleanup() {
            if (track) {
                track.classList.remove('active');
                setTimeout(function() {
                    if (track && track.parentNode) {
                        track.parentNode.removeChild(track);
                    }
                    btn.dataset.isDownloading = 'false';
                    btn.style.removeProperty('--dl-btn-color');
                    btn.style.removeProperty('--dl-swipe-bg');
                    btn.style.removeProperty('border');
                    btn.style.removeProperty('outline');
                    btn.style.removeProperty('box-shadow');
                }, 220);
            } else {
                btn.dataset.isDownloading = 'false';
                btn.style.removeProperty('border');
                btn.style.removeProperty('outline');
                btn.style.removeProperty('box-shadow');
            }
        }

        return {
            track: track,
            swipeFill: swipeFill,
            startTime: startTime,
            cleanup: cleanup
        };
    }

    /**
     * Jalankan download dengan efek sapuan putih penuh dual-layer (KIRI ke KANAN)
     */
    async function executeDownload(btn, requestUrl, options) {
        const session = setupSwipeTrack(btn);
        if (!session) return;

        const swipeFill = session.swipeFill;
        const startTime = session.startTime;

        let currentProgress = 5;
        swipeFill.style.width = currentProgress + '%';

        // Simulasi progres sapuan yang bergerak dari KIRI ke KANAN
        let progressTimer = setInterval(function() {
            if (currentProgress < 50) {
                currentProgress += 10;
            } else if (currentProgress < 75) {
                currentProgress += 5;
            } else if (currentProgress < 90) {
                currentProgress += 1.8;
            } else if (currentProgress < 94) {
                currentProgress += 0.4;
            }
            swipeFill.style.width = Math.min(94, currentProgress) + '%';
        }, 40);

        try {
            const fetchOpts = Object.assign({
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }, options || {});

            const response = await fetch(requestUrl, fetchOpts);

            if (!response.ok) {
                let errorMsg = 'Kartu belum bisa diunduh, coba lagi';
                try {
                    const data = await response.json();
                    if (data && data.message) {
                        errorMsg = data.message;
                    }
                } catch (e) {
                    if (requestUrl.includes('card') || requestUrl.includes('kartu')) {
                        errorMsg = 'Kartu belum bisa diunduh, coba lagi';
                    } else {
                        errorMsg = 'Gagal mengunduh berkas (Status: ' + response.status + ')';
                    }
                }
                throw new Error(errorMsg);
            }

            const contentLength = response.headers.get('Content-Length');
            const totalBytes = contentLength ? parseInt(contentLength, 10) : 0;
            const disposition = response.headers.get('Content-Disposition');
            const filename = extractFilename(disposition, requestUrl);

            let blob;
            if (totalBytes > 0 && response.body && window.ReadableStream) {
                const reader = response.body.getReader();
                let receivedBytes = 0;
                const chunks = [];
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    chunks.push(value);
                    receivedBytes += value.length;
                    const realPercent = Math.min(95, Math.round((receivedBytes / totalBytes) * 100));
                    if (realPercent > currentProgress) {
                        currentProgress = realPercent;
                        swipeFill.style.width = currentProgress + '%';
                    }
                }
                blob = new Blob(chunks, {
                    type: response.headers.get('Content-Type') || 'application/octet-stream'
                });
            } else {
                blob = await response.blob();
            }

            clearInterval(progressTimer);

            // Jamin durasi minimal (~0.4 detik) agar sapuan tampak mengalir utuh
            const elapsed = Date.now() - startTime;
            if (elapsed < MIN_ANIMATION_MS) {
                await new Promise(function(resolve) {
                    setTimeout(resolve, MIN_ANIMATION_MS - elapsed);
                });
            }

            // Sapuan memenuhi 100% tombol (sepenuhnya putih dengan teks berwarna)
            swipeFill.style.width = '100%';

            // Tahan sekitar 0,15 detik pada 100%, lalu unduh file dan fade out tombol
            setTimeout(function() {
                triggerBlobDownload(blob, filename);

                // Jika tombol berada di dalam modal (misal Cetak Kartu), tutup modal setelah unduhan dipicu.
                // KECUALI tombol Unduh Template di dalam modal import (jangan tutup modal import agar user bisa lanjut import berkas).
                const isTemplateDownload = requestUrl.includes('template') || 
                                           btn.classList.contains('btn-download-template') || 
                                           (btn.textContent && btn.textContent.toLowerCase().includes('template'));
                const modalEl = btn.closest('.modal');
                if (modalEl && !isTemplateDownload && window.bootstrap && window.bootstrap.Modal) {
                    try {
                        const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            setTimeout(function() { modalInstance.hide(); }, 250);
                        }
                    } catch (e) {}
                }

                // Fade out halus lapisan sapuan kembali ke warna tombol asli
                setTimeout(function() {
                    session.cleanup();
                }, 150);
            }, 150);

        } catch (error) {
            clearInterval(progressTimer);
            swipeFill.classList.add('dl-error');
            swipeFill.style.width = '100%';

            const errorMsg = error.message || 'Gagal memproses unduhan';
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Unduhan Gagal',
                    text: errorMsg,
                    timer: 3500,
                    showConfirmButton: false,
                    customClass: { popup: 'swal2-modal-soft shadow-lg border-0' }
                });
            } else {
                console.error('Download Error:', errorMsg);
            }

            setTimeout(function() {
                session.cleanup();
            }, 800);
        }
    }

    /**
     * Jalankan proses upload & import data dengan animasi sapuan putih dari KIRI ke KANAN
     */
    function executeImport(btn, form) {
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const session = setupSwipeTrack(btn);
        if (!session) return;

        const swipeFill = session.swipeFill;
        const startTime = session.startTime;

        let currentProgress = 5;
        swipeFill.style.width = currentProgress + '%';

        // Simulasi progres pemrosesan server dari 5% hingga 92%
        let progressTimer = setInterval(function() {
            if (currentProgress < 50) {
                currentProgress += 8;
            } else if (currentProgress < 75) {
                currentProgress += 4;
            } else if (currentProgress < 90) {
                currentProgress += 1.5;
            } else if (currentProgress < 92) {
                currentProgress += 0.3;
            }
            swipeFill.style.width = Math.min(92, currentProgress) + '%';
        }, 45);

        const xhr = new XMLHttpRequest();
        const action = form.getAttribute('action') || window.location.href;
        const method = (form.getAttribute('method') || 'POST').toUpperCase();

        xhr.open(method, action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (xhr.upload) {
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable && e.total > 0) {
                    const uploadPct = Math.min(85, Math.max(5, Math.round((e.loaded / e.total) * 85)));
                    if (uploadPct > currentProgress) {
                        currentProgress = uploadPct;
                        swipeFill.style.width = currentProgress + '%';
                    }
                }
            };
        }

        xhr.onload = function() {
            clearInterval(progressTimer);
            const elapsed = Date.now() - startTime;
            const waitTime = Math.max(0, MIN_ANIMATION_MS - elapsed);

            setTimeout(function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    // Sapuan 100% putih penuh
                    swipeFill.style.width = '100%';

                    setTimeout(function() {
                        const modalEl = btn.closest('.modal');
                        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                            try {
                                const modalInstance = window.bootstrap.Modal.getInstance(modalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            } catch (e) {}
                        }

                        const responseText = xhr.responseText || '';
                        if (responseText.includes('<!DOCTYPE') || responseText.includes('<html') || responseText.includes('<body')) {
                            if (xhr.responseURL && window.location.href !== xhr.responseURL) {
                                try {
                                    window.history.replaceState(null, '', xhr.responseURL);
                                } catch (e) {}
                            }
                            document.open();
                            document.write(responseText);
                            document.close();
                        } else {
                            window.location.reload();
                        }
                    }, 180);
                } else {
                    swipeFill.classList.add('dl-error');
                    swipeFill.style.width = '100%';

                    let errorMsg = 'Gagal memproses berkas import (Status ' + xhr.status + ')';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if (res.errors) {
                            errorMsg = Object.values(res.errors).flat().join('<br>');
                        } else if (res.message) {
                            errorMsg = res.message;
                        }
                    } catch (e) {}

                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal',
                            html: errorMsg,
                            confirmButtonText: 'Tutup',
                            customClass: { popup: 'swal2-modal-soft shadow-lg border-0' }
                        });
                    } else {
                        alert(errorMsg.replace(/<br>/g, '\n'));
                    }

                    setTimeout(function() {
                        session.cleanup();
                    }, 800);
                }
            }, waitTime);
        };

        xhr.onerror = function() {
            clearInterval(progressTimer);
            swipeFill.classList.add('dl-error');
            swipeFill.style.width = '100%';

            if (window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Import Gagal',
                    text: 'Koneksi terputus saat mengunggah berkas.',
                    timer: 3500,
                    showConfirmButton: false
                });
            }

            setTimeout(function() {
                session.cleanup();
            }, 800);
        };

        const formData = new FormData(form);
        xhr.send(formData);
    }

    /**
     * Global Listener untuk Click pada Download Links/Buttons
     */
    document.addEventListener('click', function(event) {
        const target = event.target;
        const btn = target instanceof Element ? target.closest('a, button') : null;
        if (!btn) return;

        // Cek pengecualian
        if (btn.hasAttribute('data-no-download') || btn.hasAttribute('data-no-import')) {
            return;
        }

        // Jangan tangani tombol submit form di sini (ditangani submit listener)
        if (btn.tagName === 'BUTTON' && btn.type === 'submit') {
            return;
        }

        const href = btn.getAttribute('href');
        const hasExplicitData = btn.hasAttribute('data-download');

        if (hasExplicitData || isDownloadTarget(href)) {
            if (!href || href === '#' || href.startsWith('javascript:')) {
                return;
            }

            // Cegah navigasi browser membuka halaman baru (mencegah tab baru!)
            event.preventDefault();
            event.stopPropagation();

            if (btn.dataset.isDownloading === 'true') {
                return; // Abaikan double-click
            }

            executeDownload(btn, href, { method: 'GET' });
        }
    }, true);

    /**
     * Global Listener untuk Form Submission (Modal Cetak Massal POST & Modal Import File)
     */
    document.addEventListener('submit', function(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.hasAttribute('data-no-download') || form.hasAttribute('data-no-import')) {
            return;
        }

        const action = form.getAttribute('action') || window.location.href;
        const submitBtn = event.submitter || form.querySelector('button[type="submit"]') || form.querySelector('input[type="submit"]');

        // 1. Cek apakah ini form import Excel / data
        if (isImportTarget(action, form, submitBtn)) {
            event.preventDefault();
            event.stopPropagation();

            if (submitBtn && submitBtn.dataset.isDownloading === 'true') {
                return; // Cegah double-submit
            }

            executeImport(submitBtn || form, form);
            return;
        }

        // 2. Cek apakah ini form download file (misal modal generate & cetak kartu massal)
        const hasExplicitData = form.hasAttribute('data-download') || (submitBtn && submitBtn.hasAttribute('data-download'));

        if (hasExplicitData || isDownloadTarget(action)) {
            // Cegah submit bawaan browser
            event.preventDefault();
            event.stopPropagation();

            if (submitBtn && submitBtn.dataset.isDownloading === 'true') {
                return;
            }

            const formData = new FormData(form);
            const method = (form.getAttribute('method') || 'POST').toUpperCase();

            let targetUrl = action;
            let fetchOptions = {
                method: method,
                credentials: 'same-origin',
            };

            if (method === 'GET') {
                const params = new URLSearchParams(formData);
                const u = new URL(action, window.location.href);
                params.forEach(function(val, key) {
                    u.searchParams.set(key, val);
                });
                targetUrl = u.toString();
            } else {
                fetchOptions.body = formData;
            }

            executeDownload(submitBtn || form, targetUrl, fetchOptions);
        }
    }, true);

})();
