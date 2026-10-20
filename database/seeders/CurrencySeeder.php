<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Rows exported from table `currencies`.
     */
    private $rows = [
        ['id' => 1, 'name' => 'EUR'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('currencies')->upsert($chunk, ['id']);
        }
    }
}
