<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelsSeeder extends Seeder
{
    /**
     * Rows exported from table `channels`.
     */
    private $rows = [
        ['id' => 1, 'name' => 'Ch1', 'track_name' => 'Cinodromo Marconi - 6', 'game_id' => 1, 'isibet_controller_id' => 84101, 'controller_event_duration' => 300],
        ['id' => 2, 'name' => 'Ch2', 'track_name' => 'Gran Premio Da Vinci', 'game_id' => 2, 'isibet_controller_id' => 84102, 'controller_event_duration' => 300],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('channels')->upsert($chunk, ['id']);
        }
    }
}
