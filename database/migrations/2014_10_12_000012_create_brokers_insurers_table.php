<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrokersInsurersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('brokers')){
            //Cadastro de corretores
            Schema::create('brokers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('broker_name',100);
                $table->string('broker_doc',100);
                $table->string('broker_cpf_cnpj',20)->nullable();
                $table->string('broker_alias',50);
                $table->string('broker_status',1)->nullable();
                $table->string('broker_col_user',100)->nullable();
                $table->string('broker_col_login',100)->nullable();
                $table->string('broker_col_senha',100)->nullable();
                $table->integer('account_id')->unsigned();
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
                
                $table->foreign('account_id')->references('id')->on('accounts');
            });
        }
        
        if(!Schema::hasTable('insurers')){
            //Cadastro de seguradoras
            Schema::create('insurers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('insurer_basename',50);
                $table->string('insurer_name',100);
                $table->string('insurer_razaosocial',100);
                $table->string('insurer_alias',50);
                $table->text('insurer_doc');
                $table->string('insurer_status',1);
                $table->text('insurer_find_rule')->nullable();
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
            });
        }
        
        if(!Schema::hasTable('brokers_insurers_data')){
            //Cadastro gerais entre corretores e seguradoras
            Schema::create('brokers_insurers_data', function (Blueprint $table) {
                $table->integer('broker_id')->unsigned()->nullable();
                $table->integer('insurer_id')->unsigned()->nullable();
                $table->string('meta_name',50);
                $table->mediumText('meta_value');
                
                $table->primary(['broker_id','insurer_id','meta_name']);//chave primária tripla
                $table->foreign('broker_id')->references('id')->on('brokers');
                $table->foreign('insurer_id')->references('id')->on('insurers');
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
        Schema::dropIfExists('brokers_insurers_data');
        Schema::dropIfExists('insurers');
        Schema::dropIfExists('brokers');
    }
}
