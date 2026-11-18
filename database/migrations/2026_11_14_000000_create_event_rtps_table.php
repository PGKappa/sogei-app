<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventRtpsTable extends Migration
{
    public function up()
    {
        Schema::create('event_rtps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->unique();
            $table->unsignedBigInteger('game_id');
            $table->text('rtp');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_rtps');
    }
}
