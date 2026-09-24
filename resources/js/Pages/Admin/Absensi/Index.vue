<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  tanggal: { type: String, default: '' },
  hariIni: { type: String, default: '' },
  kelasList: { type: Array, default: () => [] },
  total_siswa: { type: Number, default: 0 },
  sudah_absen: { type: Number, default: 0 },
  belum_absen: { type: Number, default: 0 },
  hadir_count: { type: Number, default: 0 },
  terlambat_count: { type: Number, default: 0 },
  sakit_count: { type: Number, default: 0 },
  izin_count: { type: Number, default: 0 },
  alpha_count: { type: Number, default: 0 },
  jam_masuk: { type: String, default: '07:00:00' },
  jam_pulang: { type: String, default: '14:00:00' },
  batas_terlambat: { type: String, default: '07:15:00' },
  is_holiday: { type: Boolean, default: false },
  holiday_description: { type: String, default: null },
  sekolah: { type: Object, default: () => ({}) },
  class_id: { type: [String, Number], default: null },
  selected_class: { type: Object, default: null },
  processed_students: { type: Array, default: () => [] },
});

// State
const filterDate = ref(props.tanggal || props.hariIni);
const selectedClassFilter = ref(props.class_id || '');
const isScannerOpen = ref(false);
const scannerMode = ref('alat'); // 'alat' | 'kamera'
const usbScanInput = ref('');
const isProcessingScan = ref(false);
const scanFeedback = ref({
  show: false,
  success: false,
  message: '',
  student: null,
  time: '',
  type: '',
});
const activeTab = ref(props.class_id ? 'detail' : 'rekap_kelas');

// Toggle Scanner
function toggleScanner() {
  isScannerOpen.value = !isScannerOpen.value;
  if (isScannerOpen.value && scannerMode.value === 'alat') {
    focusUsbInput();
  }
}

function focusUsbInput() {
  setTimeout(() => {
    const el = document.getElementById('vueUsbScannerInput');
    if (el) el.focus();
  }, 100);
}

// Filter submission
function applyFilter() {
  router.get('/admin/absensi', {
    tanggal: filterDate.value,
    class_id: selectedClassFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
}

function resetToToday() {
  filterDate.value = props.hariIni;
  selectedClassFilter.value = '';
  applyFilter();
}

function openClassDetail(classId) {
  selectedClassFilter.value = classId;
  activeTab.value = 'detail';
  applyFilter();
}

// Web Audio API Beep Feedback
function playBeep(isSuccess) {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    if (isSuccess) {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      gain.gain.setValueAtTime(0.18, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.18);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.18);
    } else {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(330, ctx.currentTime);
      gain.gain.setValueAtTime(0.2, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.28);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.28);
    }
  } catch (e) {
    // Audio Context not supported or blocked
  }
}

// Process USB Scan
async function handleUsbScan() {
  const token = usbScanInput.value.trim();
  if (!token || isProcessingScan.value) return;

  isProcessingScan.value = true;
  try {
    const response = await axios.post('/admin/scanner/process', { qr_token: token });
    const data = response.data;
    playBeep(true);
    scanFeedback.value = {
      show: true,
      success: true,
      message: data.message || 'Presensi berhasil dicatat!',
      student: data.student,
      time: data.time_short || data.time,
      type: data.type,
      is_late: data.is_late,
      late_minutes: data.late_minutes,
    };
    // Reload Inertia props softly
    router.reload({ only: ['kelasList', 'total_siswa', 'sudah_absen', 'belum_absen', 'processed_students'] });
  } catch (err) {
    playBeep(false);
    const errData = err.response?.data || {};
    scanFeedback.value = {
      show: true,
      success: false,
      message: errData.message || 'QR Code / Kartu tidak valid atau tidak dikenali.',
      student: errData.student || null,
      time: '-',
      type: 'error',
    };
  } finally {
    isProcessingScan.value = false;
    usbScanInput.value = '';
    focusUsbInput();
  }
}
</script>

