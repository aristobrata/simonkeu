<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Satu baris = satu baris pada template laporan keuangan.
 * total_biaya selalu dihitung ulang dari 9 kolom rincian biaya (setara =SUM(M:U) pada Excel).
 */
class TransaksiModel extends Model
{
    protected $table          = 'transaksi';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'aktivitas', 'jenis_aktivitas_id', 'pelaksanaan_id', 'akun_id', 'cost_center_id',
        'periode_bulan', 'periode_tahun', 'tgl_mulai', 'tgl_selesai', 'tempat', 'jml_peserta',
        'rencana_anggaran', 'tambahan_anggaran', 'realisasi_anggaran',
        'biaya_training', 'biaya_materi', 'biaya_konsumsi', 'biaya_perlengkapan', 'biaya_tiket',
        'biaya_hotel', 'biaya_transportasi', 'biaya_uang_saku', 'biaya_lainnya', 'total_biaya',
        'status_pembayaran', 'no_parking', 'tgl_pembayaran_terakhir', 'keterangan', 'created_by',
    ];

    protected $beforeInsert = ['hitungTotal'];
    protected $beforeUpdate = ['hitungTotal'];

    /** Kolom yang boleh dipakai untuk pengurutan daftar (kunci URL => ekspresi SQL). */
    public const URUT = [
        'tgl'         => 'transaksi.tgl_mulai',
        'aktivitas'   => 'transaksi.aktivitas',
        'jenis'       => 'j.nama',
        'rencana'     => 'transaksi.rencana_anggaran',
        'realisasi'   => 'transaksi.realisasi_anggaran',
        'total'       => 'transaksi.total_biaya',
        'diperbarui'  => 'transaksi.updated_at',
    ];

    public function __construct()
    {
        parent::__construct();

        $cfg   = config('Simonkeu');
        $uang  = 'permit_empty|numeric';
        $rules = [
            'aktivitas'          => ['label' => 'Nama aktivitas', 'rules' => 'required|max_length[500]'],
            'jenis_aktivitas_id' => ['label' => 'Jenis aktivitas', 'rules' => 'required|is_natural_no_zero|is_not_unique[jenis_aktivitas.id]'],
            'pelaksanaan_id'     => ['label' => 'Inhouse/Public', 'rules' => 'permit_empty|is_natural_no_zero|is_not_unique[pelaksanaan.id]'],
            'akun_id'            => ['label' => 'No. akun', 'rules' => 'required|is_natural_no_zero|is_not_unique[akun.id]'],
            'cost_center_id'     => ['label' => 'Cost center', 'rules' => 'required|is_natural_no_zero|is_not_unique[cost_center.id]'],
            'periode_bulan'      => ['label' => 'Bulan', 'rules' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[12]'],
            'periode_tahun'      => ['label' => 'Tahun', 'rules' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]'],
            'tgl_mulai'          => ['label' => 'Tanggal mulai', 'rules' => 'required|valid_date[Y-m-d]'],
            'tgl_selesai'        => ['label' => 'Tanggal selesai', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'tempat'             => ['label' => 'Tempat', 'rules' => 'permit_empty|max_length[150]'],
            'jml_peserta'        => ['label' => 'Jumlah peserta', 'rules' => 'permit_empty|is_natural'],
            'rencana_anggaran'   => ['label' => 'Rencana anggaran', 'rules' => $uang],
            'tambahan_anggaran'  => ['label' => 'Tambahan anggaran', 'rules' => $uang],
            'realisasi_anggaran' => ['label' => 'Realisasi anggaran', 'rules' => $uang],
            'status_pembayaran'  => ['label' => 'Status pembayaran', 'rules' => 'permit_empty|in_list[' . implode(',', $cfg->statusPembayaran) . ']'],
            'no_parking'         => ['label' => 'No. parking', 'rules' => 'permit_empty|max_length[30]'],
            'tgl_pembayaran_terakhir' => ['label' => 'Tgl pembayaran terakhir', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'keterangan'         => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[2000]'],
        ];
        foreach ($cfg->komponen as $kolom => $label) {
            $rules[$kolom] = ['label' => $label, 'rules' => $uang];
        }
        $this->validationRules = $rules;
    }

    /** Callback: total_biaya = jumlah semua komponen biaya. */
    protected function hitungTotal(array $data): array
    {
        $d     = $data['data'] ?? [];
        $ada   = false;
        $total = 0.0;
        foreach (array_keys(config('Simonkeu')->komponen) as $k) {
            if (array_key_exists($k, $d)) {
                $ada = true;
            }
            $total += (float) ($d[$k] ?? 0);
        }
        if ($ada) {
            $data['data']['total_biaya'] = $total;
        }

        return $data;
    }

    /**
     * Terapkan filter umum pada builder mana pun (daftar, dashboard, laporan).
     *
     * @param array<string,mixed> $f tahun, bulan, bulan_dari, bulan_sampai, jenis, pelaksanaan ('0' = kosong),
     *                               akun, cc, status ('-' = kosong), q
     */
    public static function terapkanFilter(BaseBuilder $b, array $f, string $t = 'transaksi'): BaseBuilder
    {
        if (! empty($f['tahun'])) {
            $b->where("$t.periode_tahun", (int) $f['tahun']);
        }
        if (! empty($f['bulan'])) {
            $b->where("$t.periode_bulan", (int) $f['bulan']);
        }
        if (! empty($f['bulan_dari'])) {
            $b->where("$t.periode_bulan >=", (int) $f['bulan_dari']);
        }
        if (! empty($f['bulan_sampai'])) {
            $b->where("$t.periode_bulan <=", (int) $f['bulan_sampai']);
        }
        if (! empty($f['jenis'])) {
            $b->where("$t.jenis_aktivitas_id", (int) $f['jenis']);
        }
        if (isset($f['pelaksanaan']) && $f['pelaksanaan'] !== '') {
            if ((string) $f['pelaksanaan'] === '0') {
                $b->where("$t.pelaksanaan_id IS NULL", null, false);
            } else {
                $b->where("$t.pelaksanaan_id", (int) $f['pelaksanaan']);
            }
        }
        if (! empty($f['akun'])) {
            $b->where("$t.akun_id", (int) $f['akun']);
        }
        if (! empty($f['cc'])) {
            $b->where("$t.cost_center_id", (int) $f['cc']);
        }
        if (! empty($f['status'])) {
            if ($f['status'] === '-') {
                $b->where("$t.status_pembayaran IS NULL", null, false);
            } else {
                $b->where("$t.status_pembayaran", $f['status']);
            }
        }
        if (isset($f['q']) && trim((string) $f['q']) !== '') {
            $q = trim((string) $f['q']);
            $b->groupStart()
                ->like("$t.aktivitas", $q)
                ->orLike("$t.no_parking", $q)
                ->orLike("$t.tempat", $q)
                ->orLike("$t.keterangan", $q)
                ->groupEnd();
        }

        return $b;
    }

    /** Siapkan query daftar (dengan join master). Panggil ->paginate() atau ->findAll() setelahnya. */
    public function daftar(array $f, string $urut = 'tgl', string $arah = 'desc'): static
    {
        $this->select('transaksi.*, j.nama AS jenis, p.nama AS pelaksanaan, a.kode AS akun_kode, a.nama AS akun_nama, c.kode AS cc_kode, c.nama AS cc_nama')
            ->join('jenis_aktivitas j', 'j.id = transaksi.jenis_aktivitas_id')
            ->join('pelaksanaan p', 'p.id = transaksi.pelaksanaan_id', 'left')
            ->join('akun a', 'a.id = transaksi.akun_id')
            ->join('cost_center c', 'c.id = transaksi.cost_center_id');

        self::terapkanFilter($this->builder(), $f, 'transaksi');

        $kolom = self::URUT[$urut] ?? self::URUT['tgl'];
        $arah  = strtolower($arah) === 'asc' ? 'ASC' : 'DESC';
        $this->orderBy($kolom, $arah);
        if ($kolom !== 'transaksi.tgl_mulai') {
            $this->orderBy('transaksi.tgl_mulai', 'DESC');
        }

        return $this->orderBy('transaksi.id', 'DESC');
    }

    /** Satu baris lengkap dengan nama master. */
    public function detail(int $id): ?array
    {
        return $this->select('transaksi.*, j.nama AS jenis, p.nama AS pelaksanaan, a.kode AS akun_kode, a.nama AS akun_nama, c.kode AS cc_kode, c.nama AS cc_nama')
            ->join('jenis_aktivitas j', 'j.id = transaksi.jenis_aktivitas_id')
            ->join('pelaksanaan p', 'p.id = transaksi.pelaksanaan_id', 'left')
            ->join('akun a', 'a.id = transaksi.akun_id')
            ->join('cost_center c', 'c.id = transaksi.cost_center_id')
            ->where('transaksi.id', $id)
            ->first();
    }

    /** Total ringkas untuk hasil filter (ditampilkan di atas tabel). */
    public function ringkasan(array $f): array
    {
        $b = $this->db->table('transaksi')->where('transaksi.deleted_at', null);
        self::terapkanFilter($b, $f, 'transaksi');

        $r = $b->select('COUNT(*) AS jumlah, COALESCE(SUM(rencana_anggaran),0) AS rencana, COALESCE(SUM(tambahan_anggaran),0) AS tambahan, '
            . 'COALESCE(SUM(realisasi_anggaran),0) AS realisasi, COALESCE(SUM(total_biaya),0) AS total', false)
            ->get()->getRowArray();

        return $r ?: ['jumlah' => 0, 'rencana' => 0, 'tambahan' => 0, 'realisasi' => 0, 'total' => 0];
    }

    /** Tahun-tahun yang memiliki data (terbaru dulu). */
    public function daftarTahun(): array
    {
        $rows = $this->db->table('transaksi')->select('periode_tahun AS t')->where('deleted_at', null)
            ->groupBy('periode_tahun')->orderBy('periode_tahun', 'DESC')->get()->getResultArray();

        return array_map(static fn ($r) => (int) $r['t'], $rows);
    }

    /**
     * Normalisasi isian form (teks, angka berformat Indonesia, tanggal) menjadi data siap simpan.
     *
     * @param array<string,mixed> $p
     */
    public function siapkanInput(array $p): array
    {
        $teks = static function ($v): ?string {
            $v = trim((string) ($v ?? ''));

            return $v === '' ? null : $v;
        };
        $id = static function ($v): ?int {
            $v = (int) $v;

            return $v > 0 ? $v : null;
        };

        $d = [
            'aktivitas'          => preg_replace('/\s+/u', ' ', trim((string) ($p['aktivitas'] ?? ''))),
            'jenis_aktivitas_id' => $id($p['jenis_aktivitas_id'] ?? null),
            'pelaksanaan_id'     => $id($p['pelaksanaan_id'] ?? null),
            'akun_id'            => $id($p['akun_id'] ?? null),
            'cost_center_id'     => $id($p['cost_center_id'] ?? null),
            'tgl_mulai'          => $teks($p['tgl_mulai'] ?? null),
            'tgl_selesai'        => $teks($p['tgl_selesai'] ?? null),
            'tempat'             => $teks($p['tempat'] ?? null),
            'jml_peserta'        => ($teks($p['jml_peserta'] ?? null) === null) ? null : (int) $p['jml_peserta'],
            'status_pembayaran'  => $teks($p['status_pembayaran'] ?? null),
            'no_parking'         => $teks($p['no_parking'] ?? null),
            'tgl_pembayaran_terakhir' => $teks($p['tgl_pembayaran_terakhir'] ?? null),
            'keterangan'         => $teks($p['keterangan'] ?? null),
        ];

        // Periode: bila kosong, turunkan dari tanggal mulai.
        $ts              = $d['tgl_mulai'] ? strtotime($d['tgl_mulai']) : false;
        $d['periode_bulan'] = $id($p['periode_bulan'] ?? null) ?? ($ts ? (int) date('n', $ts) : null);
        $d['periode_tahun'] = $id($p['periode_tahun'] ?? null) ?? ($ts ? (int) date('Y', $ts) : null);

        // Rincian biaya + total.
        $total = 0.0;
        foreach (array_keys(config('Simonkeu')->komponen) as $k) {
            $d[$k]  = to_number($p[$k] ?? 0);
            $total += $d[$k];
        }
        $d['total_biaya'] = $total;

        // Realisasi bawaan = total biaya; rencana bawaan = realisasi (sama seperti rumus pada template).
        $kosong = static fn ($v) => $v === null || trim((string) $v) === '';
        $d['realisasi_anggaran'] = $kosong($p['realisasi_anggaran'] ?? null) ? $total : to_number($p['realisasi_anggaran']);
        $d['rencana_anggaran']   = $kosong($p['rencana_anggaran'] ?? null) ? $d['realisasi_anggaran'] : to_number($p['rencana_anggaran']);
        $d['tambahan_anggaran']  = to_number($p['tambahan_anggaran'] ?? 0);

        return $d;
    }

    /** Ringkasan perubahan lama → baru untuk jejak audit. */
    public function ringkasPerubahan(array $lama, array $baru): string
    {
        $label = [
            'aktivitas' => 'Aktivitas', 'periode_bulan' => 'Bulan', 'periode_tahun' => 'Tahun',
            'rencana_anggaran' => 'Rencana', 'tambahan_anggaran' => 'Tambahan', 'realisasi_anggaran' => 'Realisasi',
            'total_biaya' => 'Total biaya', 'status_pembayaran' => 'Status', 'no_parking' => 'No. parking',
            'tgl_mulai' => 'Tgl mulai', 'tgl_selesai' => 'Tgl selesai', 'jenis_aktivitas_id' => 'Jenis (id)',
            'pelaksanaan_id' => 'Pelaksanaan (id)', 'akun_id' => 'Akun (id)', 'cost_center_id' => 'Cost center (id)',
        ] + config('Simonkeu')->komponen;

        $out = [];
        foreach ($label as $k => $nama) {
            if (! array_key_exists($k, $baru)) {
                continue;
            }
            $a = $lama[$k] ?? null;
            $b = $baru[$k] ?? null;
            $numerik = is_numeric($a) && is_numeric($b) && ! in_array($k, ['no_parking'], true);
            if (($numerik && abs((float) $a - (float) $b) < 0.005) || (! $numerik && (string) $a === (string) $b)) {
                continue;
            }
            $out[] = $nama . ': ' . ($a === null || $a === '' ? '—' : $a) . ' → ' . ($b === null || $b === '' ? '—' : $b);
        }

        return $out ? implode('; ', $out) : 'Tidak ada perubahan nilai.';
    }
}
