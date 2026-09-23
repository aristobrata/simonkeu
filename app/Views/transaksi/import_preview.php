<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Pratinjau import<?= $this->endSection() ?>
<?= $this->section('heading') ?>Pratinjau import<?= $this->endSection() ?>
<?= $this->section('subheading') ?><?= esc($nama) ?> · sheet "<?= esc($hasil['sheet']) ?>"<?= $this->endSection() ?>
<?= $this->section('actions') ?><a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi/import') ?>"><i class="bi bi-arrow-left me-1"></i>Pilih file lain</a><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $r = $hasil['ringkas']; $totalAda = array_sum($ada); ?>
<div class="ledger mb-3" style="grid-template-columns:repeat(4,1fr)">
    <div style="background:#fff"><div class="stat-label">Baris terbaca</div><div class="stat-value"><?= angka($r['jumlah']) ?></div><div class="stat-sub"><?= $hasil['dilewati'] ? angka($hasil['dilewati']) . ' baris dilewati' : 'Tidak ada yang dilewati' ?></div></div>
    <div><div class="stat-label">Rencana anggaran</div><div class="stat-value"><?= rupiah($r['rencana']) ?></div></div>
    <div><div class="stat-label">Realisasi anggaran</div><div class="stat-value"><?= rupiah($r['realisasi']) ?></div></div>
    <div><div class="stat-label">Total rincian biaya</div><div class="stat-value"><?= rupiah($r['total']) ?></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-head"><h2>Periode dalam file</h2></div>
            <div class="table-scroll" style="max-height:300px"><table class="table table-ledger">
                <thead><tr><th>Periode</th><th class="num">Baris di file</th><th class="num">Sudah ada di aplikasi</th></tr></thead>
                <tbody><?php foreach ($r['periode'] as $p => $n) : [$y, $m] = explode('-', $p); ?>
                    <tr><td><?= bulan_id((int) $m, false) . ' ' . $y ?></td><td class="num"><?= angka($n) ?></td><td class="num"><?= $ada[$p] ? angka($ada[$p]) : '—' ?></td></tr>
                <?php endforeach ?></tbody></table></div>
        </div>
    </div>
    <div class="col-lg-7">
        <form class="panel h-100" method="post" action="<?= site_url('transaksi/import/konfirmasi') ?>">
            <?= csrf_field() ?><input type="hidden" name="token" value="<?= esc($token) ?>">
            <div class="panel-head"><h2>2. Pilih cara menyimpan</h2></div>
            <div class="panel-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="mode" id="mTambah" value="tambah" <?= $totalAda ? '' : 'checked' ?>>
                    <label class="form-check-label" for="mTambah"><b>Tambahkan</b> sebagai data baru<div class="small-2">Data yang sudah ada tidak diubah. <?= $totalAda ? '<span class="text-over">Hati-hati: bulan yang sama dapat terisi ganda.</span>' : '' ?></div></label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="radio" name="mode" id="mGanti" value="ganti" <?= $totalAda ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mGanti"><b>Ganti</b> data pada periode yang sama<div class="small-2"><?= $totalAda ? angka($totalAda) . ' baris lama di bulan-bulan tersebut dihapus (masih tercatat di log), lalu diganti isi file.' : 'Belum ada data pada periode ini, jadi hasilnya sama dengan menambahkan.' ?></div></label>
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>Simpan <?= angka($r['jumlah']) ?> baris</button>
                <a class="btn btn-ghost" href="<?= site_url('transaksi/import') ?>">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php if ($hasil['peringatan']) : ?>
<div class="panel mb-3">
    <div class="panel-head"><h2>Catatan dari pembacaan file</h2><span class="chip chip-gold ms-1"><?= angka(count($hasil['peringatan'])) ?></span></div>
    <div class="panel-body" style="max-height:240px;overflow:auto"><ul class="mb-0 ps-3" style="font-size:.86rem"><?php foreach ($hasil['peringatan'] as $w) : ?><li><?= esc($w) ?></li><?php endforeach ?></ul></div>
</div>
<?php endif ?>

<div class="panel">
    <div class="panel-head"><h2>Contoh baris</h2><span class="meta ms-auto">15 baris pertama</span></div>
    <div class="table-scroll" style="max-height:420px"><table class="table table-ledger">
        <thead><tr><th>Baris</th><th>Tanggal</th><th>Aktivitas</th><th>Jenis</th><th>Inhouse/Public</th><th>Akun</th><th class="num">Rencana</th><th class="num">Realisasi</th><th class="num">Total biaya</th></tr></thead>
        <tbody><?php foreach (array_slice($hasil['baris'], 0, 15) as $b) : ?>
            <tr><td class="small-2"><?= $b['_baris'] ?></td><td class="text-nowrap"><?= tgl_id($b['tgl_mulai']) ?></td><td style="max-width:380px"><?= esc($b['aktivitas']) ?></td>
                <td><span class="chip chip-teal"><?= esc($b['jenis']) ?></span></td><td><?= esc($b['pelaksanaan'] ?? '—') ?></td><td><?= esc($b['akun']) ?></td>
                <td class="num"><?= angka($b['rencana_anggaran']) ?></td><td class="num"><?= angka($b['realisasi_anggaran']) ?></td><td class="num"><?= angka($b['total_biaya']) ?></td></tr>
        <?php endforeach ?></tbody></table></div>
</div>
<?= $this->endSection() ?>
