<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrCadApoliceDataTable extends Migration{
    
    public function up(){
        if(!Schema::hasTable('pr_cad_apolice_data')){
            //Cadastro de corretores
            Schema::create('pr_cad_apolice_data', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->smallInteger('num')->unsigned();
                $table->string('meta_name',20);
                $table->text('meta_value');
                
                $table->primary(['process_id','num','meta_name']);//chave primária tripla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
    }
    
    public function down(){
        Schema::dropIfExists('pr_cad_apolice_data');
    }
}