<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('accounts')){
            Schema::create('accounts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('account_name',100);
                $table->string('account_status',1)->nullable();
                $table->string('account_email',150);
                $table->string('account_login',50)->unique();
                $table->string('account_key',32)->unique();
                $table->timestamps();// created_at and updated_at
                $table->softDeletes();//deleted_at
                $table->boolean('process_mark')->default(false);
                $table->boolean('process_single')->default(false);
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
        Schema::dropIfExists('accounts');
    }
}
