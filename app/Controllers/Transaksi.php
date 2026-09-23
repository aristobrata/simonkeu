<?php

namespace App\Controllers;

use App\Models\AkunModel;
use App\Models\AuditLogModel;
use App\Models\CostCenterModel;
use App\Models\JenisAktivitasModel;
use App\Models\PelaksanaanModel;
use App\Models\TransaksiModel;
use CodeIgniter\Exceptions\PageNotFoundException;

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
        if (! $galat) {
            $d['created_by'] = current_user()['id'] ?? null;
            $id = $this->m->insert($d);
            if ($id) {
                AuditLogModel::catat('tambah', 'transaksi', (int) $id, $d['aktivitas'] . ' — total ' . rupiah($d['total_biaya']));

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
        if (! $galat) {
            if ($this->m->update($id, $d)) {
                AuditLogModel::catat('ubah', 'transaksi', $id, $lama['aktivitas'] . ' — ' . $this->m->ringkasPerubahan($lama, $d));

                return redirect()->to('/transaksi/' . $id)->with('success', 'Perubahan berhasil disimpan.');
            }
            $galat = $this->m->errors();
        }

        return redirect()->back()->withInput()->with('errors', $galat);
    }

    public function delete(int $id)
    {
        $row = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');

        $this->m->delete($id); // soft delete: data tetap ada di database dan tercatat di log
        AuditLogModel::catat('hapus', 'transaksi', $id, $row['aktivitas'] . ' — total ' . rupiah($row['total_biaya']));

        return redirect()->to('/transaksi')->with('success', 'Transaksi "' . mb_strimwidth($row['aktivitas'], 0, 60, '…') . '" dihapus.');
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
