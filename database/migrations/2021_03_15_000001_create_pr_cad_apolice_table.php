<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrCadApoliceTable extends Migration{
    
    public function up(){
        if(!Schema::hasTable('pr_cad_apolice')){
            Schema::create('pr_cad_apolice', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->smallInteger('num')->unsigned();
                $table->string('process',20);
                $table->string('status',1)->nullable();
                $table->integer('user_id')->unsigned()->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->boolean('is_done')->default(false);
                
                $table->primary(['process_id','num','process']);//chave primária tripla
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
    }
    
    public function down(){
        Schema::dropIfExists('pr_cad_apolice');
    }
}