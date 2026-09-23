<?php

namespace App\Libraries;

use App\Models\TransaksiModel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Membaca template "Laporan Keuangan" (Excel) dan menyimpannya ke tabel transaksi.
 *
 * - Kolom dikenali dari teks header (bukan posisi), jadi urutan kolom boleh berbeda.
 * - Nilai kategori yang tidak konsisten (training/Training, Publik/Public, dst.) dinormalisasi.
 * - Rumus pada file dihitung ulang; total_biaya selalu = jumlah kolom rincian biaya.
 */
class ExcelImporter
{
    /** kunci internal => daftar alias header (sudah dinormalisasi: huruf kecil, tanpa simbol/spasi). */
    private const HEADER = [
        'aktivitas'          => ['aktivitas', 'kegiatan', 'namaaktivitas'],
        'jenis'              => ['jenisaktivitas', 'jenis'],
        'pelaksanaan'        => ['inhousepubllic', 'inhousepublic', 'inhousepublik', 'pelaksanaan'],
        'akun'               => ['noakun', 'akun', 'kodeakun'],
        'cc'                 => ['costcenter', 'cc'],
        'bulan'              => ['bulan'],
        'tgl_mulai'          => ['tglmulai', 'tanggalmulai'],
        'tgl_selesai'        => ['tglselesai', 'tanggalselesai'],
        'tempat'             => ['tempat', 'lokasi'],
        'peserta'            => ['jmlpeserta', 'jumlahpeserta', 'peserta'],
        'rencana'            => ['rencanaanggaran', 'rencana'],
        'realisasi'          => ['realisasianggaran', 'realisasi'],
        'biaya_training'     => ['biayatraininginstruktur', 'biayatraining'],
        'biaya_materi'       => ['biayamateri'],
        'biaya_konsumsi'     => ['konsumsi'],
        'biaya_perlengkapan' => ['perlengkapan'],
        'biaya_tiket'        => ['tiketpesawat', 'tiket'],
        'biaya_hotel'        => ['hotel'],
        'biaya_transportasi' => ['transportasi'],
        'biaya_uang_saku'    => ['uangsakuspj', 'uangsaku'],
        'biaya_lainnya'      => ['biayalainnya', 'lainnya'],
        'total'              => ['totalbiaya'],
        'status'             => ['statuspembayaran'],
        'parking'            => ['noparking', 'parking'],
        'tambahan'           => ['tambahananggaran', 'tambahan'],
        'tgl_bayar'          => ['tglpembayaranterakhir'],
        'keterangan'         => ['keterangan'],
    ];

    private const WAJIB = ['aktivitas' => 'AKTIVITAS', 'jenis' => 'Jenis Aktivitas', 'akun' => 'No. Akun', 'cc' => 'Costcenter', 'tgl_mulai' => 'Tgl Mulai'];

    private const ALIAS_JENIS = [
        'training' => 'Training', 'km' => 'KM', 'pkl' => 'PKL',
        'supply kantor' => 'Supply Kantor', 'supplay kantor' => 'Supply Kantor', 'supplies kantor' => 'Supply Kantor',
        'pemeliharaan & prasarana' => 'Pemeliharaan & Prasarana', 'pemeliharaan dan prasarana' => 'Pemeliharaan & Prasarana',
        'beasiswa s2' => 'Beasiswa S2', 'beasiswa s1' => 'Beasiswa S1',
        'biaya konsultan' => 'Biaya Konsultan', 'biaya rapat' => 'Biaya Rapat', 'jamuan tamu' => 'Jamuan Tamu',
    ];

    private const ALIAS_PELAKSANAAN = [
        'in house' => 'In House', 'inhouse' => 'In House', 'in-house' => 'In House',
        'public' => 'Public', 'publik' => 'Public',
        'lat' => 'LAT', 'online' => 'Online', 'redeem poin' => 'Redeem Poin', 'spie reward' => 'SPIE Reward',
    ];

    /** Kesalahan ketik No. Akun yang sudah diketahui. */
    private const KOREKSI_AKUN = ['6431009' => '64310009'];

