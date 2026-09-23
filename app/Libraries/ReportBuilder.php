<?php

namespace App\Libraries;

use App\Models\TransaksiModel;

/**
 * Membangun dataset laporan generik yang dipakai bersama oleh pratinjau HTML, Excel, PDF, dan CSV.
 *
 * Dataset:
 *   kode, judul, sheet, kolom[], baris[], total (bool)
 * Kolom:
 *   key, label, tipe (teks|angka|uang|persen|tanggal), lebar (relatif), rumus (opsional, token {key}), hanya ('xlsx' = tidak tampil di HTML/PDF)
 * Baris:
 *   kind (normal|group|subtotal) + nilai per key.
 */
class ReportBuilder
{
    public const LAPORAN = [
        'lengkap' => [
            'judul'     => 'Laporan lengkap',
            'deskripsi' => 'Semua rekap di bawah ditambah daftar transaksi. Cocok untuk arsip bulanan.',
            'set'       => ['rekap_bulanan', 'rekap_jenis', 'matriks_jenis', 'rekap_akun', 'komponen', 'pivot', 'detail'],
        ],
        'rekap_bulanan' => [
            'judul'     => 'Rekap per bulan',
            'deskripsi' => 'Rencana, realisasi, sisa anggaran, dan serapan setiap bulan.',
            'set'       => ['rekap_bulanan'],
        ],
        'rekap_jenis' => [
            'judul'     => 'Rekap per jenis aktivitas',
            'deskripsi' => 'Ringkasan tiap jenis aktivitas dan matriks jenis × bulan.',
            'set'       => ['rekap_jenis', 'matriks_jenis'],
        ],
        'rekap_akun' => [
            'judul'     => 'Rekap per akun dan cost center',
            'deskripsi' => 'Anggaran dan realisasi berdasarkan No. akun dan cost center.',
            'set'       => ['rekap_akun'],
        ],
        'komponen' => [
            'judul'     => 'Komponen biaya per bulan',
            'deskripsi' => 'Rincian biaya training, konsumsi, tiket, hotel, dan lainnya setiap bulan.',
            'set'       => ['komponen'],
        ],
        'pivot' => [
            'judul'     => 'Rincian bulan › jenis › aktivitas',
            'deskripsi' => 'Mengikuti susunan sheet PIVOT pada template: rencana dan realisasi per aktivitas, dengan subtotal tiap bulan.',
            'set'       => ['pivot'],
        ],
        'detail' => [
            'judul'     => 'Daftar transaksi',
            'deskripsi' => 'Seluruh baris transaksi dengan kolom seperti template laporan keuangan.',
            'set'       => ['detail'],
        ],
    ];

    /** kode dataset => metode pembangun */
    private const METODE = [
        'rekap_bulanan' => 'dsRekapBulanan', 'rekap_jenis' => 'dsRekapJenis', 'matriks_jenis' => 'dsMatriksJenis',
        'rekap_akun' => 'dsRekapAkun', 'komponen' => 'dsKomponen', 'pivot' => 'dsPivot', 'detail' => 'dsDetail',
    ];

    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * @param array<string,mixed> $f tahun, bulan_dari, bulan_sampai, jenis, + filter daftar (q, pelaksanaan, akun, cc, status, bulan)
     *
     * @return array{kode:string,judul:string,meta:array<string,string>,datasets:list<array<string,mixed>>}
     */
    public function bangun(string $kode, array $f): array
    {
        $def = self::LAPORAN[$kode] ?? throw new \InvalidArgumentException('Jenis laporan tidak dikenal.');

        $dari   = max(1, (int) ($f['bulan_dari'] ?? 1));
        $sampai = min(12, (int) ($f['bulan_sampai'] ?? 12));
        if ($sampai < $dari) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        $f['bulan_dari'] = $dari;
        $f['bulan_sampai'] = $sampai;

        $datasets = [];
        foreach ($def['set'] as $s) {
            $metode     = self::METODE[$s];
            $datasets[] = $this->denganTotal($this->$metode($f, $dari, $sampai));
        }

        return [
            'kode'     => $kode,
            'judul'    => $def['judul'],
            'meta'     => $this->meta($f, $dari, $sampai),
            'datasets' => $datasets,
        ];
    }

