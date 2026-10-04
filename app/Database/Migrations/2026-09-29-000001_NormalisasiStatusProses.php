<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Label status pembayaran disesuaikan: "Belum" -> "Belum Proses",
 * "Lunas" -> "Selesai Proses" ("Diproses" tidak berubah). Ini hanya mengubah
 * teks tersimpan; alur konfirmasi (upload bukti + persetujuan admin) tetap
 * sama persis, hanya dipicu oleh nama status baru "Selesai Proses".
 */
class NormalisasiStatusProses extends Migration
{
    public function up(): void
    {
        $this->db->table('transaksi')->where('status_pembayaran', 'Belum')->update(['status_pembayaran' => 'Belum Proses']);
        $this->db->table('transaksi')->where('status_pembayaran', 'Lunas')->update(['status_pembayaran' => 'Selesai Proses']);
    }

    public function down(): void
    {
        $this->db->table('transaksi')->where('status_pembayaran', 'Belum Proses')->update(['status_pembayaran' => 'Belum']);
        $this->db->table('transaksi')->where('status_pembayaran', 'Selesai Proses')->update(['status_pembayaran' => 'Lunas']);
    }
}
