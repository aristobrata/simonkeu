<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\TransaksiModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Antrean konfirmasi status "Lunas" yang diajukan operator (menunggu keputusan admin).
 * Hanya bisa diakses administrator (dibatasi lewat filter role di Routes.php).
 */
class Konfirmasi extends BaseController
{
    public function index()
    {
        $m = new TransaksiModel();

        $rows = $m->select('transaksi.*, j.nama AS jenis, u.nama AS diajukan_nama')
            ->join('jenis_aktivitas j', 'j.id = transaksi.jenis_aktivitas_id')
            ->join('users u', 'u.id = transaksi.bukti_pembayaran_oleh', 'left')
            ->where('transaksi.lunas_menunggu', 1)
            ->orderBy('transaksi.bukti_pembayaran_at', 'ASC')
            ->findAll();

        return view('konfirmasi/index', ['rows' => $rows]);
    }

    public function setujui(int $id)
    {
        $m    = new TransaksiModel();
        $row  = $m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');

        if (! (int) $row['lunas_menunggu']) {
            return redirect()->to('/konfirmasi')->with('error', 'Transaksi ini tidak sedang menunggu konfirmasi.');
        }

        $m->update($id, [
            'status_pembayaran'     => 'Lunas',
            'lunas_menunggu'        => 0,
            'lunas_konfirmasi_oleh' => current_user()['id'] ?? null,
            'lunas_konfirmasi_at'   => date('Y-m-d H:i:s'),
            'lunas_ditolak_alasan'  => null,
        ]);
        AuditLogModel::catat('konfirmasi_lunas', 'transaksi', $id, $row['aktivitas'] . ' — disetujui, status menjadi Lunas.');

        return redirect()->to('/konfirmasi')->with('success', 'Status "Lunas" disetujui untuk "' . mb_strimwidth($row['aktivitas'], 0, 60, '…') . '".');
    }

    public function tolak(int $id)
    {
        $m   = new TransaksiModel();
        $row = $m->find($id) ?? throw PageNotFoundException::forPageNotFound('Transaksi tidak ditemukan.');

        if (! (int) $row['lunas_menunggu']) {
            return redirect()->to('/konfirmasi')->with('error', 'Transaksi ini tidak sedang menunggu konfirmasi.');
        }

        $alasan = trim((string) $this->request->getPost('alasan'));
        if ($alasan === '') {
            return redirect()->to('/konfirmasi')->with('error', 'Sertakan alasan penolakan.');
        }

        $m->update($id, [
            'lunas_menunggu'       => 0,
            'lunas_ditolak_alasan' => $alasan,
        ]);
        AuditLogModel::catat('tolak_lunas', 'transaksi', $id, $row['aktivitas'] . ' — pengajuan Lunas ditolak: ' . $alasan);

        return redirect()->to('/konfirmasi')->with('success', 'Pengajuan "Lunas" untuk "' . mb_strimwidth($row['aktivitas'], 0, 60, '…') . '" ditolak.');
    }
}
