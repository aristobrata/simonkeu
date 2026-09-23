<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Tabel referensi: jenis aktivitas, pelaksanaan (inhouse/public/...), akun, cost center. */
class CreateMaster extends Migration
{
    public function up(): void
    {
        $id  = ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true];
        $ts  = ['type' => 'DATETIME', 'null' => true];
        $opt = ['ENGINE' => 'InnoDB'];

        // Jenis aktivitas: Training, KM, PKL, Supply Kantor, ...
        $this->forge->addField([
            'id'         => $id,
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'deskripsi'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nama');
        $this->forge->createTable('jenis_aktivitas', true, $opt);

        // Pelaksanaan (kolom "Inhouse/Public"): In House, Public, LAT, Online, ...
        $this->forge->addField([
            'id'         => $id,
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'deskripsi'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nama');
        $this->forge->createTable('pelaksanaan', true, $opt);

        // Nomor akun (GL)
        $this->forge->addField([
            'id'         => $id,
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('akun', true, $opt);

        // Cost center
        $this->forge->addField([
            'id'         => $id,
            'kode'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'created_at' => $ts,
            'updated_at' => $ts,
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('kode');
        $this->forge->createTable('cost_center', true, $opt);
    }

    public function down(): void
    {
        foreach (['cost_center', 'akun', 'pelaksanaan', 'jenis_aktivitas'] as $t) {
            $this->forge->dropTable($t, true);
        }
    }
}
