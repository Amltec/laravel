<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MetadataService;
use Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Redirector;

/**
 * Classe de configurações gerais válido para todo o sistema
 */
class ConfigController extends SuperAdminBaseController {
    //somente usuários autenticados podem acessar este controle (autenticação pelo controller)
    
    public function __construct(){
        parent::__construct();
    }
    
    //Retorna aos dados da configuração
    public static function getData(){
        return \Config::data();
    }
    
    
    //Cria o cache ca configuração
    private static $config_cache_name='config';
    private function createCache(){
        Cache::forget(self::$config_cache_name);
        Cache::forever(self::$config_cache_name,self::getData());//armazena em cache
    }
    
    
    
    //***** controllers das rotas dinâmicas ******
    
    //configurações gerais
    public function index($param) {
        $configData = self::getData();
        return view('super-admin.config', ['configData'=>$configData, 'userLogged' => \Auth::user()]);
    }
    
    //atualização
    public function post_update(Request $request){
        $data = $request->all();
        $action = $data['action'];//tipo da atualização, valores: data, logo
        
        if($action=='data'){//atualização de dados
            $msgValidator = \App\Utilities\FieldsValidatorUtility::getMessages();
            
            $strvalid = [
                //'title'=>'required',  //campo descartado. Motivo: esta variável foi substituída pelo padrão do arquivo ENV('APP_NAME')
                'email'=>'required|max:150',//ignora o registro do id atual
            ];
            $validade = validator($data, $strvalid, $msgValidator);
            if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
            
            $fillable=['email'];//campos preenchíveis
            foreach($data as $field=>$value){
                if(in_array($field,$fillable))MetadataService::set('config', 0, $field, $value);
            }

            $this->createCache(); //salve os dados em cache
            \App\Services\LogsService::addFields('save','config','0',$data);
            
            return ['success'=>true,'msg' => 'Registro atualizado','action'=>'edit'];
            
        }else if($action=='logo'){//logo
            $url      = $data['url'];
            $filename = $data['filename'];
            if(!in_array($filename,['logo-main','logo-icon'])) return ['success'=>false,'msg'=>'Parâmetro filename inválido'];
            MetadataService::set('config', 0, str_replace('-','_',$filename), $url);
            MetadataService::set('config', 0, 'updated_at', time());
            $this->createCache();//salve os dados em cache
            return ['success'=>true];
                
        }else{
            return ['success'=>false,'msg'=>'Parâmetro action inválido'];
        }
    }
    
    
    
    //********** ações da conta **********
    /**
     * Faz o logoff de todos os usuários de todas as contas exigindo um novo login
     */
    public function post_doUsersReLogin(){
        //ignora o usuário logado atual
        \App\Models\User::where('id','!=',\Auth::user()->id)->update(['re_login'=>true]);
        return ['success'=>true];
    }
    
    
}
