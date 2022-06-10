<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrSeguradoraFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        if(!Schema::hasTable('pr_seguradora_files')){
            //Cadastro de corretores
            Schema::create('pr_seguradora_files', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->bigInteger('process_rel_id')->unsigned();
                $table->string('quiver_id',10)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->string('status',1)->nullable();
                $table->tinyInteger('process_count')->nullable();
                $table->primary(['process_id','process_rel_id']);//chave primária tripla
                
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
        Schema::dropIfExists('pr_seguradora_files');
    }
}
