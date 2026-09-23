<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Pengguna<?= $this->endSection() ?>
<?= $this->section('heading') ?>Pengguna<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Administrator, operator keuangan, dan peninjau<?= $this->endSection() ?>
<?= $this->section('actions') ?><a class="btn btn-primary btn-sm" href="<?= site_url('pengguna/baru') ?>"><i class="bi bi-plus-lg me-1"></i>Tambah pengguna</a><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="panel"><div class="table-scroll"><table class="table table-ledger">
    <thead><tr><th>Nama</th><th>Nama pengguna</th><th>Peran</th><th>Status</th><th>Terakhir masuk</th><th class="text-end"><span class="visually-hidden">Aksi</span></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r) : ?>
        <tr>
            <td class="fw-600"><?= esc($r['nama']) ?></td>
            <td><?= esc($r['username']) ?></td>
            <td><span class="chip <?= $r['role'] === 'admin' ? 'chip-gold' : ($r['role'] === 'operator' ? 'chip-teal' : '') ?>"><?= esc(\App\Models\UserModel::ROLE[$r['role']] ?? $r['role']) ?></span></td>
            <td><?= $r['aktif'] ? '<span class="chip chip-teal">Aktif</span>' : '<span class="chip chip-brick">Nonaktif</span>' ?></td>
            <td class="small-2"><?= esc($r['last_login'] ?? 'Belum pernah') ?></td>
            <td class="text-end text-nowrap">
                <a class="btn btn-ghost btn-sm" href="<?= site_url("pengguna/{$r['id']}/ubah") ?>" title="Ubah"><i class="bi bi-pencil"></i></a>
                <?php if ((int) $r['id'] !== (int) current_user()['id']) : ?>
                <form class="d-inline" method="post" action="<?= site_url("pengguna/{$r['id']}/hapus") ?>"><?= csrf_field() ?>
                    <button class="btn btn-ghost btn-sm text-danger" type="button" data-confirm="Hapus pengguna &quot;<?= esc($r['username'], 'attr') ?>&quot;?" title="Hapus"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody></table></div></div>
<?= $this->endSection() ?>
