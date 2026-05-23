<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Language::firstOrCreate(
            [
          'id' => 1,
          'name' => 'it-IT'
    ]
        );
        Language::firstOrCreate(
            [
          'id' => 2,
          'name' => 'en-US'
        ]
        );
    }
}
