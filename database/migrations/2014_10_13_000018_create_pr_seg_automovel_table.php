<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrSegAutomovelTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        if(!Schema::hasTable('pr_seg_automovel')){
            //ramo: automovel
            Schema::create('pr_seg_automovel', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('num')->unsigned();
                
                $table->string('prop_nome',50)->nullable();
                $table->string('veiculo_fab_code',5)->nullable();
                $table->string('veiculo_modelo',50)->nullable();
                $table->smallInteger('veiculo_ano_fab')->nullable();
                $table->smallInteger('veiculo_ano_modelo')->nullable();
                $table->string('veiculo_chassi',17)->nullable();
                $table->string('veiculo_cod_fipe',8)->nullable();
                $table->string('veiculo_placa',8)->nullable();
                $table->string('veiculo_combustivel_code',2)->nullable();
                $table->tinyInteger('veiculo_n_portas')->nullable();
                $table->tinyInteger('veiculo_n_lotacao')->nullable();
                $table->string('veiculo_ci',14)->nullable();
                $table->tinyInteger('veiculo_classe')->nullable();
                $table->boolean('veiculo_zero')->nullable();
                $table->date('veiculo_data_saida')->nullable();
                $table->string('veiculo_nf',15)->nullable();
                $table->string('segurado_pernoite_cep',9)->nullable();
                $table->string('veiculo_tipo',1)->nullable();
                
                $table->primary(['process_id','num']);//chave primária tripla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        if(!Schema::hasTable('pr_seg_automovel__s')){
            //ramo: automovel
            Schema::create('pr_seg_automovel__s', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('ctrl')->unsigned();
                $table->tinyInteger('num')->unsigned();
                
                $table->tinyInteger('prop_nome')->nullable()->default(0);
                $table->tinyInteger('veiculo_fab_code')->nullable()->default(0);
                $table->tinyInteger('veiculo_modelo')->nullable()->default(0);
                $table->tinyInteger('veiculo_ano_fab')->nullable()->default(0);
                $table->tinyInteger('veiculo_ano_modelo')->nullable()->default(0);
                $table->tinyInteger('veiculo_chassi')->nullable()->default(0);
                $table->tinyInteger('veiculo_cod_fipe')->nullable()->default(0);
                $table->tinyInteger('veiculo_placa')->nullable()->default(0);
                $table->tinyInteger('veiculo_combustivel_code')->nullable()->default(0);
                $table->tinyInteger('veiculo_n_portas')->nullable()->default(0);
                $table->tinyInteger('veiculo_n_lotacao')->nullable()->default(0);
                $table->tinyInteger('veiculo_ci')->nullable()->default(0);
                $table->tinyInteger('veiculo_classe')->nullable()->default(0);
                $table->tinyInteger('veiculo_zero')->nullable()->default(0);
                $table->tinyInteger('veiculo_data_saida')->nullable()->default(0);
                $table->tinyInteger('veiculo_nf')->nullable()->default(0);
                $table->tinyInteger('segurado_pernoite_cep')->nullable()->default(0);
                $table->tinyInteger('veiculo_tipo')->nullable()->default(0);
                
                $table->primary(['process_id','ctrl','num']);//chave primária tripla
                $table->foreign('process_id')->references('id')->on('process_robot');
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
        Schema::dropIfExists('pr_seg_automovel__s');
        Schema::dropIfExists('pr_seg_automovel');
    }
}
