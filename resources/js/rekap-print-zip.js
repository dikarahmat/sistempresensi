/**
 * Cetak Rekap "Semua Kelas" -> satu file ZIP.
 *
 * JSZip dibundel lewat Vite (bukan CDN) dan HANYA dimuat di halaman rekap
 * lewat @vite(['resources/js/rekap-print-zip.js']). Modul ini mengekspos
 * JSZip ke window supaya skrip popup (yang bukan modul ES) bisa memakainya.
 *
 * Penampung ZIP sengaja disimpan di satu tempat (window.REKAP_ZIP) supaya
 * bisa dikosongkan saat popup dibuka lagi atau saat pengguna menekan
 * "Hentikan" - tanpa itu memori browser menumpuk antar unduhan.
 */
import JSZip from 'jszip';

window.JSZip = JSZip;

if (!window.REKAP_ZIP) {
    window.REKAP_ZIP = null;
    window.REKAP_ZIP_NAMA = new Set();
}

/** Buat objek ZIP baru dan kosongkan peta nama file. */
window.REKAP_ZIP_RESET = function () {
    window.REKAP_ZIP = JSZip ? new JSZip() : null;
    window.REKAP_ZIP_NAMA = new Set();
};

/**
 * Sanitasi nama file di dalam ZIP: buang karakter yang dilarang Windows,
 * lalu Hindari nama bentrok dengan suffiks _2, _3, ...
 */
window.REKAP_ZIP_NAMA_UNIK = function (namaDasar) {
    const dasar = String(namaDasar || 'profil')
        .replace(/[\\/:*?"<>|]/g, '-')
        .replace(/\s+/g, ' ')
        .trim()
        .replace(/^\.+|\.+$/g, '');

    if (!window.REKAP_ZIP_NAMA) {
        window.REKAP_ZIP_NAMA = new Set();
    }

    let nama = dasar;
    let i = 2;
    while (window.REKAP_ZIP_NAMA.has(nama.toLowerCase())) {
        nama = dasar + '_' + i;
        i++;
    }
    window.REKAP_ZIP_NAMA.add(nama.toLowerCase());
    return nama;
};