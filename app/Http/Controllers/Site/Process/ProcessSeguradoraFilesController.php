<?php

namespace App\Http\Controllers\Site\Process;
use App\Http\Controllers\Controller;

/**
 * Classe de cadastro de apólices no Quiver
 * Rotas não autenticadas para esta classe
 * Ex de url: site.com/{class_name}/{method_name}/
 */
class ProcessSeguradoraFilesController extends Controller{
    
    private static $controller_name='\\App\\Http\\Controllers\\Process\\ProcessSeguradoraFilesController';
    
    /**
     * Adiciona novo registro de processo de procura de apólices para download na Área de Seguradoras do Quiver
     * Ex de rota para este método: site.com/process_seguradora_files/add_process
     */
    public function get_addProcessAuto(){
        return \App::call(self::$controller_name.'@get_addProcessAuto');
    }
    
    /**
     * Adiciona novo registro de processo para marcar como concluído os registros na Área de Seguradoras do Quiver
     */
    public function get_addProcessMarkdone(){
        return \App::call(self::$controller_name.'@get_addProcessMarkdone');
    }
}
