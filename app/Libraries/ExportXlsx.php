<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Menulis dataset ReportBuilder ke file .xlsx.
 * Kolom turunan (sisa, serapan, total) dan baris total ditulis sebagai RUMUS Excel,
 * sehingga tetap hidup bila pengguna mengubah angka di dalam file.
 */
class ExportXlsx
{
    private const FORMAT = [
        'uang'    => '#,##0;[Red]-#,##0;"-"',
        'angka'   => '#,##0',
        'persen'  => '0.0%',
        'tanggal' => 'dd-mmm-yyyy',
    ];

    private const BARIS_HEADER = 6;

    /** @param array{judul:string,meta:array<string,string>,datasets:list<array<string,mixed>>} $laporan */
    public function buat(array $laporan): string
    {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator(config('Simonkeu')->appName)->setTitle($laporan['judul']);
        $book->removeSheetByIndex(0);

        $dipakai = [];
        foreach ($laporan['datasets'] as $ds) {
            $nama = $this->namaSheet($ds['sheet'], $dipakai);
            $dipakai[] = $nama;
            $ws = $book->createSheet();
            $ws->setTitle($nama);
            $this->tulis($ws, $ds, $laporan['meta']);
        }
        $book->setActiveSheetIndex(0);

        $tmp = tempnam(sys_get_temp_dir(), 'skx');
        $w   = new Xlsx($book);
        $w->save($tmp);
        $isi = (string) file_get_contents($tmp);
        @unlink($tmp);
        $book->disconnectWorksheets();

        return $isi;
    }

