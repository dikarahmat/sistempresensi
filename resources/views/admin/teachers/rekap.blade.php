@section('title', __('Rekap Presensi Guru'))
@section('page_title', __('Rekap Presensi Guru'))

@push('styles')
<style>
    .mode-btn { transition: all 0.15s ease; border: 1px solid #dee2e6; }
    .mode-btn.active { background-color: #e3f2fd; border-color: #1976d2; color: #1976d2; }
    .mode-btn:not(.active):hover { background-color: #f5f5f5; }
    .tab-btn { transition: color 0.15s ease, background-color 0.15s ease; border: 1px solid #dee2e6; margin: 2px; }
    .tab-btn.active { background-color: #e3f2fd; color: #1976d2; border-color: #1976d2; }
    .teacher-recap-table th { background-color: #f8f9fa; border: 1px solid #dee2e6; white-space: nowrap; }
    .teacher-recap-table td { border: 1px solid #dee2e6; white-space: nowrap; }
    .status-hadir { background-color: #e8f5e9; color: #2e7d32; }
    .status-terlambat { background-color: #fff3e0; color: #ef6c00; }
    .status-alfa { background-color: #ffebee; color: #c62828; }
    .status-izin { background-color: #e3f2fd; color: #1565c0; }
    .status-sakit { background-color: #f1f8e9; color: #33691e; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-list me-2"></i> {{ __('Rekap Presensi Guru') }}</h4>
                <div class="mode-switch d-flex gap-2">
                    <button class="mode-btn active" data-mode="guru">{{ __('Guru') }}</button>
                    <button class="mode-btn" data-mode="siswa">{{ __('Siswa') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="btn-group" role="tablist" id="period-tabs">
                <button class="tab-btn active" data-period="harian" type="button">{{ __('Harian') }}</button>
                <button class="tab-btn" data-period="mingguan" type="button">{{ __('Mingguan') }}</button>
                <button class="tab-btn" data-period="bulanan" type="button">{{ __('Bulanan') }}</button>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-body">
                    <form id="rekap-filter-form">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label>{{ __('Tanggal') }}</label>
                                <input type="date" name="date" class="form-control" id="tanggal-filter">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>{{ __('Guru') }}</label>
                                <select name="teacher_id" class="form-select" id="guru-select">
                                    <option value="">-- Semua Guru --</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <button type="submit" class="btn btn-primary w-100">{{ __('Tampilkan') }}</button>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="{{ route('rekap.guru.pdf') }}" class="btn btn-success w-100">{{ __('CETAK PDF') }}</a>
                            </div>
                            <div class="col-md-2 mb-2">
                                <a href="{{ route('rekap.guru.excel') }}" class="btn btn-warning w-100">{{ __('CETAK EXCEL') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-info">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ __('Daftar Kehadiran Guru') }}</h4>
                    <div class="text-muted small" id="info-text">Pilih periode dan guru untuk melihat rekap</div>
                </div>
                <div class="card-body p-0">
                    <table class="table teacher-recap-table mb-0" id="rekap-table">
                        <thead>
                            <tr>
                                <th>{{ '#' }}</th>
                                <th>{{ 'NIP' }}</th>
                                <th>{{ 'Nama Guru' }}</th>
                                <th>{{ 'Jenis Kelamin' }}</th>
                                <th>{{ 'Jam Masuk' }}</th>
                                <th>{{ 'Keterlambatan' }}</th>
                                <th>{{ 'STATUS' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsec

@push('scripts')
<script>
    $(document).ready(function() {
        var table = $('#rekap-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('rekap.guru.data') }}',
                data: function(d) {
                    d.type = $('button.mode-btn.active').data('mode') === 'guru' ? 'guru' : 'siswa';
                    d.period = $('#period-tabs .active').data('period');
                    d.date = $('#tanggal-filter').val();
                    d.teacher_id = $('#guru-select').val();
                }
            },
            columns: [
                { data: 'no' },
                { data: 'nip' },
                { data: 'name' },
                { data: 'gender' },
                { data: 'check_in' },
                { data: 'late_text' },
                { data: 'status' }
            ]
        });

        $('.mode-switch .mode-btn').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            var mode = $(this).data('mode');
            table.ajax.url('{{ route('rekap.guru.data') }}?mode=' + mode).load();
            if (mode === 'guru') {
                $('#guru-select').closest('div.col-md-3').show();
            } else {
                $('#guru-select').closest('div.col-md-3').hide();
                table.ajax.reload();
            }
        });

        $('#period-tabs .tab-btn').on('click', function() {
            $(this).addClass('active').siblings().removeClass('active');
            table.ajax.reload();
        });

        $('#tanggal-filter').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
@endpush
