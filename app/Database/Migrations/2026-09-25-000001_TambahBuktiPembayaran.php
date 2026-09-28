<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Alur konfirmasi status "Lunas": operator wajib mengunggah bukti pembayaran
 * dan menunggu persetujuan admin; admin bisa langsung menandai lunas
 * (juga wajib bukti, tapi tanpa menunggu persetujuan siapa pun).
 * Kolom oleh/konfirmasi_oleh sengaja tanpa FK (pola sama seperti audit_log.user_id)
 * agar pengguna yang kemudian dihapus tidak menggagalkan data historis.
 */
class TambahBuktiPembayaran extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('transaksi', [
            'bukti_pembayaran'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'status_pembayaran'],
            'bukti_pembayaran_oleh' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'bukti_pembayaran'],
            'bukti_pembayaran_at'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'bukti_pembayaran_oleh'],
            'lunas_menunggu'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'bukti_pembayaran_at'],
            'lunas_konfirmasi_oleh' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'lunas_menunggu'],
            'lunas_konfirmasi_at'   => ['type' => 'DATETIME', 'null' => true, 'after' => 'lunas_konfirmasi_oleh'],
            'lunas_ditolak_alasan'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'lunas_konfirmasi_at'],
        ]);
        $this->forge->addKey('lunas_menunggu');
        $this->forge->processIndexes('transaksi');
    }

    public function down(): void
    {
        $this->forge->dropColumn('transaksi', [
            'bukti_pembayaran', 'bukti_pembayaran_oleh', 'bukti_pembayaran_at',
            'lunas_menunggu', 'lunas_konfirmasi_oleh', 'lunas_konfirmasi_at', 'lunas_ditolak_alasan',
        ]);
    }
}
