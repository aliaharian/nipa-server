<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOriginFormIdToFormFieldForms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_field_forms', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('form_field_forms', 'origin_form_id')) {
                $table->unsignedBigInteger('origin_form_id')->after('form_id')->nullable();
                $table->foreign('origin_form_id')->references('id')->on('forms')->onDelete('set null');

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
        Schema::table('form_field_forms', function (Blueprint $table) {
            //
        });
    }
}