    private function meta(array $f, int $dari, int $sampai): array
    {
        $cfg = config('Simonkeu');
        if (! empty($f['tahun'])) {
            $periode = ($dari === 1 && $sampai === 12 && empty($f['bulan'])) ? 'Tahun ' . $f['tahun'] : periode_teks(! empty($f['bulan']) ? (int) $f['bulan'] : $dari, ! empty($f['bulan']) ? (int) $f['bulan'] : $sampai, (int) $f['tahun']);
        } else {
            $periode = 'Semua periode';
        }

        $filter = [];
        if (! empty($f['jenis'])) {
            $r = $this->db->table('jenis_aktivitas')->select('nama')->where('id', (int) $f['jenis'])->get()->getRowArray();
            $filter[] = 'Jenis: ' . ($r['nama'] ?? '#' . $f['jenis']);
        }
        if (isset($f['pelaksanaan']) && $f['pelaksanaan'] !== '') {
            $r = $this->db->table('pelaksanaan')->select('nama')->where('id', (int) $f['pelaksanaan'])->get()->getRowArray();
            $filter[] = 'Inhouse/Public: ' . ((string) $f['pelaksanaan'] === '0' ? '(tidak diisi)' : ($r['nama'] ?? '#' . $f['pelaksanaan']));
        }
        if (! empty($f['akun'])) {
            $r = $this->db->table('akun')->select('kode')->where('id', (int) $f['akun'])->get()->getRowArray();
            $filter[] = 'Akun: ' . ($r['kode'] ?? '#' . $f['akun']);
        }
        if (! empty($f['cc'])) {
            $r = $this->db->table('cost_center')->select('kode')->where('id', (int) $f['cc'])->get()->getRowArray();
            $filter[] = 'Cost center: ' . ($r['kode'] ?? '#' . $f['cc']);
        }
        if (! empty($f['status'])) {
            $filter[] = 'Status: ' . ($f['status'] === '-' ? '(belum diisi)' : $f['status']);
        }
        if (! empty($f['q'])) {
            $filter[] = 'Cari: "' . $f['q'] . '"';
        }

        return [
            'org'     => $cfg->orgName,
            'unit'    => $cfg->unitName,
            'periode' => $periode,
            'filter'  => $filter ? implode(' · ', $filter) : 'Semua jenis aktivitas',
            'dicetak' => 'Dicetak ' . tgl_id(date('Y-m-d'), false) . ' ' . date('H:i') . (current_user() ? ' oleh ' . current_user()['nama'] : ''),
        ];
    }

    private function dasar(array $f)
    {
        $b = $this->db->table('transaksi t')->where('t.deleted_at', null);

        return TransaksiModel::terapkanFilter($b, $f, 't');
    }

    private const SUMS = 'COUNT(*) AS jumlah, COALESCE(SUM(t.rencana_anggaran),0) AS rencana, COALESCE(SUM(t.tambahan_anggaran),0) AS tambahan, '
        . 'COALESCE(SUM(t.realisasi_anggaran),0) AS realisasi, COALESCE(SUM(t.total_biaya),0) AS total';

