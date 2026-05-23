<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Channel;

class ChannelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Channel::firstOrCreate(
          ['name' => 'Ch1'],
            [
              'track_name' => 'Circuito a 6',
              'game_id' => 1,
              'isibet_controller_id' => 84101
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch2'],
            [
              'track_name' => 'Circuito a 6',
              'game_id' => 2,
              'isibet_controller_id' => 84101
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch3'],
            [
              'track_name' => 'Circuito a 6',
              'game_id' => 3,
              'isibet_controller_id' => 84101
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch4'],
            [
              'track_name' => 'Circuito a 8',
              'game_id' => 4,
              'isibet_controller_id' => 84101
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch5'],
            [
              'track_name' => 'Circuito a 8',
              'game_id' => 5,
              'isibet_controller_id' => 84101
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch6'],
            [
              'track_name' => 'Circuito a 8',
              'game_id' => 6,
              'isibet_controller_id' => 84101
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch7'],
            [
              'track_name' => 'Circuito a 6',
              'game_id' => 7,
              'isibet_controller_id' => 84102
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch8'],
            [
              'track_name' => 'Circuito a 6',
              'game_id' => 8,
              'isibet_controller_id' => 84102
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch9'],
            [
              'track_name' => 'Circuito a 6',
              'game_id' => 9,
              'isibet_controller_id' => 84102
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch10'],
            [
              'track_name' => 'Circuito a 8',
              'game_id' => 10,
              'isibet_controller_id' => 84102
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch11'],
            [
              'track_name' => 'Circuito a 8',
              'game_id' => 11,
              'isibet_controller_id' => 84102
            ]
        );
        Channel::firstOrCreate(
          ['name' => 'Ch12'],
            [
              'track_name' => 'Circuito a 8',
              'game_id' => 12,
              'isibet_controller_id' => 84102
            ]
        );
    }
}
