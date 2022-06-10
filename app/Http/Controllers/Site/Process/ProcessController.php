<?php

namespace App\Http\Controllers\Site\Process;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

use App\Http\Controllers\Controller;

/* 
 * Classe geral para todos os processos do robô
 */
class ProcessController extends Controller {
    
    
    public function get_removeAutoTrash(){
        return \App::call('\\App\\Http\\Controllers\\Process\\ProcessController@removeAutoTrash');
    }
    
    public function get_clearBusyPass($param) {
        return \App::call('\\App\\Http\\Controllers\\Process\\ProcessController@clearBusyPass');
    }
}