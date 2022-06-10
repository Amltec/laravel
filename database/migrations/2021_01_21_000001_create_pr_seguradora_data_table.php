<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrSeguradoraDataTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        if(!Schema::hasTable('pr_seguradora_data')){
            //Cadastro de corretores
            Schema::create('pr_seguradora_data', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->bigInteger('process_rel_id')->unsigned();
                $table->string('process_prod',20);
                $table->string('status',1)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->primary(['process_id','process_rel_id','process_prod'],'pr_seguradora_data_pk_id');//chave primária tripla  //obs: foi setado o nome da chave 'pr_seguradora_data_pk_id', pois o automático do laravel ficou muito longo
                
                $table->foreign('process_id')->references('id')->on('process_robot');
                $table->foreign('process_rel_id')->references('id')->on('process_robot');
            });
        }
    }
    
       
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('pr_seguradora_data');
    }
}
