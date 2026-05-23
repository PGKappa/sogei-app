<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PGVirtual\Core\Models\OperatorLevel;

class OperatorLevelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('operator_levels', function (Blueprint $table) {
            $table->id();
            $table->integer('operator_id');
            $table->integer('level_id');
            $table->timestamps();
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
