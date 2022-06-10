<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRobotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('robots')){
            //Cadastro de corretores
            Schema::create('robots', function (Blueprint $table) {
                $table->increments('id');
                $table->string('robot_name',100);
                $table->string('robot_name_cli',100);
                $table->string('robot_status',1)->nullable();
                $table->text('robot_config',1)->nullable();
                $table->string('key_active',100)->nullable();
                $table->string('key_robot',100)->nullable();
                $table->dateTime('conn_last')->nullable();
                $table->integer('account_id')->unsigned();
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
                
                $table->foreign('account_id')->references('id')->on('accounts');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('robots');
    }
}
