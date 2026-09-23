<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLog extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'aksi'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'entitas'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'entitas_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'ringkasan'  => ['type' => 'TEXT', 'null' => true],
            'ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['entitas', 'entitas_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_log', true, ['ENGINE' => 'InnoDB']);
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_log', true);
    }
}
