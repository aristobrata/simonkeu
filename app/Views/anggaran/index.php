<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Anggaran tahunan<?= $this->endSection() ?>
<?= $this->section('heading') ?>Anggaran tahunan<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Pagu anggaran <?= $tahun ?> — berkurang otomatis mengikuti realisasi transaksi<?= $this->endSection() ?>
<?= $this->section('actions') ?>
<form method="get" class="d-flex align-items-center gap-2 m-0">
    <label class="small-2" for="tahun">Tahun</label>
    <select class="form-select form-select-sm" id="tahun" name="tahun" onchange="this.form.submit()" style="width:auto">
        <?php foreach ($tahunList as $t) : ?><option <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option><?php endforeach ?>
    </select>
</form>
<?php if (has_role('admin', 'operator')) : ?>
    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#anggaranModal" data-baru data-tahun="<?= $tahun ?>"><i class="bi bi-plus-lg me-1"></i>Tambah pagu</button>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$totalNominal = array_sum(array_column($rows, 'nominal'));
$totalRealisasi = array_sum(array_column($rows, 'realisasi'));
?>

<?php if (! $rows) : ?>
    <div class="panel"><div class="empty">
        <i class="bi bi-piggy-bank"></i>Belum ada pagu anggaran untuk tahun <?= $tahun ?>.
        <?php if (has_role('admin', 'operator')) : ?>
            <div class="mt-3"><button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#anggaranModal" data-baru data-tahun="<?= $tahun ?>"><i class="bi bi-plus-lg me-1"></i>Tambah pagu pertama</button></div>
        <?php endif ?>
    </div></div>
<?php else : ?>
    <div class="panel">
        <div class="table-scroll">
            <table class="table table-ledger">
                <thead><tr>
                    <th>Cakupan</th><th class="num">Pagu anggaran</th><th class="num">Realisasi terpakai</th><th class="num">Sisa</th><th style="width:220px">Serapan</th><th>Keterangan</th><th class="text-end"><span class="visually-hidden">Aksi</span></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r) : $over = $r['serapan'] > 100.05; ?>
                    <tr>
                        <td class="cell-title"><?= $r['jenis_aktivitas_id'] === null ? '<span class="chip chip-gold">Semua jenis (total tahunan)</span>' : '<span class="chip chip-teal">' . esc($r['jenis_nama']) . '</span>' ?></td>
                        <td class="num"><?= rupiah($r['nominal']) ?></td>
                        <td class="num"><?= rupiah($r['realisasi']) ?></td>
                        <td class="num <?= selisih_kelas($r['sisa']) ?> fw-600"><?= rupiah($r['sisa']) ?></td>
                        <td>
                            <div class="d-flex justify-content-between small-2 mb-1"><span class="<?= $over ? 'text-over fw-600' : '' ?>"><?= persen($r['serapan']) ?></span></div>
                            <div class="budget-bar <?= $over ? 'over' : '' ?>" style="margin:0"><i style="width:<?= min(100, max(0, $r['serapan'])) ?>%"></i></div>
                        </td>
                        <td class="small-2"><?= esc($r['keterangan'] ?: '—') ?></td>
                        <td class="text-end text-nowrap">
                            <?php if (has_role('admin', 'operator')) : ?>
                                <button class="btn btn-ghost btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#anggaranModal"
                                    data-row='<?= esc(json_encode($r, JSON_UNESCAPED_UNICODE), 'attr') ?>' title="Ubah"><i class="bi bi-pencil"></i></button>
                                <form class="d-inline" method="post" action="<?= site_url("anggaran/{$r['id']}/hapus") ?>"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-sm text-danger" type="button" data-confirm="Hapus pagu anggaran ini?" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
                <?php if (count($rows) > 1) : ?>
                <tfoot><tr>
                    <td>Total (jumlah semua baris di atas)</td>
                    <td class="num"><?= rupiah($totalNominal) ?></td>
                    <td class="num"><?= rupiah($totalRealisasi) ?></td>
                    <td class="num"><?= rupiah($totalNominal - $totalRealisasi) ?></td>
                    <td colspan="3"></td>
                </tr></tfoot>
                <?php endif ?>
            </table>
        </div>
    </div>
    <p class="small-2 mt-2">Catatan: bila ada pagu "Semua jenis" sekaligus pagu per jenis aktivitas, keduanya dihitung terpisah (pagu per jenis bukan pecahan dari pagu total) — jumlahkan sendiri sesuai kebutuhan.</p>
<?php endif ?>

<div class="modal fade" id="anggaranModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('anggaran/simpan') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="aId" value="0">
            <div class="modal-header"><h2 class="modal-title h6" id="aTitle">Tambah pagu anggaran</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="aTahun">Tahun <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" id="aTahun" name="tahun" min="2000" max="2100" required></div>
                <div class="mb-3"><label class="form-label" for="aJenis">Cakupan</label>
                    <select class="form-select" id="aJenis" name="jenis_aktivitas_id">
                        <option value="">— Semua jenis aktivitas (total tahunan) —</option>
                        <?php foreach ($jenisList as $id => $nm) : ?><option value="<?= $id ?>"><?= esc($nm) ?></option><?php endforeach ?>
                    </select>
                    <div class="form-text">Pilih "Semua jenis" untuk satu pagu besar per tahun, atau pilih jenis tertentu untuk pagu terpisah.</div></div>
                <div class="mb-3"><label class="form-label" for="aNominal">Nominal anggaran <span class="text-danger">*</span></label>
                    <div class="input-group"><span class="input-group-text">Rp</span><input class="form-control money-input" inputmode="numeric" data-money id="aNominal" name="nominal" required></div></div>
                <div class="mb-1"><label class="form-label" for="aKet">Keterangan</label><input class="form-control" id="aKet" name="keterangan" maxlength="255"></div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  var el = document.getElementById('anggaranModal');
  el.addEventListener('show.bs.modal', function (ev) {
    var b = ev.relatedTarget, row = b && b.getAttribute('data-row');
    var data = row ? JSON.parse(row) : {};
    document.getElementById('aId').value = data.id || 0;
    document.getElementById('aTitle').textContent = data.id ? 'Ubah pagu anggaran' : 'Tambah pagu anggaran';
    document.getElementById('aTahun').value = data.tahun || (b && b.getAttribute('data-tahun')) || new Date().getFullYear();
    document.getElementById('aJenis').value = data.jenis_aktivitas_id || '';
    document.getElementById('aNominal').value = data.nominal ? Simonkeu.fmtMoney(data.nominal) : '';
    document.getElementById('aKet').value = data.keterangan || '';
  });
})();
</script>
<?= $this->endSection() ?>
