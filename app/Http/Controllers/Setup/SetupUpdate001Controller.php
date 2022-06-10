<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

use App\Models\ProcessRobot;
use App\Models\ProcessRobotData;
use Artisan;

/**
 * Classe que executa as possíveis atualizações pendentes
 * Ex de chamada desta classe:
 *      https://robo.aurlweb.com.br/super-admin/setup-update-001/index
 * Status: aguardando execução online
 */
class SetupUpdate001Controller extends Controller{
    
    private static $update_ctrl = '2019-06-01 11:54';
    private static $cache_name = 'update_ctrl';
    
    /*
     * Aqui devem ser programadas cada atualização a ser executada em ambiente de produção
     */
    public function get_index(ProcessRobot $ProcessRobotModel,ProcessRobotData $ProcessRobotDataModel){
        exit('Finalizado em '.self::$update_ctrl);
        //### OBS: executar apenas 1x e depois remover este arquivo ignorar ###
        if(Cache::has(self::$cache_name)){
            if(Cache::get(self::$cache_name)==self::$update_ctrl)return 'Atualização já executa: '.self::$update_ctrl;
        }
        /*
        //*** move do diretório público para o privado ***
        $fileSystem = new \Illuminate\Filesystem\Filesystem;
        $path_to = storage_path() .'/accounts/1/cad_apolice/automovel';
        $path_from = public_path() . '/storage/app/apolices';
        if(!file_exists($path_to))$fileSystem->makeDirectory($path_to, 0777, true, true);
        $r=$fileSystem->moveDirectory($path_from, $path_to, true);
        //dd('movido',$r,$path_from, $path_to);
        */
        
        //limpa o cache
        $r_cache= '';
        Artisan::call('clear-compiled');$r_cache.=Artisan::output();
        Artisan::call('route:clear');$r_cache.=Artisan::output();
        Artisan::call('view:clear');$r_cache.=Artisan::output();
        Artisan::call('config:clear');$r_cache.=Artisan::output();
        Artisan::call('cache:clear');$r_cache.=Artisan::output();
        
        
        //dd('---ok---');
       
        Cache::forget(self::$cache_name);
        Cache::forever(self::$cache_name,self::$update_ctrl);//armazena em cache
        
        return 'Atualização concluída <br>'.$r_cache;
    }
}
