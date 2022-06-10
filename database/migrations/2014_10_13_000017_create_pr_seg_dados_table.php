<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrSegDadosTable extends Migration{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(){
        if(!Schema::hasTable('pr_seg_dados')){
            //dados do seguro: dados cadatrais e premio
            Schema::create('pr_seg_dados', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->string('data_type',12)->nullable();
                $table->string('proposta_num',20)->nullable();
                $table->string('apolice_num',30)->nullable();
                $table->string('apolice_num_quiver',20)->nullable();
                $table->date('data_emissao')->nullable();
                $table->string('apolice_re_num',20)->nullable();
                $table->date('inicio_vigencia')->nullable();
                $table->date('termino_vigencia')->nullable();
                $table->string('segurado_nome',50)->nullable();
                $table->string('segurado_doc',20)->nullable();
                $table->string('tipo_pessoa',1)->nullable();
                $table->string('fpgto_tipo_code',7)->nullable();
                $table->tinyInteger('fpgto_n_prestacoes')->nullable();
                $table->decimal('fpgto_1_prestacao_valor',8,2)->nullable();
                $table->date('fpgto_1_prestacao_venc')->nullable();
                $table->tinyInteger('fpgto_venc_dia_2parcela')->nullable();
                $table->tinyInteger('fpgto_avista')->nullable();
                $table->decimal('fpgto_premio_total',8,2)->nullable();
                $table->decimal('fpgto_premio_liquido',8,2)->nullable();
                $table->decimal('fpgto_premio_liq_serv',8,2)->nullable();
                $table->decimal('fpgto_custo',8,2)->nullable();
                $table->decimal('fpgto_adicional',8,2)->nullable();
                $table->decimal('fpgto_iof',8,2)->nullable();
                $table->decimal('fpgto_juros',8,2)->nullable();
                $table->decimal('fpgto_juros_md',8,2)->nullable();
                $table->decimal('fpgto_desc',8,2)->nullable();
                $table->decimal('comissao_premio',8,2)->nullable();
                $table->tinyInteger('anexo_upl')->unsigned()->nullable();
                
                $table->primary('process_id');
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        if(!Schema::hasTable('pr_seg_dados__s')){
            //dados do seguro: dados cadatrais e premio
            Schema::create('pr_seg_dados__s', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('ctrl')->unsigned();
                
                $table->tinyInteger('data_type')->unsigned()->default(0);
                $table->tinyInteger('proposta_num')->unsigned()->default(0);
                $table->tinyInteger('apolice_num')->unsigned()->default(0);
                $table->tinyInteger('apolice_num_quiver')->unsigned()->default(0);
                $table->tinyInteger('data_emissao')->unsigned()->default(0);
                $table->tinyInteger('apolice_re_num')->unsigned()->default(0);
                $table->tinyInteger('inicio_vigencia')->unsigned()->default(0);
                $table->tinyInteger('termino_vigencia')->unsigned()->default(0);
                $table->tinyInteger('segurado_nome')->unsigned()->default(0);
                $table->tinyInteger('segurado_doc')->unsigned()->default(0);
                $table->tinyInteger('tipo_pessoa')->unsigned()->default(0);
                $table->tinyInteger('fpgto_tipo_code')->unsigned()->default(0);
                $table->tinyInteger('fpgto_n_prestacoes')->unsigned()->default(0);
                $table->tinyInteger('fpgto_1_prestacao_valor')->unsigned()->default(0);
                $table->tinyInteger('fpgto_1_prestacao_venc')->unsigned()->default(0);
                $table->tinyInteger('fpgto_venc_dia_2parcela')->unsigned()->default(0);
                $table->tinyInteger('fpgto_avista')->unsigned()->default(0);
                $table->tinyInteger('fpgto_premio_total')->unsigned()->default(0);
                $table->tinyInteger('fpgto_premio_liquido')->unsigned()->default(0);
                $table->tinyInteger('fpgto_premio_liq_serv')->unsigned()->default(0);
                $table->tinyInteger('fpgto_custo')->unsigned()->default(0);
                $table->tinyInteger('fpgto_adicional')->unsigned()->default(0);
                $table->tinyInteger('fpgto_iof')->unsigned()->default(0);
                $table->tinyInteger('fpgto_juros')->unsigned()->default(0);
                $table->tinyInteger('fpgto_juros_md')->unsigned()->default(0);
                $table->tinyInteger('fpgto_desc')->unsigned()->default(0);
                $table->tinyInteger('comissao_premio')->unsigned()->default(0);
                $table->tinyInteger('anexo_upl')->unsigned()->nullable();
                
                $table->primary(['process_id','ctrl']);//chave primária dupla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        
        
        
        
        
        if(!Schema::hasTable('pr_seg_parcelas')){
            //parcelas do seguro
            Schema::create('pr_seg_parcelas', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('num')->unsigned();
                $table->date('fpgto_datavenc')->nullable();
                $table->decimal('fpgto_valorparc',8,2)->nullable();
                
                $table->primary(['process_id','num']);//chave primária tripla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        if(!Schema::hasTable('pr_seg_parcelas__s')){
            //parcelas do seguro
            Schema::create('pr_seg_parcelas__s', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->tinyInteger('ctrl')->unsigned();
                $table->tinyInteger('num')->unsigned()->default(0);
                $table->tinyInteger('fpgto_datavenc')->unsigned()->default(0);
                $table->tinyInteger('fpgto_valorparc')->unsigned()->default(0);
                
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
    public function down(){
        Schema::dropIfExists('pr_seg_parcelas__s');
        Schema::dropIfExists('pr_seg_dados__s');
        Schema::dropIfExists('pr_seg_parcelas');
        Schema::dropIfExists('pr_seg_dados');
    }
}
