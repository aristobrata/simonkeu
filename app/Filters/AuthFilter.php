<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Mewajibkan pengguna sudah masuk (login). */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('user')) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Sesi berakhir. Silakan masuk kembali.']);
        }

        if (strtolower($request->getMethod()) === 'get') {
            session()->set('url_tujuan', (string) current_url(true));
        }

        return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
