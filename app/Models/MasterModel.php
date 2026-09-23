<?php

namespace App\Models;

use CodeIgniter\Model;

/** Dasar untuk tabel referensi (jenis aktivitas, pelaksanaan, akun, cost center). */
abstract class MasterModel extends Model
{
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    /** Kolom kunci asing pada tabel transaksi yang mengacu ke tabel ini. */
    protected string $fkTransaksi = '';

    /** Kolom pengurut daftar. */
    protected string $urut = 'nama';

    /** Daftar lengkap beserta jumlah transaksi yang memakainya. */
    public function denganPemakaian(): array
    {
        $sql = "SELECT m.*, (SELECT COUNT(*) FROM transaksi t WHERE t.{$this->fkTransaksi} = m.id AND t.deleted_at IS NULL) AS dipakai
                FROM {$this->table} m ORDER BY m.{$this->urut}";

        return $this->db->query($sql)->getResultArray();
    }

    public function jumlahPemakaian(int $id): int
    {
        return (int) $this->db->table('transaksi')->where($this->fkTransaksi, $id)->where('deleted_at', null)->countAllResults();
    }

    /** Untuk isian <select>: [id => label]. */
    public function opsi(): array
    {
        $out = [];
        foreach ($this->orderBy($this->urut)->findAll() as $r) {
            $out[$r['id']] = $this->label($r);
        }

        return $out;
    }

    public function label(array $r): string
    {
        return isset($r['kode']) ? $r['kode'] . ' – ' . $r['nama'] : $r['nama'];
    }
}
