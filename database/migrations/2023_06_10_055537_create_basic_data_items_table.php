<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBasicDataItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('basic_data_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('basic_data_id')->index();

            $table->string('code');
            $table->string('name');
            $table->boolean('status')->default(1);

            $table->timestamps();

            $table->foreign('basic_data_id')->references('id')->on('basic_data')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('basic_data_items');
    }
}