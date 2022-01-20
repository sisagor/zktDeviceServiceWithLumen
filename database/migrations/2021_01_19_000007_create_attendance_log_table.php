<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('attendance_log')) {
            Schema::create('attendance_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('teacher_id')->nullable();
                $table->integer('student_id')->nullable();
                $table->integer('employee_id')->nullable();
                $table->integer('type')->nullable();
                $table->integer('state')->nullable();
                $table->timestamp('punch_time')->nullable();
                $table->tinyInteger('status')->default(0)->nullable();
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
