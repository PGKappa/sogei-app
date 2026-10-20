<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserLevelsSeeder extends Seeder
{
    /**
     * Rows exported from table `user_levels`.
     */
    private $rows = [
        ['id' => 1, 'name' => 'player'],
        ['id' => 2, 'name' => 'self'],
        ['id' => 3, 'name' => 'selfap'],
        ['id' => 4, 'name' => 'operator'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('user_levels')->upsert($chunk, ['id']);
        }
    }
}
