<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcessRobotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('process_robot')){
            //Cadastro de corretores
            Schema::create('process_robot', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('broker_id')->unsigned()->nullable();
                $table->integer('insurer_id')->unsigned()->nullable();
                $table->string('process_name',20);
                $table->string('process_prod',20);
                $table->string('process_ctrl_id',20)->nullable();
                $table->string('process_status',1)->nullable();
                $table->tinyInteger('process_status_changed')->unsigned()->default('0');
                $table->date('process_date')->nullable();
                $table->boolean('process_auto')->default(false);
                //$table->string('file_ext',5)->nullable();
                $table->boolean('process_test')->default(false);
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
                $table->integer('robot_id')->unsigned()->nullable();
                $table->integer('user_id')->unsigned()->nullable();
                $table->timestamp('process_next_at')->nullable();
                $table->boolean('locked')->default(false);
                $table->timestamp('locked_at')->nullable();
                $table->integer('account_id')->unsigned();
                
                $table->foreign('broker_id')->references('id')->on('brokers');
                $table->foreign('insurer_id')->references('id')->on('insurers');
                $table->foreign('robot_id')->references('id')->on('robots');
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('account_id')->references('id')->on('accounts');
            });
        }
        
        if(!Schema::hasTable('process_robot_data')){
            //Cadastro de corretores
            Schema::create('process_robot_data', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->string('meta_name',20);
                $table->string('meta_value',50);
                
                $table->primary(['process_id','meta_name']);//chave primária drupla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        
        
        if(!Schema::hasTable('process_robot_execs')){
            //Cadastro de corretores
            Schema::create('process_robot_execs', function (Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->smallInteger('id')->unsigned();
                $table->datetime('process_start')->nullable();
                $table->datetime('process_end')->nullable();
                $table->string('status_code',6)->nullable();
                
                $table->primary(['process_id','id']);//chave primária drupla
                $table->foreign('process_id')->references('id')->on('process_robot');
            });
        }
        
        
        if(!Schema::hasTable('process_robot_resume')){
            //Cadastro de corretores
            Schema::create('process_robot_resume', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('broker_id')->unsigned()->nullable();
                $table->integer('insurer_id')->unsigned()->nullable();
                $table->string('process_name',50)->nullable();
                $table->date('process_date')->nullable();
                $table->integer('process_count')->unsigned()->default(0);
                $table->integer('process_count_o')->unsigned()->default(0);
                $table->integer('process_count_0')->unsigned()->default(0);
                $table->integer('process_count_p')->unsigned()->default(0);
                $table->integer('process_count_a')->unsigned()->default(0);
                $table->integer('process_count_f')->unsigned()->default(0);
                $table->integer('process_count_e')->unsigned()->default(0);
                $table->integer('process_count_1')->unsigned()->default(0);
                $table->integer('process_count_i')->unsigned()->default(0);
                $table->integer('process_count_c')->unsigned()->default(0);
                $table->integer('process_count_w')->unsigned()->default(0);
                $table->float('process_duration')->unsigned()->default(0);
                $table->integer('account_id')->unsigned();
                
                $table->foreign('broker_id')->references('id')->on('brokers');
                $table->foreign('insurer_id')->references('id')->on('insurers');
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
        Schema::dropIfExists('process_robot_resume');
        Schema::dropIfExists('process_robot_execs');
        Schema::dropIfExists('process_robot_data');
        Schema::dropIfExists('process_robot');
    }
}
