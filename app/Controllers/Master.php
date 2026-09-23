<?php

namespace App\Controllers;

use App\Models\AkunModel;
use App\Models\AuditLogModel;
use App\Models\CostCenterModel;
use App\Models\JenisAktivitasModel;
use App\Models\MasterModel;
use App\Models\PelaksanaanModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * CRUD generik untuk 4 tabel referensi. Slug URL => konfigurasi.
 * Field 'kode' bersifat unik; bila tidak ada, 'nama' yang unik.
 */
class Master extends BaseController
{
    private const CFG = [
        'jenis-aktivitas' => ['model' => JenisAktivitasModel::class, 'judul' => 'Jenis aktivitas', 'unik' => 'nama', 'kolom' => ['nama' => 'Nama', 'deskripsi' => 'Deskripsi'],
            'bantuan' => 'Kelompok kegiatan pada kolom "Jenis Aktivitas" (Training, KM, PKL, dst.).'],
        'pelaksanaan' => ['model' => PelaksanaanModel::class, 'judul' => 'Inhouse / Public', 'unik' => 'nama', 'kolom' => ['nama' => 'Nama', 'deskripsi' => 'Deskripsi'],
            'bantuan' => 'Pilihan pada kolom "Inhouse/Public" (In House, Public, LAT, Online, dst.).'],
        'akun' => ['model' => AkunModel::class, 'judul' => 'No. akun', 'unik' => 'kode', 'kolom' => ['kode' => 'No. akun', 'nama' => 'Nama akun'],
            'bantuan' => 'Nama akun pada data awal diturunkan dari isi template; sesuaikan dengan bagan akun resmi.'],
        'cost-center' => ['model' => CostCenterModel::class, 'judul' => 'Cost center', 'unik' => 'kode', 'kolom' => ['kode' => 'Kode cost center', 'nama' => 'Nama'],
            'bantuan' => 'Nama pada data awal masih netral; ganti dengan nama unit yang sebenarnya.'],
    ];

    private function cfg(string $slug): array
    {
        return self::CFG[$slug] ?? throw PageNotFoundException::forPageNotFound('Master data tidak dikenal.');
    }

    private function model(array $cfg): MasterModel
    {
        return new $cfg['model']();
    }

    public function index(string $slug)
    {
        $cfg = $this->cfg($slug);

        return view('master/index', ['slug' => $slug, 'cfg' => $cfg, 'rows' => $this->model($cfg)->denganPemakaian()]);
    }

    public function save(string $slug)
    {
        $cfg   = $this->cfg($slug);
        $model = $this->model($cfg);
        $id    = (int) $this->request->getPost('id');

        $data  = [];
        $rules = [];
        foreach ($cfg['kolom'] as $k => $label) {
            $data[$k] = trim((string) $this->request->getPost($k));
            $wajib    = $k === 'deskripsi' ? 'permit_empty' : 'required';
            $rules[$k] = ['label' => $label, 'rules' => $wajib . '|max_length[' . ($k === 'kode' ? 20 : 150) . ']'];
        }
        // Keunikan pada kolom pengenal (abaikan baris yang sedang diubah)
        $u = $cfg['unik'];
        $rules[$u]['rules'] .= '|is_unique[' . $model->table . '.' . $u . ',id,' . $id . ']';
        if ($u === 'kode') {
            $rules['kode']['rules'] .= '|regex_match[/^[0-9A-Za-z.\-]+$/]';
        }

        if (! $this->validateData($data, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        if (array_key_exists('deskripsi', $data) && $data['deskripsi'] === '') {
            $data['deskripsi'] = null;
        }

        if ($id > 0) {
            $model->find($id) ?? throw PageNotFoundException::forPageNotFound();
            $model->update($id, $data);
            AuditLogModel::catat('ubah', 'master:' . $slug, $id, json_encode($data, JSON_UNESCAPED_UNICODE));
            $pesan = $cfg['judul'] . ' diperbarui.';
        } else {
            $newId = $model->insert($data);
            AuditLogModel::catat('tambah', 'master:' . $slug, (int) $newId, json_encode($data, JSON_UNESCAPED_UNICODE));
            $pesan = $cfg['judul'] . ' ditambahkan.';
        }

        return redirect()->to('/master/' . $slug)->with('success', $pesan);
    }

    public function delete(string $slug, int $id)
    {
        $cfg   = $this->cfg($slug);
        $model = $this->model($cfg);
        $row   = $model->find($id) ?? throw PageNotFoundException::forPageNotFound();

        if ($model->jumlahPemakaian($id) > 0) {
            return redirect()->to('/master/' . $slug)->with('error', 'Tidak bisa dihapus: masih dipakai oleh transaksi. Ubah namanya, atau pindahkan transaksinya lebih dulu.');
        }

        $model->delete($id);
        AuditLogModel::catat('hapus', 'master:' . $slug, $id, json_encode($row, JSON_UNESCAPED_UNICODE));

        return redirect()->to('/master/' . $slug)->with('success', $cfg['judul'] . ' dihapus.');
    }
}
