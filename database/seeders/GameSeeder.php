<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameSeeder extends Seeder
{
    /**
     * Rows exported from table `games`.
     */
    private $rows = [
        ['id' => 1, 'name' => 'dogs', 'racers' => 6, 'rng' => 'Isibet2'],
        ['id' => 2, 'name' => 'horses', 'racers' => 6, 'rng' => 'Isibet2'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('games')->upsert($chunk, ['id']);
        }
    }
}
