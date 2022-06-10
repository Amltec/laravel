<?php

namespace App\Http\Controllers\Site;
use App\Http\Controllers\AppController;

/**
 * Carregamento de controllers dinâmicos a partir de qualquer nome de rota informada $routename
 * As classes das rotas devem estar dentro da pasta \App\Http\Controllers\Site
 * Obs: todas estas rotas NÃO são autenticadas
 */
class SiteController extends AppController{
     protected $folder_app='Site';
     
     //Acesso ao método não autenticado de carregamento de arquivos
     public function robotFileLoad($process_name,$data_serialize,$filename){
         return \App::call('\\App\\Http\\Controllers\\Site\\Process\\Process'. studly_case($process_name) .'Controller@robotFileLoad',[$data_serialize,$filename]);
     }
}
