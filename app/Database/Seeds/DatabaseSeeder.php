<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** Jalankan:  php spark db:seed DatabaseSeeder */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('MasterSeeder');
        $this->call('TransaksiSeeder');
    }
}
