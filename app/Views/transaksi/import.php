<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Import Excel<?= $this->endSection() ?>
<?= $this->section('heading') ?>Import dari Excel<?= $this->endSection() ?>
<?= $this->section('subheading') ?>Unggah template laporan keuangan; Anda dapat memeriksa hasilnya sebelum disimpan<?= $this->endSection() ?>
<?= $this->section('actions') ?><a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-lg-6">
        <form class="panel" method="post" action="<?= site_url('transaksi/import/unggah') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="panel-head"><h2>1. Pilih file</h2></div>
            <div class="panel-body">
                <div class="dropzone mb-3">
                    <i class="bi bi-file-earmark-excel"></i>
                    <div class="fw-600 mt-2">File Excel template laporan keuangan</div>
                    <div class="small-2 mb-3">Format .xlsx, maksimal 10 MB</div>
                    <input class="form-control" type="file" name="berkas" accept=".xlsx,.xlsm" required>
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Baca dan tampilkan pratinjau</button>
            </div>
        </form>
    </div>
    <div class="col-lg-6">
        <div class="panel h-100">
            <div class="panel-head"><h2>Yang perlu diketahui</h2></div>
            <div class="panel-body" style="font-size:.9rem">
                <ul class="ps-3 mb-3">
                    <li class="mb-2">Sistem mencari baris header <b>AKTIVITAS</b> di sheet mana pun, lalu membaca kolom berdasarkan <b>nama kolom</b>, bukan posisinya.</li>
                    <li class="mb-2">Nilai yang tidak konsisten dirapikan otomatis (mis. <i>training</i> → Training, <i>Publik</i> → Public, <i>Supplay Kantor</i> → Supply Kantor). Semua perubahan ditampilkan sebagai peringatan.</li>
                    <li class="mb-2">Rumus pada file dihitung ulang. <b>Total Biaya</b> mengikuti jumlah rincian; bila Total Biaya di file diketik manual dan lebih besar, selisihnya dicatat sebagai <i>Biaya Lainnya</i>.</li>
                    <li>Anda dapat memilih <b>menambahkan</b> sebagai data baru, atau <b>mengganti</b> data pada bulan yang sama (cocok bila file diperbarui berkala).</li>
                </ul>
                <div class="fw-600 mb-1">Kolom yang dikenali</div>
                <ol class="kolom-list">
                    <?php foreach (['AKTIVITAS*', 'Jenis Aktivitas*', 'Inhouse/Public', 'No. Akun*', 'Costcenter*', 'BULAN', 'Tgl Mulai*', 'Tgl Selesai', 'Tempat', 'Jml Peserta', 'Rencana Anggaran', 'Realisasi Anggaran', 'Biaya Training/Instruktur', 'Biaya Materi', 'Konsumsi', 'Perlengkapan', 'Tiket pesawat', 'Hotel', 'Transportasi', 'Uang saku/SPJ', 'Biaya Lainnya', 'Total Biaya', 'Status pembayaran', 'No. Parking', 'Tambahan Anggaran', 'Tgl pembayaran terakhir', 'Keterangan'] as $k) : ?><li><?= esc($k) ?></li><?php endforeach ?>
                </ol>
                <div class="small-2 mt-2">* wajib ada</div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
