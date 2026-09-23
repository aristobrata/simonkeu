<?php

namespace App\Controllers;

use App\Libraries\ExportPdf;
use App\Libraries\ExportXlsx;
use App\Libraries\ReportBuilder;
use App\Models\AuditLogModel;
use App\Models\JenisAktivitasModel;
use App\Models\PelaksanaanModel;
use App\Models\TransaksiModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Laporan extends BaseController
{
    /** Filter dari query string (dipakai halaman ini maupun tautan ekspor dari daftar transaksi). */
    private function filter(string $kode): array
    {
        $g = fn (string $k) => $this->request->getGet($k);
        $f = [
            'tahun'        => (int) $g('tahun'),
            'bulan'        => (int) $g('bulan'),
            'bulan_dari'   => (int) ($g('dari') ?: 1),
            'bulan_sampai' => (int) ($g('sampai') ?: 12),
            'jenis'        => (int) $g('jenis'),
            'pelaksanaan'  => (string) $g('pelaksanaan'),
            'akun'         => (int) $g('akun'),
            'cc'           => (int) $g('cc'),
            'status'       => (string) $g('status'),
            'q'            => trim((string) $g('q')),
        ];
        // Rekap wajib per tahun; daftar transaksi boleh lintas tahun.
        if ($f['tahun'] < 2000 && $kode !== 'detail') {
            $f['tahun'] = (new TransaksiModel())->daftarTahun()[0] ?? (int) date('Y');
        }

        return $f;
    }

    public function index()
    {
        $kode = (string) $this->request->getGet('laporan');
        if (! isset(ReportBuilder::LAPORAN[$kode])) {
            $kode = 'rekap_bulanan';
        }
        $f   = $this->filter($kode);
        $lap = (new ReportBuilder())->bangun($kode, $f);

        $query = array_filter([
            'laporan' => $kode, 'tahun' => $f['tahun'] ?: null, 'dari' => $f['bulan_dari'], 'sampai' => $f['bulan_sampai'],
            'jenis' => $f['jenis'] ?: null, 'pelaksanaan' => $f['pelaksanaan'] !== '' ? $f['pelaksanaan'] : null,
        ], static fn ($v) => $v !== null);

        return view('laporan/index', [
            'kode'            => $kode,
            'lap'             => $lap,
            'f'               => $f,
            'tahunList'       => (new TransaksiModel())->daftarTahun(),
            'jenisList'       => (new JenisAktivitasModel())->opsi(),
            'pelaksanaanList' => (new PelaksanaanModel())->opsi(),
            'query'           => $query,
        ]);
    }

    /** laporan/unduh/{kode}?format=xlsx|pdf|csv + filter */
    public function download(string $kode)
    {
        if (! isset(ReportBuilder::LAPORAN[$kode])) {
            throw PageNotFoundException::forPageNotFound('Jenis laporan tidak dikenal.');
        }
        $format = strtolower((string) $this->request->getGet('format'));
        if (! in_array($format, ['xlsx', 'pdf', 'csv'], true)) {
            $format = 'xlsx';
        }

        set_time_limit(180);
        ini_set('memory_limit', '512M');

        $f   = $this->filter($kode);
        $lap = (new ReportBuilder())->bangun($kode, $f);

        $nama = 'SIMONKEU_' . preg_replace('/[^A-Za-z0-9]+/', '-', $lap['judul']) . '_' . ($f['tahun'] ?: 'semua') . '_' . date('Ymd');
        $nama = trim(preg_replace('/-+/', '-', $nama), '-');

        AuditLogModel::catat('ekspor', 'laporan', null, $lap['judul'] . ' (' . strtoupper($format) . ') — ' . $lap['meta']['periode'] . ' — ' . $lap['meta']['filter']);

        switch ($format) {
            case 'pdf':
                return $this->kirimFile((new ExportPdf())->buat($lap), $nama . '.pdf', 'application/pdf');

            case 'csv':
                return $this->kirimFile($this->csv($lap), $nama . '.csv', 'text/csv; charset=utf-8');

            default:
                return $this->kirimFile((new ExportXlsx())->buat($lap), $nama . '.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
    }

    /** CSV (pemisah titik koma agar terbuka benar di Excel berlokal Indonesia), UTF-8 dengan BOM. */
    private function csv(array $lap): string
    {
        // Untuk laporan gabungan, CSV berisi daftar transaksi.
        $ds = null;
        foreach ($lap['datasets'] as $d) {
            if ($d['kode'] === 'detail') {
                $ds = $d;
            }
        }
        $ds ??= $lap['datasets'][0];

        $h = fopen('php://temp', 'r+');
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, array_column($ds['kolom'], 'label'), ';');
        foreach ($ds['baris'] as $r) {
            if (($r['kind'] ?? 'normal') === 'group') {
                continue;
            }
            $row = [];
            foreach ($ds['kolom'] as $k) {
                $v = $r[$k['key']] ?? '';
                $row[] = in_array($k['tipe'], ['uang', 'angka'], true) && $v !== '' ? (string) (float) $v : ($k['tipe'] === 'persen' && $v !== '' ? number_format((float) $v, 4, ',', '') : $v);
            }
            fputcsv($h, $row, ';');
        }
        rewind($h);
        $isi = (string) stream_get_contents($h);
        fclose($h);

        return $isi;
    }
}
