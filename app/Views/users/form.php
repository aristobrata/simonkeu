<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $mode === 'baru' ? 'Tambah pengguna' : 'Ubah pengguna' ?><?= $this->endSection() ?>
<?= $this->section('heading') ?><?= $mode === 'baru' ? 'Tambah pengguna' : 'Ubah pengguna' ?><?= $this->endSection() ?>
<?= $this->section('actions') ?><a class="btn btn-ghost btn-sm" href="<?= site_url('pengguna') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $v = static function (string $k, $d = '') use ($row) { $o = old($k); return $o !== null ? $o : ($row[$k] ?? $d); }; ?>
<form class="panel" style="max-width:640px" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <div class="panel-body">
        <div class="row g-3">
            <div class="col-12"><label class="form-label" for="nama">Nama lengkap</label><input class="form-control" id="nama" name="nama" value="<?= esc($v('nama')) ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="username">Nama pengguna</label><input class="form-control" id="username" name="username" value="<?= esc($v('username')) ?>" required autocomplete="off"></div>
            <div class="col-md-6"><label class="form-label" for="role">Peran</label>
                <select class="form-select" id="role" name="role"><?php foreach (\App\Models\UserModel::ROLE as $k => $n) : ?><option value="<?= $k ?>" <?= $v('role') === $k ? 'selected' : '' ?>><?= esc($n) ?></option><?php endforeach ?></select>
                <div class="form-text">Operator boleh mengelola transaksi dan master data; peninjau hanya membaca.</div></div>
            <div class="col-12"><label class="form-label" for="password">Kata sandi<?= $mode === 'ubah' ? ' baru' : '' ?></label><input class="form-control" type="password" id="password" name="password" <?= $mode === 'baru' ? 'required' : '' ?> minlength="8" autocomplete="new-password">
                <div class="form-text"><?= $mode === 'ubah' ? 'Kosongkan bila tidak ingin mengganti. ' : '' ?>Minimal 8 karakter.</div></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="aktif" name="aktif" value="1" <?= (int) (old('nama') !== null ? (old('aktif') ? 1 : 0) : $row['aktif']) ? 'checked' : '' ?>><label class="form-check-label" for="aktif">Akun aktif</label></div></div>
        </div>
    </div>
    <div class="panel-head border-top border-bottom-0 justify-content-end"><a class="btn btn-ghost" href="<?= site_url('pengguna') ?>">Batal</a><button class="btn btn-primary" type="submit">Simpan</button></div>
</form>
<?= $this->endSection() ?>
