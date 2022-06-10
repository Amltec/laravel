<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountPassTable extends Migration{
    
    public function up(){
        if(!Schema::hasTable('account_pass')){
            //Cadastro de corretores
            Schema::create('account_pass', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned();
                $table->string('pass_area',10)->nullable();
                $table->string('pass_user',50)->nullable();
                $table->string('pass_login',50)->nullable();
                $table->string('pass_pass',255)->nullable();
                $table->string('pass_status',1)->nullable();
                $table->string('pass_type',1)->nullable();
                $table->string('status_code',7)->nullable();
                $table->timestamp('acessed_at')->nullable();
                $table->timestamp('created_at')->nullable();
                
                $table->foreign('account_id')->references('id')->on('accounts');
            });
        }
    }
    
    public function down(){
        Schema::dropIfExists('account_pass');
    }
}