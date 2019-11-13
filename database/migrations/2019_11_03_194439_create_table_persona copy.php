<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePersonaCopy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persona', function (Blueprint $table) {
            $table->increments('id');
            $table->text('cuil');
            $table->integer('estado');
        });

    



    for($i=20000000;$i<50000000;$i++){
        $estado=rand(1,5);
        $cuil="20-".$i."-2";
            DB::table('persona')->insert(
                 ['cuil'=>$cuil,'estado'=>$estado]

        
        
     );
    }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('persona');
    }
}
