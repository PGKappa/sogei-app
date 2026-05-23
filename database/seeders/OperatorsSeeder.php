<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Operator;

class OperatorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array['whitelistedIPs'][0] = '127.0.0.1';

        Operator::firstOrCreate(
          ['name' => 'pg'],
          ['status' => 1,
          'opts' => json_encode($array),
          'callback' => 'http://pgv/api',
          'authkey' => '66c3a015ef3b279932e3d7111df388e4' //md5(rand(101000, 999999))
        ]);

        
    }
}
