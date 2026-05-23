<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Game;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Game::firstOrCreate(
            [
            'id' => 1,
            'name' => 'dogs',
            'racers' => 6,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 2,
            'name' => 'horses',
            'racers' => 6,
            'rng' => 'Isibet2'
      ]
        );
    Game::firstOrCreate(
            [
            'id' => 3,
            'name' => 'harness',
            'racers' => 6,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 4,
            'name' => 'dogs',
            'racers' => 8,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 5,
            'name' => 'horses',
            'racers' => 8,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 6,
            'name' => 'harness',
            'racers' => 8,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 7,
            'name' => 'dogs',
            'racers' => 6,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 8,
            'name' => 'horses',
            'racers' => 6,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 9,
            'name' => 'harness',
            'racers' => 6,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 10,
            'name' => 'dogs',
            'racers' => 8,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 11,
            'name' => 'horses',
            'racers' => 8,
            'rng' => 'Isibet2'
      ]
        );
        Game::firstOrCreate(
            [
            'id' => 12,
            'name' => 'harness',
            'racers' => 8,
            'rng' => 'Isibet2'
      ]
        );
    }
}
