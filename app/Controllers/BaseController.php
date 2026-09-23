<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /** Helper yang dimuat untuk semua controller & view. */
    protected $helpers = ['form', 'url'];

    protected $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->session = service('session');
    }

    /** Pembantu: kirim respons file (unduhan) dari string biner. */
    protected function kirimFile(string $isi, string $namaFile, string $mime): ResponseInterface
    {
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
            ->setHeader('Content-Length', (string) strlen($isi))
            ->setBody($isi);
    }
}
