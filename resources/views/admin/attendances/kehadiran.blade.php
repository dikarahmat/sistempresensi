@extends('layouts.app')

@section('title', $viewFor === 'guru' ? __('Catatan Kehadiran Guru') : __('Catatan Kehadiran Siswa'))
@section('page_title', $viewFor === 'guru' ? __('CATATAN KEHADIRAN GURU') : __('CATATAN KEHADIRAN SISWA'))

@section('canvas_class', 'page-canvas-fixed')

@push('styles')
<style>
    .table-custom-card {
        background: #ffffff;
        border: 0 !important;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }

    .table-responsive {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    /* ==========================================================================
       FILTER BAR: [ Kolom Pencarian, Kelas, & Tombol Reset Responsif ]
       ========================================================================== */
    form#formFilterKehadiran.kehadiran-filter-form {
        --kh-h: 38px;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) var(--kh-h);
        grid-template-areas:
            "search reset"
            "kelas  kelas";
        column-gap: 8px;
        row-gap: 8px;
        align-items: center;
        width: 100% !important;
        flex: 1 1 100% !important;
        height: auto;
        min-height: 0;
        min-width: 0;
        margin: 0;
        padding: 0;
    }

    @media (min-width: 1024px) {
        form#formFilterKehadiran.kehadiran-filter-form {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) var(--kh-h);
            grid-template-areas: "search kelas reset";
            row-gap: 0;
            column-gap: 8px;
        }
    }

    form#formFilterKehadiran.kehadiran-filter-form.kh-no-kelas {
        grid-template-columns: minmax(0, 1fr) var(--kh-h);
        grid-template-areas: "search reset";
        row-gap: 0;
    }

    form#formFilterKehadiran.kehadiran-filter-form .search-box-wrap,
    form#formFilterKehadiran.kehadiran-filter-form .filter-box-wrap {
        flex: none !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .search-box-wrap {
        grid-area: search;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset {
        grid-area: reset;
    }
    form#formFilterKehadiran.kehadiran-filter-form .filter-box-wrap {
        grid-area: kelas;
    }

    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        height: var(--kh-h) !important;
        min-height: var(--kh-h) !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .form-control,
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn {
        height: var(--kh-h) !important;
        min-height: var(--kh-h) !important;
        box-sizing: border-box;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .form-control {
        min-width: 0;
        font-size: 0.85rem !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn i,
    form#formFilterKehadiran.kehadiran-filter-form .input-group.search-box-wrap > .btn .bx {
        font-size: 18px !important;
        line-height: 1;
    }

    form#formFilterKehadiran.kehadiran-filter-form .filter-box-wrap select.form-select {
        width: 100% !important;
        height: var(--kh-h) !important;
        min-height: var(--kh-h) !important;
        box-sizing: border-box;
        font-size: 0.82rem !important;
    }

    /* TOMBOL RESET KOTAK FLEKSIBEL (Disamakan dengan Data Guru: warna #64748b, ukuran 38px) */
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset {
        flex: 0 0 var(--kh-h) !important;
        width: var(--kh-h) !important;
        height: var(--kh-h) !important;
        min-width: var(--kh-h) !important;
        max-width: var(--kh-h) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-sizing: border-box !important;
        padding: 0 !important;
        background: #64748b !important;
        border: 1px solid #64748b !important;
        border-radius: 6px !important;
        color: #ffffff !important;
        text-decoration: none !important;
        cursor: pointer !important;
        outline: none !important;
        transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset svg {
        width: 18px !important;
        height: 18px !important;
        stroke: currentColor !important;
        fill: none !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset:hover,
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset:focus-visible {
        background: #475569 !important;
        border-color: #475569 !important;
        color: #ffffff !important;
        text-decoration: none !important;
    }
    form#formFilterKehadiran.kehadiran-filter-form .btn-reset:focus-visible {
        box-shadow: 0 0 0 3px rgba(100, 116, 139, .3) !important;
    }

    .table-enterprise {
        width: 100%;
        white-space: nowrap;
    }

    .table-enterprise thead th {
        font-weight: 700 !important;
        background-color: #f8fafc;
        color: #000000 !important;
        font-size: 0.78rem;
        padding: 0.85rem 1.1rem;
        border-bottom: 1.5px solid #edf2f7;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        vertical-align: middle;
        white-space: nowrap;
    }

    .table-enterprise tbody tr:nth-child(even) > td,
    .table-enterprise tbody tr.baris-abu > td,
    .table-zebra-custom tbody tr:nth-child(even) > td,
    .table-zebra-custom tbody tr.baris-abu > td {
        background-color: #f1f5f9 !important;
    }
    .table-enterprise tbody tr:nth-child(odd) > td,
    .table-enterprise tbody tr.baris-putih > td,
    .table-zebra-custom tbody tr:nth-child(odd) > td,
    .table-zebra-custom tbody tr.baris-putih > td {
        background-color: #ffffff !important;
    }
    .table-enterprise tbody tr.baris-abu:hover > td,
    .table-enterprise tbody tr.baris-putih:hover > td,
    .table-enterprise tbody tr:hover > td,
    .table-zebra-custom tbody tr.baris-abu:hover > td,
    .table-zebra-custom tbody tr.baris-putih:hover > td,
    .table-zebra-custom tbody tr:hover > td {
        background-color: #e2e8f0 !important;
    }
    .table-enterprise tbody td {
        padding: 0.85rem 1.1rem;
        font-size: 0.86rem;
        font-weight: 400 !important;
        color: #000000 !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        white-space: nowrap !important;
    }

    .btn-action-lihat {
        background: transparent !important;
        border: none;
        border-radius: 0 !important;
        padding: 0.2rem;
        font-size: 0.8rem;
        font-weight: 500;
        color: #16a34a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        text-decoration: none;
        white-space: nowrap;
        transition: color 0.15s ease, opacity 0.15s ease;
        box-shadow: none !important;
        cursor: pointer;
        width: auto;
        height: auto;
    }
    .btn-action-lihat:hover {
        color: #15803d;
        opacity: 0.85;
        box-shadow: none !important;
    }
    .btn-action-lihat svg {
        display: block;
        width: 20px;
        height: 20px;
    }

    #daftar-kehadiran {
        display: block;
        height: auto !important;
        max-height: none !important;
        min-height: 0 !important;
        margin-bottom: 0 !important;
    }

    #daftar-kehadiran > .p-3 {
        height: auto;
    }

    #daftar-kehadiran .table-responsive {
        max-height: none !important;
        overflow-y: visible !important;
    }

    #daftar-kehadiran .table-responsive > table > thead {
        z-index: 5 !important;
    }

    #daftar-kehadiran .table-responsive > table > thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 5 !important;
        background-color: #f8fafc !important;
    }

    #daftar-kehadiran .table-responsive > table > tbody > tr > td {
        position: relative;
        z-index: 1;
    }

    .kehadiran-pagination {
        padding: 1.25rem 0.5rem 1.5rem;
        margin: 0;
        text-align: center;
        border-top: 1px solid #f1f5f9;
        background-color: #ffffff;
        width: 100%;
        box-sizing: border-box;
    }

    .kehadiran-pagination-list {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.15rem 1.1rem;
        list-style: none;
        margin: 0 0 0.35rem;
        padding: 0;
    }

    .kehadiran-pagination-step,
    .kehadiran-pagination-page {
        display: inline-block;
        padding: 0.45rem 0.3rem;
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.2;
        text-decoration: none;
        background: none !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        color: #64748b;
        cursor: pointer;
    }

    .kehadiran-pagination-page.is-active {
        color: #2563eb !important;
        font-weight: 700;
        border-bottom: 2px solid #2563eb !important;
        cursor: default;
    }

    .kehadiran-pagination-step:hover,
    .kehadiran-pagination-page:hover {
        color: #2563eb !important;
        text-decoration: underline;
    }

    .kehadiran-pagination-step:focus-visible,
    .kehadiran-pagination-page:focus-visible {
        outline: 2px solid #2563eb !important;
        outline-offset: 1px;
        border-radius: 0 !important;
    }

    .kehadiran-pagination-step.is-disabled {
        color: #cbd5e1;
        cursor: not-allowed;
        pointer-events: none;
    }

    .kehadiran-pagination-step.is-disabled:hover {
        color: #cbd5e1;
        text-decoration: none;
    }

    .kehadiran-pagination-ellipsis {
        display: inline-block;
        padding: 0.45rem 0.1rem;
        font-size: 0.95rem;
        color: #94a3b8;
        line-height: 1.2;
    }

    .kehadiran-pagination-info {
        margin: 0;
        font-size: 0.85rem;
        color: #64748b;
        letter-spacing: 0.02em;
    }

    @media (max-width: 767.98px) {
        .table-enterprise thead th {
            font-size: 0.75rem;
            padding: 0.7rem 0.9rem;
        }

        .table-enterprise tbody td {
            font-size: 0.82rem;
            padding: 0.7rem 0.9rem;
        }

        .btn-action-lihat svg {
            width: 18px;
            height: 18px;
        }
    }
