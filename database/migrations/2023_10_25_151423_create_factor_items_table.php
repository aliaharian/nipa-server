<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFactorItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('factor_items', function (Blueprint $table) {
            $table->id();
            //factor id
            $table->unsignedBigInteger('factor_id');
            $table->foreign('factor_id')->references('id')->on('factors');
            //order id if this item is from orders and it can be null
            $table->unsignedBigInteger('order_id')->nullable();
            $table->foreign('order_id')->references('id')->on('orders');
            //product id if this item is from products and it can be null
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products');
            //code and name and count type(m2 or quantity)if item from non of above
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->enum('count_type', ['m2', 'quantity'])->default('quantity');

            //width and height if count type is m2
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->integer('count')->nullable();
            //unit price
            $table->integer('unit_price')->nullable();
            //total price is unit price * count* width * height or unit price * count
            //off price
            $table->integer('off_price')->nullable();
            //additional price
            $table->integer('additional_price')->nullable();
            //final price is total price - off price + additional price
            //description
            $table->string('description')->nullable();

            
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
        Schema::dropIfExists('factor_items');
    }
}
