<?php

namespace App\Libraries;

use App\Models\TransaksiModel;

/** Agregasi untuk dashboard dan laporan. Semua query memakai filter yang sama (TransaksiModel::terapkanFilter). */
class Statistik
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    private function dasar(array $f)
    {
        $b = $this->db->table('transaksi t')->where('t.deleted_at', null);

        return TransaksiModel::terapkanFilter($b, $f, 't');
    }

    private const SUMS = 'COUNT(*) AS jumlah, COALESCE(SUM(t.rencana_anggaran),0) AS rencana, COALESCE(SUM(t.tambahan_anggaran),0) AS tambahan, '
        . 'COALESCE(SUM(t.realisasi_anggaran),0) AS realisasi, COALESCE(SUM(t.total_biaya),0) AS total';

    /** Semua data dashboard dalam satu paket JSON. */
    public function dashboard(array $f): array
    {
        $dari   = max(1, (int) ($f['bulan_dari'] ?? 1));
        $sampai = min(12, (int) ($f['bulan_sampai'] ?? 12));
        if ($sampai < $dari) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        $f['bulan_dari'] = $dari;
        $f['bulan_sampai'] = $sampai;

        $bulanan = $this->bulanan($f, $dari, $sampai);
        $jenis   = $this->perJenis($f);

        return [
            'filter'      => ['tahun' => (int) ($f['tahun'] ?? 0), 'dari' => $dari, 'sampai' => $sampai, 'jenis' => (int) ($f['jenis'] ?? 0)],
            'kpi'         => $this->kpi($f, $bulanan),
            'bulanan'     => $bulanan,
            'jenis'       => $jenis,
            'jenis_bulan' => $this->jenisBulan($f, $dari, $sampai, $jenis),
            'komponen'    => $this->komponen($f),
            'pelaksanaan' => $this->perPelaksanaan($f),
            'akun'        => $this->perAkun($f),
            'cost_center' => $this->perCostCenter($f),
            'top'         => $this->topKegiatan($f, 10),
            'terbaru'     => $this->terbaru($f, 8),
            'anggaran_tahunan' => (new \App\Models\AnggaranModel())->ringkasan((int) ($f['tahun'] ?? 0)),
        ];
    }

    private function kpi(array $f, array $bulanan): array
    {
        $r = $this->dasar($f)->select(self::SUMS . ', COALESCE(SUM(t.jml_peserta),0) AS peserta', false)->get()->getRowArray();

        $anggaran = (float) $r['rencana'] + (float) $r['tambahan'];
        $terisi   = array_values(array_filter($bulanan, static fn ($b) => $b['jumlah'] > 0));

        $terakhir = $terisi ? $terisi[count($terisi) - 1] : null;
        $sebelum  = null;
        if ($terakhir) {
            foreach ($bulanan as $b) {
                if ($b['bulan'] === $terakhir['bulan'] - 1) {
                    $sebelum = $b;
                }
            }
        }
        $puncak = null;
        foreach ($terisi as $b) {
            if ($puncak === null || $b['realisasi'] > $puncak['realisasi']) {
                $puncak = $b;
            }
        }

        return [
            'jumlah'        => (int) $r['jumlah'],
            'rencana'       => (float) $r['rencana'],
            'tambahan'      => (float) $r['tambahan'],
            'anggaran'      => $anggaran,
            'realisasi'     => (float) $r['realisasi'],
            'total_biaya'   => (float) $r['total'],
            'sisa'          => $anggaran - (float) $r['realisasi'],
            'serapan'       => $anggaran > 0 ? (float) $r['realisasi'] / $anggaran * 100 : 0,
            'peserta'       => (int) $r['peserta'],
            'bulan_berdata' => count($terisi),
            'rata_bulanan'  => $terisi ? (float) $r['realisasi'] / count($terisi) : 0,
            'bulan_terakhir' => $terakhir ? ['bulan' => $terakhir['bulan'], 'label' => $terakhir['label'], 'realisasi' => $terakhir['realisasi']] : null,
            'bulan_sebelum'  => ($sebelum && $sebelum['realisasi'] > 0) ? ['label' => $sebelum['label'], 'realisasi' => $sebelum['realisasi']] : null,
            'perubahan_pct'  => ($terakhir && $sebelum && $sebelum['realisasi'] > 0) ? ($terakhir['realisasi'] - $sebelum['realisasi']) / $sebelum['realisasi'] * 100 : null,
            'bulan_puncak'   => $puncak ? ['label' => $puncak['label'], 'realisasi' => $puncak['realisasi']] : null,
            'selisih_rincian' => (float) $r['realisasi'] - (float) $r['total'],
            'perlu_diperiksa' => (new DataChecks())->jumlahPerluDiperiksa($f),
        ];
    }

    /** Satu entri per bulan pada rentang, termasuk yang kosong. */
    public function bulanan(array $f, int $dari = 1, int $sampai = 12): array
    {
        $rows = $this->dasar($f)->select('t.periode_bulan AS bulan, ' . self::SUMS, false)->groupBy('t.periode_bulan')->get()->getResultArray();
        $map  = [];
        foreach ($rows as $r) {
            $map[(int) $r['bulan']] = $r;
        }

        $out = [];
        $kumRencana = $kumRealisasi = 0.0;
        for ($m = $dari; $m <= $sampai; $m++) {
            $r = $map[$m] ?? null;
            $kumRencana   += $r ? (float) $r['rencana'] + (float) $r['tambahan'] : 0;
            $kumRealisasi += $r ? (float) $r['realisasi'] : 0;
            $out[] = [
                'bulan'     => $m,
                'label'     => bulan_id($m),
                'jumlah'    => $r ? (int) $r['jumlah'] : 0,
                'rencana'   => $r ? (float) $r['rencana'] : 0.0,
                'tambahan'  => $r ? (float) $r['tambahan'] : 0.0,
                'anggaran'  => $r ? (float) $r['rencana'] + (float) $r['tambahan'] : 0.0,
                'realisasi' => $r ? (float) $r['realisasi'] : 0.0,
                'total'     => $r ? (float) $r['total'] : 0.0,
                'kum_anggaran'  => $kumRencana,
                'kum_realisasi' => $kumRealisasi,
            ];
        }

        // Kumulatif dipotong setelah bulan terakhir yang memiliki data.
        $lastIdx = -1;
        foreach ($out as $i => $b) {
            if ($b['jumlah'] > 0) {
                $lastIdx = $i;
            }
        }
        foreach ($out as $i => &$b) {
            if ($i > $lastIdx) {
                $b['kum_anggaran'] = null;
                $b['kum_realisasi'] = null;
            }
        }

        return $out;
    }

    /** @return list<array{id:int,nama:string,jumlah:int,rencana:float,realisasi:float}> urut realisasi terbesar */
    public function perJenis(array $f): array
    {
        $rows = $this->dasar($f)->select('j.id, j.nama, ' . self::SUMS, false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->groupBy('j.id')->groupBy('j.nama')->orderBy('realisasi', 'DESC')->get()->getResultArray();

        return array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'nama' => $r['nama'], 'jumlah' => (int) $r['jumlah'],
            'rencana' => (float) $r['rencana'] + (float) $r['tambahan'], 'realisasi' => (float) $r['realisasi'],
        ], $rows);
    }

    /** Realisasi jenis × bulan: enam jenis teratas + "Lainnya" untuk grafik; seluruh jenis untuk matriks. */
    private function jenisBulan(array $f, int $dari, int $sampai, array $jenis): array
    {
        $rows = $this->dasar($f)->select('j.id, t.periode_bulan AS bulan, COALESCE(SUM(t.realisasi_anggaran),0) AS realisasi', false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->groupBy('j.id')->groupBy('t.periode_bulan')->get()->getResultArray();

        $sel = [];
        foreach ($rows as $r) {
            $sel[(int) $r['id']][(int) $r['bulan']] = (float) $r['realisasi'];
        }

        $bulan = range($dari, $sampai);
        $baris = [];
        foreach ($jenis as $j) {
            $nilai = [];
            foreach ($bulan as $m) {
                $nilai[] = $sel[$j['id']][$m] ?? 0.0;
            }
            $baris[] = ['nama' => $j['nama'], 'nilai' => $nilai, 'total' => array_sum($nilai)];
        }

        // Untuk grafik bertumpuk: 6 teratas + Lainnya.
        $seri = array_slice($baris, 0, 6);
        if (count($baris) > 6) {
            $lain = array_fill(0, count($bulan), 0.0);
            foreach (array_slice($baris, 6) as $b) {
                foreach ($b['nilai'] as $i => $v) {
                    $lain[$i] += $v;
                }
            }
            $seri[] = ['nama' => 'Lainnya', 'nilai' => $lain, 'total' => array_sum($lain)];
        }

        return [
            'labels' => array_map(static fn ($m) => bulan_id($m), $bulan),
            'seri'   => $seri,
            'matriks' => $baris,
        ];
    }

    /** Komponen biaya (M–U) bernilai > 0, terbesar dulu. */
    public function komponen(array $f): array
    {
        $kolom = config('Simonkeu')->komponen;
        $sel   = [];
        foreach (array_keys($kolom) as $k) {
            $sel[] = "COALESCE(SUM(t.$k),0) AS $k";
        }
        $r   = $this->dasar($f)->select(implode(', ', $sel), false)->get()->getRowArray() ?: [];
        $out = [];
        foreach ($kolom as $k => $label) {
            if ((float) ($r[$k] ?? 0) > 0) {
                $out[] = ['kunci' => $k, 'label' => $label, 'nilai' => (float) $r[$k]];
            }
        }
        usort($out, static fn ($a, $b) => $b['nilai'] <=> $a['nilai']);

        return $out;
    }

    public function perPelaksanaan(array $f): array
    {
        $rows = $this->dasar($f)->select("COALESCE(p.nama, 'Tidak diisi') AS nama, " . self::SUMS, false)
            ->join('pelaksanaan p', 'p.id = t.pelaksanaan_id', 'left')
            ->groupBy("COALESCE(p.nama, 'Tidak diisi')", false)->orderBy('realisasi', 'DESC')->get()->getResultArray();

        return array_map(static fn ($r) => ['nama' => $r['nama'], 'jumlah' => (int) $r['jumlah'], 'realisasi' => (float) $r['realisasi']], $rows);
    }

    public function perAkun(array $f): array
    {
        $rows = $this->dasar($f)->select("a.kode, a.nama, " . self::SUMS, false)
            ->join('akun a', 'a.id = t.akun_id')->groupBy('a.id')->groupBy('a.kode')->groupBy('a.nama')->orderBy('realisasi', 'DESC')->get()->getResultArray();

        return array_map(static fn ($r) => ['kode' => $r['kode'], 'nama' => $r['nama'], 'jumlah' => (int) $r['jumlah'], 'realisasi' => (float) $r['realisasi']], $rows);
    }

    public function perCostCenter(array $f): array
    {
        $rows = $this->dasar($f)->select("c.kode, c.nama, " . self::SUMS, false)
            ->join('cost_center c', 'c.id = t.cost_center_id')->groupBy('c.id')->groupBy('c.kode')->groupBy('c.nama')->orderBy('realisasi', 'DESC')->get()->getResultArray();

        return array_map(static fn ($r) => ['kode' => $r['kode'], 'nama' => $r['nama'], 'jumlah' => (int) $r['jumlah'], 'realisasi' => (float) $r['realisasi']], $rows);
    }

    public function topKegiatan(array $f, int $n = 10): array
    {
        $rows = $this->dasar($f)->select('t.id, t.aktivitas, t.tgl_mulai, t.realisasi_anggaran, j.nama AS jenis', false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->orderBy('t.realisasi_anggaran', 'DESC')->orderBy('t.id', 'ASC')->limit($n)->get()->getResultArray();

        return array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'aktivitas' => $r['aktivitas'], 'jenis' => $r['jenis'],
            'tgl' => $r['tgl_mulai'], 'realisasi' => (float) $r['realisasi_anggaran'],
        ], $rows);
    }

    public function terbaru(array $f, int $n = 8): array
    {
        $rows = $this->dasar($f)->select('t.id, t.aktivitas, t.tgl_mulai, t.realisasi_anggaran, t.rencana_anggaran, j.nama AS jenis, p.nama AS pelaksanaan', false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')->join('pelaksanaan p', 'p.id = t.pelaksanaan_id', 'left')
            ->orderBy('t.tgl_mulai', 'DESC')->orderBy('t.id', 'DESC')->limit($n)->get()->getResultArray();

        return array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'aktivitas' => $r['aktivitas'], 'jenis' => $r['jenis'], 'pelaksanaan' => $r['pelaksanaan'],
            'tgl' => $r['tgl_mulai'], 'rencana' => (float) $r['rencana_anggaran'], 'realisasi' => (float) $r['realisasi_anggaran'],
        ], $rows);
    }
}
