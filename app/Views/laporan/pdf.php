<?php /** @var array $lap */ $m = $lap['meta']; $n = count($lap['datasets']); ?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title><?= esc($lap['judul']) ?></title>
<style>
    @page { margin: 15mm 11mm 17mm 11mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 7.6pt; color: #18212B; }
    .kop { border-bottom: 2pt solid #0B7A75; padding-bottom: 5pt; margin-bottom: 8pt; }
    .kop .org { font-size: 8pt; color: #4A5563; }
    .kop h1 { font-size: 14pt; margin: 2pt 0 2pt; }
    .kop .info { font-size: 7.5pt; color: #4A5563; }
    h2 { font-size: 10pt; margin: 0 0 5pt; color: #075E5A; }
    .dataset { page-break-after: always; }
    .dataset.last { page-break-after: auto; }
    table.pdf-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .pdf-table th { background: #18212B; color: #fff; font-size: 7pt; padding: 4pt 4pt; text-align: left; }
    .pdf-table th.num { text-align: right; }
    .pdf-table td { padding: 3pt 4pt; border-bottom: .5pt solid #D5DAE0; vertical-align: top; word-wrap: break-word; }
    .pdf-table td.num { text-align: right; }
    .pdf-table thead { display: table-header-group; }
    .pdf-table tr { page-break-inside: avoid; }
    .pdf-table tr.row-group { page-break-after: avoid; }
    .pdf-table tr.row-group td { background: #E9EDF0; font-weight: bold; }
    .pdf-table tr.row-subtotal td { background: #E1F0EF; font-weight: bold; }
    .pdf-table tr.row-total td { background: #F1E3B5; font-weight: bold; border-top: 1.2pt solid #18212B; }
    .empty-cell { text-align: center; color: #7A8592; padding: 14pt; }
</style></head>
<body>
<?php foreach ($lap['datasets'] as $i => $ds) : ?>
    <div class="dataset <?= $i === $n - 1 ? 'last' : '' ?>">
        <div class="kop">
            <div class="org"><?= esc($m['org']) ?> — <?= esc($m['unit']) ?></div>
            <h1><?= esc($ds['judul']) ?></h1>
            <div class="info"><?= esc($m['periode']) ?> · <?= esc($m['filter']) ?> · <?= esc($m['dicetak']) ?></div>
        </div>
        <?= view('laporan/_dataset', ['ds' => $ds, 'mode' => 'pdf', 'batas' => 0]) ?>
    </div>
<?php endforeach ?>
</body></html>
