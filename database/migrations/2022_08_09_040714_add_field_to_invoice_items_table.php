<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToInvoiceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->after('id')->nullable();
            $table->string('width')->after('price')->nullable();
            $table->string('length')->after('price')->nullable();
            $table->string('area')->after('price')->nullable();
            $table->string('off_percent')->after('price')->nullable();
            $table->string('description')->after('price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            //
        });
    }
}
