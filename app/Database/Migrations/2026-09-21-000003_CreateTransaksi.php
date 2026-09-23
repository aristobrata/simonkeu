<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel utama: satu baris = satu baris pada template laporan keuangan (Excel).
 * Kolom biaya mengikuti kolom M–U template; total_biaya = jumlah kolom tersebut.
 */
class CreateTransaksi extends Migration
{
    public function up(): void
    {
        $int  = ['type' => 'INT', 'constraint' => 10, 'unsigned' => true];
        $uang = ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0];

        $this->forge->addField([
            'id'                      => $int + ['auto_increment' => true],
            'aktivitas'               => ['type' => 'VARCHAR', 'constraint' => 500],
            'jenis_aktivitas_id'      => $int,
            'pelaksanaan_id'          => $int + ['null' => true],
            'akun_id'                 => $int,
            'cost_center_id'          => $int,
            'periode_bulan'           => ['type' => 'TINYINT', 'constraint' => 2, 'unsigned' => true],
            'periode_tahun'           => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true],
            'tgl_mulai'               => ['type' => 'DATE'],
            'tgl_selesai'             => ['type' => 'DATE', 'null' => true],
            'tempat'                  => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'jml_peserta'             => $int + ['null' => true],
            'rencana_anggaran'        => $uang,
            'tambahan_anggaran'       => $uang,
            'realisasi_anggaran'      => $uang,
            'biaya_training'          => $uang,
            'biaya_materi'            => $uang,
            'biaya_konsumsi'          => $uang,
            'biaya_perlengkapan'      => $uang,
            'biaya_tiket'             => $uang,
            'biaya_hotel'             => $uang,
            'biaya_transportasi'      => $uang,
            'biaya_uang_saku'         => $uang,
            'biaya_lainnya'           => $uang,
            'total_biaya'             => $uang,
            'status_pembayaran'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'no_parking'              => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'tgl_pembayaran_terakhir' => ['type' => 'DATE', 'null' => true],
            'keterangan'              => ['type' => 'TEXT', 'null' => true],
            'created_by'              => $int + ['null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['periode_tahun', 'periode_bulan']);
        $this->forge->addKey('tgl_mulai');
        $this->forge->addKey('no_parking');
        $this->forge->addForeignKey('jenis_aktivitas_id', 'jenis_aktivitas', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('pelaksanaan_id', 'pelaksanaan', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('akun_id', 'akun', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('cost_center_id', 'cost_center', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('transaksi', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('transaksi', true);
    }
}
