<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateInfoCidadesTable.
 */
class CreateDevToolsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('dev_process_robot_test_read_pdf')) {
            Schema::create('dev_process_robot_test_read_pdf', function(Blueprint $table) {
                $table->bigInteger('process_id')->unsigned();
                $table->timestamp('dt_start')->nullable();
                $table->timestamp('dt_end')->nullable();
                $table->string('status', 1)->nullable();
                $table->string('msg',1000)->nullable();
                $table->boolean('opt_extract')->default(false);
                $table->boolean('opt_save_index')->default(false);
                $table->string('engine',50)->nullable();
                $table->primary('process_id');
            });
            
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('dev_process_robot_test_read_pdf');
    }

}
