<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;

class Profil extends BaseController
{
    public function index()
    {
        return view('profil/index');
    }

    public function password()
    {
        $rules = [
            'password_lama' => ['label' => 'Kata sandi saat ini', 'rules' => 'required'],
            'password_baru' => ['label' => 'Kata sandi baru', 'rules' => 'required|min_length[8]|max_length[72]'],
            'konfirmasi'    => ['label' => 'Konfirmasi', 'rules' => 'required|matches[password_baru]'],
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $users = new UserModel();
        $user  = $users->find(session()->get('user')['id']);
        if (! $user || ! password_verify((string) $this->request->getPost('password_lama'), $user['password_hash'])) {
            return redirect()->back()->with('errors', ['password_lama' => 'Kata sandi saat ini salah.']);
        }

        $users->update($user['id'], ['password_hash' => password_hash((string) $this->request->getPost('password_baru'), PASSWORD_DEFAULT)]);
        AuditLogModel::catat('ubah_password', 'users', (int) $user['id'], 'Mengubah kata sandi sendiri');

        return redirect()->to('/profil')->with('success', 'Kata sandi berhasil diubah.');
    }
}
