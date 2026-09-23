<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    public const ROLE = [
        'admin'    => 'Administrator',
        'operator' => 'Operator keuangan',
        'viewer'   => 'Peninjau (hanya baca)',
    ];

    protected $table         = 'users';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['nama', 'username', 'password_hash', 'role', 'aktif', 'last_login'];
}
