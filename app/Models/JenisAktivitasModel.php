<?php

namespace App\Models;

class JenisAktivitasModel extends MasterModel
{
    protected $table            = 'jenis_aktivitas';
    protected $allowedFields    = ['nama', 'deskripsi'];
    protected string $fkTransaksi = 'jenis_aktivitas_id';
}
