<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Multiplier;

class MultiplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      Multiplier::firstOrCreate([
        'operator_id' => 1,
        'currency_id' => 1,
        'multiplier' => 2,
        'amount' => 0,
        'growth_rate' => 0.4//,
        //'win_rate' => 2.22
      ]);
      Multiplier::firstOrCreate([
        'operator_id' => 1,
        'currency_id' => 1,
        'multiplier' => 3,
        'amount' => 0,
        'growth_rate' => 0.6//,
        //'win_rate' => 0.55
      ]);
    }
}
