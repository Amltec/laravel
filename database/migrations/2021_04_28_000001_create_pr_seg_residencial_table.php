<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrSegResidencialTable extends Migration{
    
    public function up(){
        if(!Schema::hasTable('pr_seg_residencial')){
            //ramo: residencial
            Schema::create('pr_seg_residencial', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('num')->unsigned();
                $table->string('residencial_endereco',50)->nullable();
                $table->string('residencial_numero',5)->nullable();
                $table->string('residencial_compl',20)->nullable();
                $table->string('residencial_bairro',20)->nullable();
                $table->string('residencial_cidade',20)->nullable();
                $table->string('residencial_uf',2)->nullable();
                $table->string('residencial_cep',9)->nullable();
                
                $table->primary(['process_id','num']);//chave primária tripla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        if(!Schema::hasTable('pr_seg_residencial__s')){
            //ramo: residencial
            Schema::create('pr_seg_residencial__s', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('ctrl')->unsigned();
                $table->tinyInteger('num')->unsigned();
                $table->tinyInteger('residencial_endereco')->nullable()->default(0);
                $table->tinyInteger('residencial_numero')->nullable()->default(0);
                $table->tinyInteger('residencial_compl')->nullable()->default(0);
                $table->tinyInteger('residencial_bairro')->nullable()->default(0);
                $table->tinyInteger('residencial_cidade')->nullable()->default(0);
                $table->tinyInteger('residencial_uf')->nullable()->default(0);
                $table->tinyInteger('residencial_cep')->nullable()->default(0);
                
                $table->primary(['process_id','ctrl','num']);//chave primária tripla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        
    }
    
    
    public function down(){
        Schema::dropIfExists('pr_seg_residencial__s');
        Schema::dropIfExists('pr_seg_residencial');
    }
}
