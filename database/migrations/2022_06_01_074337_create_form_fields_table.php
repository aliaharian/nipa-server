<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('label')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('helper_text')->nullable();
            $table->json('validation')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('min')->default(0);
            $table->unsignedInteger('max')->default(100);
            
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
        Schema::dropIfExists('form_fields');
    }
}
