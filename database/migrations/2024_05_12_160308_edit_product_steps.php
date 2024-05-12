<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditProductSteps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('product_steps', function (Blueprint $table) {
            $table->dropForeign(['parent_step_id']);
            $table->dropColumn('parent_step_id');

            $table->unsignedBigInteger('next_step_id')->after("step_name")->nullable();

            $table->foreign('next_step_id')->references('id')->on('product_steps')->onDelete('set null');

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
