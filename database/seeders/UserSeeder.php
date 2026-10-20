<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Rows exported from table `users`.
     */
    private $rows = [
        ['id' => 1, 'name' => '~viewer-net4media', 'password' => 'cdc421206e0b5e42ee9bbc09bf7a9fc1', 'group_id' => null, 'level' => 1, 'operator_id' => 1, 'timezone' => 'Europe/Rome', 'language_id' => 1, 'currency_id' => 1, 'status' => '1', 'remember_token' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05', 'deleted_at' => null],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('users')->upsert($chunk, ['id']);
        }
    }
}
