<?php

namespace App\Database\Seeds;

use App\Libraries\ExcelImporter;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Mengisi tabel transaksi dari file Excel bawaan (template laporan keuangan)
 * memakai ExcelImporter yang sama dengan fitur Import di aplikasi.
 * Dilewati bila tabel transaksi sudah berisi data.
 */
class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('transaksi')->countAllResults() > 0) {
            CLI::write('Tabel transaksi sudah berisi data — seeder dilewati.', 'yellow');

            return;
        }

        $file = __DIR__ . '/data/Template_Laporan_Keuangan_Per_2_Agus_26.xlsx';
        if (! is_file($file)) {
            CLI::write('File Excel bawaan tidak ditemukan: ' . $file, 'red');

            return;
        }

        $imp   = new ExcelImporter();
        $hasil = $imp->parse($file);
        $admin = $this->db->table('users')->select('id')->where('username', 'admin')->get()->getRowArray();
        $simpan = $imp->simpan($hasil['baris'], 'tambah', $admin['id'] ?? null);

        CLI::write(sprintf('Sheet "%s": %d baris diimpor, %d dilewati.', $hasil['sheet'], $simpan['dimasukkan'], $hasil['dilewati']), 'green');
        foreach ($hasil['peringatan'] as $p) {
            CLI::write('  • ' . $p, 'yellow');
        }
        foreach ($simpan['master_baru'] as $m) {
            CLI::write('  + master baru: ' . $m, 'cyan');
        }
    }
}
