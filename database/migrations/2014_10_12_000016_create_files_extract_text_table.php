<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateInfoCidadesTable.
 */
class CreateFilesExtractTextTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('files_extract_text')) {
            Schema::create('files_extract_text', function(Blueprint $table) {
                $table->bigIncrements('id')->unsigned();
                $table->string('file_url',1000);
                $table->string('file_path',1000);
                $table->string('area_name',50)->index();
                $table->bigInteger('area_id')->default(0)->unsigned()->index();
                $table->timestamp('created_at')->nullable();
                $table->string('engine', 50)->nullable();
                $table->string('status', 1)->nullable();
                $table->text('callback')->nullable();
                $table->text('file_text')->nullable();
            });
            
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('files_extract_text');
    }

}
