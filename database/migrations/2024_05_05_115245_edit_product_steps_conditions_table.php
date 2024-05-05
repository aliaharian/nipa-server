<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditProductStepsConditionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('product_steps_conditions', function (Blueprint $table) {
            //
            $column = Schema::hasColumn('product_steps_conditions', 'basic_data_item_id');

            if (!$column) {
                $table->unsignedBigInteger("basic_data_item_id")->index()->nullable()->after('form_field_option_id');
                $table->foreign('basic_data_item_id')->references('id')->on('basic_data_items')->onDelete('cascade');

            }
            $table->unsignedBigInteger("form_field_option_id")->index()->nullable()->change();

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
