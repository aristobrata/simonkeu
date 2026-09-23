<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($cfg['judul']) ?><?= $this->endSection() ?>
<?= $this->section('heading') ?><?= esc($cfg['judul']) ?><?= $this->endSection() ?>
<?= $this->section('subheading') ?><?= esc($cfg['bantuan']) ?><?= $this->endSection() ?>
<?= $this->section('actions') ?>
<button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#masterModal" data-baru><i class="bi bi-plus-lg me-1"></i>Tambah</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="panel">
    <div class="table-scroll">
        <table class="table table-ledger">
            <thead><tr>
                <?php foreach ($cfg['kolom'] as $label) : ?><th><?= esc($label) ?></th><?php endforeach ?>
                <th class="num">Dipakai</th><th class="text-end"><span class="visually-hidden">Aksi</span></th>
            </tr></thead>
            <tbody>
            <?php if (! $rows) : ?><tr><td colspan="<?= count($cfg['kolom']) + 2 ?>"><div class="empty"><i class="bi bi-inbox"></i>Belum ada data.</div></td></tr><?php endif ?>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <?php foreach (array_keys($cfg['kolom']) as $i => $k) : ?><td class="<?= $i === 0 ? 'fw-600' : '' ?>"><?= esc($r[$k] ?? '') ?: '<span class="small-2">—</span>' ?></td><?php endforeach ?>
                    <td class="num"><?= angka($r['dipakai']) ?> transaksi</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-ghost btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#masterModal"
                                data-row='<?= esc(json_encode($r, JSON_UNESCAPED_UNICODE), 'attr') ?>' title="Ubah"><i class="bi bi-pencil"></i></button>
                        <form class="d-inline" method="post" action="<?= site_url("master/$slug/{$r['id']}/hapus") ?>"><?= csrf_field() ?>
                            <button class="btn btn-ghost btn-sm text-danger" type="button" <?= $r['dipakai'] > 0 ? 'disabled title="Masih dipakai transaksi"' : 'title="Hapus" data-confirm="Hapus &quot;' . esc(($r['kode'] ?? $r['nama']), 'attr') . '&quot;?"' ?>><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="masterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url("master/$slug/simpan") ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="mId" value="0">
            <div class="modal-header"><h2 class="modal-title h6" id="mTitle">Tambah <?= esc(mb_strtolower($cfg['judul'])) ?></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <?php foreach ($cfg['kolom'] as $k => $label) : ?>
                    <div class="mb-3"><label class="form-label" for="m_<?= $k ?>"><?= esc($label) ?><?= $k === 'deskripsi' ? '' : ' <span class="text-danger">*</span>' ?></label>
                        <input class="form-control" id="m_<?= $k ?>" name="<?= $k ?>" maxlength="<?= $k === 'kode' ? 20 : 150 ?>" <?= $k === 'deskripsi' ? '' : 'required' ?>></div>
                <?php endforeach ?>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  var el = document.getElementById('masterModal');
  var judul = <?= json_encode(mb_strtolower($cfg['judul'])) ?>;
  el.addEventListener('show.bs.modal', function (ev) {
    var b = ev.relatedTarget, row = b && b.getAttribute('data-row');
    var data = row ? JSON.parse(row) : {};
    document.getElementById('mId').value = data.id || 0;
    document.getElementById('mTitle').textContent = (data.id ? 'Ubah ' : 'Tambah ') + judul;
    el.querySelectorAll('input.form-control').forEach(function (i) { i.value = data[i.name] || ''; });
  });
  el.addEventListener('shown.bs.modal', function () { var f = el.querySelector('input.form-control'); if (f) f.focus(); });
})();
</script>
<?= $this->endSection() ?>
