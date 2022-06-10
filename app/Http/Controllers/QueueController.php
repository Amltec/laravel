<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisan;

/**
 * Classe para execução da fila de processos do laravel
 * Utilizada para ser chamado e executado diretamente pela url:
 * Esta classe faz o mesmo do comando no terminal do provedor: php artisan queue:work
 * por url: site.com.br/queue/start?a=1
 */
class QueueController extends Controller{
    
    public function execute(Request $request,$action){
        if(method_exists($this,$action)){
             $this->$action($request);
        }
    }
    
    
    private function start($request){
        //Artisan::call('queue:restart', []);
        echo Artisan::call('queue:work', [
            '--timeout' => 60,
            '--memory' => 150,
            '--tries' => 15,
            '--once' => true        //processa apenas um único trabalho por carregarmento deste controller
        ]);
    }
}
