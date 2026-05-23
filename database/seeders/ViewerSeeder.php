<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PGVirtual\Core\Models\Viewer;

class ViewerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      Viewer::firstOrCreate(
        [
          'macaddress' => 'viewer-net4media-dogs6',
          'monitor' => 1
        ],
        [
          'channel_id' => 1,
          'language_id' => 2,
          'user_id' => 1,
          'videoURL' => "http://localhost/dogs6sd/"
        ]
      );
      Viewer::firstOrCreate(
        [
          'macaddress' => 'viewer-net4media-horses6',
          'monitor' => 2
        ],
        [
          'channel_id' => 3,
          'language_id' => 2,
          'user_id' => 1,
          'videoURL' => "http://localhost/horses6/"
        ]
      );
    }
}
