<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_wallets', function (Blueprint $table) {
            $table->id();
            //user_id , balance , credit , blocked , active , meta
            $table->unsignedBigInteger('user_id');
            $table->integer('balance')->default(0);
            $table->integer('credit')->default(0);
            $table->integer('blocked')->default(0);
            $table->boolean('active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            //foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_wallets');
    }
}
