<?php

namespace App\Models;

class AkunModel extends MasterModel
{
    protected $table            = 'akun';
    protected $allowedFields    = ['kode', 'nama'];
    protected string $fkTransaksi = 'akun_id';
    protected string $urut        = 'kode';
}
