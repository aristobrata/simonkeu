<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Status pembayaran disederhanakan menjadi 3 pilihan: Belum, Diproses, Lunas
 * (sebelumnya: Belum Dibayar, Diproses, Lunas, Akrual).
 * Data lama disesuaikan otomatis: "Belum Dibayar" -> "Belum", "Akrual" -> "Diproses"
 * (akrual berarti biaya sudah diakui tapi pembayaran belum tuntas, paling dekat
 * dengan makna "Diproses"). Baris yang belum diisi (NULL) tidak berubah.
 */
class NormalisasiStatusPembayaran extends Migration
{
    public function up(): void
    {
        $this->db->table('transaksi')->where('status_pembayaran', 'Belum Dibayar')->update(['status_pembayaran' => 'Belum']);
        $this->db->table('transaksi')->where('status_pembayaran', 'Akrual')->update(['status_pembayaran' => 'Diproses']);
    }

    public function down(): void
    {
        // Data content changes are not perfectly reversible (Akrual tergabung ke Diproses).
        $this->db->table('transaksi')->where('status_pembayaran', 'Belum')->update(['status_pembayaran' => 'Belum Dibayar']);
    }
}
