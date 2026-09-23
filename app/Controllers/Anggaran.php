<?php

namespace App\Controllers;

use App\Models\AnggaranModel;
use App\Models\AuditLogModel;
use App\Models\JenisAktivitasModel;
use App\Models\TransaksiModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Pagu anggaran tahunan: total per tahun, dan/atau rincian per jenis aktivitas.
 * Realisasi otomatis mengurangi sisa pagu setiap ada transaksi baru — dihitung langsung
 * dari tabel transaksi, jadi selalu mutakhir tanpa perlu proses tambahan.
 */
class Anggaran extends BaseController
{
    public function index()
    {
        $m = new AnggaranModel();

        $tahunList = array_values(array_unique(array_merge($m->daftarTahun(), (new TransaksiModel())->daftarTahun())));
        rsort($tahunList);

        $tahun = (int) $this->request->getGet('tahun');
        if (! in_array($tahun, $tahunList, true)) {
            $tahun = $tahunList[0] ?? (int) date('Y');
        }

        return view('anggaran/index', [
            'tahun'     => $tahun,
            'tahunList' => $tahunList ?: [$tahun],
            'rows'      => $m->ringkasan($tahun),
            'jenisList' => (new JenisAktivitasModel())->opsi(),
        ]);
    }

    public function save()
    {
        $m     = new AnggaranModel();
        $id    = (int) $this->request->getPost('id');
        $tahun = (int) $this->request->getPost('tahun');

        $jenisRaw = trim((string) $this->request->getPost('jenis_aktivitas_id'));
        $jenisId  = $jenisRaw === '' ? null : (int) $jenisRaw;

        $rules = [
            'tahun'      => ['label' => 'Tahun', 'rules' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]'],
            'nominal'    => ['label' => 'Nominal anggaran', 'rules' => 'required|numeric|greater_than_equal_to[0]'],
            'keterangan' => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[255]'],
        ];
        if ($jenisId !== null) {
            $rules['jenis_aktivitas_id'] = ['label' => 'Jenis aktivitas', 'rules' => 'is_not_unique[jenis_aktivitas.id]'];
        }
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($m->sudahAda($tahun, $jenisId, $id)) {
            $label = $jenisId === null ? 'seluruh jenis aktivitas (total tahunan)' : 'jenis aktivitas ini';
            return redirect()->back()->withInput()->with('errors', ['jenis_aktivitas_id' => "Pagu tahun $tahun untuk $label sudah ada. Ubah yang sudah ada, atau pilih cakupan lain."]);
        }

        $data = [
            'tahun'              => $tahun,
            'jenis_aktivitas_id' => $jenisId,
            'nominal'            => to_number($this->request->getPost('nominal')),
            'keterangan'         => trim((string) $this->request->getPost('keterangan')) ?: null,
        ];

        if ($id > 0) {
            $m->find($id) ?? throw PageNotFoundException::forPageNotFound('Pagu anggaran tidak ditemukan.');
            $m->update($id, $data);
            AuditLogModel::catat('ubah', 'anggaran', $id, "Tahun $tahun — " . ($jenisId === null ? 'Semua jenis' : 'Jenis #' . $jenisId) . ': ' . rupiah($data['nominal']));
            $pesan = 'Pagu anggaran diperbarui.';
        } else {
            $newId = $m->insert($data);
            AuditLogModel::catat('tambah', 'anggaran', (int) $newId, "Tahun $tahun — " . ($jenisId === null ? 'Semua jenis' : 'Jenis #' . $jenisId) . ': ' . rupiah($data['nominal']));
            $pesan = 'Pagu anggaran ditambahkan.';
        }

        return redirect()->to('/anggaran?tahun=' . $tahun)->with('success', $pesan);
    }

    public function delete(int $id)
    {
        $m   = new AnggaranModel();
        $row = $m->find($id) ?? throw PageNotFoundException::forPageNotFound('Pagu anggaran tidak ditemukan.');

        $m->delete($id);
        AuditLogModel::catat('hapus', 'anggaran', $id, 'Tahun ' . $row['tahun'] . ': ' . rupiah($row['nominal']));

        return redirect()->to('/anggaran?tahun=' . $row['tahun'])->with('success', 'Pagu anggaran dihapus.');
    }
}
