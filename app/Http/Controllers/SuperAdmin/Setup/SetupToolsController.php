<?php

namespace App\Http\Controllers\SuperAdmin\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Artisan;
use Auth;

/**
 * Classe que executa algumas ações de nível de instalação (somente para usuário nível desenvolvedor)
 */
class SetupToolsController extends Controller{
    
    public function __construct() {
        if(!Auth::check())exit('negado');
        if(Auth::user()->user_level!='dev')exit('negado');
    }

    
    /*
     * Limpa o cache do sistema
     */
    public function post_clearCache(){
        //limpa o cache
        $r_cache= '';
        Artisan::call('clear-compiled');$r_cache.=Artisan::output().'<br>';
        Artisan::call('route:clear');$r_cache.=Artisan::output().'<br>';
        Artisan::call('view:clear');$r_cache.=Artisan::output().'<br>';
        Artisan::call('config:clear');$r_cache.=Artisan::output().'<br>';
        Artisan::call('cache:clear');$r_cache.=Artisan::output().'<br>';
        return ['success'=>true,'msg'=>'Concluído em '. date('Y-m-d H:i:s') .'<br>'.$r_cache];
    }
}
