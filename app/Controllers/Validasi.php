<?php

namespace App\Controllers;

use App\Libraries\DataChecks;
use App\Models\TransaksiModel;

/** Halaman "Validasi data": daftar temuan kualitas data berdasarkan aturan DataChecks. */
class Validasi extends BaseController
{
    public function index()
    {
        $tahunList = (new TransaksiModel())->daftarTahun();
        $tahun     = (int) $this->request->getGet('tahun');   // 0 = semua tahun
        $f         = $tahun > 0 ? ['tahun' => $tahun] : [];

        $cek     = new DataChecks();
        $ringkas = $cek->ringkasan($f);
        $temuan  = [];
        foreach ($ringkas as $kode => $n) {
            $temuan[$kode] = ['jumlah' => $n, 'baris' => $cek->daftar($kode, $f, 200)];
        }

        return view('validasi/index', ['tahunList' => $tahunList, 'tahun' => $tahun, 'temuan' => $temuan, 'aturan' => DataChecks::ATURAN]);
    }
}
