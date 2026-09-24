<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
  classHistories: { type: Array, default: () => [] },
  academicYears: { type: Array, default: () => [] },
  selectedYearId: { type: [String, Number], default: null },
  activeYear: { type: Object, default: null },
  search: { type: String, default: '' },
  totalKelas: { type: Number, default: 0 },
  maxHari: { type: Number, default: 0 },
  totalSiswaSemua: { type: Number, default: 0 },
  avgPersentase: { type: Number, default: 0 },
});

const filterYear = ref(props.selectedYearId || '');
const searchQuery = ref(props.search || '');

function applyFilter() {
  router.get('/admin/kehadiran', {
    academic_year_id: filterYear.value || undefined,
    search: searchQuery.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
}

function resetFilter() {
  filterYear.value = '';
  searchQuery.value = '';
  applyFilter();
}
</script>

<template>
  <Head title="Catatan Kehadiran" />

  <div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- 1. Header & Top Action -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Catatan Kehadiran</h1>
        <p class="text-sm text-slate-500">Lihat histori kehadiran per kelas</p>
      </div>

      <div>
        <!-- Tombol Scan Absensi Hari Ini -->
        <Link
          href="/admin/absensi"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-xs"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" /></svg>
          <span>Scan Absensi Hari Ini</span>
        </Link>
      </div>
    </div>

    <!-- 2. Ringkasan Metrik Korporat -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 block mb-1">Total Kelas Aktif</span>
          <div class="text-2xl font-bold text-slate-900">{{ totalKelas }}</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 block mb-1">Total Siswa Terdaftar</span>
          <div class="text-2xl font-bold text-slate-900">{{ totalSiswaSemua }}</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 block mb-1">Akumulasi Hari Efektif</span>
          <div class="text-2xl font-bold text-slate-900">{{ maxHari }} <span class="text-xs font-normal text-slate-400">Hari</span></div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
        </div>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
        <div>
          <span class="text-xs font-semibold text-slate-500 block mb-1">Rata-rata Kehadiran</span>
          <div class="text-2xl font-bold" :class="avgPersentase >= 80 ? 'text-emerald-600' : 'text-amber-600'">{{ avgPersentase }}%</div>
        </div>
        <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
        </div>
      </div>
    </div>

    <!-- 3. Filter Bar: Tahun Ajaran & Pencarian Kelas -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-5 space-y-1.5">
          <label class="text-xs font-semibold text-slate-600">Tahun Ajaran / Semester</label>
          <select
            v-model="filterYear"
            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Semua Tahun Ajaran</option>
            <option v-for="y in academicYears" :key="y.id" :value="y.id">
              {{ y.name }} – Semester {{ y.semester }} {{ y.is_active ? '(Aktif)' : '' }}
            </option>
          </select>
        </div>

        <div class="md:col-span-4 space-y-1.5">
          <label class="text-xs font-semibold text-slate-600">Cari Nama Kelas</label>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Contoh: IX A, VII B..."
            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div class="md:col-span-3 flex gap-2">
          <button
            type="button"
            @click="applyFilter"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-xl text-sm transition shadow-xs"
          >
            Terapkan
          </button>
          <button
            v-if="filterYear || searchQuery"
            type="button"
            @click="resetFilter"
            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-3 rounded-xl text-sm transition border border-slate-200"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- 4. Tabel Histori Kehadiran (Kolom: Kelas, Tahun Ajaran, Wali Kelas, Total Hari, Aksi) -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-slate-500 font-bold text-xs uppercase border-b border-slate-200 tracking-wider">
            <tr>
              <th class="py-3.5 px-4 text-center w-12">No.</th>
              <th class="py-3.5 px-4">Kelas</th>
              <th class="py-3.5 px-4">Tahun Ajaran</th>
              <th class="py-3.5 px-4">Wali Kelas</th>
              <th class="py-3.5 px-4 text-center">Total Hari</th>
              <th class="py-3.5 px-4 text-center">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(item, idx) in classHistories"
              :key="item.id"
              class="hover:bg-slate-50/80 transition"
              :class="idx % 2 === 1 ? 'bg-slate-50/40' : 'bg-white'"
            >
              <td class="py-3.5 px-4 text-center text-slate-400 font-semibold">{{ idx + 1 }}</td>
              <td class="py-3.5 px-4">
                <div class="font-bold text-slate-800 text-base">Kelas {{ item.name }}</div>
                <div class="text-xs text-slate-400">Tingkat {{ item.grade }} &bull; {{ item.total_students }} Siswa</div>
              </td>
              <td class="py-3.5 px-4">
                <span class="inline-block px-2.5 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold">
                  {{ item.tahun_ajaran }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <div class="font-semibold text-slate-800">{{ item.teacher }}</div>
                <div class="text-xs text-slate-400">Wali Kelas</div>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="inline-block px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full font-bold text-sm">
                  {{ item.total_hari }} Hari
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <div class="inline-flex items-center gap-2">
                  <!-- Tombol "Absensi" -->
                  <Link
                    :href="`/admin/absensi?class_id=${item.id}`"
                    class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs py-1.5 px-3 rounded-lg shadow-xs transition"
                  >
                    <span>Absensi</span>
                  </Link>

                  <!-- Tombol "Lihat >" -->
                  <Link
                    :href="`/admin/kehadiran/${item.id}`"
                    class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs py-1.5 px-3 rounded-lg border border-slate-200 transition"
                  >
                    <span>Lihat</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                  </Link>
                </div>
              </td>
            </tr>

            <tr v-if="classHistories.length === 0">
              <td colspan="6" class="py-8 text-center text-slate-400">
                Belum ada data rombel / kelas yang tercatat.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
