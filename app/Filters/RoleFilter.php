<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pembatasan peran. Contoh pemakaian pada route:  ['filter' => 'role:admin,operator']
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = session()->get('user')['role'] ?? null;

        if ($role !== null && in_array($role, (array) $arguments, true)) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'Anda tidak memiliki akses.']);
        }

        return redirect()->to('/')->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
