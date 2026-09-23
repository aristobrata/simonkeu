<?php

namespace App\Controllers;

use App\Libraries\Statistik;
use App\Models\JenisAktivitasModel;
use App\Models\TransaksiModel;

class Dashboard extends BaseController
{
    /** Kerangka halaman; data dimuat lewat dashboard/data agar filter tidak memuat ulang halaman. */
    public function index()
    {
        $tahunList = (new TransaksiModel())->daftarTahun();
        $tahun     = (int) $this->request->getGet('tahun');
        if (! in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        return view('dashboard/index', [
            'tahunList' => $tahunList ?: [$tahun],
            'tahun'     => $tahun,
            'dari'      => max(1, min(12, (int) ($this->request->getGet('dari') ?: 1))),
            'sampai'    => max(1, min(12, (int) ($this->request->getGet('sampai') ?: 12))),
            'jenisId'   => (int) $this->request->getGet('jenis'),
            'jenisList' => (new JenisAktivitasModel())->opsi(),
        ]);
    }

    public function data()
    {
        $f = [
            'tahun'        => (int) $this->request->getGet('tahun'),
            'bulan_dari'   => (int) ($this->request->getGet('dari') ?: 1),
            'bulan_sampai' => (int) ($this->request->getGet('sampai') ?: 12),
            'jenis'        => (int) $this->request->getGet('jenis'),
        ];
        if ($f['tahun'] < 2000) {
            $f['tahun'] = (int) date('Y');
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON((new Statistik())->dashboard($f));
    }
}
