<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Presensi Guru</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h2 { text-align: center; margin: 0 0 4px; font-size: 12px; }
        p.sub { text-align: center; margin: 0 0 10px; font-size: 9px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { border: 1px solid #333; padding: 3px 4px; }
        table.data th { background: #e5e7eb; text-transform: uppercase; }
        table.matrix { font-size: 7px; }
        table.matrix th, table.matrix td { padding: 2px 1px; text-align: center; }
        .c { text-align: center; }
        .hol { background: #fee2e2; }
        .sign { width: 100%; margin-top: 24px; border-collapse: collapse; }
        .sign td { border: none; text-align: center; width: 50%; }
    </style>
</head>
<body>
    <h2>LAPORAN REKAPITULASI PRESENSI {{ $periodLabel ?? '' }} - SMP PGRI PARUNG PANJANG</h2>
    <p class="sub">Rekap Presensi Guru</p>

    @if (($type ?? 'harian') === 'harian')
        <table class="data">
            <thead>
                <tr>
                    <th class="c">No</th>
                    <th>NIP</th>
                    <th>Nama Guru</th>
                    <th class="c">JK</th>
                    <th class="c">Jam Masuk</th>
                    <th class="c">Keterlambatan</th>
                    <th class="c">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($dataRows ?? []) as $i => $r)
                    <tr>
                        <td class="c">{{ $i + 1 }}</td>
                        <td>{{ $r['nip'] ?? '-' }}</td>
                        <td>{{ strtoupper($r['name'] ?? '') }}</td>
                        <td class="c">{{ $r['jk'] ?? '-' }}</td>
                        <td class="c">{{ $r['check_in'] ?? '-' }}</td>
                        <td class="c">{{ $r['late_text'] ?? '-' }}</td>
                        <td class="c">{{ strtoupper($r['status'] ?? '-') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="c" colspan="7">Belum ada data guru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="data matrix">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIP</th>
                    <th>Nama Guru</th>
                    <th>JK</th>
                    @foreach (($dateColumns ?? []) as $col)
                        <th class="{{ $col['is_holiday'] ? 'hol' : '' }}">{{ $col['label'] }}</th>
                    @endforeach
                    <th>H</th>
                    <th>T</th>
                    <th>S</th>
                    <th>I</th>
                    <th>A</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($dataRows ?? []) as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $r['nip'] ?? '-' }}</td>
                        <td style="text-align:left;">{{ strtoupper($r['name'] ?? '') }}</td>
                        <td>{{ $r['jk'] ?? '-' }}</td>
                        @foreach (($dateColumns ?? []) as $col)
                            <td class="{{ $col['is_holiday'] ? 'hol' : '' }}">{{ ($r['days'] ?? [])[$col['date']] ?? '-' }}</td>
                        @endforeach
                        <td>{{ $r['hadir'] ?? 0 }}</td>
                        <td>{{ $r['terlambat'] ?? 0 }}</td>
                        <td>{{ $r['sakit'] ?? 0 }}</td>
                        <td>{{ $r['izin'] ?? 0 }}</td>
                        <td>{{ $r['alfa'] ?? 0 }}</td>
                        <td>{{ $r['percentage'] ?? 0 }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 4 + count($dateColumns ?? []) + 6 }}">Belum ada data guru.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <table class="sign">
        <tr>
            <td></td>
            <td>
                Parung Panjang, {{ $printedAt ?? '' }}<br>
                Kepala Sekolah<br><br><br><br>
                ( ................................ )
            </td>
        </tr>
    </table>
</body>
</html>