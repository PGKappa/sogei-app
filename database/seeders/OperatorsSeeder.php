<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperatorsSeeder extends Seeder
{
    /**
     * Rows exported from table `operators`.
     */
    private $rows = [
        ['id' => 1, 'name' => 'pg', 'opts' => '{"whitelistedIPs":["127.0.0.1"]}', 'callback' => 'http://pgv/api', 'authkey' => '66c3a015ef3b279932e3d7111df388e4', 'status' => 1],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('operators')->upsert($chunk, ['id']);
        }
    }
}
