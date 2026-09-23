<?php

namespace App\Models;

use CodeIgniter\Model;

/** Jejak audit: siapa mengubah apa dan kapan. */
class AuditLogModel extends Model
{
    protected $table         = 'audit_log';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
    protected $allowedFields = ['user_id', 'username', 'aksi', 'entitas', 'entitas_id', 'ringkasan', 'ip', 'created_at'];

    /** Catat satu kejadian. Kegagalan mencatat tidak boleh menggagalkan proses utama. */
    public static function catat(string $aksi, string $entitas, ?int $entitasId = null, ?string $ringkasan = null): void
    {
        try {
            $user = session()->get('user');
            model(self::class)->insert([
                'user_id'    => $user['id'] ?? null,
                'username'   => $user['username'] ?? null,
                'aksi'       => $aksi,
                'entitas'    => $entitas,
                'entitas_id' => $entitasId,
                'ringkasan'  => $ringkasan !== null ? mb_substr($ringkasan, 0, 1900) : null,
                'ip'         => service('request')->getIPAddress(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal mencatat audit: ' . $e->getMessage());
        }
    }
}
