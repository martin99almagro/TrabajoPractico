<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cuota_request', function (Blueprint $table) {
            $table->increments('id');
            $table->int('max')->default(5000);
            $table->int('actual')->default(0);
            $table->int('user_id');
        });

        DB::table('cuota_request')->insert([
                ['user_id'=>1]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_users');
    }
}
