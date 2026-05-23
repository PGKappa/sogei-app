<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\UserLevel;


class UserLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserLevel::firstOrCreate(['id' => 1,'name' => 'player']);
        UserLevel::firstOrCreate(['id' => 2,'name' => 'self']);
        UserLevel::firstOrCreate(['id' => 3,'name' => 'selfap']);
        UserLevel::firstOrCreate(['id' => 4,'name' => 'operator']);
    }
}