    private function namaSheet(string $nama, array $dipakai): string
    {
        $nama = mb_substr(trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $nama)), 0, 31);
        $asli = $nama;
        for ($i = 2; in_array($nama, $dipakai, true); $i++) {
            $nama = mb_substr($asli, 0, 28) . ' ' . $i;
        }

        return $nama;
    }

    private function tulis($ws, array $ds, array $meta): void
    {
        // Kolom yang tampil di Excel = semua kolom.
        $kolom = $ds['kolom'];
        $n     = count($kolom);
        $idx   = [];                       // key => nomor kolom (1-based)
        foreach ($kolom as $i => $k) {
            $idx[$k['key']] = $i + 1;
        }
        $huruf = static fn (int $c) => Coordinate::stringFromColumnIndex($c);
        $akhir = $huruf($n);

        // ---- Kop
        $ws->setCellValue('A1', $meta['org'] . ' — ' . $meta['unit']);
        $ws->setCellValue('A2', $ds['judul']);
        $ws->setCellValue('A3', $meta['periode'] . '  ·  ' . $meta['filter']);
        $ws->setCellValue('A4', $meta['dicetak']);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('18212B');
        $ws->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0B7A75');
        $ws->getStyle('A3:A4')->getFont()->setSize(9)->getColor()->setRGB('7A8592');

        // ---- Header
        $h = self::BARIS_HEADER;
        foreach ($kolom as $i => $k) {
            $ws->setCellValue([$i + 1, $h], $k['label']);
        }
        $ws->getStyle("A$h:$akhir$h")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '18212B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $ws->getRowDimension($h)->setRowHeight(32);

        // ---- Baris data
        $r        = $h + 1;
        $awal     = $r;
        $subAwal  = null;
        $subBaris = [];         // baris subtotal (untuk total besar)
        $normal   = [];         // baris normal
        $adaGrup  = false;

        $terapkanRumus = function (string $tpl, int $baris) use ($idx, $huruf): string {
            return '=' . preg_replace_callback('/\{(\w+)\}/', static fn ($m) => $huruf($idx[$m[1]]) . $baris, $tpl);
        };
        $jumlahable = static fn (array $k) => ! isset($k['rumus']) && in_array($k['tipe'], ['uang', 'angka'], true) && empty($k['nomor']);

        foreach ($ds['baris'] as $row) {
            $kind = $row['kind'] ?? 'normal';

            if ($kind === 'group') {
                $adaGrup = true;
                $ws->setCellValue([1, $r], $row[$kolom[0]['key']] ?? '');
                $ws->getStyle("A$r:$akhir$r")->applyFromArray([
                    'font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9EDF0']],
                ]);
                $subAwal = $r + 1;
                $r++;
                continue;
            }

            foreach ($kolom as $i => $k) {
                $c = $i + 1;
                if (isset($k['rumus'])) {
                    $ws->setCellValue([$c, $r], $terapkanRumus($k['rumus'], $r));
                } elseif ($kind === 'subtotal' && $jumlahable($k) && $subAwal !== null) {
                    $ws->setCellValue([$c, $r], '=SUM(' . $huruf($c) . $subAwal . ':' . $huruf($c) . ($r - 1) . ')');
                } else {
                    $this->isi($ws, $c, $r, $k, $row[$k['key']] ?? null, $r - $awal + 1);
                }
            }

            if ($kind === 'subtotal') {
                $subBaris[] = $r;
                $ws->getStyle("A$r:$akhir$r")->applyFromArray([
                    'font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E1F0EF']],
                    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CC9C5']]],
                ]);
            } else {
                $normal[] = $r;
            }
            $r++;
        }
        $akhirData = $r - 1;

        // ---- Total besar (rumus)
        if (! empty($ds['total']) && $ds['baris']) {
            $t = $r;
            $ws->setCellValue([1, $t], 'Total');
            foreach ($kolom as $i => $k) {
                $c = $i + 1;
                $L = $huruf($c);
                if (isset($k['rumus'])) {
                    $ws->setCellValue([$c, $t], $terapkanRumus($k['rumus'], $t));
                } elseif ($jumlahable($k)) {
                    if ($subBaris) {
                        $ws->setCellValue([$c, $t], '=SUM(' . implode(',', array_map(static fn ($b) => $L . $b, $subBaris)) . ')');
                    } else {
                        $ws->setCellValue([$c, $t], "=SUM($L$awal:$L$akhirData)");
                    }
                }
            }
            $ws->getStyle("A$t:$akhir$t")->applyFromArray([
                'font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1E3B5']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '18212B']]],
            ]);
            $akhirData = $t;
        }

        // ---- Format kolom
        foreach ($kolom as $i => $k) {
            $c = $i + 1;
            $L = $huruf($c);
            $ws->getColumnDimension($L)->setWidth(max(6, (float) ($k['lebar'] ?? 14)));
            if (isset(self::FORMAT[$k['tipe']])) {
                $ws->getStyle("$L" . ($h + 1) . ":$L$akhirData")->getNumberFormat()->setFormatCode(self::FORMAT[$k['tipe']]);
                $ws->getStyle("$L" . ($h + 1) . ":$L$akhirData")->getAlignment()->setHorizontal($k['tipe'] === 'tanggal' ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_RIGHT);
            }
            if ($k['tipe'] === 'teks' && ($k['lebar'] ?? 0) >= 30) {
                $ws->getStyle("$L" . ($h + 1) . ":$L$akhirData")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            }
        }
        $ws->getStyle("A" . ($h + 1) . ":$akhir$akhirData")->getFont()->setSize(10);
        $ws->getStyle("A$h:$akhir$akhirData")->getBorders()->getHorizontal()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('C9CFD6');

        // ---- Tampilan & cetak
        $ws->freezePane('A' . ($h + 1));
        if (! $adaGrup && $ds['baris']) {
            $ws->setAutoFilter("A$h:$akhir" . ($h + count($ds['baris'])));
        }
        $ws->setShowGridlines(false);
        $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0);
        $ws->getSheetView()->setZoomScale(100);
        $ws->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($h, $h);
        $ws->getPageMargins()->setLeft(0.4)->setRight(0.4)->setTop(0.5)->setBottom(0.6);
        $ws->getHeaderFooter()->setOddFooter('&L&8' . $meta['org'] . ' — ' . $meta['unit'] . '&R&8Halaman &P dari &N');
    }

    /** Tulis satu sel bernilai (bukan rumus) dengan tipe yang benar. */
    private function isi($ws, int $c, int $r, array $k, mixed $v, int $nomor): void
    {
        if (! empty($k['nomor'])) {
            $ws->setCellValue([$c, $r], $nomor);

            return;
        }
        if ($v === null || $v === '') {
            return;
        }
        switch ($k['tipe']) {
            case 'tanggal':
                // Pakai komponen tanggal (bukan timestamp) agar tidak bergeser oleh zona waktu server.
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                    $ws->setCellValue([$c, $r], XlsDate::formattedPHPToExcel((int) $m[1], (int) $m[2], (int) $m[3]));
                }
                break;
            case 'uang':
            case 'angka':
            case 'persen':
                $ws->setCellValue([$c, $r], (float) $v);
                break;
            default:
                // Simpan sebagai teks eksplisit agar kode seperti "64310009" tidak berubah bentuk.
                $ws->setCellValueExplicit([$c, $r], (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }
}
