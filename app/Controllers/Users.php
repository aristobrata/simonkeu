<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/** Manajemen pengguna (khusus administrator). */
class Users extends BaseController
{
    private UserModel $m;

    public function __construct()
    {
        $this->m = new UserModel();
    }

    public function index()
    {
        return view('users/index', ['rows' => $this->m->orderBy('nama')->findAll()]);
    }

    public function create()
    {
        return view('users/form', ['row' => ['role' => 'viewer', 'aktif' => 1], 'mode' => 'baru', 'action' => site_url('pengguna/simpan')]);
    }

    public function store()
    {
        $rules = $this->rules(0, true);
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = $this->m->insert([
            'nama'          => trim((string) $this->request->getPost('nama')),
            'username'      => trim((string) $this->request->getPost('username')),
            'role'          => $this->request->getPost('role'),
            'aktif'         => $this->request->getPost('aktif') ? 1 : 0,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
        ]);
        AuditLogModel::catat('tambah', 'users', (int) $id, 'Pengguna ' . $this->request->getPost('username') . ' (' . $this->request->getPost('role') . ')');

        return redirect()->to('/pengguna')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $row = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound();

        return view('users/form', ['row' => $row, 'mode' => 'ubah', 'action' => site_url("pengguna/$id/ubah")]);
    }

    public function update(int $id)
    {
        $row = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound();

        $rules = $this->rules($id, false);
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role  = (string) $this->request->getPost('role');
        $aktif = $this->request->getPost('aktif') ? 1 : 0;

        // Jangan sampai tidak ada administrator aktif tersisa.
        if ($row['role'] === 'admin' && ($role !== 'admin' || ! $aktif) && $this->jumlahAdminAktif($id) === 0) {
            return redirect()->back()->withInput()->with('errors', ['role' => 'Harus tersisa minimal satu administrator aktif.']);
        }

        $data = [
            'nama'     => trim((string) $this->request->getPost('nama')),
            'username' => trim((string) $this->request->getPost('username')),
            'role'     => $role,
            'aktif'    => $aktif,
        ];
        if ((string) $this->request->getPost('password') !== '') {
            $data['password_hash'] = password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT);
        }
        $this->m->update($id, $data);
        AuditLogModel::catat('ubah', 'users', $id, 'Pengguna ' . $data['username'] . ' (' . $role . ', ' . ($aktif ? 'aktif' : 'nonaktif') . ')' . (isset($data['password_hash']) ? ', kata sandi diganti' : ''));

        // Perbarui sesi bila mengubah akun sendiri.
        if ($id === (int) session()->get('user')['id']) {
            session()->set('user', ['id' => $id, 'nama' => $data['nama'], 'username' => $data['username'], 'role' => $role]);
        }

        return redirect()->to('/pengguna')->with('success', 'Pengguna diperbarui.');
    }

    public function delete(int $id)
    {
        $row = $this->m->find($id) ?? throw PageNotFoundException::forPageNotFound();

        if ($id === (int) session()->get('user')['id']) {
            return redirect()->to('/pengguna')->with('error', 'Anda tidak dapat menghapus akun yang sedang dipakai.');
        }
        if ($row['role'] === 'admin' && $this->jumlahAdminAktif($id) === 0) {
            return redirect()->to('/pengguna')->with('error', 'Harus tersisa minimal satu administrator aktif.');
        }

        $this->m->delete($id);
        AuditLogModel::catat('hapus', 'users', $id, 'Pengguna ' . $row['username']);

        return redirect()->to('/pengguna')->with('success', 'Pengguna dihapus.');
    }

    private function jumlahAdminAktif(int $kecuali): int
    {
        return (int) $this->m->where('role', 'admin')->where('aktif', 1)->where('id !=', $kecuali)->countAllResults();
    }

    private function rules(int $id, bool $baru): array
    {
        return [
            'nama'     => ['label' => 'Nama', 'rules' => 'required|max_length[100]'],
            'username' => ['label' => 'Nama pengguna', 'rules' => 'required|alpha_numeric_punct|min_length[3]|max_length[50]|is_unique[users.username,id,' . $id . ']'],
            'role'     => ['label' => 'Peran', 'rules' => 'required|in_list[' . implode(',', array_keys(UserModel::ROLE)) . ']'],
            'password' => ['label' => 'Kata sandi', 'rules' => ($baru ? 'required' : 'permit_empty') . '|min_length[8]|max_length[72]'],
        ];
    }
}
