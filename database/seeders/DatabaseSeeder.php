<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserLevelsSeeder::class);
        $this->call(GameSeeder::class);
        $this->call(VideosSeeder::class);
        $this->call(ChannelsSeeder::class);
        $this->call(RacersRegistrySeeder::class);
        $this->call(OperatorsSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ConfigurationsSeeder::class);
        $this->call(UserChannelSeeder::class);
        $this->call(MultiplierSeeder::class);
        $this->call(ViewerSeeder::class);
    }
}
