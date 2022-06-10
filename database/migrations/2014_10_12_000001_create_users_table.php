<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('users')){
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('user_name',100);
                $table->string('user_alias',100);
                $table->string('user_email',150)->index();
                $table->string('user_pass',255)->nullable();
                $table->string('user_status',1)->nullable();
                $table->string('user_level',10);
                $table->string('area_name',50)->nullable()->index();
                $table->boolean('re_login')->default(false);
                $table->rememberToken();
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
            });
            
            if(!Schema::hasTable('user_account_relations')){
                Schema::create('user_account_relations', function (Blueprint $table) {
                    $table->integer('user_id')->unsigned();
                    $table->integer('account_id')->unsigned();
                    
                    $table->foreign('user_id')->references('id')->on('users');
                    $table->foreign('account_id')->references('id')->on('accounts');
                    $table->primary(['user_id','account_id']);//chave primária dupla
                });
            }
            
            if(!Schema::hasTable('user_logs')){
                Schema::create('user_logs', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->integer('user_id')->nullable()->unsigned();
                    $table->integer('account_id')->nullable()->unsigned();
                    $table->string('user_level',10)->nullable();
                    $table->string('area_name',50)->index();
                    $table->bigInteger('area_id')->default(0)->unsigned()->index();
                    $table->string('action',7)->index();
                    $table->text('log_data')->nullable();
                    $table->timestamp('created_at');
                    $table->string('url',500);
                    
                    $table->foreign('user_id')->references('id')->on('users');
                    $table->foreign('account_id')->references('id')->on('accounts');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_logs');
        Schema::dropIfExists('user_account_relations');
        Schema::dropIfExists('users');
    }
}
