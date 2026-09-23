<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan khusus aplikasi SIMONKEU.
 * Nilai skalar dapat ditimpa lewat .env, contoh:  Simonkeu.orgName = 'PT Contoh'
 */
class Simonkeu extends BaseConfig
{
    public string $appName  = 'SIMONKEU';
    public string $unitName = 'Pusdiklat';
    public string $orgName  = 'PT Semen Padang';

    /** Pilihan status pembayaran (kolom "Status pembayaran" pada template). */
    public array $statusPembayaran = ['Belum Dibayar', 'Diproses', 'Lunas', 'Akrual'];

    /** Rincian biaya sesuai kolom M–U template: kolom DB => label. */
    public array $komponen = [
        'biaya_training'     => 'Biaya Training / Instruktur',
        'biaya_materi'       => 'Biaya Materi',
        'biaya_konsumsi'     => 'Konsumsi',
        'biaya_perlengkapan' => 'Perlengkapan',
        'biaya_tiket'        => 'Tiket Pesawat',
        'biaya_hotel'        => 'Hotel',
        'biaya_transportasi' => 'Transportasi',
        'biaya_uang_saku'    => 'Uang Saku / SPJ',
        'biaya_lainnya'      => 'Biaya Lainnya',
    ];

    public int $perPage = 25;
}
