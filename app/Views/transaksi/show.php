<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Detail transaksi<?= $this->endSection() ?>
<?= $this->section('heading') ?>Detail transaksi<?= $this->endSection() ?>
<?= $this->section('actions') ?>
<a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi') ?>"><i class="bi bi-arrow-left me-1"></i>Daftar</a>
<?php if (has_role('admin', 'operator')) : ?>
    <a class="btn btn-primary btn-sm" href="<?= site_url("transaksi/{$row['id']}/ubah") ?>"><i class="bi bi-pencil me-1"></i>Ubah</a>
    <form class="d-inline m-0" method="post" action="<?= site_url("transaksi/{$row['id']}/hapus") ?>"><?= csrf_field() ?>
        <button class="btn btn-ghost btn-sm text-danger" type="button" data-confirm="Hapus transaksi ini? Data tetap tersimpan di log aktivitas."><i class="bi bi-trash me-1"></i>Hapus</button>
    </form>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$komponen = config('Simonkeu')->komponen;
$total    = (float) $row['total_biaya'];
$anggaran = (float) $row['rencana_anggaran'] + (float) $row['tambahan_anggaran'];
$sisa     = $anggaran - (float) $row['realisasi_anggaran'];
$serapan  = $anggaran > 0 ? (float) $row['realisasi_anggaran'] / $anggaran * 100 : 0;
$beda     = abs((float) $row['realisasi_anggaran'] - $total) > 0.5;
?>
<div class="panel mb-3">
    <div class="panel-body">
        <h2 class="h5 mb-2"><?= esc($row['aktivitas']) ?></h2>
        <span class="chip chip-teal"><?= esc($row['jenis']) ?></span>
        <?php if ($row['pelaksanaan']) : ?><span class="chip"><?= esc($row['pelaksanaan']) ?></span><?php endif ?>
        <?php if ($row['status_pembayaran']) : ?><span class="chip chip-slate"><?= esc($row['status_pembayaran']) ?></span><?php endif ?>
        <span class="chip chip-gold"><?= bulan_id((int) $row['periode_bulan'], false) . ' ' . $row['periode_tahun'] ?></span>
    </div>
</div>

<?php if ($beda) : ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Realisasi anggaran (<?= rupiah($row['realisasi_anggaran']) ?>) berbeda dari total rincian biaya (<?= rupiah($total) ?>). Periksa apakah salah satunya perlu dikoreksi.</div>
<?php endif ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-head"><h3>Informasi</h3></div>
            <div class="panel-body">
                <dl class="dl">
                    <dt>Tanggal</dt><dd><?= tgl_id($row['tgl_mulai'], false) ?><?= $row['tgl_selesai'] ? ' – ' . tgl_id($row['tgl_selesai'], false) : '' ?></dd>
                    <dt>Tempat</dt><dd><?= esc($row['tempat'] ?: '—') ?></dd>
                    <dt>Jumlah peserta</dt><dd><?= $row['jml_peserta'] !== null ? angka($row['jml_peserta']) : '—' ?></dd>
                    <dt>No. akun</dt><dd><?= esc($row['akun_kode'] . ' – ' . $row['akun_nama']) ?></dd>
                    <dt>Cost center</dt><dd><?= esc($row['cc_kode'] . ' – ' . $row['cc_nama']) ?></dd>
                    <dt>No. parking</dt><dd><?= esc($row['no_parking'] ?: '—') ?></dd>
                    <dt>Tgl pembayaran terakhir</dt><dd><?= $row['tgl_pembayaran_terakhir'] ? tgl_id($row['tgl_pembayaran_terakhir'], false) : '—' ?></dd>
                    <dt>Keterangan</dt><dd><?= $row['keterangan'] ? nl2br(esc($row['keterangan'])) : '—' ?></dd>
                    <dt>Terakhir diperbarui</dt><dd><?= esc($row['updated_at'] ?? '—') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-head"><h3>Anggaran</h3></div>
            <div class="panel-body">
                <dl class="dl" style="grid-template-columns:150px 1fr">
                    <dt>Rencana</dt><dd class="num text-start"><?= rupiah($row['rencana_anggaran']) ?></dd>
                    <dt>Tambahan</dt><dd class="num text-start"><?= rupiah($row['tambahan_anggaran']) ?></dd>
                    <dt>Realisasi</dt><dd class="num text-start fw-bold"><?= rupiah($row['realisasi_anggaran']) ?></dd>
                    <dt>Sisa anggaran</dt><dd class="num text-start <?= selisih_kelas($sisa) ?> fw-600"><?= rupiah($sisa) ?></dd>
                    <dt>Serapan</dt><dd><?= $anggaran > 0 ? persen($serapan) : '—' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-head"><h3>Rincian biaya</h3><span class="meta ms-auto">Total <b class="text-body"><?= rupiah($total) ?></b></span></div>
            <div class="panel-body">
                <?php $ada = false; ?>
                <ul class="split-list">
                    <?php foreach ($komponen as $kolom => $label) : $v = (float) $row[$kolom]; if (abs($v) < 0.005) { continue; } $ada = true; ?>
                        <li><span><?= esc($label) ?></span><b class="num"><?= rupiah($v) ?></b>
                            <span class="track"><i style="width:<?= $total > 0 ? min(100, max(0, $v / $total * 100)) : 0 ?>%"></i></span></li>
                    <?php endforeach ?>
                </ul>
                <?php if (! $ada) : ?><div class="small-2">Belum ada rincian biaya.</div><?php endif ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-head"><h3>Riwayat perubahan</h3></div>
            <div class="panel-body">
                <?php if (! $riwayat) : ?><div class="small-2">Belum ada riwayat (data berasal dari import awal).</div><?php endif ?>
                <ul class="list-unstyled mb-0" style="font-size:.85rem">
                    <?php foreach ($riwayat as $h) : ?>
                        <li class="mb-2"><b><?= esc(ucfirst($h['aksi'])) ?></b> oleh <?= esc($h['username'] ?? 'sistem') ?>
                            <div class="cell-meta"><?= esc($h['created_at']) ?></div>
                            <?php if ($h['ringkasan']) : ?><div class="text-secondary"><?= esc(mb_strimwidth($h['ringkasan'], 0, 240, '…')) ?></div><?php endif ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
