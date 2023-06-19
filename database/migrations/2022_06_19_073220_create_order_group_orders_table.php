<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderGroupOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_group_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_group_id')->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->timestamps();

            $table->foreign('order_group_id')->references('id')->on('order_groups')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_group_orders');
    }
}
