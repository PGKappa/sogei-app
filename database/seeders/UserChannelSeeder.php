<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserChannelSeeder extends Seeder
{
    /**
     * Rows exported from table `user_channel_links`.
     */
    private $rows = [
        ['id' => 1, 'user_id' => 1, 'channel_id' => 1],
        ['id' => 3, 'user_id' => 1, 'channel_id' => 2],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('user_channel_links')->upsert($chunk, ['id']);
        }
    }
}
