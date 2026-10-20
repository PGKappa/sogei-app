<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    /**
     * Rows exported from table `languages`.
     */
    private $rows = [
        ['id' => 1, 'name' => 'it-IT'],
        ['id' => 2, 'name' => 'en-US'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('languages')->upsert($chunk, ['id']);
        }
    }
}
