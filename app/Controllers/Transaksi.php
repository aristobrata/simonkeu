<?php

namespace App\Controllers;

use App\Models\AkunModel;
use App\Models\AuditLogModel;
use App\Models\CostCenterModel;
use App\Models\JenisAktivitasModel;
use App\Models\PelaksanaanModel;
use App\Models\TransaksiModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;

/** CRUD transaksi biaya (baris pada template laporan keuangan). */
class Transaksi extends BaseController
{
    private TransaksiModel $m;

    public function __construct()
    {
        $this->m = new TransaksiModel();
    }

    /** Daftar dengan filter, pengurutan, dan penomoran halaman. */
    public function index()
    {
        $f = [
            'q'           => trim((string) $this->request->getGet('q')),
            'tahun'       => (int) $this->request->getGet('tahun'),
            'bulan'       => (int) $this->request->getGet('bulan'),
            'jenis'       => (int) $this->request->getGet('jenis'),
            'pelaksanaan' => (string) $this->request->getGet('pelaksanaan'),
            'akun'        => (int) $this->request->getGet('akun'),
            'cc'          => (int) $this->request->getGet('cc'),
            'status'      => (string) $this->request->getGet('status'),
        ];
        $urut    = (string) $this->request->getGet('urut') ?: 'tgl';
        $arah    = strtolower((string) $this->request->getGet('arah')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $this->request->getGet('per_page');
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = config('Simonkeu')->perPage;
        }

        $rows  = $this->m->daftar($f, $urut, $arah)->paginate($perPage);
        $pager = $this->m->pager;

        return view('transaksi/index', [
            'rows'      => $rows,
            'pager'     => $pager,
            'f'         => $f,
            'urut'      => isset(TransaksiModel::URUT[$urut]) ? $urut : 'tgl',
            'arah'      => $arah,
            'perPage'   => $perPage,
            'total'     => $this->m->ringkasan($f),
            'lookup'    => $this->lookup(),
            'tahunList' => $this->m->daftarTahun(),
        ]);
    }

