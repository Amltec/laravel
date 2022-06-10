<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateInfoCidadesTable.
 */
class CreateTaxonomysTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('terms')) {
            Schema::create('terms', function(Blueprint $table) {
                $table->increments('id');
                $table->string('term_title', 50);
                $table->string('term_singular_title', 50)->nullable();
                $table->string('term_short_title', 50)->nullable();
                $table->string('term_description', 255)->nullable();
            });
            
        }
        
        if (!Schema::hasTable('taxs')) {
            Schema::create('taxs', function(Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('term_id')->unsigned(); 
                $table->string('tax_title',50);
                $table->text('tax_description')->nullable();
                $table->bigInteger('tax_id_parent')->nullable()->unsigned();
                $table->string('tax_opt',50)->nullable();
                $table->boolean('tax_hide')->default(false);
                $table->integer('account_id')->nullable()->unsigned();
                
                $table->foreign('term_id')->references('id')->on('terms');
                $table->foreign('tax_id_parent')->references('id')->on('taxs');
                $table->foreign('account_id')->references('id')->on('accounts');
            });
        }
        
        if (!Schema::hasTable('tax_relations')) {
            Schema::create('tax_relations', function(Blueprint $table) {
                $table->bigInteger('tax_id')->unsigned();
                $table->string('area_name',50)->nullable()->index();
                $table->bigInteger('area_id')->default(0)->index();
                
                $table->foreign('tax_id')->references('id')->on('taxs');//->onDelete('cascade');
                $table->primary(['tax_id','area_name','area_id']);//chave primária tripla
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('tax_relations', function (Blueprint $table) {
            $table->dropForeign('tax_relations_tax_id_foreign');
        });
        Schema::table('taxs', function (Blueprint $table) {
            $table->dropForeign('taxs_tax_id_parent_foreign');
            $table->dropForeign('taxs_term_id_foreign');
        });
        Schema::table('terms', function (Blueprint $table) {
            $table->dropForeign('terms_account_id_foreign');
        });
        Schema::dropIfExists('tax_relations');
        Schema::dropIfExists('taxs');
        Schema::dropIfExists('terms');
    }

}
