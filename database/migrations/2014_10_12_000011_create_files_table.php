<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('files')){
            Schema::create('files', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('file_title',255);
                $table->string('file_name',255);
                $table->string('file_size',100);
                $table->string('file_mimetype',100)->nullable();
                $table->string('file_path',255);
                $table->string('file_ext',5);
                $table->string('file_thumbnails',200)->nullable();
                $table->boolean('private')->default(false);
                $table->string('folder',50);
                $table->integer('user_id')->unsigned();
                $table->integer('account_id')->unsigned()->nullable();
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
                
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('account_id')->references('id')->on('accounts');
            });
        }
        
        if(!Schema::hasTable('files_relations')){
            Schema::create('files_relations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('file_id')->unsigned();
                $table->string('area_name',50)->nullable()->index();
                $table->bigInteger('area_id')->default(0)->unsigned()->index();
                $table->string('status',1);
                
                $table->foreign('file_id')->references('id')->on('files');//->onDelete('cascade');
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
        Schema::dropIfExists('files_relations');
        Schema::dropIfExists('files');
    }
}