    private const BULAN = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'MEI' => 5, 'JUN' => 6, 'JUL' => 7,
        'AUG' => 8, 'AGU' => 8, 'AGT' => 8, 'SEP' => 9, 'OCT' => 10, 'OKT' => 10, 'NOV' => 11, 'DEC' => 12, 'DES' => 12,
    ];

    /** Maksimum peringatan yang disimpan agar tampilan tetap ringkas. */
    private const MAKS_PERINGATAN = 300;

    // ------------------------------------------------------------------ PARSE

    /**
     * Membaca file dan mengembalikan baris yang sudah dinormalisasi (belum disimpan).
     *
     * @return array{sheet:string,baris:list<array<string,mixed>>,peringatan:list<string>,dilewati:int,ringkas:array<string,mixed>}
     */
    public function parse(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadFilter(new class () implements IReadFilter {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return Coordinate::columnIndexFromString($columnAddress) <= 40;
            }
        });
        $book = $reader->load($path);

        // Cari sheet yang memiliki header "AKTIVITAS".
        $sheet = null;
        $hdrRow = 0;
        $peta   = [];
        foreach ($book->getWorksheetIterator() as $ws) {
            [$hdrRow, $peta] = $this->cariHeader($ws);
            if ($hdrRow > 0) {
                $sheet = $ws;
                break;
            }
        }
        if ($sheet === null) {
            throw new \RuntimeException('Header "AKTIVITAS" tidak ditemukan pada sheet mana pun. Pastikan file mengikuti template laporan keuangan.');
        }

        $hilang = array_diff_key(self::WAJIB, $peta);
        if ($hilang) {
            throw new \RuntimeException('Kolom wajib tidak ditemukan: ' . implode(', ', $hilang) . '.');
        }

        $awal   = min($peta);
        $akhir  = max($peta);
        $rentang = Coordinate::stringFromColumnIndex($awal + 1) . '%d:' . Coordinate::stringFromColumnIndex($akhir + 1) . '%d';
        $terakhir = $sheet->getHighestDataRow(Coordinate::stringFromColumnIndex($peta['aktivitas'] + 1));

        $cfg       = config('Simonkeu');
        $baris     = [];
        $peringatan = [];
        $dilewati  = 0;

        for ($r = $hdrRow + 1; $r <= $terakhir; $r++) {
            $nilai = $sheet->rangeToArray(sprintf($rentang, $r, $r), null, true, false, false)[0] ?? [];
            $ambil = static fn (string $k) => isset($peta[$k]) ? ($nilai[$peta[$k] - $awal] ?? null) : null;

            $aktivitas = preg_replace('/\s+/u', ' ', trim((string) $ambil('aktivitas'))) ?? '';
            if ($aktivitas === '' || in_array(mb_strtolower($aktivitas), ['total', 'jumlah', 'grand total'], true)) {
                continue;
            }

            $w = static function (string $pesan) use (&$peringatan, $r): void {
                if (count($peringatan) < self::MAKS_PERINGATAN) {
                    $peringatan[] = "Baris $r: $pesan";
                }
            };

            $tglMulai = $this->tanggal($ambil('tgl_mulai'));
            if ($tglMulai === null) {
                $w('dilewati — Tgl Mulai kosong atau tidak terbaca.');
                $dilewati++;
                continue;
            }

            $jenisMentah = trim((string) $ambil('jenis'));
            if ($jenisMentah === '') {
                $w('dilewati — Jenis Aktivitas kosong.');
                $dilewati++;
                continue;
            }
            $jenis = self::ALIAS_JENIS[$this->kunci($jenisMentah)] ?? $jenisMentah;
            if ($jenis !== $jenisMentah && $this->kunci($jenis) !== $this->kunci($jenisMentah)) {
                $w("Jenis Aktivitas \"$jenisMentah\" dinormalkan menjadi \"$jenis\".");
            }

            $pelMentah   = trim((string) $ambil('pelaksanaan'));
            $pelaksanaan = null;
            if ($pelMentah !== '') {
                $kp = $this->kunci($pelMentah);
                if (array_key_exists($kp, self::ALIAS_PELAKSANAAN)) {
                    $pelaksanaan = self::ALIAS_PELAKSANAAN[$kp];
                } elseif ($kp === 'konsumsi') {
                    $w('"Konsumsi" pada kolom Inhouse/Public bukan jenis pelaksanaan — dikosongkan.');
                } else {
                    $pelaksanaan = $pelMentah;
                }
            }

            $akun = $this->kode($ambil('akun'));
            if (isset(self::KOREKSI_AKUN[$akun])) {
                $w("No. Akun $akun diperbaiki menjadi " . self::KOREKSI_AKUN[$akun] . '.');
                $akun = self::KOREKSI_AKUN[$akun];
            }
            $cc = $this->kode($ambil('cc'));
            if ($akun === '' || $cc === '') {
                $w('dilewati — No. Akun atau Costcenter kosong.');
                $dilewati++;
                continue;
            }

            // Periode (bulan & tahun)
            $tm    = (int) date('n', strtotime($tglMulai));
            $ty    = (int) date('Y', strtotime($tglMulai));
            $bulan = $this->bulan($ambil('bulan')) ?? $tm;
            if ($bulan === 1 && $tm === 12) {
                $ty++;
            } elseif ($bulan === 12 && $tm === 1) {
                $ty--;
            }

            $row = [
                '_baris'      => $r,
                'aktivitas'   => $aktivitas,
                'jenis'       => $jenis,
                'pelaksanaan' => $pelaksanaan,
                'akun'        => $akun,
                'cc'          => $cc,
                'periode_bulan' => $bulan,
                'periode_tahun' => $ty,
                'tgl_mulai'   => $tglMulai,
                'tgl_selesai' => $this->tanggal($ambil('tgl_selesai')),
                'tempat'      => $this->teks($ambil('tempat')),
                'jml_peserta' => is_numeric($ambil('peserta')) ? (int) $ambil('peserta') : null,
                'rencana_anggaran'   => to_number($ambil('rencana')),
                'realisasi_anggaran' => to_number($ambil('realisasi')),
                'tambahan_anggaran'  => to_number($ambil('tambahan')),
                'status_pembayaran'  => null,
                'no_parking'  => $this->kode($ambil('parking')) ?: null,
                'tgl_pembayaran_terakhir' => $this->tanggal($ambil('tgl_bayar')),
                'keterangan'  => $this->teks($ambil('keterangan')),
            ];

            $total = 0.0;
            foreach (array_keys($cfg->komponen) as $k) {
                $row[$k] = to_number($ambil($k));
                $total  += $row[$k];
            }

            // Total Biaya pada file kadang diketik manual (bukan =SUM) dan lebih besar dari rincian.
            // Total di sumber harus tetap cocok, jadi selisih dicatat sebagai "Biaya Lainnya".
            if (isset($peta['total']) && $ambil('total') !== null && $ambil('total') !== '') {
                $totalFile = to_number($ambil('total'));
                $selisih   = $totalFile - $total;
                if (abs($selisih) >= 0.5) {
                    $row['biaya_lainnya'] += $selisih;
                    $total                = $totalFile;
                    $w('Total Biaya ' . angka($totalFile) . ' berbeda dari jumlah rincian; selisih ' . angka($selisih) . ' dimasukkan ke Biaya Lainnya.');
                }
            }
            $row['total_biaya'] = $total;

            $st = trim((string) $ambil('status'));
            if ($st !== '') {
                foreach ($cfg->statusPembayaran as $opsi) {
                    if (mb_strtolower($opsi) === mb_strtolower($st)) {
                        $row['status_pembayaran'] = $opsi;
                    }
                }
                if ($row['status_pembayaran'] === null) {
                    $w("Status pembayaran \"$st\" tidak dikenal — dikosongkan.");
                }
            }

            $baris[] = $row;
        }

        $ringkas = ['jumlah' => count($baris), 'rencana' => 0.0, 'realisasi' => 0.0, 'total' => 0.0, 'periode' => []];
        foreach ($baris as $b) {
            $ringkas['rencana']   += $b['rencana_anggaran'];
            $ringkas['realisasi'] += $b['realisasi_anggaran'];
            $ringkas['total']     += $b['total_biaya'];
            $key = $b['periode_tahun'] . '-' . str_pad((string) $b['periode_bulan'], 2, '0', STR_PAD_LEFT);
            $ringkas['periode'][$key] = ($ringkas['periode'][$key] ?? 0) + 1;
        }
        ksort($ringkas['periode']);

        return [
            'sheet'      => $sheet->getTitle(),
            'baris'      => $baris,
            'peringatan' => $peringatan,
            'dilewati'   => $dilewati,
            'ringkas'    => $ringkas,
        ];
    }

    /** @return array{0:int,1:array<string,int>} [nomor baris header, peta kunci => indeks kolom (0-based)] */
    private function cariHeader(Worksheet $ws): array
    {
        $maks = min(25, $ws->getHighestDataRow());
        for ($r = 1; $r <= $maks; $r++) {
            $ketemu = false;
            $peta   = [];
            for ($c = 0; $c < 40; $c++) {
                $v = $ws->getCell([$c + 1, $r])->getValue();
                // Sel bisa berupa objek RichText (mis. dari file inlineStr); jadikan teks biasa.
                if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
                    $v = $v->getPlainText();
                }
                if (! is_string($v) || $v === '') {
                    continue;
                }
                $n = preg_replace('/[^a-z0-9]/', '', mb_strtolower($v));
                if ($n === 'aktivitas' && $c <= 3) {
                    $ketemu = true;
                }
                foreach (self::HEADER as $kunci => $alias) {
                    if (! isset($peta[$kunci]) && in_array($n, $alias, true)) {
                        $peta[$kunci] = $c;
                    }
                }
            }
            if ($ketemu) {
                return [$r, $peta];
            }
        }

        return [0, []];
    }

    // ------------------------------------------------------------------ SIMPAN

    /**
     * Menyimpan baris hasil parse() ke database dalam satu transaksi.
     *
     * @param list<array<string,mixed>> $baris
     * @param 'tambah'|'ganti'          $mode   ganti = hapus (soft delete) data pada periode yang sama lebih dulu
     *
     * @return array{dimasukkan:int,dihapus:int,master_baru:list<string>}
     */
    public function simpan(array $baris, string $mode = 'tambah', ?int $userId = null): array
    {
        $db = db_connect();
        $db->transStart();

        $peta = [
            'jenis_aktivitas' => $this->petaMaster($db, 'jenis_aktivitas', 'nama'),
            'pelaksanaan'     => $this->petaMaster($db, 'pelaksanaan', 'nama'),
            'akun'            => $this->petaMaster($db, 'akun', 'kode'),
            'cost_center'     => $this->petaMaster($db, 'cost_center', 'kode'),
        ];
        $baru = [];

        $dihapus = 0;
        if ($mode === 'ganti' && $baris) {
            $periode = [];
            foreach ($baris as $b) {
                $periode[$b['periode_tahun'] . '-' . $b['periode_bulan']] = [(int) $b['periode_tahun'], (int) $b['periode_bulan']];
            }
            foreach ($periode as [$thn, $bln]) {
                $db->table('transaksi')
                    ->where('periode_tahun', $thn)->where('periode_bulan', $bln)->where('deleted_at', null)
                    ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                $dihapus += $db->affectedRows();
            }
        }

        $komponen = array_keys(config('Simonkeu')->komponen);
        $sekarang = date('Y-m-d H:i:s');
        $batch    = [];
        $jumlah   = 0;
        $model    = model(TransaksiModel::class);

        foreach ($baris as $b) {
            $data = [
                'aktivitas'          => $b['aktivitas'],
                'jenis_aktivitas_id' => $this->idMaster($db, $peta, $baru, 'jenis_aktivitas', $b['jenis'], 'nama'),
                'pelaksanaan_id'     => $b['pelaksanaan'] !== null ? $this->idMaster($db, $peta, $baru, 'pelaksanaan', $b['pelaksanaan'], 'nama') : null,
                'akun_id'            => $this->idMaster($db, $peta, $baru, 'akun', $b['akun'], 'kode'),
                'cost_center_id'     => $this->idMaster($db, $peta, $baru, 'cost_center', $b['cc'], 'kode'),
                'periode_bulan'      => $b['periode_bulan'],
                'periode_tahun'      => $b['periode_tahun'],
                'tgl_mulai'          => $b['tgl_mulai'],
                'tgl_selesai'        => $b['tgl_selesai'],
                'tempat'             => $b['tempat'],
                'jml_peserta'        => $b['jml_peserta'],
                'rencana_anggaran'   => $b['rencana_anggaran'],
                'tambahan_anggaran'  => $b['tambahan_anggaran'],
                'realisasi_anggaran' => $b['realisasi_anggaran'],
                'status_pembayaran'  => $b['status_pembayaran'],
                'no_parking'         => $b['no_parking'],
                'tgl_pembayaran_terakhir' => $b['tgl_pembayaran_terakhir'],
                'keterangan'         => $b['keterangan'],
                'created_by'         => $userId,
                'created_at'         => $sekarang,
                'updated_at'         => $sekarang,
            ];
            $total = 0.0;
            foreach ($komponen as $k) {
                $data[$k] = $b[$k] ?? 0;
                $total   += (float) $data[$k];
            }
            $data['total_biaya'] = $total;

            $batch[] = $data;
            if (count($batch) >= 100) {
                $jumlah += $this->sisip($db, $batch);
                $batch = [];
            }
        }
        if ($batch) {
            $jumlah += $this->sisip($db, $batch);
        }

        $db->transComplete();
        if (! $db->transStatus()) {
            throw new \RuntimeException('Penyimpanan gagal dan dibatalkan (rollback): ' . ($db->error()['message'] ?? 'kesalahan database'));
        }
        unset($model);

        return ['dimasukkan' => $jumlah, 'dihapus' => $dihapus, 'master_baru' => $baru];
    }

    private function sisip($db, array $batch): int
    {
        $db->table('transaksi')->insertBatch($batch);

        return count($batch);
    }

    /** @return array<string,int> kunci-normal => id */
    private function petaMaster($db, string $tabel, string $kolom): array
    {
        $out = [];
        foreach ($db->table($tabel)->select("id, $kolom")->get()->getResultArray() as $r) {
            $out[$this->kunci((string) $r[$kolom])] = (int) $r['id'];
        }

        return $out;
    }

    private function idMaster($db, array &$peta, array &$baru, string $tabel, string $nilai, string $kolom): int
    {
        $k = $this->kunci($nilai);
        if (isset($peta[$tabel][$k])) {
            return $peta[$tabel][$k];
        }

        $now = date('Y-m-d H:i:s');
        if ($kolom === 'kode') {
            $label = $tabel === 'akun' ? 'Akun ' . $nilai : 'Cost center ' . $nilai;
            $db->table($tabel)->insert(['kode' => $nilai, 'nama' => $label, 'created_at' => $now, 'updated_at' => $now]);
        } else {
            $db->table($tabel)->insert(['nama' => $nilai, 'created_at' => $now, 'updated_at' => $now]);
        }
        $id = (int) $db->insertID();
        $peta[$tabel][$k] = $id;
        $baru[]           = str_replace('_', ' ', $tabel) . ': ' . $nilai;

        return $id;
    }

    // ---------------------------------------------------------------- PEMBANTU

    /** Kunci pencocokan: huruf kecil + spasi dirapatkan. */
    private function kunci(string $s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s) ?? ''));
    }

    private function teks(mixed $v): ?string
    {
        $s = trim((string) ($v ?? ''));

        return $s === '' ? null : preg_replace('/\s+/u', ' ', $s);
    }

    /** Angka/teks kode (akun, cost center, no. parking) -> string digit tanpa desimal. */
    private function kode(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        if (is_numeric($v)) {
            return number_format((float) $v, 0, '', '');
        }

        return trim((string) $v);
    }

    private function bulan(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v) && (int) $v >= 1 && (int) $v <= 12) {
            return (int) $v;
        }
        $k = strtoupper(substr(trim((string) $v), 0, 3));

        return self::BULAN[$k] ?? null;
    }

    private function tanggal(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        if (is_numeric($v)) {
            $f = (float) $v;

            return ($f > 20000 && $f < 80000) ? XlsDate::excelToDateTimeObject($f)->format('Y-m-d') : null;
        }
        $s = trim((string) $v);
        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $s, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
        }
        $t = strtotime($s);

        return $t ? date('Y-m-d', $t) : null;
    }
}
