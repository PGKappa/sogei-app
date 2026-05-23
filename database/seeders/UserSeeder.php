<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      User::firstOrCreate(
        [
          'name' => '~viewer-net4media',
          'operator_id' => 1
        ],
        [
          'password' => md5('viewer-net4media'),
          'level' => 1,
          'timezone' => "Europe/Rome",
          'language_id' => 2,
          'currency_id' => 1,
          'status' => 1
        ]
      );
    }
}
