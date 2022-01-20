<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('school_id')->nullable();
                $table->string('type')->nullable();
                $table->string('ip')->nullable();
                $table->tinyInteger('status')->nullable();
                $table->timestamp('create_at')->nullable();
                $table->timestamp('modified_at')->nullable();
                $table->tinyInteger('created_by')->default(0)->nullable();
                $table->tinyInteger('modified_by')->default(0)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('attenddance_log');
    }
}
