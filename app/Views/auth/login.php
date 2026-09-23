<?php $cfg = config('Simonkeu'); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · <?= esc($cfg->appName) ?></title>
    <link rel="stylesheet" href="<?= asset('vendor/fonts/plus-jakarta-sans.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="login">
    <section class="login-art" aria-hidden="false">
        <div class="brand">
            <svg class="brand-mark" viewBox="0 0 34 34" aria-hidden="true"><rect width="34" height="34" rx="8" fill="#0B7A75"/><rect x="7" y="18" width="5" height="9" rx="1.2" fill="#fff"/><rect x="14.5" y="10" width="5" height="17" rx="1.2" fill="#E8A317"/><rect x="22" y="14" width="5" height="13" rx="1.2" fill="#fff"/></svg>
            <div><b><?= esc($cfg->appName) ?></b><span><?= esc($cfg->unitName . ' · ' . $cfg->orgName) ?></span></div>
        </div>
        <h1>Rencana dan realisasi anggaran diklat, dalam satu tampilan.</h1>
        <p>Pantau serapan biaya per bulan, per jenis aktivitas, dan per akun. Data mengikuti template laporan keuangan yang sudah Anda pakai.</p>
        <div class="ledger-bars" aria-hidden="true">
            <?php foreach ([42, 64, 51, 78, 60, 92, 70] as $h) : ?><span style="height:<?= $h ?>%"></span><?php endforeach ?>
        </div>
    </section>
    <section class="login-form">
        <form method="post" action="<?= site_url('login') ?>" autocomplete="on">
            <?= csrf_field() ?>
            <h2 class="mb-1">Masuk</h2>
            <p class="text-secondary mb-4">Gunakan akun yang diberikan administrator.</p>

            <?php if ($e = session()->getFlashdata('error')) : ?>
                <div class="alert alert-danger py-2" role="alert"><?= esc($e) ?></div>
            <?php endif ?>
            <?php if ($e = session()->getFlashdata('success')) : ?>
                <div class="alert alert-success py-2" role="status"><?= esc($e) ?></div>
            <?php endif ?>

            <div class="mb-3">
                <label class="form-label" for="username">Nama pengguna</label>
                <input class="form-control" id="username" name="username" value="<?= esc(old('username')) ?>" autofocus required autocomplete="username">
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Kata sandi</label>
                <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary w-100 py-2" type="submit">Masuk</button>
        </form>
    </section>
</div>
</body>
</html>
