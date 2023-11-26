<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('payment_method', ['online', 'offline']);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('status_id')->index();
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->enum('transaction_type', [
                "increaseBalance",
                "increaseCredit",
                "Withdrawal"
            ]); //increaseBalance , increaseCredit , Withdrawal
            $table->boolean('isValid')->default(1); //to check to be counted in table and result
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('status_id')->references('id')->on('transaction_statuses')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
