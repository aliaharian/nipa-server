<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBasicDataIdToFormConditions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_conditions', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('form_conditions', 'basic_data_item_id')) {
                $table->unsignedBigInteger('basic_data_item_id')->after('operation')->nullable();
                $table->foreign('basic_data_item_id')->references('id')->on('basic_data_items')->onDelete('cascade');

            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_conditions', function (Blueprint $table) {
            //
        });
    }
}