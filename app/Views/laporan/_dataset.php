<?php
/**
 * Tabel satu dataset laporan.
 * @var array  $ds    dataset ReportBuilder
 * @var string $mode  'html' | 'pdf'
 * @var int    $batas maksimum baris ditampilkan (0 = semua)
 */
$mode  = $mode ?? 'html';
$batas = $batas ?? 0;
$kolom = array_values(array_filter($ds['kolom'], static fn ($k) => ($k['hanya'] ?? '') !== 'xlsx'));
$jumlahLebar = array_sum(array_map(static fn ($k) => (float) ($k['lebar'] ?? 12), $kolom)) ?: 1;

$fmt = static function (array $k, $v): string {
    if ($v === null || $v === '') {
        return '';
    }
    switch ($k['tipe']) {
        case 'uang':   return abs((float) $v) < 0.005 ? '–' : angka($v);
        case 'angka':  return angka($v);
        case 'persen': return persen((float) $v * 100);
        case 'tanggal': return esc(tgl_id((string) $v));
        default:       return esc((string) $v);
    }
};

$baris = $ds['baris'];
$terpotong = $batas > 0 && count($baris) > $batas;
if ($terpotong) {
    $baris = array_slice($baris, 0, $batas);
}
$cls = $mode === 'pdf' ? 'pdf-table' : 'table table-ledger';
?>
<table class="<?= $cls ?>">
    <colgroup><?php foreach ($kolom as $k) : ?><col style="width:<?= round(($k['lebar'] ?? 12) / $jumlahLebar * 100, 2) ?>%"><?php endforeach ?></colgroup>
    <thead><tr>
        <?php foreach ($kolom as $k) : ?><th class="<?= in_array($k['tipe'], ['uang', 'angka', 'persen'], true) ? 'num' : '' ?>"><?= esc($k['label']) ?></th><?php endforeach ?>
    </tr></thead>
    <tbody>
    <?php if (! $baris) : ?><tr><td colspan="<?= count($kolom) ?>" class="empty-cell">Tidak ada data untuk filter ini.</td></tr><?php endif ?>
    <?php foreach ($baris as $r) : $kind = $r['kind'] ?? 'normal'; ?>
        <?php if ($kind === 'group') : ?>
            <tr class="row-group"><td colspan="<?= count($kolom) ?>"><?= esc($r[$kolom[0]['key']] ?? '') ?></td></tr>
            <?php continue; ?>
        <?php endif ?>
        <tr class="<?= $kind === 'subtotal' ? 'row-subtotal' : '' ?>">
            <?php foreach ($kolom as $k) : ?><td class="<?= in_array($k['tipe'], ['uang', 'angka', 'persen'], true) ? 'num' : '' ?>"><?= $fmt($k, $r[$k['key']] ?? null) ?></td><?php endforeach ?>
        </tr>
    <?php endforeach ?>
    </tbody>
    <?php if ($ds['total_baris'] && ! $terpotong) : ?>
    <tfoot><tr class="row-total">
        <?php foreach ($kolom as $k) : ?><td class="<?= in_array($k['tipe'], ['uang', 'angka', 'persen'], true) ? 'num' : '' ?>"><?= isset($ds['total_baris'][$k['key']]) ? (is_string($ds['total_baris'][$k['key']]) ? esc($ds['total_baris'][$k['key']]) : $fmt($k, $ds['total_baris'][$k['key']])) : '' ?></td><?php endforeach ?>
    </tr></tfoot>
    <?php endif ?>
</table>
<?php if ($terpotong) : ?><p class="pdf-note"><?= $mode === 'pdf' ? '' : 'Pratinjau menampilkan ' . angka($batas) . ' baris pertama dari ' . angka(count($ds['baris'])) . '. Unduh Excel atau PDF untuk data lengkap.' ?></p><?php endif ?>
