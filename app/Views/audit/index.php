<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Log aktivitas<?= $this->endSection() ?>
<?= $this->section('heading') ?>Log aktivitas<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Jejak siapa mengubah apa dan kapan<?= $this->endSection() ?>
<?= $this->section('content') ?>
<form class="panel mb-3" method="get"><div class="panel-body"><div class="filterbar">
    <div class="f"><label for="aksi">Aksi</label><select class="form-select form-select-sm" id="aksi" name="aksi"><option value="">Semua</option><?php foreach ($aksi as $a) : ?><option <?= $f['aksi'] === $a ? 'selected' : '' ?>><?= esc($a) ?></option><?php endforeach ?></select></div>
    <div class="f"><label for="entitas">Objek</label><input class="form-control form-control-sm" id="entitas" name="entitas" value="<?= esc($f['entitas']) ?>" placeholder="transaksi, users, master"></div>
    <div class="f"><label for="user">Pengguna</label><input class="form-control form-control-sm" id="user" name="user" value="<?= esc($f['user']) ?>"></div>
    <div class="f" style="min-width:auto"><label>&nbsp;</label><div class="d-flex gap-2"><button class="btn btn-primary btn-sm">Terapkan</button><a class="btn btn-ghost btn-sm" href="<?= site_url('audit') ?>">Atur ulang</a></div></div>
</div></div></form>
<div class="panel"><div class="table-scroll"><table class="table table-ledger">
    <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Objek</th><th>Ringkasan</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (! $rows) : ?><tr><td colspan="6"><div class="empty"><i class="bi bi-inbox"></i>Belum ada aktivitas tercatat.</div></td></tr><?php endif ?>
    <?php foreach ($rows as $r) : ?>
        <tr><td class="text-nowrap"><?= esc($r['created_at']) ?></td><td><?= esc($r['username'] ?? '—') ?></td>
            <td><span class="chip <?= in_array($r['aksi'], ['hapus', 'login_gagal'], true) ? 'chip-brick' : (in_array($r['aksi'], ['tambah', 'import'], true) ? 'chip-teal' : '') ?>"><?= esc($r['aksi']) ?></span></td>
            <td class="text-nowrap"><?php if ($r['entitas'] === 'transaksi' && $r['entitas_id']) : ?><a href="<?= site_url('transaksi/' . $r['entitas_id']) ?>">transaksi #<?= $r['entitas_id'] ?></a><?php else : ?><?= esc($r['entitas']) . ($r['entitas_id'] ? ' #' . $r['entitas_id'] : '') ?><?php endif ?></td>
            <td style="max-width:520px"><?= esc($r['ringkasan'] ?? '') ?></td><td class="small-2"><?= esc($r['ip'] ?? '') ?></td></tr>
    <?php endforeach ?>
    </tbody></table></div>
    <div class="panel-head border-top border-bottom-0 justify-content-center"><?= $pager->links('default', 'simonkeu') ?></div></div>
<?= $this->endSection() ?>
