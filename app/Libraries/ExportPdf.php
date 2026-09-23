<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

/** Membuat PDF (A4 landscape) dari dataset ReportBuilder memakai Dompdf. */
class ExportPdf
{
    /** @param array{judul:string,meta:array<string,string>,datasets:list<array<string,mixed>>} $laporan */
    public function buat(array $laporan): string
    {
        $dir = WRITEPATH . 'dompdf';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $opt = new Options();
        $opt->set('defaultFont', 'DejaVu Sans');
        $opt->set('isRemoteEnabled', false);
        $opt->set('tempDir', $dir);
        $opt->set('logOutputFile', $dir . '/log.htm');

        $pdf = new Dompdf($opt);
        $pdf->setPaper('A4', 'landscape');
        $pdf->loadHtml(view('laporan/pdf', ['lap' => $laporan]));
        $pdf->render();

        // Nomor halaman
        $canvas = $pdf->getCanvas();
        $font   = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $w      = $canvas->get_width();
        $h      = $canvas->get_height();
        $canvas->page_text(32, $h - 26, $laporan['meta']['org'] . ' — ' . $laporan['meta']['unit'], $font, 7, [0.45, 0.45, 0.45]);
        $canvas->page_text($w - 110, $h - 26, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [0.45, 0.45, 0.45]);

        return $pdf->output();
    }
}