</style>
@endpush

@section('content')
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden md:mb-4 bg-white" id="daftar-kehadiran">

        <div class="p-3 p-md-4 border-bottom border-gray-100 bg-white">
            <form method="GET" action="{{ $viewFor === 'guru' ? panel_route('kehadiran.guru') : panel_route('kehadiran') }}" class="action-search-form kehadiran-filter-form {{ $viewFor === 'guru' ? 'kh-no-kelas' : '' }}" id="formFilterKehadiran">
                <div class="input-group search-box-wrap">
                    <input type="text"
                           name="search"
                           id="filterSearch"
                           class="form-control shadow-none"
                           placeholder="{{ $viewFor === 'guru' ? __('Cari nama atau NIP...') : __('Cari nama atau NISN...') }}"
                           value="{{ $search }}"
                           aria-label="{{ $viewFor === 'guru' ? __('Cari nama atau NIP') : __('Cari nama atau NISN') }}"
                           autocomplete="off">
                    <button class="btn shadow-none" type="submit" title="{{ __('Cari') }}" aria-label="{{ __('Cari') }}">
                        <i class='bx bx-search fs-6'></i>
                    </button>
                </div>

                @if($viewFor !== 'guru')
                <div class="filter-box-wrap">
                    <select name="class_filter" id="classFilter" class="form-select border-secondary-subtle shadow-none fw-normal w-100" aria-label="{{ __('Semua Kelas') }}">
                        <option value="" {{ request('class_filter') == '' ? 'selected' : '' }}>{{ __('Semua Kelas') }}</option>
                        @foreach($classHistories as $class)
                            <option value="{{ $class->name }}" {{ request('class_filter') == $class->name ? 'selected' : '' }}>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Tombol Reset (Disamakan warna & ukurannya dengan Data Guru) -->
                <a href="{{ $viewFor === 'guru' ? panel_route('kehadiran.guru') : panel_route('kehadiran') }}" class="btn-reset" role="button" title="{{ __('Reset pencarian') }}" aria-label="{{ __('Reset pencarian') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/>
                    </svg>
                </a>
            </form>
        </div>

        <div class="table-responsive" style="padding: 0 24px;">
            <table class="table table-hover align-middle mb-0 table-enterprise table-zebra-custom">
                <thead class="bg-light">
                    <tr class="small fw-bold text-uppercase" style="letter-spacing: 0.03em; color: #000000 !important;">
                        <th class="text-center py-3" style="width: 60px;">{{ __('No') }}</th>
                        <th class="text-start py-3" style="padding-left: 20px;">{{ $viewFor === 'guru' ? __('NIP') : __('NISN') }}</th>
                        <th class="text-start py-3" style="padding-left: 20px;">{{ $viewFor === 'guru' ? __('Nama Guru') : __('Nama Siswa') }}</th>
                        @if($viewFor !== 'guru')
                        <th class="text-center py-3">{{ __('Kelas') }}</th>
                        @endif
                        <th class="text-center py-3"><span style="color:#059669 !important; font-weight: 700 !important;">{{ __('Hadir') }}</span></th>
                        @if($viewFor !== 'guru')
                        <th class="text-center py-3"><span style="color:#d97706 !important; font-weight: 700 !important;">{{ __('Terlambat') }}</span></th>
                        @endif
                        <th class="text-center py-3"><span style="color:#2563eb !important; font-weight: 700 !important;">{{ __('Sakit') }}</span></th>
                        <th class="text-center py-3"><span style="color:#7e22ce !important; font-weight: 700 !important;">{{ __('Izin') }}</span></th>
                        <th class="text-center py-3"><span style="color:#dc2626 !important; font-weight: 700 !important;">{{ __('Alfa') }}</span></th>
                        <th class="text-center py-3">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentHistories ?? [] as $idx => $item)
                    @php
                        $rowNumber = ($studentHistories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            ? ($studentHistories->firstItem() + $idx)
                            : ($idx + 1);
                    @endphp
                    <tr class="{{ $loop->odd ? 'baris-abu' : 'baris-putih' }}">
                        <td class="text-center text-muted fw-normal">{{ $rowNumber }}</td>
                        <td class="text-start fw-normal" style="padding-left: 20px;">{{ $viewFor === 'guru' ? ($item->nip ?: '-') : ($item->nisn ?: '-') }}</td>
                        <td class="text-start fw-normal" style="padding-left: 20px;">{{ $item->name }}</td>
                        @if($viewFor !== 'guru')
                        <td class="text-center fw-normal">{{ $item->class_name }}</td>
                        @endif
                        <td class="text-center fw-bold" style="color:#059669 !important"><span style="color:#059669 !important; font-weight: 700 !important;">{{ $item->hadir }}</span></td>
                        @if($viewFor !== 'guru')
                        <td class="text-center fw-bold" style="color:#d97706 !important"><span style="color:#d97706 !important; font-weight: 700 !important;">{{ $item->terlambat }}</span></td>
                        @endif
                        <td class="text-center fw-bold" style="color:#2563eb !important"><span style="color:#2563eb !important; font-weight: 700 !important;">{{ $item->sakit }}</span></td>
                        <td class="text-center fw-bold" style="color:#7e22ce !important"><span style="color:#7e22ce !important; font-weight: 700 !important;">{{ $item->izin }}</span></td>
                        <td class="text-center fw-bold" style="color:#dc2626 !important"><span style="color:#dc2626 !important; font-weight: 700 !important;">{{ $item->alfa }}</span></td>
                        <td class="text-center">
                            @if($viewFor === 'guru')
                            <a href="{{ panel_route('kehadiran.guru-history', $item->id) }}" class="btn-action-lihat" title="{{ __('Lihat riwayat presensi') }} {{ $item->name }}" aria-label="{{ __('Lihat riwayat presensi') }} {{ $item->name }}">
                            @else
                            <a href="{{ panel_route('kehadiran.student-history', $item->id) }}" class="btn-action-lihat" title="{{ __('Lihat riwayat presensi') }} {{ $item->name }}" aria-label="{{ __('Lihat riwayat presensi') }} {{ $item->name }}">
                            @endif
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="20" height="20" style="display:block"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $viewFor === 'guru' ? 8 : 10 }}" class="text-center py-5 text-muted fw-normal empty-state" style="white-space: normal !important;">
                            <i class='bx bx-error' aria-hidden='true'></i>
                            @if(request('search') || request('class_filter'))
                                {{ $viewFor === 'guru' ? __('TIDAK ADA DATA GURU YANG COCOK DENGAN PENCARIAN.') : __('TIDAK ADA DATA SISWA YANG COCOK DENGAN PENCARIAN/FILTER.') }}
                            @else
                                {{ $viewFor === 'guru' ? __('BELUM ADA DATA GURU YANG TERDAFTAR.') : __('BELUM ADA DATA SISWA YANG TERDAFTAR.') }}
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

        @php
            $pgCurrent = $studentHistories->currentPage();
            $pgLast = $studentHistories->lastPage();
            $pgHash = '#daftar-kehadiran';

            $pgPages = [];
            if ($pgLast <= 7) {
                $pgPages = range(1, $pgLast);
            } else {
                $pgPages[] = 1;
                $pgStart = max(2, $pgCurrent - 1);
                $pgEnd = min($pgLast - 1, $pgCurrent + 1);
                if ($pgStart > 2) {
                    $pgPages[] = '...';
                }
                for ($i = $pgStart; $i <= $pgEnd; $i++) {
                    $pgPages[] = $i;
                }
                if ($pgEnd < $pgLast - 1) {
                    $pgPages[] = '...';
                }
                $pgPages[] = $pgLast;
            }
        @endphp

        @if ($pgLast > 1)
            <nav class="kehadiran-pagination" id="kehadiran-pagination" aria-label="{{ __('Navigasi halaman catatan kehadiran') }}">
                <ul class="kehadiran-pagination-list">
                    @if ($pgCurrent <= 1)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">&lsaquo; {{ __('Sebelumnya') }}</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $studentHistories->previousPageUrl() }}{{ $pgHash }}" rel="prev">&lsaquo; {{ __('Sebelumnya') }}</a></li>
                    @endif

                    @foreach ($pgPages as $pgItem)
                        @if ($pgItem === '...')
                            <li><span class="kehadiran-pagination-ellipsis" aria-hidden="true">&hellip;</span></li>
                        @elseif ($pgItem === $pgCurrent)
                            <li><span class="kehadiran-pagination-page is-active" aria-current="page">{{ $pgItem }}</span></li>
                        @else
                            <li><a class="kehadiran-pagination-page" href="{{ $studentHistories->url($pgItem) }}{{ $pgHash }}">{{ $pgItem }}</a></li>
                        @endif
                    @endforeach

                    @if ($pgCurrent >= $pgLast)
                        <li><span class="kehadiran-pagination-step is-disabled" aria-disabled="true">{{ __('Berikutnya') }} &rsaquo;</span></li>
                    @else
                        <li><a class="kehadiran-pagination-step" href="{{ $studentHistories->nextPageUrl() }}{{ $pgHash }}" rel="next">{{ __('Berikutnya') }} &rsaquo;</a></li>
                    @endif
                </ul>

                <p class="kehadiran-pagination-info">
                    {{ __('Menampilkan') }} {{ $studentHistories->firstItem() ?? 0 }}&ndash;{{ $studentHistories->lastItem() ?? 0 }}
                    {{ __('dari') }} {{ $studentHistories->total() }} {{ $viewFor === 'guru' ? __('guru') : __('siswa') }}
                </p>
            </nav>
        @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const classFilter = document.getElementById('classFilter');
            if (classFilter) {
                classFilter.addEventListener('change', function () {
                    this.form.submit();
                });
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('page')) {
                const el = document.getElementById('daftar-kehadiran');
                if (el) {
                    var scroller = el.closest('main');
                    scroller = scroller ? scroller.querySelector(':scope > .flex-1') : null;
                    var header = document.querySelector('.app-header-bar');
                    var offset = (header ? header.getBoundingClientRect().height : 0) + 12;
                    if (scroller) {
                        var rect = el.getBoundingClientRect();
                        var srect = scroller.getBoundingClientRect();
                        scroller.scrollTo({ top: scroller.scrollTop + (rect.top - srect.top) - offset, behavior: 'auto' });
                    } else {
                        el.scrollIntoView({ behavior: 'auto', block: 'start' });
                    }
                }
            }
        });
    </script>
    @endpush
@endsection