    public function show(int $id)
    {
        $row = $this->m->detail($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');

        $riwayat = (new AuditLogModel())->where('entitas', 'transaksi')->where('entitas_id', $id)->orderBy('id', 'DESC')->findAll(15);

        return view('transaksi/show', ['row' => $row, 'riwayat' => $riwayat]);
    }

    public function create()
    {
        $now = time();

        return view('transaksi/form', [
            'row'    => ['periode_bulan' => (int) date('n', $now), 'periode_tahun' => (int) date('Y', $now), 'tgl_mulai' => date('Y-m-d', $now)],
            'mode'   => 'baru',
            'action' => site_url('transaksi/simpan'),
            'lookup' => $this->lookup(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $d    = $this->m->siapkanInput($post);

        $galat = $this->periksa($post, $d);
        $alur  = $this->m->alurStatus(null, $d['status_pembayaran'], has_role('admin'), (int) (current_user()['id'] ?? 0));
        $this->prosesBukti($alur, null, $d, $galat);

        if (! $galat) {
            $d['created_by'] = current_user()['id'] ?? null;
            $id = $this->m->insert($d);
            if ($id) {
                AuditLogModel::catat('tambah', 'transaksi', (int) $id, $d['aktivitas'] . ' — total ' . rupiah($d['total_biaya']));
                if ($alur['mengajukan']) {
                    AuditLogModel::catat('ajukan_lunas', 'transaksi', (int) $id, 'Mengajukan status Lunas, menunggu konfirmasi admin.');

                    return redirect()->to('/transaksi/' . $id)->with('success', 'Transaksi ditambahkan. Pengajuan status "Lunas" terkirim dan menunggu konfirmasi admin.');
                }

                return redirect()->to('/transaksi/' . $id)->with('success', 'Transaksi berhasil ditambahkan.');
            }
            $galat = $this->m->errors();
        }

        return redirect()->back()->withInput()->with('errors', $galat);
    }

    public function edit(int $id)
    {
        $row = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');

        return view('transaksi/form', [
            'row'    => $row,
            'mode'   => 'ubah',
            'action' => site_url("transaksi/$id/ubah"),
            'lookup' => $this->lookup(),
        ]);
    }

    public function update(int $id)
    {
        $lama = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');
        $post = $this->request->getPost();
        $d    = $this->m->siapkanInput($post);

        $galat = $this->periksa($post, $d);
        $alur  = $this->m->alurStatus($lama['status_pembayaran'], $d['status_pembayaran'], has_role('admin'), (int) (current_user()['id'] ?? 0));
        $this->prosesBukti($alur, $lama, $d, $galat);

        if (! $galat) {
            if ($this->m->update($id, $d)) {
                AuditLogModel::catat('ubah', 'transaksi', $id, $lama['aktivitas'] . ' — ' . $this->m->ringkasPerubahan($lama, $d));
                if ($alur['mengajukan']) {
                    AuditLogModel::catat('ajukan_lunas', 'transaksi', $id, 'Mengajukan status Lunas, menunggu konfirmasi admin.');

                    return redirect()->to('/transaksi/' . $id)->with('success', 'Perubahan disimpan. Pengajuan status "Lunas" terkirim dan menunggu konfirmasi admin.');
                }
                if (($alur['data']['status_pembayaran'] ?? null) === 'Lunas' && $lama['status_pembayaran'] !== 'Lunas') {
                    AuditLogModel::catat('konfirmasi_lunas', 'transaksi', $id, 'Admin menandai lunas langsung (tanpa antre konfirmasi).');
                }

                return redirect()->to('/transaksi/' . $id)->with('success', 'Perubahan berhasil disimpan.');
            }
            $galat = $this->m->errors();
        }

        return redirect()->back()->withInput()->with('errors', $galat);
    }

    public function delete(int $id)
    {
        $row = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');

        $this->hapusFileBukti($row['bukti_pembayaran'] ?? null);
        $this->m->delete($id); // soft delete: data tetap ada di database dan tercatat di log
        AuditLogModel::catat('hapus', 'transaksi', $id, $row['aktivitas'] . ' — total ' . rupiah($row['total_biaya']));

        return redirect()->to('/transaksi')->with('success', 'Transaksi "' . mb_strimwidth($row['aktivitas'], 0, 60, '…') . '" dihapus.');
    }

    /** Tampilkan/unduh file bukti pembayaran (disimpan di luar folder publik, hanya untuk pengguna yang sudah masuk). */
    public function bukti(int $id)
    {
        $row  = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');
        $nama = $row['bukti_pembayaran'] ?? null;
        $path = $nama ? WRITEPATH . 'uploads/bukti/' . $nama : null;

        if (! $nama || ! is_file($path)) {
            throw PageNotFoundException::forPageNotFound('Belum ada bukti pembayaran untuk transaksi ini.');
        }

        $mime = match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf'         => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            default       => 'application/octet-stream',
        };

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="bukti-' . $id . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setBody((string) file_get_contents($path));
    }

    /**
     * Tangani unggahan bukti pembayaran & terapkan hasil alurStatus() ke $d.
     * Mengisi $galat['bukti_pembayaran'] bila wajib tapi tidak tersedia (baru maupun lama).
     *
     * @param array{data:array<string,mixed>,butuh_bukti:bool,mengajukan:bool} $alur
     */
    private function prosesBukti(array $alur, ?array $lama, array &$d, array &$galat): void
    {
        $d = array_merge($d, $alur['data']);

        $file        = $this->request->getFile('bukti_pembayaran');
        $adaFileBaru = $file instanceof UploadedFile && $file->isValid() && ! $file->hasMoved();

        // File dipilih tapi ditolak PHP (mis. melebihi upload_max_filesize) — beri pesan yang jelas.
        if ($file instanceof UploadedFile && ! $file->isValid() && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            $galat['bukti_pembayaran'] = in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'Ukuran file terlalu besar (maksimal ' . number_format(config('Simonkeu')->buktiMaksKb / 1024, 1) . ' MB).'
                : 'File gagal diunggah: ' . $file->getErrorString();
        }

        if ($adaFileBaru) {
            $cfg = config('Simonkeu');
            $ext = strtolower((string) $file->getClientExtension());
            if (! in_array($ext, $cfg->buktiExt, true)) {
                $galat['bukti_pembayaran'] = 'Format file harus ' . strtoupper(implode('/', $cfg->buktiExt)) . '.';
            } elseif ($file->getSizeByUnit('kb') > $cfg->buktiMaksKb) {
                $galat['bukti_pembayaran'] = 'Ukuran file maksimal ' . number_format($cfg->buktiMaksKb / 1024, 1) . ' MB.';
            } else {
                $dir = WRITEPATH . 'uploads/bukti';
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $namaBaru = 'bukti_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if ($file->move($dir, $namaBaru)) {
                    $this->hapusFileBukti($lama['bukti_pembayaran'] ?? null);
                    $d['bukti_pembayaran']      = $namaBaru;
                    $d['bukti_pembayaran_oleh'] = current_user()['id'] ?? null;
                    $d['bukti_pembayaran_at']   = date('Y-m-d H:i:s');
                } else {
                    $galat['bukti_pembayaran'] = 'File gagal diunggah. Coba lagi.';
                }
            }
        }

        if ($alur['butuh_bukti'] && empty($galat['bukti_pembayaran'])) {
            $sudahAda = $adaFileBaru || ! empty($lama['bukti_pembayaran']);
            if (! $sudahAda) {
                $galat['bukti_pembayaran'] = 'Unggah bukti pembayaran (' . strtoupper(implode('/', config('Simonkeu')->buktiExt)) . ', maks ' . number_format(config('Simonkeu')->buktiMaksKb / 1024, 1) . ' MB) untuk mengubah status menjadi Lunas.';
            }
        }
    }

    private function hapusFileBukti(?string $nama): void
    {
        if ($nama) {
            $path = WRITEPATH . 'uploads/bukti/' . $nama;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Semua pemeriksaan sekaligus, agar pengguna melihat seluruh kesalahan dalam satu kali tampil:
     * aturan model + angka yang tidak terbaca + pemeriksaan lintas-kolom.
     *
     * @return array<string,string>
     */
    private function periksa(array $post, array $d): array
    {
        $galat = $this->m->validate($d) ? [] : $this->m->errors();

        // Teks yang bukan angka pada kolom uang jangan diam-diam dianggap 0.
        $uang = array_merge(['rencana_anggaran' => 'Rencana anggaran', 'realisasi_anggaran' => 'Realisasi anggaran', 'tambahan_anggaran' => 'Tambahan anggaran'], config('Simonkeu')->komponen);
        foreach ($uang as $k => $label) {
            $v = trim((string) ($post[$k] ?? ''));
            if ($v !== '' && ! preg_match('/^[\s\-()Rp.,0-9]+$/i', $v)) {
                $galat[$k] = "$label harus berupa angka.";
            }
        }

        if ($d['tgl_mulai'] && $d['tgl_selesai'] && $d['tgl_selesai'] < $d['tgl_mulai']) {
            $galat['tgl_selesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
        }

        return $galat;
    }

    /** Pilihan untuk dropdown filter & form. */
    private function lookup(): array
    {
        return [
            'jenis'       => (new JenisAktivitasModel())->opsi(),
            'pelaksanaan' => (new PelaksanaanModel())->opsi(),
            'akun'        => (new AkunModel())->opsi(),
            'cc'          => (new CostCenterModel())->opsi(),
            'status'      => config('Simonkeu')->statusPembayaran,
        ];
    }
}
