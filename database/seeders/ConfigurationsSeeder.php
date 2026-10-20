<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationsSeeder extends Seeder
{
    /**
     * Rows exported from table `configurations`.
     */
    private $rows = [
        ['id' => 1, 'key' => 'WIN_MAX', 'value' => '10000', 'operator_id' => 1, 'currency_id' => 1, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 2, 'key' => 'INCREMENT_STEP', 'value' => '0.5', 'operator_id' => 1, 'currency_id' => 1, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 3, 'key' => 'BET_MIN', 'value' => '1', 'operator_id' => 1, 'currency_id' => 1, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 4, 'key' => 'STAKE_MIN', 'value' => '0.05', 'operator_id' => 1, 'currency_id' => 1, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 5, 'key' => 'EVENT_CLOSES_IN_SECONDS', 'value' => '5', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 1, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 6, 'key' => 'EVENT_CLOSES_IN_SECONDS', 'value' => '5', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 2, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 7, 'key' => 'PREV_EVENTS_SHOW', 'value' => '10', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 1, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 8, 'key' => 'PREV_EVENTS_SHOW', 'value' => '10', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 2, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 9, 'key' => 'NEXT_EVENTS_SHOW', 'value' => '10', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 1, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 10, 'key' => 'NEXT_EVENTS_SHOW', 'value' => '10', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 2, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 11, 'key' => 'PAGE_MAIN_DURATION', 'value' => '20', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 12, 'key' => 'PAGE_TRIFECTA_DURATION', 'value' => '10', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 13, 'key' => 'PAGE_RESULTS_DURATION', 'value' => '30', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 14, 'key' => 'PAGE_MAIN_DURATION', 'value' => '20', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 15, 'key' => 'PAGE_TRIFECTA_DURATION', 'value' => '10', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 16, 'key' => 'PAGE_RESULTS_DURATION', 'value' => '30', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 23, 'key' => 'PODIUM_LENGTH', 'value' => '3', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 1, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 24, 'key' => 'PODIUM_LENGTH', 'value' => '3', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 2, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 25, 'key' => 'STREAM_TYPE', 'value' => 'embedded', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 26, 'key' => 'STREAM_URL', 'value' => 'http://192.168.1.53:8080/', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 27, 'key' => 'STREAM_TYPE', 'value' => 'embedded', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 28, 'key' => 'STREAM_URL', 'value' => 'http://192.168.1.53:8080/', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 33, 'key' => 'MULTIPLIER_ENABLED', 'value' => 'false', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 34, 'key' => 'MULTIPLIER_ENABLED', 'value' => 'false', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 37, 'key' => 'MULTIPLIER_GROWTH_RATE_X2', 'value' => '0.4', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 38, 'key' => 'MULTIPLIER_GROWTH_RATE_X3', 'value' => '0.6', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:05', 'updated_at' => '2024-07-13 03:49:05'],
        ['id' => 39, 'key' => 'MULTIPLIER_WIN_RATE_X2', 'value' => '2.22', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 40, 'key' => 'MULTIPLIER_WIN_RATE_X3', 'value' => '0.55', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 41, 'key' => 'ENABLED_CHANNELS_FOR_OPERATOR', 'value' => 'true', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 42, 'key' => 'DAY_CONFIG', 'value' => '{"firstEventTime":0, "eventDuration":240, "eventsCount":360}', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 43, 'key' => 'ENABLED_CHANNELS_FOR_OPERATOR', 'value' => 'true', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 44, 'key' => 'DAY_CONFIG', 'value' => '{"firstEventTime":0, "eventDuration":240, "eventsCount":360}', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 45, 'key' => 'ISIBET_ENDPOINT', 'value' => 'http://192.168.10.116:8082api/virtual', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => null, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 46, 'key' => 'STREAMING_VIDEO_URL', 'value' => 'http://localhost/dogs6sd', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 1, 'channel_id' => 1, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 47, 'key' => 'STREAMING_VIDEO_URL', 'value' => 'http://localhost/horses6', 'operator_id' => 1, 'currency_id' => null, 'game_id' => 2, 'channel_id' => 2, 'created_at' => '2024-07-13 03:49:06', 'updated_at' => '2024-07-13 03:49:06'],
        ['id' => 57, 'key' => 'ISIBET_LOG', 'value' => 'true', 'operator_id' => 1, 'currency_id' => null, 'game_id' => null, 'channel_id' => null, 'created_at' => null, 'updated_at' => null],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (array_chunk($this->rows, 200) as $chunk) {
            DB::table('configurations')->upsert($chunk, ['id']);
        }
    }
}
