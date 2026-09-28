<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Konfirmasi Lunas<?= $this->endSection() ?>
<?= $this->section('heading') ?>Konfirmasi Lunas<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Pengajuan status "Lunas" dari operator yang menunggu keputusan admin<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if (! $rows) : ?>
    <div class="panel"><div class="empty"><i class="bi bi-check2-circle text-ok"></i>Tidak ada pengajuan yang menunggu konfirmasi.</div></div>
<?php else : ?>
    <div class="panel">
        <div class="panel-head"><h2><?= angka(count($rows)) ?> pengajuan menunggu</h2><span class="meta ms-auto">Periksa bukti pembayaran sebelum menyetujui</span></div>
        <div class="table-scroll" style="max-height:none">
            <table class="table table-ledger">
                <thead><tr><th>Diajukan</th><th>Aktivitas</th><th>Jenis</th><th class="num">Realisasi</th><th>Bukti</th><th class="text-end">Keputusan</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r) : ?>
                    <tr>
                        <td class="text-nowrap"><?= esc($r['bukti_pembayaran_at'] ?? '—') ?><div class="cell-meta">oleh <?= esc($r['diajukan_nama'] ?? 'tidak diketahui') ?></div></td>
                        <td style="max-width:380px"><a class="cell-title text-body" href="<?= site_url('transaksi/' . $r['id']) ?>"><?= esc($r['aktivitas']) ?></a></td>
                        <td><span class="chip chip-teal"><?= esc($r['jenis']) ?></span></td>
                        <td class="num"><?= angka($r['realisasi_anggaran']) ?></td>
                        <td><a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="<?= site_url("transaksi/{$r['id']}/bukti") ?>"><i class="bi bi-paperclip me-1"></i>Lihat bukti</a></td>
                        <td class="text-end text-nowrap">
                            <form class="d-inline" method="post" action="<?= site_url("konfirmasi/{$r['id']}/setujui") ?>"><?= csrf_field() ?>
                                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2 me-1"></i>Setujui</button>
                            </form>
                            <button class="btn btn-ghost btn-sm text-danger" type="button" data-bs-toggle="modal" data-bs-target="#tolakModal" data-id="<?= $r['id'] ?>" data-nama="<?= esc(mb_strimwidth($r['aktivitas'], 0, 80, '…'), 'attr') ?>"><i class="bi bi-x-lg me-1"></i>Tolak</button>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif ?>

<div class="modal fade" id="tolakModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" id="tolakForm" action="">
            <?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title h6">Tolak pengajuan Lunas</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="small-2 mb-3" id="tolakNama"></p>
                <label class="form-label" for="alasan">Alasan penolakan <span class="text-danger">*</span></label>
                <textarea class="form-control" id="alasan" name="alasan" rows="3" maxlength="255" required></textarea>
                <div class="form-text">Status transaksi tetap seperti semula. Operator dapat mengajukan ulang dengan bukti yang benar.</div>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger" type="submit">Tolak pengajuan</button></div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('tolakModal').addEventListener('show.bs.modal', function (ev) {
  var b = ev.relatedTarget;
  document.getElementById('tolakForm').action = <?= json_encode(rtrim(site_url('konfirmasi'), '/')) ?> + '/' + b.getAttribute('data-id') + '/tolak';
  document.getElementById('tolakNama').textContent = b.getAttribute('data-nama');
  document.getElementById('alasan').value = '';
});
</script>
<?= $this->endSection() ?>
