<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ubah kata sandi<?= $this->endSection() ?>
<?= $this->section('heading') ?>Ubah kata sandi<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $u = current_user(); ?>
<div class="panel" style="max-width:520px">
    <div class="panel-head"><h2>Akun <?= esc($u['username']) ?></h2></div>
    <form class="panel-body" method="post" action="<?= site_url('profil/password') ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label" for="pl">Kata sandi saat ini</label><input class="form-control" type="password" id="pl" name="password_lama" required autocomplete="current-password"></div>
        <div class="mb-3"><label class="form-label" for="pb">Kata sandi baru</label><input class="form-control" type="password" id="pb" name="password_baru" required minlength="8" autocomplete="new-password"><div class="form-text">Minimal 8 karakter.</div></div>
        <div class="mb-4"><label class="form-label" for="kf">Ulangi kata sandi baru</label><input class="form-control" type="password" id="kf" name="konfirmasi" required autocomplete="new-password"></div>
        <button class="btn btn-primary" type="submit">Simpan kata sandi</button>
    </form>
</div>
<?= $this->endSection() ?>
