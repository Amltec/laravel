<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration{
    
    public function up(){
        Schema::create('post_folder', function(Blueprint $table){
            $table->increments('id');
            $table->string('post_type',20);
            $table->string('folder_title',500);
            $table->string('folder_resume',1000)->nullable();
            $table->string('folder_version',10)->nullable();
            $table->integer('folder_version_id')->unsigned()->nullable();
            $table->string('folder_status',1)->nullable();
            $table->timestamps();// created_at and updated_at
            $table->softDeletes();//deleted_at
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('account_id')->unsigned();
            $table->string('area_name',50)->nullable()->index();
            $table->bigInteger('area_id')->default(0)->unsigned()->index();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('account_id')->references('id')->on('accounts');
        });
        
        
        Schema::create('posts', function(Blueprint $table){
            $table->bigIncrements('id');
            $table->string('post_type',20);
            $table->string('post_title',500);
            $table->string('post_resume',1000)->nullable();
            $table->longText('post_content')->nullable();
            $table->string('post_content_type',1)->nullable();
            $table->string('post_status',1)->nullable();
            $table->string('post_visibility',1)->nullable();
            $table->string('post_name',200)->nullable();
            $table->bigInteger('post_parent')->nullable()->unsigned();
            $table->integer('post_order')->nullable();
            $table->string('post_pass',32)->nullable();
            $table->bigInteger('post_version')->nullable()->unsigned();
            $table->string('user_level',10)->nullable();
            $table->timestamps();// created_at and updated_at
            $table->softDeletes();//deleted_at
            $table->timestamp('published_at')->nullable();
            $table->integer('account_id')->nullable()->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('area_name',50)->nullable()->index();
            $table->bigInteger('area_id')->nullable()->unsigned()->index();
            
            //$table->foreign('post_parent')->references('id')->on('posts');
            //$table->foreign('post_version')->references('id')->on('posts');
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('user_id')->references('id')->on('users');
        });
        
        
        Schema::create('post_hist', function(Blueprint $table){
            $table->smallInteger('id')->unsigned();
            $table->bigInteger('post_id')->unsigned();
            $table->string('action',7);
            $table->text('log_data')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->timestamp('created_at')->nullable();
            $table->primary(['id','post_id']);
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('post_id')->references('id')->on('posts');
        });
        
        
        Schema::create('post_data', function(Blueprint $table){
            $table->bigInteger('post_id')->unsigned();
            $table->string('meta_name',20);
            $table->string('meta_value',50);
            $table->primary(['post_id','meta_name']);
            
            $table->foreign('post_id')->references('id')->on('posts');
        });
    }

    
    public function down(){
        Schema::dropIfExists('posts_data');
        Schema::dropIfExists('posts_hist');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_folder');
    }
}
