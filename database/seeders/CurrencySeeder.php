<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Currency::firstOrCreate(
            [
           'name' => 'EUR'
            ]
        );
    }
}
