<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pagu anggaran tahunan. Setiap baris adalah satu pagu: untuk seluruh jenis aktivitas
 * (jenis_aktivitas_id NULL) atau untuk satu jenis aktivitas tertentu. Realisasi yang
 * memakan pagu ini dihitung langsung dari tabel transaksi (bukan disimpan berulang),
 * sehingga selalu sinkron dengan data transaksi terbaru.
 */
class AnggaranModel extends Model
{
    protected $table         = 'anggaran_tahunan';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['tahun', 'jenis_aktivitas_id', 'nominal', 'keterangan'];

    protected $validationRules = [
        'tahun'      => ['label' => 'Tahun', 'rules' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]'],
        'nominal'    => ['label' => 'Nominal anggaran', 'rules' => 'required|numeric|greater_than_equal_to[0]'],
        'keterangan' => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[255]'],
    ];

    /**
     * Semua pagu pada satu tahun, lengkap dengan realisasi yang sudah terpakai.
     * Pagu "seluruh jenis" ditampilkan lebih dulu.
     *
     * @return list<array{id:int,tahun:int,jenis_aktivitas_id:?int,jenis_nama:?string,nominal:float,keterangan:?string,realisasi:float,sisa:float,serapan:float}>
     */
    public function ringkasan(int $tahun): array
    {
        $rows = $this->select('anggaran_tahunan.*, j.nama AS jenis_nama')
            ->join('jenis_aktivitas j', 'j.id = anggaran_tahunan.jenis_aktivitas_id', 'left')
            ->where('tahun', $tahun)
            ->orderBy('jenis_aktivitas_id IS NULL', 'DESC', false)
            ->orderBy('j.nama', 'ASC')
            ->findAll();

        $db = db_connect();
        foreach ($rows as &$r) {
            $b = $db->table('transaksi')->selectSum('realisasi_anggaran')->where('periode_tahun', $tahun)->where('deleted_at', null);
            if ($r['jenis_aktivitas_id'] !== null) {
                $b->where('jenis_aktivitas_id', (int) $r['jenis_aktivitas_id']);
            }
            $realisasi     = (float) ($b->get()->getRowArray()['realisasi_anggaran'] ?? 0);
            $r['nominal']  = (float) $r['nominal'];
            $r['realisasi'] = $realisasi;
            $r['sisa']     = $r['nominal'] - $realisasi;
            $r['serapan']  = $r['nominal'] > 0 ? $realisasi / $r['nominal'] * 100 : 0.0;
        }
        unset($r);

        return $rows;
    }

    /** Cek duplikasi (tahun, jenis) — NULL-safe, karena unique index MySQL tidak menjaga ini untuk NULL. */
    public function sudahAda(int $tahun, ?int $jenisId, int $kecuali = 0): bool
    {
        $b = $this->where('tahun', $tahun)->where('jenis_aktivitas_id', $jenisId);
        if ($kecuali > 0) {
            $b->where('id !=', $kecuali);
        }

        return $b->countAllResults() > 0;
    }

    /** Tahun-tahun yang sudah punya pagu (untuk pemilih tahun). */
    public function daftarTahun(): array
    {
        $rows = $this->select('tahun')->distinct()->orderBy('tahun', 'DESC')->findAll();

        return array_map(static fn ($r) => (int) $r['tahun'], $rows);
    }
}
