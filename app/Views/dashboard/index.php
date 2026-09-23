<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('heading') ?>Dashboard anggaran<?= $this->endSection() ?>
<?= $this->section('subheading') ?><span id="subHeading">Memuat data…</span><?= $this->endSection() ?>
<?= $this->section('actions') ?>
<a class="btn btn-ghost btn-sm" id="lnkLaporan" href="<?= site_url('laporan') ?>"><i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan & ekspor</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<form class="panel mb-3" id="filterForm" onsubmit="return false">
    <div class="panel-body py-3">
        <div class="filterbar">
            <div class="f"><label for="fTahun">Tahun</label>
                <select class="form-select form-select-sm" id="fTahun" name="tahun">
                    <?php foreach ($tahunList as $t) : ?><option value="<?= $t ?>" <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option><?php endforeach ?>
                </select></div>
            <div class="f"><label for="fDari">Dari bulan</label>
                <select class="form-select form-select-sm" id="fDari" name="dari"><?php for ($i = 1; $i <= 12; $i++) : ?><option value="<?= $i ?>" <?= $i === $dari ? 'selected' : '' ?>><?= bulan_id($i, false) ?></option><?php endfor ?></select></div>
            <div class="f"><label for="fSampai">Sampai bulan</label>
                <select class="form-select form-select-sm" id="fSampai" name="sampai"><?php for ($i = 1; $i <= 12; $i++) : ?><option value="<?= $i ?>" <?= $i === $sampai ? 'selected' : '' ?>><?= bulan_id($i, false) ?></option><?php endfor ?></select></div>
            <div class="f"><label for="fJenis">Jenis aktivitas</label>
                <select class="form-select form-select-sm" id="fJenis" name="jenis"><option value="">Semua jenis</option>
                    <?php foreach ($jenisList as $id => $nama) : ?><option value="<?= $id ?>" <?= (int) $id === $jenisId ? 'selected' : '' ?>><?= esc($nama) ?></option><?php endforeach ?>
                </select></div>
            <div class="f" style="min-width:auto"><label>&nbsp;</label><button type="button" class="btn btn-ghost btn-sm" id="btnReset"><i class="bi bi-arrow-counterclockwise me-1"></i>Atur ulang</button></div>
        </div>
    </div>
</form>

<div class="panel" id="emptyState" hidden>
    <div class="empty"><i class="bi bi-bar-chart"></i>Belum ada data untuk periode ini.
        <?php if (has_role('admin', 'operator')) : ?><div class="mt-3"><a class="btn btn-primary btn-sm" href="<?= site_url('transaksi/import') ?>">Import dari Excel</a> <a class="btn btn-ghost btn-sm" href="<?= site_url('transaksi/baru') ?>">Tambah transaksi</a></div><?php endif ?>
    </div>
</div>

