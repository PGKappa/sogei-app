<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPalinsestoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('palinsestos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('channel_id');
            $table->time('orario');
            $table->integer('durata');
            $table->string('posizione', 1)->default('I');
            $table->string('giorno',1)->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
