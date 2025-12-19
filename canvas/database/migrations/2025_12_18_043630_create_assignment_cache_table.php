<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_cache', function (Blueprint $table) {
            $table->unsignedBigInteger('canvas_assignment_id')->primary();
            $table->unsignedBigInteger('canvas_course_id');

            $table->string('name');
            $table->timestamp('due_at')->nullable();

            $table->timestamp('last_synced_at')->nullable();

            $table->foreign('canvas_course_id')
                  ->references('canvas_course_id')
                  ->on('course_cache')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignment_cache');
    }
}
