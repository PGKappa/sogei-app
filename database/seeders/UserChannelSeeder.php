<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;
use PGVirtual\Core\Models\UserChannelLink;

class UserChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '1',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '3',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '2',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '4',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '5',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '6',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '7',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '8',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '9',
    ]);
    UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '10',
    ]);
     UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '11',
    ]);
     UserChannelLink::firstOrCreate(
    [
        'user_id' => '1',
        'channel_id' => '12',
    ]);
    }
}
