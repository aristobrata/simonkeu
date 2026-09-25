<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Transaksi biaya<?= $this->endSection() ?>
<?= $this->section('heading') ?>Transaksi biaya<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Setiap baris mengikuti baris pada template laporan keuangan<?= $this->endSection() ?>
<?= $this->section('actions') ?>
<?php if (has_role('admin', 'operator')) : ?>
    <a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi/import') ?>"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel</a>
    <a class="btn btn-primary btn-sm" href="<?= site_url('transaksi/baru') ?>"><i class="bi bi-plus-lg me-1"></i>Tambah transaksi</a>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = static function (array $ganti = []): string {
    $q = array_merge($_GET, $ganti);
    unset($q['page']);
    return site_url('transaksi') . '?' . http_build_query(array_filter($q, static fn ($v) => $v !== '' && $v !== null));
};
$urutLink = static function (string $kunci, string $label, bool $kanan = false) use ($urut, $arah, $qs): string {
    $aktif = $urut === $kunci;
    $arahBaru = ($aktif && $arah === 'asc') ? 'desc' : 'asc';
    $ikon = $aktif ? '<i class="bi bi-caret-' . ($arah === 'asc' ? 'up' : 'down') . '-fill sort-ind"></i>' : '';
    return '<a href="' . esc($qs(['urut' => $kunci, 'arah' => $arahBaru])) . '">' . esc($label) . $ikon . '</a>';
};
$selisih = (float) $total['rencana'] + (float) $total['tambahan'] - (float) $total['realisasi'];
?>

