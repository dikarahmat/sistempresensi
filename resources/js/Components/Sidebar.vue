<template>
  <aside
    :class="['app-sidebar-drawer', { 'mobile-sidebar-active': isOpen }]"
    x-cloak
  >
    <div class="app-sidebar-panel d-flex flex-column">
      <!-- Brand / Logo -->
      <div class="sidebar-brand flex-shrink-0">
        <div class="d-flex align-items-center gap-2 pb-3 border-bottom border-light border-opacity-25">
          <img
            :src="logoUrl"
            alt="Logo Sekolah"
            class=""
            style="width: 46px; height: 46px; object-fit: contain;"
            @error="handleLogoError"
          />
          <div
            class="d-flex flex-column text-uppercase fw-bold text-nowrap"
            style="font-size: 0.9rem; letter-spacing: 0px; line-height: 1.2;"
          >
            <span>{{ brandLine1 }}</span>
            <span v-if="brandLine2">{{ brandLine2 }}</span>
          </div>
        </div>
      </div>

      <!-- Scrollable Menu -->
      <div class="flex-grow-1 py-2 sidebar-menu-scroll">
        <ul class="nav nav-pills flex-column mb-auto gap-2">
          <li class="nav-item">
            <a
              :href="dashboardUrl"
              :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isDashboardActive }]"
              style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
            >
              <i :class="['bx bxs-dashboard fs-5', isDashboardActive ? 'text-primary' : 'text-white']"></i>
              Dashboard
            </a>
          </li>

          <li class="nav-item">
            <a
              :href="absensiUrl"
              :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isAbsensiActive }]"
              style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
            >
              <i :class="['bx bx-qr-scan fs-5', isAbsensiActive ? 'text-primary' : 'text-white']"></i>
              Presensi
            </a>
          </li>

          <li class="nav-item">
            <a
              :href="kehadiranUrl"
              :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isKehadiranActive }]"
              style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
            >
              <i :class="['bx bx-calendar-check fs-5', isKehadiranActive ? 'text-primary' : 'text-white']"></i>
              Kehadiran
            </a>
          </li>

          <li class="nav-item">
            <a
              :href="rekapUrl"
              :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isRekapActive }]"
              style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
            >
              <i :class="['bx bx-folder-open fs-5', isRekapActive ? 'text-primary' : 'text-white']"></i>
              Rekap
            </a>
          </li>

          <li class="nav-item">
            <a
              :href="siswaUrl"
              :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isSiswaActive }]"
              style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
            >
              <i :class="['bx bx-user fs-5', isSiswaActive ? 'text-primary' : 'text-white']"></i>
              Siswa
            </a>
          </li>

          <template v-if="userRole === 'admin' || userRole === 'kesiswaan'">
            <li class="nav-item">
              <a
                :href="guruUrl"
                :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isGuruActive }]"
                style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
              >
                <i :class="['bx bx-group fs-5', isGuruActive ? 'text-primary' : 'text-white']"></i>
                Guru
              </a>
            </li>
          </template>

          <template v-if="userRole === 'admin' || userRole === 'kesiswaan'">
            <li class="nav-item">
              <a
                :href="kelasUrl"
                :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isKelasActive }]"
                style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
              >
                <i :class="['bx bx-buildings fs-5', isKelasActive ? 'text-primary' : 'text-white']"></i>
                Kelas
              </a>
            </li>
          </template>

          <template v-if="userRole === 'admin'">
            <li class="nav-item">
              <a
                :href="pengaturanUrl"
                :class="['nav-link text-white d-flex align-items-center gap-3 mb-1', { 'active bg-white text-primary shadow-sm fw-medium': isPengaturanActive }]"
                style="margin: 0 0.75rem; padding: 0.6rem 0.75rem; border-radius: 12px; transition: 0.2s;"
              >
                <i :class="['bx bx-cog fs-5', isPengaturanActive ? 'text-primary' : 'text-white']"></i>
                Pengaturan
              </a>
            </li>
          </template>
        </ul>
      </div>

      <!-- Footer: Logout -->
      <div class="sidebar-footer border-top border-light border-opacity-25 flex-shrink-0">
        <form action="/logout" method="POST" class="m-0">
          <input type="hidden" name="_token" :value="csrfToken">
          <button type="submit" class="sidebar-logout-btn" title="Keluar dari aplikasi">
            <i class='bx bx-log-out'></i>
            Log Out
          </button>
        </form>
      </div>
    </aside>
</template>

<script>
export default {
  name: 'Sidebar',
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    userRole: {
      type: String,
      default: 'admin'
    },
    csrfToken: {
      type: String,
      default: ''
    }
  },
  computed: {
    dashboardUrl() {
      return `/${this.userRole}/dashboard`;
    },
    absensiUrl() {
      return `/${this.userRole}/absensi`;
    },
    kehadiranUrl() {
      return `/${this.userRole}/kehadiran`;
    },
    rekapUrl() {
      return `/${this.userRole}/rekap`;
    },
    siswaUrl() {
      return `/${this.userRole}/students`;
    },
    guruUrl() {
      return this.userRole === 'kesiswaan' ? '/kesiswaan/teachers' : '/admin/guru';
    },
    kelasUrl() {
      return this.userRole === 'kesiswaan' ? '/kesiswaan/classes' : '/admin/classes';
    },
    pengaturanUrl() {
      return '/admin/settings';
    }
  }
};
</script>

<style scoped>
/* Sidebar styles are handled by the main layout CSS */
</style>