    /** Kolom standar anggaran: anggaran, realisasi, sisa, serapan. */
    private function kolomAnggaran(bool $denganTambahan = true): array
    {
        $k = [['key' => 'jumlah', 'label' => 'Transaksi', 'tipe' => 'angka', 'lebar' => 10],
            ['key' => 'rencana', 'label' => 'Rencana', 'tipe' => 'uang', 'lebar' => 17]];
        if ($denganTambahan) {
            $k[] = ['key' => 'tambahan', 'label' => 'Tambahan', 'tipe' => 'uang', 'lebar' => 15];
        }
        $k[] = ['key' => 'anggaran', 'label' => 'Anggaran', 'tipe' => 'uang', 'lebar' => 17, 'rumus' => '{rencana}' . ($denganTambahan ? '+{tambahan}' : '')];
        $k[] = ['key' => 'realisasi', 'label' => 'Realisasi', 'tipe' => 'uang', 'lebar' => 17];
        $k[] = ['key' => 'sisa', 'label' => 'Sisa anggaran', 'tipe' => 'uang', 'lebar' => 17, 'rumus' => '{anggaran}-{realisasi}'];
        $k[] = ['key' => 'serapan', 'label' => 'Serapan', 'tipe' => 'persen', 'lebar' => 10, 'rumus' => 'IF({anggaran}=0,0,{realisasi}/{anggaran})'];

        return $k;
    }

    /** Lengkapi nilai turunan dari hasil SUM SQL. */
    private function turunan(array $r): array
    {
        $r['jumlah']    = (int) ($r['jumlah'] ?? 0);
        $r['rencana']   = (float) ($r['rencana'] ?? 0);
        $r['tambahan']  = (float) ($r['tambahan'] ?? 0);
        $r['realisasi'] = (float) ($r['realisasi'] ?? 0);
        $r['anggaran']  = $r['rencana'] + $r['tambahan'];
        $r['sisa']      = $r['anggaran'] - $r['realisasi'];
        $r['serapan']   = $r['anggaran'] > 0 ? $r['realisasi'] / $r['anggaran'] : 0.0;

        return $r;
    }

    // ------------------------------------------------------------------ total (sisi PHP)

    /** Menambahkan 'total_baris' (nilai) agar HTML/PDF/CSV sama dengan rumus total pada Excel. */
    private function denganTotal(array $ds): array
    {
        $ds['total_baris'] = null;
        if (empty($ds['total']) || ! $ds['baris']) {
            return $ds;
        }

        $sub    = array_filter($ds['baris'], static fn ($r) => ($r['kind'] ?? 'normal') === 'subtotal');
        $sumber = $sub ?: array_filter($ds['baris'], static fn ($r) => ($r['kind'] ?? 'normal') === 'normal');
        $keys   = array_column($ds['kolom'], 'key');

        $tot = [$ds['kolom'][0]['key'] => 'Total'];
        foreach ($ds['kolom'] as $k) {
            if (! isset($k['rumus']) && in_array($k['tipe'], ['uang', 'angka'], true) && empty($k['nomor'])) {
                $tot[$k['key']] = array_sum(array_map(static fn ($r) => (float) ($r[$k['key']] ?? 0), $sumber));
            }
        }
        foreach ($ds['kolom'] as $k) {
            if (isset($k['rumus'])) {
                $tot[$k['key']] = $this->hitungRumus($k['rumus'], $tot, $keys);
            }
        }
        $ds['total_baris'] = $tot;

        return $ds;
    }

    /** Evaluator rumus sederhana: SUM({a}:{b}), IF({x}=0,0,{y}/{z}), dan penjumlahan/pengurangan {a}+{b}-{c}. */
    private function hitungRumus(string $r, array $v, array $keys): float
    {
        if (preg_match('/^SUM\(\{(\w+)\}:\{(\w+)\}\)$/', $r, $m)) {
            $a = (int) array_search($m[1], $keys, true);
            $b = (int) array_search($m[2], $keys, true);
            $s = 0.0;
            for ($i = $a; $i <= $b; $i++) {
                $s += (float) ($v[$keys[$i]] ?? 0);
            }

            return $s;
        }
        if (preg_match('/^IF\(\{(\w+)\}=0,0,\{(\w+)\}\/\{(\w+)\}\)$/', $r, $m)) {
            $d = (float) ($v[$m[1]] ?? 0);

            return $d == 0.0 ? 0.0 : (float) ($v[$m[2]] ?? 0) / (float) ($v[$m[3]] ?? 1);
        }
        $s = 0.0;
        if (preg_match_all('/([+-]?)\{(\w+)\}/', $r, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $x) {
                $s += ($x[1] === '-' ? -1 : 1) * (float) ($v[$x[2]] ?? 0);
            }
        }

        return $s;
    }