<form class="panel mb-3" method="get" action="<?= site_url('transaksi') ?>">
    <div class="panel-body">
        <div class="filterbar">
            <div class="f grow"><label for="q">Cari</label><input class="form-control form-control-sm" id="q" name="q" value="<?= esc($f['q']) ?>" placeholder="Aktivitas, no. parking, tempat, keterangan"></div>
            <div class="f"><label for="tahun">Tahun</label>
                <select class="form-select form-select-sm" id="tahun" name="tahun"><option value="">Semua</option>
                    <?php foreach ($tahunList as $t) : ?><option value="<?= $t ?>" <?= $f['tahun'] === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach ?>
                </select></div>
            <div class="f"><label for="bulan">Bulan</label>
                <select class="form-select form-select-sm" id="bulan" name="bulan"><option value="">Semua</option>
                    <?php for ($i = 1; $i <= 12; $i++) : ?><option value="<?= $i ?>" <?= $f['bulan'] === $i ? 'selected' : '' ?>><?= bulan_id($i, false) ?></option><?php endfor ?>
                </select></div>
            <div class="f"><label for="jenis">Jenis aktivitas</label>
                <select class="form-select form-select-sm" id="jenis" name="jenis"><option value="">Semua</option>
                    <?php foreach ($lookup['jenis'] as $id => $nama) : ?><option value="<?= $id ?>" <?= $f['jenis'] === (int) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select></div>
            <div class="f"><label for="pelaksanaan">Inhouse / Public</label>
                <select class="form-select form-select-sm" id="pelaksanaan" name="pelaksanaan"><option value="">Semua</option><option value="0" <?= $f['pelaksanaan'] === '0' ? 'selected' : '' ?>>(Tidak diisi)</option>
                    <?php foreach ($lookup['pelaksanaan'] as $id => $nama) : ?><option value="<?= $id ?>" <?= $f['pelaksanaan'] === (string) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select></div>
            <div class="f"><label for="akun">No. akun</label>
                <select class="form-select form-select-sm" id="akun" name="akun"><option value="">Semua</option>
                    <?php foreach ($lookup['akun'] as $id => $nama) : ?><option value="<?= $id ?>" <?= $f['akun'] === (int) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select></div>
            <div class="f"><label for="status">Status bayar</label>
                <select class="form-select form-select-sm" id="status" name="status"><option value="">Semua</option><option value="-" <?= $f['status'] === '-' ? 'selected' : '' ?>>(Belum diisi)</option>
                    <?php foreach ($lookup['status'] as $s) : ?><option value="<?= esc($s) ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach ?>
                </select></div>
            <div class="f" style="min-width:auto"><label>&nbsp;</label>
                <div class="d-flex gap-2"><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
                <a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi') ?>">Atur ulang</a></div></div>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-head">
        <h2><?= angka($total['jumlah']) ?> transaksi</h2>
        <span class="meta">Rencana <b class="text-body"><?= rupiah($total['rencana']) ?></b> · Realisasi <b class="text-body"><?= rupiah($total['realisasi']) ?></b> ·
            Sisa <b class="<?= selisih_kelas($selisih) ?>"><?= rupiah($selisih) ?></b></span>
        <div class="ms-auto">
            <a class="btn btn-ghost btn-sm" href="<?= site_url('laporan/unduh/detail') . '?' . http_build_query(array_filter($f + ['format' => 'xlsx'], static fn ($v) => $v !== '' && $v !== null && $v !== 0)) ?>" title="Ekspor hasil filter ke Excel"><i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel</a>
        </div>
    </div>
    <div class="table-scroll">
        <table class="table table-ledger">
            <thead><tr>
                <th><?= $urutLink('tgl', 'Tanggal') ?></th>
                <th><?= $urutLink('aktivitas', 'Aktivitas') ?></th>
                <th><?= $urutLink('jenis', 'Jenis') ?></th>
                <th>Akun · CC</th>
                <th class="num"><?= $urutLink('rencana', 'Rencana') ?></th>
                <th class="num"><?= $urutLink('realisasi', 'Realisasi') ?></th>
                <th class="num"><?= $urutLink('total', 'Total biaya') ?></th>
                <th>Status</th>
                <th class="text-end"><span class="visually-hidden">Aksi</span></th>
            </tr></thead>
            <tbody>
            <?php if (! $rows) : ?>
                <tr><td colspan="9"><div class="empty"><i class="bi bi-inbox"></i>Tidak ada transaksi yang cocok dengan filter.<br><a href="<?= site_url('transaksi') ?>">Atur ulang filter</a></div></td></tr>
            <?php endif ?>
            <?php foreach ($rows as $r) :
                $beda = abs((float) $r['realisasi_anggaran'] - (float) $r['total_biaya']) > 0.5; ?>
                <tr>
                    <td class="text-nowrap"><?= tgl_id($r['tgl_mulai']) ?><?php if ($r['tgl_selesai']) : ?><div class="cell-meta">s.d. <?= tgl_id($r['tgl_selesai']) ?></div><?php endif ?></td>
                    <td style="min-width:280px;max-width:460px">
                        <a class="cell-title text-body" href="<?= site_url('transaksi/' . $r['id']) ?>"><?= esc($r['aktivitas']) ?></a>
                        <?php if ($r['no_parking']) : ?><div class="cell-meta">Parking <?= esc($r['no_parking']) ?></div><?php endif ?>
                    </td>
                    <td><span class="chip chip-teal"><?= esc($r['jenis']) ?></span><?php if ($r['pelaksanaan']) : ?> <span class="chip"><?= esc($r['pelaksanaan']) ?></span><?php endif ?></td>
                    <td class="text-nowrap"><?= esc($r['akun_kode']) ?><div class="cell-meta"><?= esc($r['cc_kode']) ?></div></td>
                    <td class="num"><?= angka($r['rencana_anggaran']) ?></td>
                    <td class="num"><?= angka($r['realisasi_anggaran']) ?></td>
                    <td class="num"><?= angka($r['total_biaya']) ?><?php if ($beda) : ?> <i class="bi bi-exclamation-triangle-fill text-gold" title="Realisasi berbeda dari total biaya"></i><?php endif ?></td>
                    <td><?= $r['status_pembayaran'] ? '<span class="chip ' . status_kelas($r['status_pembayaran']) . '">' . esc($r['status_pembayaran']) . '</span>' : '<span class="small-2">—</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi/' . $r['id']) ?>" title="Lihat"><i class="bi bi-eye"></i></a>
                        <?php if (has_role('admin', 'operator')) : ?>
                            <a class="btn btn-ghost btn-sm" href="<?= site_url("transaksi/{$r['id']}/ubah") ?>" title="Ubah"><i class="bi bi-pencil"></i></a>
                            <form class="d-inline" method="post" action="<?= site_url("transaksi/{$r['id']}/hapus") ?>"><?= csrf_field() ?>
                                <button class="btn btn-ghost btn-sm text-danger" type="button" data-confirm="Hapus transaksi &quot;<?= esc(mb_strimwidth($r['aktivitas'], 0, 70, '…'), 'attr') ?>&quot;?" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
            <?php if ($rows) : ?>
            <tfoot><tr>
                <td colspan="4">Total seluruh hasil filter</td>
                <td class="num"><?= angka($total['rencana']) ?></td>
                <td class="num"><?= angka($total['realisasi']) ?></td>
                <td class="num"><?= angka($total['total']) ?></td>
                <td colspan="2"></td>
            </tr></tfoot>
            <?php endif ?>
        </table>
    </div>
    <div class="panel-head border-top border-bottom-0 justify-content-between">
        <span class="meta">Halaman <?= $pager->getCurrentPage() ?> dari <?= max(1, $pager->getPageCount()) ?></span>
        <?= $pager->links('default', 'simonkeu') ?>
        <form method="get" class="d-flex align-items-center gap-2 m-0">
            <?php foreach ($_GET as $k => $v) : if (in_array($k, ['per_page', 'page'], true) || is_array($v)) { continue; } ?><input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>"><?php endforeach ?>
            <label class="small-2" for="pp">Baris per halaman</label>
            <select class="form-select form-select-sm" id="pp" name="per_page" onchange="this.form.submit()" style="width:auto">
                <?php foreach ([10, 25, 50, 100] as $n) : ?><option <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach ?>
            </select>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
