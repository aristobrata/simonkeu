<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('user')) {
            return redirect()->to('/');
        }

        return view('auth/login');
    }

    public function attempt()
    {
        // Batasi percobaan masuk: 8 kali per menit per alamat IP.
        $throttler = service('throttler');
        if (! $throttler->check('login_' . md5($this->request->getIPAddress()), 8, MINUTE)) {
            return redirect()->to('/login')->withInput()->with('error', 'Terlalu banyak percobaan masuk. Coba lagi dalam satu menit.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $users = new UserModel();
        $user  = $username !== '' ? $users->where('username', $username)->first() : null;

        if (! $user || ! (int) $user['aktif'] || ! password_verify($password, $user['password_hash'])) {
            AuditLogModel::catat('login_gagal', 'users', null, 'Percobaan masuk gagal untuk "' . mb_substr($username, 0, 50) . '"');

            return redirect()->to('/login')->withInput()->with('error', 'Nama pengguna atau kata sandi salah.');
        }

        $this->session->regenerate(true);
        $this->session->set('user', [
            'id' => (int) $user['id'], 'nama' => $user['nama'], 'username' => $user['username'], 'role' => $user['role'],
        ]);
        $users->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
        AuditLogModel::catat('login', 'users', (int) $user['id'], 'Masuk ke aplikasi');

        $tujuan = $this->session->get('url_tujuan');
        $this->session->remove('url_tujuan');

        return redirect()->to($tujuan ?: '/');
    }

    public function logout()
    {
        AuditLogModel::catat('logout', 'users', session()->get('user')['id'] ?? null, 'Keluar dari aplikasi');
        $this->session->destroy();

        return redirect()->to('/login')->with('success', 'Anda telah keluar.');
    }
}
