<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pagu anggaran per tahun. jenis_aktivitas_id NULL = anggaran total (seluruh jenis aktivitas);
 * diisi = anggaran khusus jenis aktivitas tersebut. Keunikan (tahun, jenis_aktivitas_id)
 * ditegakkan di level aplikasi (AnggaranModel::sudahAda), bukan index DB, karena NULL
 * pada MySQL tidak dianggap sama oleh unique index biasa.
 */
class CreateAnggaranTahunan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'tahun'              => ['type' => 'SMALLINT', 'constraint' => 4, 'unsigned' => true],
            'jenis_aktivitas_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'nominal'            => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0],
            'keterangan'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tahun', 'jenis_aktivitas_id']);
        $this->forge->addForeignKey('jenis_aktivitas_id', 'jenis_aktivitas', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('anggaran_tahunan', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('anggaran_tahunan', true);
    }
}
