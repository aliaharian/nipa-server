<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentStepColumnProductStepsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_steps', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_step_id')->after('step_name')->nullable()->index();

            $table->foreign('parent_step_id')->references('id')->on('product_steps')->onDelete('cascade');
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
