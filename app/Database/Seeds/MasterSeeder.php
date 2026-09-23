<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Data referensi awal (diturunkan dari isi template Excel) + akun pengguna awal.
 * Aman dijalankan berulang: data yang sudah ada tidak diduplikasi.
 */
class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // ---- Pengguna awal (GANTI kata sandi setelah masuk pertama kali!)
        $users = [
            ['nama' => 'Administrator',     'username' => 'admin',    'role' => 'admin',    'pw' => 'admin123'],
            ['nama' => 'Operator Keuangan', 'username' => 'operator', 'role' => 'operator', 'pw' => 'operator123'],
            ['nama' => 'Peninjau',          'username' => 'viewer',   'role' => 'viewer',   'pw' => 'viewer123'],
        ];
        foreach ($users as $u) {
            if (! $this->db->table('users')->where('username', $u['username'])->countAllResults()) {
                $this->db->table('users')->insert([
                    'nama' => $u['nama'], 'username' => $u['username'], 'role' => $u['role'], 'aktif' => 1,
                    'password_hash' => password_hash($u['pw'], PASSWORD_DEFAULT),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // ---- Jenis aktivitas
        $jenis = [
            ['Training', 'Pelatihan, sertifikasi, dan kegiatan diklat'],
            ['KM', 'Knowledge Management'],
            ['PKL', 'Praktik Kerja Lapangan / magang'],
            ['Supply Kantor', 'Perlengkapan dan kebutuhan kantor'],
            ['Pemeliharaan & Prasarana', 'Perbaikan dan pemeliharaan sarana prasarana'],
            ['Beasiswa S2', null],
            ['Beasiswa S1', null],
            ['Biaya Konsultan', 'Konsultan / assessment'],
            ['Biaya Rapat', null],
            ['Jamuan Tamu', null],
        ];
        foreach ($jenis as [$nama, $ket]) {
            $this->sisipJikaBelumAda('jenis_aktivitas', 'nama', $nama, ['deskripsi' => $ket]);
        }

        // ---- Pelaksanaan (kolom "Inhouse/Public" pada template)
        $pelaksanaan = [
            ['In House', 'Diselenggarakan sendiri'],
            ['Public', 'Mengikuti pelatihan publik/eksternal'],
            ['LAT', null],
            ['Online', null],
            ['Redeem Poin', 'Penukaran poin (KM)'],
            ['SPIE Reward', 'Apresiasi kompetisi inovasi (SPIE)'],
        ];
        foreach ($pelaksanaan as [$nama, $ket]) {
            $this->sisipJikaBelumAda('pelaksanaan', 'nama', $nama, ['deskripsi' => $ket]);
        }

        // ---- Akun. Nama diturunkan dari isi data; SESUAIKAN dengan bagan akun (COA) resmi.
        $akun = [
            ['64310009', 'Biaya Diklat, KM & PKL'],
            ['64320009', 'Biaya Diklat Public'],
            ['64220009', 'Biaya Konsultan'],
            ['65510009', 'Biaya Pemeliharaan & Prasarana'],
            ['67420001', 'Biaya Supply Kantor'],
            ['67810001', 'Biaya Lain-lain (Seminar & Jamuan)'],
            ['67810002', 'Biaya Lain-lain (Rapat & Operasional)'],
        ];
        foreach ($akun as [$kode, $nama]) {
            $this->sisipJikaBelumAda('akun', 'kode', $kode, ['nama' => $nama]);
        }

        // ---- Cost center. Nama netral; SESUAIKAN dengan struktur organisasi.
        foreach (['3104310000', '3104300000', '3105221000', '3102200000', '3103230000', '3140310000'] as $kode) {
            $this->sisipJikaBelumAda('cost_center', 'kode', $kode, ['nama' => 'Cost center ' . $kode]);
        }
    }

    private function sisipJikaBelumAda(string $tabel, string $kolom, string $nilai, array $tambahan): void
    {
        if ($this->db->table($tabel)->where($kolom, $nilai)->countAllResults()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->table($tabel)->insert([$kolom => $nilai] + $tambahan + ['created_at' => $now, 'updated_at' => $now]);
    }
}
