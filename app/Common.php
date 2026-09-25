<?php

/**
 * Fungsi global aplikasi SIMONKEU (otomatis dimuat CodeIgniter).
 * Berisi pemformat angka/tanggal gaya Indonesia dan pembantu hak akses.
 */

if (! function_exists('to_number')) {
    /**
     * Mengubah teks angka (format Indonesia maupun internasional) menjadi float.
     * "1.500.000" -> 1500000 | "1.234,56" -> 1234.56 | "Rp 25.000" -> 25000 | "(500)" -> -500
     */
    function to_number(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }

        $s = trim(str_replace(['Rp', 'rp', ' ', "\xC2\xA0"], '', (string) $v));
        if ($s === '' || $s === '-') {
            return 0.0;
        }

        $neg = false;
        if (preg_match('/^\((.*)\)$/', $s, $m)) {
            $neg = true;
            $s   = $m[1];
        }
        if (str_starts_with($s, '-')) {
            $neg = true;
            $s   = substr($s, 1);
        }

        $hasDot   = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            // pemisah desimal = simbol yang muncul paling akhir
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace(',', '.', str_replace('.', '', $s));
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            $s = preg_match('/^\d{1,3}(,\d{3})+$/', $s) ? str_replace(',', '', $s) : str_replace(',', '.', $s);
        } elseif ($hasDot && preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            $s = str_replace('.', '', $s);
        }

        $s = preg_replace('/[^0-9.]/', '', $s) ?? '';
        if ($s === '' || $s === '.') {
            return 0.0;
        }

        return $neg ? -(float) $s : (float) $s;
    }
}

if (! function_exists('rupiah')) {
    /** 1234567 -> "Rp 1.234.567" */
    function rupiah(mixed $n, bool $prefix = true): string
    {
        $n = (float) $n;

        return ($n < 0 ? '-' : '') . ($prefix ? 'Rp ' : '') . number_format(abs($n), 0, ',', '.');
    }
}

if (! function_exists('angka')) {
    function angka(mixed $n, int $desimal = 0): string
    {
        return number_format((float) $n, $desimal, ',', '.');
    }
}

if (! function_exists('persen')) {
    /** persen(95.34) -> "95,3%" (nilai sudah dalam satuan persen) */
    function persen(mixed $n, int $desimal = 1): string
    {
        return number_format((float) $n, $desimal, ',', '.') . '%';
    }
}

if (! function_exists('bulan_id')) {
    function bulan_id(int $m, bool $singkat = true): string
    {
        $pendek = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $penuh  = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $arr    = $singkat ? $pendek : $penuh;

        return $arr[$m] ?? '';
    }
}

if (! function_exists('tgl_id')) {
    /** "2026-01-05" -> "5 Jan 2026" */
    function tgl_id(?string $tanggal, bool $singkat = true): string
    {
        if ($tanggal === null || $tanggal === '' || str_starts_with($tanggal, '0000')) {
            return '';
        }
        $t = strtotime($tanggal);
        if ($t === false) {
            return '';
        }

        return date('j', $t) . ' ' . bulan_id((int) date('n', $t), $singkat) . ' ' . date('Y', $t);
    }
}

if (! function_exists('periode_teks')) {
    /** periode_teks(1, 7, 2026) -> "Januari – Juli 2026" */
    function periode_teks(int $dari, int $sampai, int $tahun): string
    {
        if ($dari === $sampai) {
            return bulan_id($dari, false) . ' ' . $tahun;
        }

        return bulan_id($dari, false) . ' – ' . bulan_id($sampai, false) . ' ' . $tahun;
    }
}

if (! function_exists('current_user')) {
    function current_user(): ?array
    {
        return session()->get('user');
    }
}

if (! function_exists('has_role')) {
    function has_role(string ...$roles): bool
    {
        $role = session()->get('user')['role'] ?? null;

        return $role !== null && in_array($role, $roles, true);
    }
}

if (! function_exists('asset')) {
    /** URL aset publik + versi (cache-busting berdasarkan waktu ubah file). */
    function asset(string $path): string
    {
        $file = FCPATH . 'assets/' . ltrim($path, '/');
        $ver  = is_file($file) ? '?v=' . filemtime($file) : '';

        return base_url('assets/' . ltrim($path, '/')) . $ver;
    }
}

if (! function_exists('status_kelas')) {
    /** Kelas chip untuk status pembayaran (Belum/Diproses/Lunas). */
    function status_kelas(?string $status): string
    {
        return match ($status) {
            'Lunas'    => 'chip-teal',
            'Diproses' => 'chip-gold',
            'Belum'    => 'chip-brick',
            default    => 'chip-slate',
        };
    }
}

if (! function_exists('selisih_kelas')) {
    /** Kelas warna teks untuk selisih (sisa anggaran): positif = hemat, negatif = melebihi. */
    function selisih_kelas(float $selisih): string
    {
        if (abs($selisih) < 0.5) {
            return 'text-muted';
        }

        return $selisih > 0 ? 'text-ok' : 'text-over';
    }
}
