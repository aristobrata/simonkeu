<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Validasi data<?= $this->endSection() ?>
<?= $this->section('heading') ?>Validasi data<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Baris yang perlu dicek sebelum laporan diterbitkan<?= $this->endSection() ?>
<?= $this->section('actions') ?>
<form method="get" class="d-flex align-items-center gap-2 m-0">
    <label class="small-2" for="tahun">Tahun</label>
    <select class="form-select form-select-sm" id="tahun" name="tahun" onchange="this.form.submit()" style="width:auto">
        <option value="0">Semua</option>
        <?php foreach ($tahunList as $t) : ?><option <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option><?php endforeach ?>
    </select>
</form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $warna = ['danger' => 'chip-brick', 'warning' => 'chip-gold', 'info' => 'chip-slate']; $nama = ['danger' => 'Perlu diperbaiki', 'warning' => 'Perlu dicek', 'info' => 'Catatan']; ?>

<?php if (! $temuan) : ?>
    <div class="panel"><div class="empty"><i class="bi bi-check2-circle text-ok"></i>Tidak ada temuan. Semua aturan pemeriksaan terpenuhi.</div></div>
<?php else : ?>
    <div class="panel mb-3"><div class="panel-body">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($temuan as $kode => $t) : $a = $aturan[$kode]; ?>
                <a class="chip <?= $warna[$a['level']] ?> py-2 px-3" href="#<?= $kode ?>"><?= esc($a['judul']) ?> · <b><?= angka($t['jumlah']) ?></b></a>
            <?php endforeach ?>
        </div>
    </div></div>

    <?php foreach ($temuan as $kode => $t) : $a = $aturan[$kode]; ?>
    <section class="panel mb-3" id="<?= $kode ?>">
        <div class="panel-head">
            <h2><?= esc($a['judul']) ?></h2><span class="chip <?= $warna[$a['level']] ?>"><?= $nama[$a['level']] ?></span>
            <span class="meta ms-auto"><?= angka($t['jumlah']) ?> baris<?= $t['jumlah'] > count($t['baris']) ? ' (menampilkan ' . count($t['baris']) . ' pertama)' : '' ?></span>
        </div>
        <div class="panel-body pb-0 pt-2"><p class="small-2 mb-2"><?= esc($a['penjelasan']) ?></p></div>
        <div class="table-scroll" style="max-height:340px">
            <table class="table table-ledger">
                <thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Jenis</th><th class="num">Rencana</th><th class="num">Realisasi</th><th class="num">Total biaya</th><th class="text-end"><span class="visually-hidden">Aksi</span></th></tr></thead>
                <tbody>
                <?php foreach ($t['baris'] as $r) : ?>
                    <tr>
                        <td class="text-nowrap"><?= tgl_id($r['tgl_mulai']) ?><?= $r['tgl_selesai'] && $kode === 'tanggal_terbalik' ? '<div class="cell-meta text-over">selesai ' . tgl_id($r['tgl_selesai']) . '</div>' : '' ?></td>
                        <td style="max-width:420px"><a class="cell-title text-body" href="<?= site_url('transaksi/' . $r['id']) ?>"><?= esc($r['aktivitas']) ?></a></td>
                        <td><span class="chip chip-teal"><?= esc($r['jenis']) ?></span></td>
                        <td class="num"><?= angka($r['rencana_anggaran']) ?></td>
                        <td class="num"><?= angka($r['realisasi_anggaran']) ?></td>
                        <td class="num"><?= angka($r['total_biaya']) ?></td>
                        <td class="text-end"><?php if (has_role('admin', 'operator')) : ?><a class="btn btn-ghost btn-sm" href="<?= site_url("transaksi/{$r['id']}/ubah") ?>">Perbaiki</a><?php endif ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endforeach ?>
<?php endif ?>
<?= $this->endSection() ?>
