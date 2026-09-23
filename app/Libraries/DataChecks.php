<?php

namespace App\Libraries;

use App\Models\TransaksiModel;

/**
 * Aturan pemeriksaan kualitas data. Setiap aturan = kondisi SQL pada tabel transaksi (alias t)
 * dan pelaksanaan (alias p). Kondisi bersifat tetap (bukan input pengguna).
 */
class DataChecks
{
    /** @var array<string,array{judul:string,penjelasan:string,level:string,kondisi:string}> */
    public const ATURAN = [
        'anggaran_kosong' => [
            'judul'      => 'Rencana atau realisasi kosong',
            'penjelasan' => 'Ada total biaya, tetapi rencana anggaran atau realisasi anggaran bernilai 0. Bisa jadi baris ganda atau belum terisi.',
            'level'      => 'danger',
            'kondisi'    => '(t.rencana_anggaran = 0 OR t.realisasi_anggaran = 0) AND t.total_biaya > 0',
        ],
        'melebihi_anggaran' => [
            'judul'      => 'Realisasi melebihi anggaran',
            'penjelasan' => 'Realisasi lebih besar daripada rencana ditambah tambahan anggaran.',
            'level'      => 'danger',
            'kondisi'    => 't.rencana_anggaran > 0 AND t.realisasi_anggaran > t.rencana_anggaran + t.tambahan_anggaran + 0.5',
        ],
        'tanggal_terbalik' => [
            'judul'      => 'Tanggal selesai sebelum tanggal mulai',
            'penjelasan' => 'Kemungkinan salah ketik tanggal atau tahun.',
            'level'      => 'danger',
            'kondisi'    => 't.tgl_selesai IS NOT NULL AND t.tgl_selesai < t.tgl_mulai',
        ],
        'selisih_realisasi' => [
            'judul'      => 'Realisasi berbeda dari total biaya',
            'penjelasan' => 'Realisasi anggaran tidak sama dengan jumlah rincian biaya. Pastikan memang disengaja (misalnya sebagian dibayar lewat dokumen lain).',
            'level'      => 'warning',
            'kondisi'    => 't.rencana_anggaran > 0 AND t.realisasi_anggaran > 0 AND ABS(t.realisasi_anggaran - t.total_biaya) > 0.5',
        ],
        'public_biaya_internal' => [
            'judul'      => 'Kegiatan Public memiliki biaya materi, konsumsi, atau perlengkapan',
            'penjelasan' => 'Menurut aturan pada template, kegiatan Public tidak memiliki biaya materi, konsumsi, dan perlengkapan.',
            'level'      => 'warning',
            'kondisi'    => "p.nama = 'Public' AND (t.biaya_materi + t.biaya_konsumsi + t.biaya_perlengkapan) > 0",
        ],
        'tanpa_parking' => [
            'judul'      => 'No. parking belum diisi',
            'penjelasan' => 'Nomor dokumen parking diperlukan untuk penelusuran pembayaran.',
            'level'      => 'info',
            'kondisi'    => "(t.no_parking IS NULL OR t.no_parking = '')",
        ],
        'tanpa_nilai' => [
            'judul'      => 'Tidak ada nilai biaya',
            'penjelasan' => 'Total biaya dan realisasi sama-sama 0.',
            'level'      => 'info',
            'kondisi'    => 't.total_biaya = 0 AND t.realisasi_anggaran = 0',
        ],
    ];

    /** Jumlah temuan per aturan (hanya yang > 0). @return array<string,int> */
    public function ringkasan(array $f = []): array
    {
        $out = [];
        foreach (self::ATURAN as $kode => $a) {
            $n = $this->query($f, $a['kondisi'])->countAllResults();
            if ($n > 0) {
                $out[$kode] = $n;
            }
        }

        return $out;
    }

    /** Jumlah baris unik yang terkena minimal satu aturan berlevel danger/warning (dipakai untuk lencana dashboard). */
    public function jumlahPerluDiperiksa(array $f = []): int
    {
        $kondisi = [];
        foreach (self::ATURAN as $a) {
            if (in_array($a['level'], ['danger', 'warning'], true)) {
                $kondisi[] = '(' . $a['kondisi'] . ')';
            }
        }

        return $this->query($f, '(' . implode(' OR ', $kondisi) . ')')->countAllResults();
    }

    /** Baris temuan untuk satu aturan. */
    public function daftar(string $kode, array $f = [], int $batas = 500): array
    {
        $a = self::ATURAN[$kode] ?? null;
        if ($a === null) {
            return [];
        }

        return $this->query($f, $a['kondisi'])
            ->select('t.id, t.aktivitas, t.tgl_mulai, t.tgl_selesai, t.rencana_anggaran, t.tambahan_anggaran, t.realisasi_anggaran, t.total_biaya, t.no_parking, j.nama AS jenis, p.nama AS pelaksanaan')
            ->orderBy('t.tgl_mulai', 'ASC')->orderBy('t.id', 'ASC')
            ->limit($batas)->get()->getResultArray();
    }

    private function query(array $f, string $kondisi)
    {
        $b = db_connect()->table('transaksi t')
            ->join('jenis_aktivitas j', 'j.id = t.jenis_aktivitas_id')
            ->join('pelaksanaan p', 'p.id = t.pelaksanaan_id', 'left')
            ->where('t.deleted_at', null)
            ->where($kondisi, null, false);
        TransaksiModel::terapkanFilter($b, $f, 't');

        return $b;
    }
}
