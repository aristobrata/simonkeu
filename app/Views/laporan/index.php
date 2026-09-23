<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Laporan & ekspor<?= $this->endSection() ?>
<?= $this->section('heading') ?>Laporan & ekspor<?= $this->endSection() ?>
<?= $this->section('subheading') ?><?= esc($lap['meta']['periode']) ?> · <?= esc($lap['meta']['filter']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qDasar = array_diff_key($query, ['laporan' => 1]);
$unduh  = static fn (string $fmt): string => site_url('laporan/unduh/' . $kode) . '?' . http_build_query($qDasar + ['format' => $fmt]);
?>
<form class="panel mb-3" method="get" action="<?= site_url('laporan') ?>">
    <div class="panel-body">
        <div class="filterbar">
            <div class="f grow"><label for="laporan">Jenis laporan</label>
                <select class="form-select form-select-sm" id="laporan" name="laporan">
                    <?php foreach (\App\Libraries\ReportBuilder::LAPORAN as $k => $def) : ?><option value="<?= $k ?>" <?= $k === $kode ? 'selected' : '' ?>><?= esc($def['judul']) ?></option><?php endforeach ?>
                </select></div>
            <div class="f"><label for="tahun">Tahun</label>
                <select class="form-select form-select-sm" id="tahun" name="tahun"><?php foreach ($tahunList as $t) : ?><option <?= (int) $f['tahun'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach ?></select></div>
            <div class="f"><label for="dari">Dari bulan</label>
                <select class="form-select form-select-sm" id="dari" name="dari"><?php for ($i = 1; $i <= 12; $i++) : ?><option value="<?= $i ?>" <?= $f['bulan_dari'] === $i ? 'selected' : '' ?>><?= bulan_id($i, false) ?></option><?php endfor ?></select></div>
            <div class="f"><label for="sampai">Sampai bulan</label>
                <select class="form-select form-select-sm" id="sampai" name="sampai"><?php for ($i = 1; $i <= 12; $i++) : ?><option value="<?= $i ?>" <?= $f['bulan_sampai'] === $i ? 'selected' : '' ?>><?= bulan_id($i, false) ?></option><?php endfor ?></select></div>
            <div class="f"><label for="jenis">Jenis aktivitas</label>
                <select class="form-select form-select-sm" id="jenis" name="jenis"><option value="">Semua jenis</option>
                    <?php foreach ($jenisList as $id => $nm) : ?><option value="<?= $id ?>" <?= $f['jenis'] === (int) $id ? 'selected' : '' ?>><?= esc($nm) ?></option><?php endforeach ?></select></div>
            <div class="f" style="min-width:auto"><label>&nbsp;</label><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-eye me-1"></i>Tampilkan</button></div>
        </div>
        <p class="small-2 mt-3 mb-0"><?= esc(\App\Libraries\ReportBuilder::LAPORAN[$kode]['deskripsi']) ?></p>
    </div>
</form>

<div class="panel mb-3">
    <div class="panel-body d-flex flex-wrap gap-2 align-items-center">
        <span class="fw-600 me-2">Unduh laporan ini</span>
        <a class="btn btn-primary btn-sm" href="<?= $unduh('xlsx') ?>"><i class="bi bi-file-earmark-excel me-1"></i>Excel (.xlsx)</a>
        <a class="btn btn-ghost btn-sm" href="<?= $unduh('pdf') ?>"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a class="btn btn-ghost btn-sm" href="<?= $unduh('csv') ?>" title="Berisi tabel utama (untuk laporan lengkap: daftar transaksi)"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <span class="small-2 ms-auto">Excel memuat rumus hidup untuk total, sisa, dan serapan.</span>
    </div>
</div>

<?php foreach ($lap['datasets'] as $ds) : ?>
    <section class="panel mb-3">
        <div class="panel-head"><h2><?= esc($ds['judul']) ?></h2><span class="meta ms-auto"><?= angka(count($ds['baris'])) ?> baris</span></div>
        <div class="table-scroll" style="max-height:520px">
            <?= view('laporan/_dataset', ['ds' => $ds, 'mode' => 'html', 'batas' => 300]) ?>
        </div>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