<template>
  <Head title="Absensi Hari Ini" />

  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- 1. Header & Top Action Buttons -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Absensi Hari Ini</h1>
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
            {{ tanggal }}
          </span>
        </div>
        <p class="text-sm text-slate-500">
          Monitoring presensi harian siswa, status keterlambatan, rekapitulasi rombel, dan scanner terintegrasi.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <!-- Tombol Mode Gerbang -->
        <a
          href="/admin/absensi/kiosk"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-sm font-semibold border border-blue-600 text-blue-600 hover:bg-blue-50 transition shadow-xs"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>Mode Gerbang</span>
        </a>

        <!-- Tombol Toggle Buka/Tutup Scanner QR -->
        <button
          type="button"
          @click="toggleScanner"
          :class="isScannerOpen ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition shadow-xs"
        >
          <svg v-if="!isScannerOpen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
          </svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <span>{{ isScannerOpen ? 'Tutup Scanner' : 'Buka Scanner QR' }}</span>
        </button>
      </div>
    </div>

    <!-- 2. Area Scanner Inline (Collapsible) -->
    <div v-show="isScannerOpen" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-300">
      <!-- Header Scanner -->
      <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-slate-800 text-sm">Scanner Presensi Cepat (Inline)</h3>
          <p class="text-xs text-slate-500">Pindai kartu identitas atau QR Code siswa secara realtime</p>
        </div>

        <div class="flex items-center gap-2">
          <div class="bg-white p-1 rounded-lg border border-slate-200 flex gap-1">
            <button
              type="button"
              @click="scannerMode = 'alat'; focusUsbInput();"
              :class="scannerMode === 'alat' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900'"
              class="px-3 py-1 rounded text-xs transition"
            >
              Alat Scanner
            </button>
            <button
              type="button"
              @click="scannerMode = 'kamera'"
              :class="scannerMode === 'kamera' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900'"
              class="px-3 py-1 rounded text-xs transition"
            >
              Kamera
            </button>
          </div>
          <button @click="isScannerOpen = false" class="text-slate-400 hover:text-slate-600 p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
      </div>

      <!-- Scanner Body -->
      <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Kolom Input Scanner -->
        <div class="space-y-3">
          <div v-if="scannerMode === 'alat'" class="bg-slate-50 border border-slate-200 rounded-xl p-6 text-center space-y-4">
            <div class="flex items-center justify-center gap-2">
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
              </span>
              <span class="text-xs font-bold text-emerald-700">Siap Menerima Scan (USB / Barcode Scanner)</span>
            </div>

            <input
              id="vueUsbScannerInput"
              v-model="usbScanInput"
              @keydown.enter="handleUsbScan"
              type="text"
              class="w-full text-center text-lg font-bold py-3 px-4 border-2 border-blue-500 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-100 bg-white"
              placeholder="Siap menerima scan..."
              autocomplete="off"
            />
            <p class="text-xs text-slate-500">
              Arahkan alat scanner ke QR code siswa. Input otomatis terproses saat enter ditekan.
            </p>
          </div>

          <div v-else class="bg-slate-50 border border-slate-200 rounded-xl p-6 text-center space-y-3">
            <div class="w-full h-48 bg-slate-900 rounded-xl flex items-center justify-center text-slate-400 text-xs">
              Fitur Scanner Kamera Aktif via Html5Qrcode
            </div>
            <p class="text-xs text-slate-500">Arahkan kamera webcam ke kartu siswa</p>
          </div>
        </div>

        <!-- Kolom Hasil Scan Realtime -->
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between">
          <div class="border-b border-slate-200 pb-2 flex justify-between items-center text-xs text-slate-500 font-semibold uppercase">
            <span>Hasil Pemindaian Terakhir</span>
            <span>{{ scanFeedback.time || '-' }}</span>
          </div>

          <!-- State Idle -->
          <div v-if="!scanFeedback.show" class="py-8 text-center space-y-2">
            <div class="w-12 h-12 bg-white rounded-full mx-auto flex items-center justify-center shadow-xs border text-slate-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
            </div>
            <div class="font-semibold text-slate-700 text-sm">Belum Ada Pemindaian</div>
            <p class="text-xs text-slate-400">Pindai kartu siswa untuk merekam kehadiran secara instan.</p>
          </div>

          <!-- State Result -->
          <div v-else class="py-4 space-y-3">
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-xl bg-white border border-slate-200 flex items-center justify-center font-bold text-blue-600 text-lg shadow-xs overflow-hidden">
                <img v-if="scanFeedback.student?.photo" :src="scanFeedback.student.photo" class="w-full h-full object-cover" />
                <span v-else>{{ scanFeedback.student?.name?.substring(0, 2).toUpperCase() || '?' }}</span>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-base leading-tight">{{ scanFeedback.student?.name || 'Kartu Tidak Dikenal' }}</h4>
                <p class="text-xs text-slate-500">NIS: {{ scanFeedback.student?.nis || '-' }} | Kelas: {{ scanFeedback.student?.class || '-' }}</p>
              </div>
            </div>

            <div class="bg-white p-3 rounded-lg border border-slate-200 space-y-1">
              <div class="flex justify-between items-center text-xs">
                <span class="text-slate-500 font-medium">Status:</span>
                <span :class="scanFeedback.success ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'" class="px-2 py-0.5 rounded font-bold">
                  {{ scanFeedback.message }}
                </span>
              </div>
              <div class="flex justify-between items-center text-xs">
                <span class="text-slate-500 font-medium">Waktu:</span>
                <span class="font-mono font-bold text-slate-800">{{ scanFeedback.time }} WIB</span>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-200 pt-2 flex justify-between text-[11px] text-slate-400">
            <span>Status: Terhubung Database</span>
            <span>Mode Standby</span>
          </div>
        </div>
      </div>

      <!-- Footer Jam Operasional -->
      <div class="px-6 py-2.5 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-xs text-slate-600">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          <span class="font-bold text-slate-800">Jam Operasional Presensi:</span>
          <span>Masuk: <strong class="text-slate-900">{{ jam_masuk }}</strong></span>
          <span class="text-slate-300">|</span>
          <span>Pulang: <strong class="text-slate-900">{{ jam_pulang }}</strong></span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold">Server Siap</span>
      </div>
    </div>

    <!-- 3. Filter Tanggal & Rombel -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-4 space-y-1.5">
          <label class="text-xs font-semibold text-slate-600">Pilih Tanggal Presensi</label>
          <input
            v-model="filterDate"
            type="date"
            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="md:col-span-5 space-y-1.5">
          <label class="text-xs font-semibold text-slate-600">Filter Rombongan Belajar / Kelas</label>
          <select
            v-model="selectedClassFilter"
            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Kelas (Seluruh Siswa)</option>
            <option v-for="c in kelasList" :key="c.id" :value="c.id">
              Kelas {{ c.nama_kelas }} (Tingkat {{ c.tingkat }})
            </option>
          </select>
        </div>

        <div class="md:col-span-3 flex gap-2">
          <button
            type="button"
            @click="applyFilter"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl text-sm transition shadow-xs"
          >
            Terapkan Filter
          </button>
          <button
            type="button"
            @click="resetToToday"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-3 rounded-xl text-sm transition border border-slate-200"
          >
            Hari Ini
          </button>
        </div>
      </div>
    </div>

    <!-- 4. 3 Status Badge Counters -->
    <div class="flex flex-wrap items-center gap-3">
      <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ringkasan Presensi:</span>

      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-xs">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        <span><strong>{{ sudah_absen }}</strong> sudah absen</span>
      </div>

      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-amber-50 text-amber-800 border border-amber-200 shadow-xs">
        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span><strong>{{ belum_absen }}</strong> belum absen</span>
      </div>

      <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-blue-50 text-blue-800 border border-blue-200 shadow-xs">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        <span><strong>{{ total_siswa }}</strong> total siswa</span>
      </div>
    </div>

    <!-- 5. Nav Tabs: Rekapitulasi Per Kelas & Detail Siswa -->
    <div class="border-b border-slate-200 flex gap-4">
      <button
        type="button"
        @click="activeTab = 'rekap_kelas'"
        :class="activeTab === 'rekap_kelas' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
        class="pb-3 border-b-2 text-sm transition font-medium"
      >
        Rekapitulasi Per Kelas
      </button>
      <button
        type="button"
        @click="activeTab = 'detail'"
        :class="activeTab === 'detail' ? 'border-blue-600 text-blue-600 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
        class="pb-3 border-b-2 text-sm transition font-medium flex items-center gap-1.5"
      >
        <span>Daftar Presensi Siswa</span>
        <span v-if="selected_class" class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-bold">
          {{ selected_class.name }}
        </span>
      </button>
    </div>

    <!-- 6. TABEL REKAPITULASI PER KELAS (Kolom: Kelas, Total Siswa, Sudah Absen, Belum, Persentase Kehadiran, Buka Kelas) -->
    <div v-show="activeTab === 'rekap_kelas'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 font-bold text-xs uppercase border-b border-slate-200 tracking-wider">
            <tr>
              <th class="py-3.5 px-4 text-center w-12">No.</th>
              <th class="py-3.5 px-4">Kelas</th>
              <th class="py-3.5 px-4 text-center">Total Siswa</th>
              <th class="py-3.5 px-4 text-center text-emerald-700">Sudah Absen</th>
              <th class="py-3.5 px-4 text-center text-slate-500">Belum</th>
              <th class="py-3.5 px-4 text-center">Persentase Kehadiran</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(cls, idx) in kelasList"
              :key="cls.id"
              class="hover:bg-slate-50/80 transition"
              :class="idx % 2 === 1 ? 'bg-slate-50/40' : 'bg-white'"
            >
              <td class="py-3.5 px-4 text-center text-slate-400 font-semibold">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-800 text-base">Kelas {{ cls.nama_kelas }}</div>
                <div class="text-xs text-slate-400">Wali: <span class="text-slate-600">{{ cls.wali_kelas }}</span></div>
              </td>
              <td class="py-3.5 px-4 text-center font-bold text-slate-800 text-base">
                {{ cls.total_siswa ?? 0 }}
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-sm">
                  {{ cls.sudah_absen ?? 0 }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="inline-block px-3 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-lg font-bold text-sm">
                  {{ cls.belum ?? 0 }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="font-bold text-sm mb-1" :class="cls.persentase >= 80 ? 'text-emerald-600' : cls.persentase >= 50 ? 'text-amber-600' : 'text-rose-600'">
                  {{ cls.persentase }}%
                </div>
                <div class="w-24 bg-slate-100 rounded-full h-1.5 mx-auto overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all"
                    :class="cls.persentase >= 80 ? 'bg-emerald-500' : cls.persentase >= 50 ? 'bg-amber-500' : 'bg-rose-500'"
                    :style="{ width: cls.persentase + '%' }"
                  ></div>
                </div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <button
                  type="button"
                  @click="openClassDetail(cls.id)"
                  class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs py-1.5 px-3.5 rounded-lg shadow-xs transition"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" /></svg>
                  <span>Buka Kelas</span>
                </button>
              </td>
            </tr>

            <tr v-if="kelasList.length === 0">
              <td colspan="7" class="py-8 text-center text-slate-400">
                Belum ada data rombel / kelas yang tercatat.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 7. TABEL DETAIL DAFTAR SISWA -->
    <div v-show="activeTab === 'detail'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
      <div class="p-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
        <span class="text-sm font-bold text-slate-800">
          Daftar Siswa {{ selected_class ? 'Kelas ' + selected_class.name : '(Semua Kelas)' }}
        </span>
        <button
          v-if="selected_class"
          type="button"
          @click="selectedClassFilter = ''; activeTab = 'rekap_kelas'; applyFilter();"
          class="text-xs font-semibold text-blue-600 hover:underline"
        >
          &larr; Tampilkan Semua Rekap Kelas
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 font-bold text-xs uppercase border-b border-slate-200">
            <tr>
              <th class="py-3 px-4 text-center w-12">No.</th>
              <th class="py-3 px-4">NIS</th>
              <th class="py-3 px-4">Nama Siswa</th>
              <th class="py-3 px-4 text-center">Kelas</th>
              <th class="py-3 px-4 text-center">Jam Masuk</th>
              <th class="py-3 px-4 text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(st, idx) in processed_students"
              :key="st.id"
              class="hover:bg-slate-50/80 transition"
              :class="idx % 2 === 1 ? 'bg-slate-50/40' : 'bg-white'"
            >
              <td class="py-3 px-4 text-center text-slate-400 font-semibold">{{ idx + 1 }}</td>
              <td class="py-3 px-4 font-semibold text-slate-800">{{ st.nis || '-' }}</td>
              <td class="py-3 px-4 font-bold text-slate-900">{{ st.name }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-xs border">{{ st.school_class?.name || '-' }}</span>
              </td>
              <td class="py-3 px-4 text-center font-mono text-xs text-slate-600">
                {{ st.check_in_time ? st.check_in_time.substring(0, 5) + ' WIB' : '-' }}
              </td>
              <td class="py-3 px-4 text-center">
                <span
                  class="px-2.5 py-1 rounded text-xs font-bold"
                  :class="{
                    'bg-emerald-100 text-emerald-700': st.current_status === 'Hadir' && st.current_time_remark !== 'Terlambat',
                    'bg-amber-100 text-amber-700': st.current_time_remark === 'Terlambat',
                    'bg-blue-100 text-blue-700': st.current_status === 'Sakit',
                    'bg-purple-100 text-purple-700': st.current_status === 'Izin',
                    'bg-rose-100 text-rose-700': st.current_status === 'Alfa',
                    'bg-slate-100 text-slate-500': !st.current_status || st.current_status === 'Belum Hadir'
                  }"
                >
                  {{ st.current_time_remark === 'Terlambat' ? 'Terlambat' : (st.current_status || 'Belum Hadir') }}
                </span>
              </td>
            </tr>

            <tr v-if="processed_students.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-400">
                Tidak ada data siswa ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
