<?php

namespace App\Models;

class CostCenterModel extends MasterModel
{
    protected $table            = 'cost_center';
    protected $allowedFields    = ['kode', 'nama'];
    protected string $fkTransaksi = 'cost_center_id';
    protected string $urut        = 'kode';
}