<div id="dash" class="d-grid gap-3" aria-live="polite">
    <div class="alert-strip" id="alertStrip" hidden><i class="bi bi-exclamation-octagon-fill"></i><span></span></div>

    <section class="ledger" aria-label="Ringkasan anggaran">
        <div>
            <div class="stat-label">Serapan anggaran <span class="chip chip-gold" data-k="periode"></span></div>
            <div class="serapan" id="serapanWrap"><span class="pct"><span data-k="serapan">–</span><small>%</small></span></div>
            <div class="budget-bar" id="budgetBar" role="img" aria-label="Bilah serapan anggaran"><i style="width:0%"></i></div>
            <div class="budget-legend">
                <span><i class="dot" style="background:var(--teal)"></i>Realisasi <b data-k="realisasi_full">–</b></span>
                <span><i class="dot" style="background:#F0D9A0"></i>Anggaran <b data-k="anggaran_full">–</b></span>
            </div>
        </div>
        <div><div class="stat-label">Realisasi</div><div class="stat-value" data-k="realisasi_c">–</div><div class="stat-sub" data-k="rata_sub"></div></div>
        <div><div class="stat-label">Sisa anggaran</div><div class="stat-value" data-k="sisa_c">–</div><div class="stat-sub" data-k="sisa_sub"></div></div>
        <div><div class="stat-label">Jumlah transaksi</div><div class="stat-value" data-k="jumlah">–</div><div class="stat-sub" data-k="jumlah_sub"></div></div>
        <div><div class="stat-label"><span data-k="bl_label">Bulan terakhir</span> <span class="delta" data-k="bl_delta" hidden></span></div><div class="stat-value" data-k="bl_c">–</div><div class="stat-sub" data-k="bl_sub"></div></div>
    </section>

    <section class="panel" id="anggaranTahunanPanel" hidden>
        <div class="panel-head"><h2>Anggaran tahunan</h2><span class="meta ms-auto">Pagu berkurang otomatis mengikuti realisasi</span>
            <a class="btn btn-ghost btn-sm ms-2" href="<?= site_url('anggaran') ?>">Kelola pagu</a></div>
        <div class="panel-body"><div id="anggaranTahunanList" class="row g-3"></div></div>
    </section>

    <div class="grid-12">
        <section class="panel span-8">
            <div class="panel-head"><h2>Rencana dan realisasi per bulan</h2><span class="meta ms-auto">Batang emas = anggaran, teal = realisasi</span></div>
            <div class="panel-body"><div class="chart-box tall"><canvas id="chBulanan" role="img" aria-label="Grafik batang rencana dan realisasi per bulan"></canvas></div></div>
        </section>
        <section class="panel span-4">
            <div class="panel-head"><h2>Akumulasi sepanjang tahun</h2></div>
            <div class="panel-body"><div class="chart-box tall"><canvas id="chKum" role="img" aria-label="Grafik garis akumulasi rencana dan realisasi"></canvas></div></div>
        </section>

        <section class="panel span-4">
            <div class="panel-head"><h2>Porsi per jenis aktivitas</h2></div>
            <div class="panel-body">
                <div class="chart-box" style="height:210px"><canvas id="chJenis" role="img" aria-label="Grafik donat porsi realisasi per jenis aktivitas"></canvas></div>
                <ul class="legend-list" id="legendJenis"></ul>
            </div>
        </section>
        <section class="panel span-8">
            <div class="panel-head"><h2>Realisasi bulanan per jenis aktivitas</h2><span class="meta ms-auto">Enam jenis terbesar, sisanya digabung</span></div>
            <div class="panel-body"><div class="chart-box tall"><canvas id="chJenisBulan" role="img" aria-label="Grafik batang bertumpuk realisasi bulanan per jenis"></canvas></div></div>
        </section>

        <section class="panel span-5">
            <div class="panel-head"><h2>Komponen biaya</h2><span class="meta ms-auto">Dari rincian biaya</span></div>
            <div class="panel-body"><div class="chart-box tall"><canvas id="chKomponen" role="img" aria-label="Grafik batang komponen biaya"></canvas></div></div>
        </section>
        <section class="panel span-7">
            <div class="panel-head"><h2>Sepuluh kegiatan dengan realisasi terbesar</h2></div>
            <div class="panel-body"><div class="chart-box tall"><canvas id="chTop" role="img" aria-label="Grafik batang sepuluh kegiatan terbesar"></canvas></div></div>
        </section>

        <section class="panel span-5">
            <div class="panel-head"><h2>Inhouse, Public, dan lainnya</h2></div>
            <div class="panel-body">
                <div class="chart-box short"><canvas id="chPel" role="img" aria-label="Grafik proporsi pelaksanaan"></canvas></div>
                <ul class="legend-list" id="legendPel"></ul>
            </div>
        </section>
        <section class="panel span-7">
            <div class="panel-head"><h2>Matriks realisasi: jenis aktivitas × bulan</h2><span class="meta ms-auto">Juta rupiah, hanya bulan yang berdata</span></div>
            <div class="panel-body flush"><div class="table-scroll" style="max-height:none"><table class="table heat mb-0" id="heat"></table></div></div>
        </section>

        <section class="panel span-6">
            <div class="panel-head"><h2>Realisasi per nomor akun</h2></div>
            <div class="panel-body"><ul class="bars" id="barsAkun"></ul></div>
        </section>
        <section class="panel span-6">
            <div class="panel-head"><h2>Realisasi per cost center</h2></div>
            <div class="panel-body"><ul class="bars" id="barsCc"></ul></div>
        </section>

        <section class="panel span-12">
            <div class="panel-head"><h2>Transaksi terbaru</h2><a class="ms-auto small" href="<?= site_url('transaksi') ?>">Lihat semua</a></div>
            <div class="table-scroll" style="max-height:none"><table class="table table-ledger" id="tblTerbaru"></table></div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>window.SIMONKEU = { dataUrl: <?= json_encode(site_url('dashboard/data')) ?>, baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>, loginUrl: <?= json_encode(site_url('login')) ?>, tahunDefault: <?= (int) $tahun ?> };</script>
<script src="<?= asset('vendor/chartjs/chart.umd.js') ?>"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>
<?= $this->endSection() ?>
