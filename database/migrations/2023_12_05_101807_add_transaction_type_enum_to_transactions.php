<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransactionTypeEnumToTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //add enum "refund" to transaction_type column
            //before was : Withdrawal, increaseBalance, increasCredit
            //this column alrady exists and i want to add one enum to it
            DB::statement("ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM('Withdrawal', 'increaseBalance', 'increaseCredit', 'refund')");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            //
        });
    }
}
