<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateMetadataTable.
 */
class CreateMetadataTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('metadata')) {
            Schema::create('metadata', function(Blueprint $table) {
                $table->string('area_name',50)->nullable()->index();
                $table->bigInteger('area_id')->default(0)->unsigned()->index();
                $table->string('meta_name',50);
                $table->text('meta_value');
                $table->primary(['area_name','area_id','meta_name']);//chave primária tripla
            });

        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropIfExists('metadata');
    }

}
