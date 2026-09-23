<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ---- Akses
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->post('logout', 'Auth::logout');

// ---- Area terautentikasi
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard/data', 'Dashboard::data');

    $routes->get('profil', 'Profil::index');
    $routes->post('profil/password', 'Profil::password');

    $routes->get('transaksi', 'Transaksi::index');

    $routes->get('anggaran', 'Anggaran::index');

    // Tulis: administrator & operator (didefinisikan sebelum rute (:num))
    $routes->group('', ['filter' => 'role:admin,operator'], static function (RouteCollection $routes) {
        $routes->get('transaksi/baru', 'Transaksi::create');
        $routes->post('transaksi/simpan', 'Transaksi::store');
        $routes->get('transaksi/import', 'Import::index');
        $routes->post('transaksi/import/unggah', 'Import::upload');
        $routes->post('transaksi/import/konfirmasi', 'Import::confirm');
        $routes->get('transaksi/(:num)/ubah', 'Transaksi::edit/$1');
        $routes->post('transaksi/(:num)/ubah', 'Transaksi::update/$1');
        $routes->post('transaksi/(:num)/hapus', 'Transaksi::delete/$1');

        $routes->post('anggaran/simpan', 'Anggaran::save');
        $routes->post('anggaran/(:num)/hapus', 'Anggaran::delete/$1');

        $routes->get('master/(:segment)', 'Master::index/$1');
        $routes->post('master/(:segment)/simpan', 'Master::save/$1');
        $routes->post('master/(:segment)/(:num)/hapus', 'Master::delete/$1/$2');
    });

    $routes->get('transaksi/(:num)', 'Transaksi::show/$1');

    $routes->get('laporan', 'Laporan::index');
    $routes->get('laporan/unduh/(:segment)', 'Laporan::download/$1');
    $routes->get('validasi', 'Validasi::index');

    // Administrator
    $routes->group('', ['filter' => 'role:admin'], static function (RouteCollection $routes) {
        $routes->get('pengguna', 'Users::index');
        $routes->get('pengguna/baru', 'Users::create');
        $routes->post('pengguna/simpan', 'Users::store');
        $routes->get('pengguna/(:num)/ubah', 'Users::edit/$1');
        $routes->post('pengguna/(:num)/ubah', 'Users::update/$1');
        $routes->post('pengguna/(:num)/hapus', 'Users::delete/$1');
        $routes->get('audit', 'Audit::index');
    });
});
