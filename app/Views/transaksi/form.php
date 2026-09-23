<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $mode === 'baru' ? 'Tambah transaksi' : 'Ubah transaksi' ?><?= $this->endSection() ?>
<?= $this->section('heading') ?><?= $mode === 'baru' ? 'Tambah transaksi' : 'Ubah transaksi' ?><?= $this->endSection() ?>
<?= $this->section('subheading') ?>Isi sesuai kolom pada template laporan keuangan<?= $this->endSection() ?>
<?= $this->section('actions') ?><a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar</a><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$nilai = static function (string $k, $bawaan = '') use ($row) {
    $v = old($k);
    return $v !== null ? $v : ($row[$k] ?? $bawaan);
};
$uang = static function (string $k) use ($nilai): string {
    $v = to_number($nilai($k));
    return abs($v) < 0.005 ? '' : angka($v);
};
$anggaran = static function (string $k) use ($nilai): string {
    $raw = $nilai($k);
    return ($raw === '' || $raw === null) ? '' : angka(to_number($raw));
};
$komponen = config('Simonkeu')->komponen;
?>
<form method="post" action="<?= $action ?>" class="panel" id="formTransaksi" novalidate>
    <?= csrf_field() ?>

    <section class="form-section">
        <h3>Kegiatan</h3>
        <p>Nama kegiatan dan klasifikasinya.</p>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="aktivitas">Aktivitas <span class="text-danger">*</span></label>
                <textarea class="form-control" id="aktivitas" name="aktivitas" rows="2" maxlength="500" required><?= esc($nilai('aktivitas')) ?></textarea>
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label" for="jenis_aktivitas_id">Jenis aktivitas <span class="text-danger">*</span></label>
                <select class="form-select" id="jenis_aktivitas_id" name="jenis_aktivitas_id" required><option value="">Pilih…</option>
                    <?php foreach ($lookup['jenis'] as $id => $nama) : ?><option value="<?= $id ?>" <?= (int) $nilai('jenis_aktivitas_id') === (int) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label" for="pelaksanaan_id">Inhouse / Public</label>
                <select class="form-select" id="pelaksanaan_id" name="pelaksanaan_id"><option value="">— tidak diisi —</option>
                    <?php foreach ($lookup['pelaksanaan'] as $id => $nama) : ?><option value="<?= $id ?>" <?= (int) $nilai('pelaksanaan_id') === (int) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label" for="akun_id">No. akun <span class="text-danger">*</span></label>
                <select class="form-select" id="akun_id" name="akun_id" required><option value="">Pilih…</option>
                    <?php foreach ($lookup['akun'] as $id => $nama) : ?><option value="<?= $id ?>" <?= (int) $nilai('akun_id') === (int) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label" for="cost_center_id">Cost center <span class="text-danger">*</span></label>
                <select class="form-select" id="cost_center_id" name="cost_center_id" required><option value="">Pilih…</option>
                    <?php foreach ($lookup['cc'] as $id => $nama) : ?><option value="<?= $id ?>" <?= (int) $nilai('cost_center_id') === (int) $id ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select>
            </div>
        </div>
    </section>

    <section class="form-section">
        <h3>Waktu dan tempat</h3>
        <p>Bulan dan tahun laporan terisi otomatis dari tanggal mulai; ubah bila perlu.</p>
        <div class="row g-3">
            <div class="col-md-6 col-xl-3"><label class="form-label" for="tgl_mulai">Tanggal mulai <span class="text-danger">*</span></label><input class="form-control" type="date" id="tgl_mulai" name="tgl_mulai" value="<?= esc($nilai('tgl_mulai')) ?>" required></div>
            <div class="col-md-6 col-xl-3"><label class="form-label" for="tgl_selesai">Tanggal selesai</label><input class="form-control" type="date" id="tgl_selesai" name="tgl_selesai" value="<?= esc($nilai('tgl_selesai')) ?>"></div>
            <div class="col-6 col-xl-3"><label class="form-label" for="periode_bulan">Bulan laporan</label>
                <select class="form-select" id="periode_bulan" name="periode_bulan">
                    <?php for ($i = 1; $i <= 12; $i++) : ?><option value="<?= $i ?>" <?= (int) $nilai('periode_bulan') === $i ? 'selected' : '' ?>><?= bulan_id($i, false) ?></option><?php endfor ?>
                </select></div>
            <div class="col-6 col-xl-3"><label class="form-label" for="periode_tahun">Tahun laporan</label><input class="form-control" type="number" min="2000" max="2100" id="periode_tahun" name="periode_tahun" value="<?= esc($nilai('periode_tahun')) ?>"></div>
            <div class="col-md-8"><label class="form-label" for="tempat">Tempat</label><input class="form-control" id="tempat" name="tempat" maxlength="150" value="<?= esc($nilai('tempat')) ?>"></div>
            <div class="col-md-4"><label class="form-label" for="jml_peserta">Jumlah peserta</label><input class="form-control" type="number" min="0" id="jml_peserta" name="jml_peserta" value="<?= esc($nilai('jml_peserta')) ?>"></div>
        </div>
    </section>

    <section class="form-section">
        <h3>Rincian biaya</h3>
        <p>Total biaya dihitung otomatis dari kolom di bawah, sama seperti rumus <code>=SUM</code> pada template.</p>
        <div id="petunjukAturan" class="hint-box mb-3" hidden></div>
        <div class="row g-3">
            <?php foreach ($komponen as $kolom => $label) : ?>
                <div class="col-sm-6 col-xl-4">
                    <label class="form-label" for="<?= $kolom ?>"><?= esc($label) ?></label>
                    <div class="input-group"><span class="input-group-text">Rp</span>
                        <input class="form-control money-input js-komponen" inputmode="numeric" data-money id="<?= $kolom ?>" name="<?= $kolom ?>" value="<?= esc($uang($kolom)) ?>" placeholder="0"></div>
                </div>
            <?php endforeach ?>
            <div class="col-12"><div class="total-box"><span class="fw-600">Total biaya</span><b id="totalBiaya">Rp 0</b></div></div>
        </div>
    </section>

    <section class="form-section">
        <h3>Anggaran</h3>
        <p>Kosongkan agar otomatis: realisasi = total biaya, rencana = realisasi (perilaku template). Isi bila berbeda.</p>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="rencana_anggaran">Rencana anggaran</label>
                <div class="input-group"><span class="input-group-text">Rp</span><input class="form-control money-input" inputmode="numeric" data-money id="rencana_anggaran" name="rencana_anggaran" value="<?= esc($anggaran('rencana_anggaran')) ?>" placeholder="otomatis"></div></div>
            <div class="col-md-4"><label class="form-label" for="realisasi_anggaran">Realisasi anggaran</label>
                <div class="input-group"><span class="input-group-text">Rp</span><input class="form-control money-input" inputmode="numeric" data-money id="realisasi_anggaran" name="realisasi_anggaran" value="<?= esc($anggaran('realisasi_anggaran')) ?>" placeholder="otomatis"></div></div>
            <div class="col-md-4"><label class="form-label" for="tambahan_anggaran">Tambahan anggaran</label>
                <div class="input-group"><span class="input-group-text">Rp</span><input class="form-control money-input" inputmode="numeric" data-money id="tambahan_anggaran" name="tambahan_anggaran" value="<?= esc($uang('tambahan_anggaran')) ?>" placeholder="0"></div></div>
        </div>
    </section>

    <section class="form-section">
        <h3>Pembayaran dan catatan</h3>
        <p>&nbsp;</p>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="status_pembayaran">Status pembayaran</label>
                <select class="form-select" id="status_pembayaran" name="status_pembayaran"><option value="">— belum diisi —</option>
                    <?php foreach ($lookup['status'] as $s) : ?><option <?= $nilai('status_pembayaran') === $s ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach ?>
                </select></div>
            <div class="col-md-4"><label class="form-label" for="no_parking">No. parking</label><input class="form-control" id="no_parking" name="no_parking" maxlength="30" value="<?= esc($nilai('no_parking')) ?>"></div>
            <div class="col-md-4"><label class="form-label" for="tgl_pembayaran_terakhir">Tgl pembayaran terakhir</label><input class="form-control" type="date" id="tgl_pembayaran_terakhir" name="tgl_pembayaran_terakhir" value="<?= esc($nilai('tgl_pembayaran_terakhir')) ?>"></div>
            <div class="col-12"><label class="form-label" for="keterangan">Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan" rows="2" maxlength="2000"><?= esc($nilai('keterangan')) ?></textarea></div>
        </div>
    </section>

    <div class="form-actions">
        <a class="btn btn-ghost" href="<?= $mode === 'ubah' ? site_url('transaksi/' . $row['id']) : site_url('transaksi') ?>">Batal</a>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i><?= $mode === 'baru' ? 'Simpan transaksi' : 'Simpan perubahan' ?></button>
    </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= asset('js/transaksi-form.js') ?>"></script>
<?= $this->endSection() ?>
