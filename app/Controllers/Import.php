<?php

namespace App\Controllers;

use App\Libraries\ExcelImporter;
use App\Models\AuditLogModel;

/**
 * Import template laporan keuangan (.xlsx) dalam dua langkah:
 * 1) unggah + baca + pratinjau, 2) konfirmasi dan simpan.
 */
class Import extends BaseController
{
    private function dir(): string
    {
        $d = WRITEPATH . 'uploads';
        if (! is_dir($d)) {
            mkdir($d, 0775, true);
        }

        return $d;
    }

    /** Hapus berkas sementara yang berumur > 1 hari. */
    private function bersihkan(): void
    {
        foreach (glob($this->dir() . '/import_*') ?: [] as $f) {
            if (is_file($f) && filemtime($f) < time() - 86400) {
                @unlink($f);
            }
        }
    }

    public function index()
    {
        return view('transaksi/import');
    }

    public function upload()
    {
        set_time_limit(180);
        ini_set('memory_limit', '512M');

        $ok = $this->validate([
            'berkas' => [
                'label' => 'File Excel',
                'rules' => 'uploaded[berkas]|ext_in[berkas,xlsx,xlsm]|max_size[berkas,10240]',
                'errors' => [
                    'uploaded' => 'Pilih file Excel (.xlsx) terlebih dahulu.',
                    'ext_in'   => 'Format file harus .xlsx.',
                    'max_size' => 'Ukuran file maksimal 10 MB.',
                ],
            ],
        ]);
        if (! $ok) {
            return redirect()->to('/transaksi/import')->with('errors', $this->validator->getErrors());
        }

        $this->bersihkan();
        $file  = $this->request->getFile('berkas');
        $token = bin2hex(random_bytes(16));
        $path  = $this->dir() . "/import_$token.xlsx";
        $file->move($this->dir(), "import_$token.xlsx");

        try {
            $hasil = (new ExcelImporter())->parse($path);
        } catch (\Throwable $e) {
            @unlink($path);
            log_message('error', 'Import gagal dibaca: ' . $e->getMessage());

            return redirect()->to('/transaksi/import')->with('error', 'File tidak dapat dibaca: ' . $e->getMessage());
        }
        @unlink($path);

        if (! $hasil['baris']) {
            return redirect()->to('/transaksi/import')->with('error', 'Tidak ada baris data yang dapat diimpor dari file ini.');
        }

        file_put_contents($this->dir() . "/import_$token.json", json_encode($hasil, JSON_UNESCAPED_UNICODE));
        $this->session->set('import', ['token' => $token, 'nama' => $file->getClientName()]);

        // Berapa data yang sudah ada pada periode yang sama (untuk mode "ganti")?
        $ada = [];
        foreach (array_keys($hasil['ringkas']['periode']) as $p) {
            [$thn, $bln] = array_map('intval', explode('-', $p));
            $ada[$p] = db_connect()->table('transaksi')->where('periode_tahun', $thn)->where('periode_bulan', $bln)->where('deleted_at', null)->countAllResults();
        }

        return view('transaksi/import_preview', ['hasil' => $hasil, 'token' => $token, 'nama' => $file->getClientName(), 'ada' => $ada]);
    }

    public function confirm()
    {
        set_time_limit(180);

        $token = (string) $this->request->getPost('token');
        $sesi  = $this->session->get('import');
        if (! preg_match('/^[a-f0-9]{32}$/', $token) || ! $sesi || ($sesi['token'] ?? '') !== $token) {
            return redirect()->to('/transaksi/import')->with('error', 'Sesi import tidak valid atau sudah kedaluwarsa. Unggah ulang file.');
        }
        $json = $this->dir() . "/import_$token.json";
        if (! is_file($json)) {
            return redirect()->to('/transaksi/import')->with('error', 'Data pratinjau tidak ditemukan. Unggah ulang file.');
        }

        $hasil = json_decode((string) file_get_contents($json), true);
        $mode  = $this->request->getPost('mode') === 'ganti' ? 'ganti' : 'tambah';

        try {
            $r = (new ExcelImporter())->simpan($hasil['baris'], $mode, current_user()['id'] ?? null);
        } catch (\Throwable $e) {
            log_message('error', 'Import gagal disimpan: ' . $e->getMessage());

            return redirect()->to('/transaksi/import')->with('error', 'Import dibatalkan, tidak ada data yang berubah. ' . $e->getMessage());
        }

        @unlink($json);
        $this->session->remove('import');

        $ringkas = "{$r['dimasukkan']} baris dari \"" . ($sesi['nama'] ?? 'file') . '"';
        if ($r['dihapus'] > 0) {
            $ringkas .= ", {$r['dihapus']} baris lama pada periode yang sama diganti";
        }
        AuditLogModel::catat('import', 'transaksi', null, $ringkas . ($r['master_baru'] ? '; master baru: ' . implode(', ', $r['master_baru']) : ''));

        $pesan = "Import selesai: $ringkas.";
        if ($r['master_baru']) {
            $pesan .= ' Master baru dibuat: ' . implode(', ', $r['master_baru']) . ' — lengkapi namanya di Master data.';
        }

        return redirect()->to('/transaksi')->with('success', $pesan);
    }
}