    // ------------------------------------------------------------------ dataset

    private function dsRekapBulanan(array $f, int $dari, int $sampai): array
    {
        $baris = [];
        foreach ((new Statistik())->bulanan($f, $dari, $sampai) as $b) {
            if ($b['jumlah'] === 0) {
                continue;
            }
            $baris[] = ['kind' => 'normal', 'label' => bulan_id($b['bulan'], false)] + $this->turunan($b + ['total' => $b['total']]);
        }

        return [
            'kode' => 'rekap_bulanan', 'judul' => 'Rekap per bulan', 'sheet' => 'Rekap Bulanan', 'total' => true,
            'kolom' => array_merge([['key' => 'label', 'label' => 'Bulan', 'tipe' => 'teks', 'lebar' => 16]], $this->kolomAnggaran(), [
                ['key' => 'total', 'label' => 'Total rincian biaya', 'tipe' => 'uang', 'lebar' => 18],
            ]),
            'baris' => $baris,
        ];
    }

    private function dsRekapJenis(array $f): array
    {
        $rows = $this->dasar($f)->select('j.nama AS label, ' . self::SUMS, false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->groupBy('j.id')->groupBy('j.nama')->orderBy('realisasi', 'DESC')->get()->getResultArray();

        return [
            'kode' => 'rekap_jenis', 'judul' => 'Rekap per jenis aktivitas', 'sheet' => 'Rekap Jenis', 'total' => true,
            'kolom' => array_merge([['key' => 'label', 'label' => 'Jenis aktivitas', 'tipe' => 'teks', 'lebar' => 26]], $this->kolomAnggaran(), [
                ['key' => 'total', 'label' => 'Total rincian biaya', 'tipe' => 'uang', 'lebar' => 18],
            ]),
            'baris' => array_map(fn ($r) => ['kind' => 'normal'] + $this->turunan($r) + ['total' => (float) $r['total']], $rows),
        ];
    }

    private function dsMatriksJenis(array $f, int $dari, int $sampai): array
    {
        $rows = $this->dasar($f)->select('j.nama, t.periode_bulan AS bulan, COALESCE(SUM(t.realisasi_anggaran),0) AS v', false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->groupBy('j.id')->groupBy('j.nama')->groupBy('t.periode_bulan')->get()->getResultArray();

        $sel = [];
        $adaBulan = [];
        foreach ($rows as $r) {
            $sel[$r['nama']][(int) $r['bulan']] = (float) $r['v'];
            if ((float) $r['v'] != 0.0) {
                $adaBulan[(int) $r['bulan']] = true;
            }
        }
        $bulan = array_values(array_filter(range($dari, $sampai), static fn ($m) => isset($adaBulan[$m])));

        $kolom = [['key' => 'label', 'label' => 'Jenis aktivitas', 'tipe' => 'teks', 'lebar' => 26]];
        foreach ($bulan as $m) {
            $kolom[] = ['key' => 'm' . $m, 'label' => bulan_id($m), 'tipe' => 'uang', 'lebar' => 15];
        }
        $first = $bulan ? 'm' . $bulan[0] : null;
        $last  = $bulan ? 'm' . $bulan[count($bulan) - 1] : null;
        $kolom[] = ['key' => 'jumlah', 'label' => 'Total', 'tipe' => 'uang', 'lebar' => 17, 'rumus' => $first ? 'SUM({' . $first . '}:{' . $last . '})' : '0'];

        $baris = [];
        foreach ($sel as $nama => $per) {
            $row = ['kind' => 'normal', 'label' => $nama, 'jumlah' => 0.0];
            foreach ($bulan as $m) {
                $row['m' . $m] = $per[$m] ?? 0.0;
                $row['jumlah'] += $row['m' . $m];
            }
            $baris[] = $row;
        }
        usort($baris, static fn ($a, $b) => $b['jumlah'] <=> $a['jumlah']);

        return ['kode' => 'matriks_jenis', 'judul' => 'Realisasi per jenis aktivitas dan bulan', 'sheet' => 'Jenis x Bulan', 'total' => true, 'kolom' => $kolom, 'baris' => $baris];
    }

    private function dsRekapAkun(array $f): array
    {
        $rows = $this->dasar($f)->select('a.kode AS akun, a.nama AS akun_nama, c.kode AS cc, ' . self::SUMS, false)
            ->join('akun a', 'a.id = t.akun_id')->join('cost_center c', 'c.id = t.cost_center_id')
            ->groupBy('a.id')->groupBy('a.kode')->groupBy('a.nama')->groupBy('c.id')->groupBy('c.kode')
            ->orderBy('a.kode')->orderBy('c.kode')->get()->getResultArray();

        return [
            'kode' => 'rekap_akun', 'judul' => 'Rekap per akun dan cost center', 'sheet' => 'Akun & Cost Center', 'total' => true,
            'kolom' => array_merge([
                ['key' => 'akun', 'label' => 'No. akun', 'tipe' => 'teks', 'lebar' => 12],
                ['key' => 'akun_nama', 'label' => 'Nama akun', 'tipe' => 'teks', 'lebar' => 30],
                ['key' => 'cc', 'label' => 'Cost center', 'tipe' => 'teks', 'lebar' => 13],
            ], $this->kolomAnggaran()),
            'baris' => array_map(fn ($r) => ['kind' => 'normal', 'akun' => $r['akun'], 'akun_nama' => $r['akun_nama'], 'cc' => $r['cc']] + $this->turunan($r), $rows),
        ];
    }

    private function dsKomponen(array $f, int $dari, int $sampai): array
    {
        $komp = config('Simonkeu')->komponen;
        $sel  = [];
        foreach (array_keys($komp) as $k) {
            $sel[] = "COALESCE(SUM(t.$k),0) AS $k";
        }
        $rows = $this->dasar($f)->select('t.periode_bulan AS bulan, ' . implode(', ', $sel), false)->groupBy('t.periode_bulan')->orderBy('t.periode_bulan')->get()->getResultArray();

        $kolom = [['key' => 'label', 'label' => 'Bulan', 'tipe' => 'teks', 'lebar' => 14]];
        foreach ($komp as $k => $label) {
            $kolom[] = ['key' => $k, 'label' => $label, 'tipe' => 'uang', 'lebar' => 15];
        }
        $ks = array_keys($komp);
        $kolom[] = ['key' => 'total', 'label' => 'Total biaya', 'tipe' => 'uang', 'lebar' => 17, 'rumus' => 'SUM({' . $ks[0] . '}:{' . end($ks) . '})'];

        $baris = [];
        foreach ($rows as $r) {
            $row = ['kind' => 'normal', 'label' => bulan_id((int) $r['bulan'], false), 'total' => 0.0];
            foreach ($ks as $k) {
                $row[$k]      = (float) $r[$k];
                $row['total'] += $row[$k];
            }
            $baris[] = $row;
        }

        return ['kode' => 'komponen', 'judul' => 'Komponen biaya per bulan', 'sheet' => 'Komponen Biaya', 'total' => true, 'kolom' => $kolom, 'baris' => $baris];
    }

    /** Meniru sheet PIVOT: Bulan › Jenis Aktivitas › Aktivitas, dengan subtotal per bulan. */
    private function dsPivot(array $f): array
    {
        $rows = $this->dasar($f)->select('t.periode_bulan AS bulan, t.periode_tahun AS tahun, j.nama AS jenis, t.aktivitas, '
            . 'COALESCE(SUM(t.rencana_anggaran + t.tambahan_anggaran),0) AS rencana, COALESCE(SUM(t.realisasi_anggaran),0) AS realisasi', false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->groupBy('t.periode_tahun')->groupBy('t.periode_bulan')->groupBy('j.nama')->groupBy('t.aktivitas')
            ->orderBy('t.periode_tahun')->orderBy('t.periode_bulan')->orderBy('j.nama')->orderBy('t.aktivitas')->get()->getResultArray();

        $baris = [];
        $kunci = null;
        $sub   = ['rencana' => 0.0, 'realisasi' => 0.0];
        $tutup = static function () use (&$baris, &$sub, &$kunci) {
            if ($kunci !== null) {
                $baris[] = ['kind' => 'subtotal', 'jenis' => $kunci['sum'], 'aktivitas' => '', 'rencana' => $sub['rencana'], 'realisasi' => $sub['realisasi'],
                    'sisa' => $sub['rencana'] - $sub['realisasi']];
            }
        };
        $jenisAktif = null;
        foreach ($rows as $r) {
            $k = (int) $r['tahun'] * 100 + (int) $r['bulan'];
            if ($kunci === null || $kunci['k'] !== $k) {
                $tutup();
                $kunci = ['k' => $k, 'sum' => strtoupper(bulan_id((int) $r['bulan'])) . ' Sum'];
                $sub   = ['rencana' => 0.0, 'realisasi' => 0.0];
                $baris[] = ['kind' => 'group', 'jenis' => bulan_id((int) $r['bulan'], false) . ' ' . $r['tahun']];
                $jenisAktif = null;
            }
            $baris[] = [
                'kind' => 'normal', 'jenis' => $r['jenis'] === $jenisAktif ? '' : $r['jenis'], 'aktivitas' => $r['aktivitas'],
                'rencana' => (float) $r['rencana'], 'realisasi' => (float) $r['realisasi'], 'sisa' => (float) $r['rencana'] - (float) $r['realisasi'],
            ];
            $jenisAktif = $r['jenis'];
            $sub['rencana']   += (float) $r['rencana'];
            $sub['realisasi'] += (float) $r['realisasi'];
        }
        $tutup();

        return [
            'kode' => 'pivot', 'judul' => 'Rincian bulan › jenis aktivitas › aktivitas', 'sheet' => 'Rincian per Aktivitas', 'total' => true,
            'kolom' => [
                ['key' => 'jenis', 'label' => 'Bulan / Jenis aktivitas', 'tipe' => 'teks', 'lebar' => 24],
                ['key' => 'aktivitas', 'label' => 'Aktivitas', 'tipe' => 'teks', 'lebar' => 58],
                ['key' => 'rencana', 'label' => 'Rencana anggaran', 'tipe' => 'uang', 'lebar' => 18],
                ['key' => 'realisasi', 'label' => 'Realisasi anggaran', 'tipe' => 'uang', 'lebar' => 18],
                ['key' => 'sisa', 'label' => 'Sisa', 'tipe' => 'uang', 'lebar' => 16, 'rumus' => '{rencana}-{realisasi}'],
            ],
            'baris' => $baris,
        ];
    }

    private function dsDetail(array $f): array
    {
        $rows = $this->dasar($f)->select('t.*, j.nama AS jenis, p.nama AS pelaksanaan, a.kode AS akun_kode, c.kode AS cc_kode', false)
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->join('pelaksanaan p', 'p.id = t.pelaksanaan_id', 'left')
            ->join('akun a', 'a.id = t.akun_id')->join('cost_center c', 'c.id = t.cost_center_id')
            ->orderBy('t.tgl_mulai')->orderBy('t.id')->get()->getResultArray();

        $komp  = config('Simonkeu')->komponen;
        $kolom = [
            ['key' => 'no', 'label' => 'No', 'tipe' => 'angka', 'lebar' => 5, 'nomor' => true],
            ['key' => 'tgl_mulai', 'label' => 'Tgl mulai', 'tipe' => 'tanggal', 'lebar' => 11],
            ['key' => 'tgl_selesai', 'label' => 'Tgl selesai', 'tipe' => 'tanggal', 'lebar' => 11, 'hanya' => 'xlsx'],
            ['key' => 'aktivitas', 'label' => 'Aktivitas', 'tipe' => 'teks', 'lebar' => 46],
            ['key' => 'jenis', 'label' => 'Jenis aktivitas', 'tipe' => 'teks', 'lebar' => 17],
            ['key' => 'pelaksanaan', 'label' => 'Inhouse/Public', 'tipe' => 'teks', 'lebar' => 13],
            ['key' => 'akun_kode', 'label' => 'No. akun', 'tipe' => 'teks', 'lebar' => 12],
            ['key' => 'cc_kode', 'label' => 'Costcenter', 'tipe' => 'teks', 'lebar' => 13, 'hanya' => 'xlsx'],
            ['key' => 'bulan', 'label' => 'Bulan', 'tipe' => 'teks', 'lebar' => 8, 'hanya' => 'xlsx'],
            ['key' => 'tempat', 'label' => 'Tempat', 'tipe' => 'teks', 'lebar' => 14, 'hanya' => 'xlsx'],
            ['key' => 'jml_peserta', 'label' => 'Jml peserta', 'tipe' => 'angka', 'lebar' => 9, 'hanya' => 'xlsx'],
            ['key' => 'rencana_anggaran', 'label' => 'Rencana anggaran', 'tipe' => 'uang', 'lebar' => 16],
            ['key' => 'tambahan_anggaran', 'label' => 'Tambahan anggaran', 'tipe' => 'uang', 'lebar' => 15, 'hanya' => 'xlsx'],
            ['key' => 'realisasi_anggaran', 'label' => 'Realisasi anggaran', 'tipe' => 'uang', 'lebar' => 16],
        ];
        foreach ($komp as $k => $label) {
            $kolom[] = ['key' => $k, 'label' => $label, 'tipe' => 'uang', 'lebar' => 15, 'hanya' => 'xlsx'];
        }
        $ks = array_keys($komp);
        $kolom[] = ['key' => 'total_biaya', 'label' => 'Total biaya', 'tipe' => 'uang', 'lebar' => 16, 'rumus' => 'SUM({' . $ks[0] . '}:{' . end($ks) . '})'];
        $kolom[] = ['key' => 'status_pembayaran', 'label' => 'Status pembayaran', 'tipe' => 'teks', 'lebar' => 14, 'hanya' => 'xlsx'];
        $kolom[] = ['key' => 'no_parking', 'label' => 'No. parking', 'tipe' => 'teks', 'lebar' => 14, 'hanya' => 'xlsx'];
        $kolom[] = ['key' => 'tgl_pembayaran_terakhir', 'label' => 'Tgl pembayaran terakhir', 'tipe' => 'tanggal', 'lebar' => 14, 'hanya' => 'xlsx'];
        $kolom[] = ['key' => 'keterangan', 'label' => 'Keterangan', 'tipe' => 'teks', 'lebar' => 30, 'hanya' => 'xlsx'];

        $baris = [];
        $n = 0;
        foreach ($rows as $r) {
            $r['kind']  = 'normal';
            $r['no']    = ++$n;
            $r['bulan'] = strtoupper(bulan_id((int) $r['periode_bulan']));
            foreach (array_merge(['rencana_anggaran', 'tambahan_anggaran', 'realisasi_anggaran', 'total_biaya'], $ks) as $k) {
                $r[$k] = (float) $r[$k];
            }
            $baris[] = $r;
        }

        return ['kode' => 'detail', 'judul' => 'Daftar transaksi', 'sheet' => 'Daftar Transaksi', 'total' => true, 'kolom' => $kolom, 'baris' => $baris];
    }
}
