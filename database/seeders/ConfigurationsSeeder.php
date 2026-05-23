<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\Configuration;

class ConfigurationsSeeder extends Seeder
{

      private $configs = [
            [
                  'key' => 'WIN_MAX',
                  'value' => 10000,
                  'operator_id' => 1,
                  'currency_id' => 1,
            ],
            [
                  'key' => 'INCREMENT_STEP',
                  'value' => 0.5,
                  'operator_id' => 1,
                  'currency_id' => 1,
            ],
            [
                  'key' => 'BET_MIN',
                  'value' => 1,
                  'operator_id' => 1,
                  'currency_id' => 1,
            ],
            [
                  'key' => 'STAKE_MIN',
                  'value' => 0.05,
                  'operator_id' => 1,
                  'currency_id' => 1,
            ],
            [
                  'key' => 'EVENT_CLOSES_IN_SECONDS',
                  'value' => 5,
                  'operator_id' => 1,
                  'game_id' => 1,
            ],
            [
                  'key' => 'EVENT_CLOSES_IN_SECONDS',
                  'value' => 5,
                  'operator_id' => 1,
                  'game_id' => 2,
            ],
            [
                  'key' => 'PREV_EVENTS_SHOW',
                  'value' => 10,
                  'game_id' => 1,
            ],
            [
                  'key' => 'PREV_EVENTS_SHOW',
                  'value' => 10,
                  'game_id' => 2,
            ],
            [
                  'key' => 'NEXT_EVENTS_SHOW',
                  'value' => 10,
                  'game_id' => 1,
            ],
            [
                  'key' => 'NEXT_EVENTS_SHOW',
                  'value' => 10,
                  'game_id' => 2,
            ],
            [
                  'key' => 'PAGE_MAIN_DURATION',
                  'value' => 20,
                  'operator_id' => 1,
                  'channel_id' => 1,
            ],
            [
                  'key' => 'PAGE_TRIFECTA_DURATION',
                  'value' => 10,
                  'operator_id' => 1,
                  'channel_id' => 1,
            ],
            [
                  'key' => 'PAGE_RESULTS_DURATION',
                  'value' => 15,
                  'operator_id' => 1,
                  'channel_id' => 1,
            ],
            [
                  'key' => 'PAGE_MAIN_DURATION',
                  'value' => 20,
                  'operator_id' => 1,
                  'channel_id' => 2,
            ],
            [
                  'key' => 'PAGE_TRIFECTA_DURATION',
                  'value' => 10,
                  'operator_id' => 1,
                  'channel_id' => 2,
            ],
            [
                  'key' => 'PAGE_RESULTS_DURATION',
                  'value' => 15,
                  'operator_id' => 1,
                  'channel_id' => 2,
            ],
            [
                  'key' => 'PAGE_MAIN_DURATION',
                  'value' => 20,
                  'operator_id' => 1,
                  'channel_id' => 3,
            ],
            [
                  'key' => 'PAGE_TRIFECTA_DURATION',
                  'value' => 10,
                  'operator_id' => 1,
                  'channel_id' => 3,
            ],
            [
                  'key' => 'PAGE_RESULTS_DURATION',
                  'value' => 15,
                  'operator_id' => 1,
                  'channel_id' => 3,
            ],
            [
                  'key' => 'PAGE_MAIN_DURATION',
                  'value' => 20,
                  'operator_id' => 1,
                  'channel_id' => 4,
            ],
            [
                  'key' => 'PAGE_TRIFECTA_DURATION',
                  'value' => 10,
                  'operator_id' => 1,
                  'channel_id' => 4,
            ],
            [
                  'key' => 'PAGE_RESULTS_DURATION',
                  'value' => 15,
                  'operator_id' => 1,
                  'channel_id' => 4,
            ],
            [
                  'key' => 'PODIUM_LENGTH',
                  'value' => 3,
                  'game_id' => 1,
            ],
            [
                  'key' => 'PODIUM_LENGTH',
                  'value' => 3,
                  'game_id' => 2,
            ],
            [
                  'key' => 'STREAM_TYPE',
                  'value' => 'embedded',
                  'operator_id' => 1,
                  'channel_id' => 1,
            ],
            [
                  'key' => 'STREAM_URL',
                  'value' => 'http://192.168.1.53:8080/',
                  'operator_id' => 1,
                  'channel_id' => 1,
            ],
            [
                  'key' => 'STREAM_TYPE',
                  'value' => 'embedded',
                  'operator_id' => 1,
                  'channel_id' => 2,
            ],
            [
                  'key' => 'STREAM_URL',
                  'value' => 'http://192.168.1.53:8080/',
                  'operator_id' => 1,
                  'channel_id' => 2,
            ],
            [
                  'key' => 'STREAM_TYPE',
                  'value' => 'embedded',
                  'operator_id' => 1,
                  'channel_id' => 3,
            ],
            [
                  'key' => 'STREAM_URL',
                  'value' => 'http://192.168.1.53:8080/',
                  'operator_id' => 1,
                  'channel_id' => 3,
            ],
            [
                  'key' => 'STREAM_TYPE',
                  'value' => 'embedded',
                  'operator_id' => 1,
                  'channel_id' => 4,
            ],
            [
                  'key' => 'STREAM_URL',
                  'value' => 'http://192.168.1.53:8080/',
                  'operator_id' => 1,
                  'channel_id' => 4,
            ],
            [
                  'key' => 'MULTIPLIER_ENABLED',
                  'value' => 'false',
                  'channel_id' => 1,
            ],
            [
                  'key' => 'MULTIPLIER_ENABLED',
                  'value' => 'false',
                  'channel_id' => 2,
            ],
            [
                  'key' => 'MULTIPLIER_ENABLED',
                  'value' => 'false',
                  'channel_id' => 3,
            ],
            [
                  'key' => 'MULTIPLIER_ENABLED',
                  'value' => 'false',
                  'channel_id' => 4,
            ],
            [
                  'key' => 'MULTIPLIER_GROWTH_RATE_X2',
                  'value' => '0.4',
            ],
            [
                  'key' => 'MULTIPLIER_GROWTH_RATE_X3',
                  'value' => '0.6',
            ],
            [
                  'key' => 'MULTIPLIER_WIN_RATE_X2',
                  'value' => '2.22',
            ],
            [
                  'key' => 'MULTIPLIER_WIN_RATE_X3',
                  'value' => '0.55',
            ],
            [
                  'key' => 'ENABLED_CHANNELS_FOR_OPERATOR',
                  'operator_id' => 1,
                  'channel_id' => 1,
                  'value' => 'true'      
            ],
            [
                  'key' => 'DAY_CONFIG',
                  'operator_id' => 1,
                  'channel_id' => 1,
                  'value' => '{"firstEventTime":0, "eventDuration":240, "eventsCount":360}'
            ]
      ];
      /**
       * Run the database seeds.
       *
       * @return void
       */
      public function run()
      {
            foreach ($this->configs as $config) {
                  Configuration::firstOrCreate($config);
           }
      }
}
