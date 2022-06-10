<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoginAttemptTable extends Migration{
    
    public function up(){
        if(!Schema::hasTable('login_attempt')){
            Schema::create('login_attempt', function (Blueprint $table) {
                $table->integer('user_id')->unsigned();
                $table->timestamp('created_at');
                $table->ipAddress('ip');
            });
        }
    }
    
    public function down(){
        Schema::dropIfExists('login_attempt');
    }
}
