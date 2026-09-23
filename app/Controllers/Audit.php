<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

/** Log aktivitas (khusus administrator). */
class Audit extends BaseController
{
    public function index()
    {
        $m = new AuditLogModel();
        $f = [
            'aksi'    => (string) $this->request->getGet('aksi'),
            'entitas' => (string) $this->request->getGet('entitas'),
            'user'    => trim((string) $this->request->getGet('user')),
        ];
        if ($f['aksi'] !== '') {
            $m->where('aksi', $f['aksi']);
        }
        if ($f['entitas'] !== '') {
            $m->like('entitas', $f['entitas'], 'after');
        }
        if ($f['user'] !== '') {
            $m->where('username', $f['user']);
        }

        $rows = $m->orderBy('id', 'DESC')->paginate(30);

        return view('audit/index', [
            'rows'  => $rows,
            'pager' => $m->pager,
            'f'     => $f,
            'aksi'  => array_column($m->select('aksi')->distinct()->orderBy('aksi')->findAll(), 'aksi'),
        ]);
    }
}
