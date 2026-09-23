<?php

namespace App\Models;

class PelaksanaanModel extends MasterModel
{
    protected $table            = 'pelaksanaan';
    protected $allowedFields    = ['nama', 'deskripsi'];
    protected string $fkTransaksi = 'pelaksanaan_id';
}
