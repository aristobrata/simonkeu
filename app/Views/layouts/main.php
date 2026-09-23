<?php
$cfg  = config('Simonkeu');
$user = current_user();
$inisial = strtoupper(mb_substr($user['nama'] ?? '?', 0, 1));

// Menu: [label, url, ikon, pola URL aktif, peran yang boleh melihat]
$nav = [
    ['Ringkasan' => [
        ['Dashboard', '/', 'bi-grid-1x2', ['/'], null],
    ]],
    ['Data' => [
        ['Transaksi biaya', 'transaksi', 'bi-receipt', ['transaksi', 'transaksi/(:num)*', 'transaksi/baru', 'transaksi/*/ubah'], null],
        ['Import Excel', 'transaksi/import', 'bi-file-earmark-arrow-up', ['transaksi/import*'], ['admin', 'operator']],
    ]],
    ['Anggaran' => [
        ['Anggaran tahunan', 'anggaran', 'bi-piggy-bank', ['anggaran*'], null],
    ]],
    ['Laporan' => [
        ['Laporan & ekspor', 'laporan', 'bi-file-earmark-bar-graph', ['laporan*'], null],
        ['Validasi data', 'validasi', 'bi-shield-check', ['validasi*'], null],
    ]],
    ['Master data' => [
        ['Jenis aktivitas', 'master/jenis-aktivitas', 'bi-tags', ['master/jenis-aktivitas'], ['admin', 'operator']],
        ['Inhouse / Public', 'master/pelaksanaan', 'bi-diagram-2', ['master/pelaksanaan'], ['admin', 'operator']],
        ['No. akun', 'master/akun', 'bi-journal-text', ['master/akun'], ['admin', 'operator']],
        ['Cost center', 'master/cost-center', 'bi-building', ['master/cost-center'], ['admin', 'operator']],
    ]],
    ['Administrasi' => [
        ['Pengguna', 'pengguna', 'bi-people', ['pengguna*'], ['admin']],
        ['Log aktivitas', 'audit', 'bi-clock-history', ['audit*'], ['admin']],
    ]],
];
$aktif = static function (array $pola): bool {
    $uri = trim(uri_string(), '/');
    foreach ($pola as $p) {
        $p = trim($p, '/');
        if ($p === '' && $uri === '') { return true; }
        if ($p === '') { continue; }
        $re = '#^' . str_replace(['\*', '\(:num\)'], ['.*', '\d+'], preg_quote($p, '#')) . '$#';
        if (preg_match($re, $uri)) { return true; }
    }
    return false;
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(trim($this->renderSection('title')) ?: 'Beranda') ?> · <?= esc($cfg->appName) ?></title>
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/fonts/plus-jakarta-sans.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= site_url('/') ?>">
            <svg class="brand-mark" viewBox="0 0 34 34" aria-hidden="true"><rect width="34" height="34" rx="8" fill="#0B7A75"/><rect x="7" y="18" width="5" height="9" rx="1.2" fill="#fff"/><rect x="14.5" y="10" width="5" height="17" rx="1.2" fill="#E8A317"/><rect x="22" y="14" width="5" height="13" rx="1.2" fill="#fff"/></svg>
            <div><b><?= esc($cfg->appName) ?></b><span><?= esc($cfg->unitName . ' · ' . $cfg->orgName) ?></span></div>
        </a>
        <nav aria-label="Menu utama">
            <?php foreach ($nav as $grup) : foreach ($grup as $judul => $items) :
                $tampil = array_filter($items, static fn ($i) => $i[4] === null || has_role(...$i[4]));
                if (! $tampil) { continue; } ?>
                <div class="nav-group">
                    <small><?= esc($judul) ?></small>
                    <?php foreach ($tampil as [$label, $url, $ikon, $pola]) : ?>
                        <a class="nav-link-s <?= $aktif($pola) ? 'active' : '' ?>" href="<?= site_url($url) ?>"<?= $aktif($pola) ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= $ikon ?>"></i><span><?= esc($label) ?></span>
                        </a>
                    <?php endforeach ?>
                </div>
            <?php endforeach; endforeach ?>
        </nav>
        <div class="side-foot">Data sesuai template laporan keuangan (Excel).</div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="btn btn-ghost btn-sm sidebar-toggle" type="button" data-toggle-nav aria-label="Buka menu"><i class="bi bi-list fs-5"></i></button>
            <div>
                <h1 class="page-title"><?= $this->renderSection('heading') ?></h1>
                <?php if ($sub = trim($this->renderSection('subheading'))) : ?><p class="page-sub"><?= $sub ?></p><?php endif ?>
            </div>
            <div class="spacer"></div>
            <?= $this->renderSection('actions') ?>
            <div class="dropdown">
                <button class="user-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar"><?= esc($inisial) ?></span>
                    <span class="d-none d-md-block text-start lh-sm"><span class="d-block fw-600" style="font-size:.86rem"><?= esc($user['nama'] ?? '') ?></span><span class="small-2"><?= esc(\App\Models\UserModel::ROLE[$user['role'] ?? ''] ?? '') ?></span></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= site_url('profil') ?>"><i class="bi bi-key me-2"></i>Ubah kata sandi</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="<?= site_url('logout') ?>" method="post" class="m-0"><?= csrf_field() ?>
                            <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="content">
            <?php foreach (['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'] as $kunci => $kelas) :
                if ($pesan = session()->getFlashdata($kunci)) : ?>
                <div class="alert alert-<?= $kelas ?> alert-dismissible fade show" role="alert">
                    <?= is_array($pesan) ? implode('<br>', array_map('esc', $pesan)) : esc($pesan) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; endforeach ?>
            <?php if ($errs = session()->getFlashdata('errors')) : ?>
                <div class="alert alert-danger" role="alert">
                    <b>Data belum bisa disimpan. Periksa isian berikut:</b>
                    <ul class="mb-0 mt-1"><?php foreach ((array) $errs as $e) : ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
                </div>
            <?php endif ?>

            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body pt-4">
                <p class="mb-0" data-confirm-text>Yakin?</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm" data-confirm-ok>Hapus</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
