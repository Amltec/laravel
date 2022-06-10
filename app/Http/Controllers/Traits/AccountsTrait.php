<?php

namespace App\Http\Controllers\Traits;
use App\Services\AccountsService;

/**
 * Classe trait de funções para o controller Accounts
 */
trait AccountsTrait {
    //somente usuários autenticados podem acessar este controle (autenticação pelo controller)
    
    //*** sistema de logins desativado em 06/04/2021 - estes logins foram movidos para a tabela account_pass ***
    //private $logins_limit=5;//limite de logins adicionais
    
    //Atualização dos logotipos
    private function setFilesUpdate($data,$model){//$model = account model
        $action = $data['action'];//tipo da atualização, valores: logo
        
        if($action=='logo'){//logo
            $url      = $data['url'];
            $filename = $data['filename'];
            if(!in_array($filename,['logo-main','logo-icon'])) return ['success'=>false,'msg'=>'Parâmetro filename inválido'];
            
            $model->setMetadata(str_replace('-','_',$filename), $url);
            $model->setMetadata('updated_at', time());
            return ['success'=>true];
                
        }else{
            return ['success'=>false,'msg'=>'Parâmetro action inválido'];
        }
    }
    
    
    /**
     * Carrega as configurações
     */
    protected function edit_configIndex($accountModel,$dataAccount){
        return [
            'configProcessNames'=>\App\ProcessRobot\VarsProcessRobot::$configProcessNames,
            //valores salvos
            'configData'=>AccountsService::getAccountConfig($accountModel,false,true)
        ];
    }
    
     /**
     * Função para preparar a configuração antes de ser salvar
     * @param Account $model 
     * @param Request $request - dados da configuração 
     * @param string $user_level - valores: dev, superadmin, admin
     */
    protected function defaultConfigSave($model,$request,$user_level){
        //monta a array na estrutura correta para salvar
        //$original = AccountsService::getAccountConfig($model,true);//true para forçar a leitura no db
        //return array_merge($original, $data);
        if(!$model)return ['success'=>false,'msg'=>'Erro ao localizar registro'];
        
        $data = $request->all();
        $original = AccountsService::getAccountConfig($model,true);//true para forçar a leitura do db
        //
        //extract($data);
        
        //monta a array na estrutura correta para salvar
        //estrutura completa (user_level='superadmin')
        $arr=[
            'instances'=> array_get($data,'instances', $original['instances']),
            'cad_apolice'=>[
                'active' => array_get($data,'cad_apolice--active'),
                'products_active' => array_get($data,'cad_apolice--products_active'),
                'login_mode' => array_get($data,'cad_apolice--login_mode'),
                //'quiver_user' => array_get($data,'cad_apolice--quiver_user'),
                //'quiver_login' => array_get($data,'cad_apolice--quiver_login'),
                //'quiver_senha' => array_get($data,'cad_apolice--quiver_senha'),
            ],
            'seguradora_files'=>[
                'active' => array_get($data,'seguradora_files--active'),
                'show_cli' => array_get($data,'seguradora_files--show_cli'),
                //'quiver_user' => array_get($data,'seguradora_files--quiver_user'),
                //'quiver_login' => array_get($data,'seguradora_files--quiver_login'),
                //'quiver_senha' => array_get($data,'seguradora_files--quiver_senha'),
            ],
            'seguradora_data'=>[
                'active' => array_get($data,'seguradora_data--active'),
                'active_apolice_check' => array_get($data,'seguradora_data--active_apolice_check'),
                'active_boleto_seg' => array_get($data,'seguradora_data--active_boleto_seg'),
                'active_boleto_seg_prods' => array_get($data,'seguradora_data--active_boleto_seg_prods'),
                'active_boleto_quiver' => array_get($data,'seguradora_data--active_boleto_quiver'),
            ]
        ];
        
        /*
        //*** sistema de logins desativado em 06/04/2021 - estes logins foram movidos para a tabela account_pass ***
        for($i=0;$i<=$this->logins_limit;$i++){
            $n=($i>0?'_'.$i:'');
            //cad_apolice
            $arr['cad_apolice']['quiver_user'.$n]       = array_get($data,'cad_apolice--quiver_user'.$n);
            $arr['cad_apolice']['quiver_login'.$n]      = array_get($data,'cad_apolice--quiver_login'.$n);
            $s                                          = array_get($data,'cad_apolice--quiver_senha'.$n);
            if(!$s)$s = array_get($original,'cad_apolice.quiver_senha'.$n);
            if(empty($arr['cad_apolice']['quiver_user'.$n]) || empty($arr['cad_apolice']['quiver_login'.$n]) || in_array($s,['not','null']))$s=null;//limpa o campo de senha, pois o user e login não existe //obs: se senha not ou null - quer dizer que deve limpar o campo senha
            $arr['cad_apolice']['quiver_senha'.$n] = $s;
            
            //*** sistema de logins desativado em 15/03/2021 - utilizado apenas os logins do cad_apolice ***
            ////seguradora_files
            //$arr['seguradora_files']['quiver_user'.$n]  = array_get($data,'seguradora_files--quiver_user'.$n);
            //$arr['seguradora_files']['quiver_login'.$n] = array_get($data,'seguradora_files--quiver_login'.$n);
            //$arr['seguradora_files']['quiver_senha'.$n] = array_get($data,'seguradora_files--quiver_senha'.$n);
            //if(!$arr['seguradora_files']['quiver_senha'.$n])$arr['seguradora_files']['quiver_senha'.$n] = array_get($original,'seguradora_files.quiver_senha'.$n);
            //if(empty($arr['seguradora_files']['quiver_user'.$n]) || empty($arr['seguradora_files']['quiver_login'.$n]))$arr['seguradora_files']['quiver_senha'.$n]=null;//limpa o campo de senha, pois o user e login não existe
            
            //seguradora_data
            //não tem configurações adicionais de login aqui
        }
        */
        
        
        //mescla as configurações já salvas com as novas
        $arr = array_merge($original, $arr);
        if($user_level=='admin'){
            //ajusta as permissões que não podem ser alteradas
            $arr['cad_apolice']['active']=$original['cad_apolice']['active'];
            $arr['cad_apolice']['products_active']=$original['cad_apolice']['products_active'];
            $arr['seguradora_files']['active']=$original['seguradora_files']['active'];
            $arr['seguradora_files']['show_cli']=$original['seguradora_files']['show_cli'];
            $arr['seguradora_data']['active']=$original['seguradora_data']['active'];
            $arr['seguradora_data']['active_apolice_check']=$original['seguradora_data']['active_apolice_check'];
            $arr['seguradora_data']['active_boleto_seg']=$original['seguradora_data']['active_boleto_seg'];
            $arr['seguradora_data']['active_boleto_seg_prods']=$original['seguradora_data']['active_boleto_seg_prods'];
            $arr['seguradora_data']['active_boleto_quiver']=$original['seguradora_data']['active_boleto_quiver'];
            
            //atualiza para as configurações orginais todo o bloco de informações que não estão ativas
            if($arr['cad_apolice']['active']!='s')$arr['cad_apolice']=$original['cad_apolice'];
            if($arr['seguradora_files']['active']!='s')$arr['seguradora_files']=$original['seguradora_files'];
            if($arr['seguradora_files']['show_cli']!='s')$arr['seguradora_files']=$original['seguradora_files'];
            //if($arr['seguradora_data']... //nenhuma configuração aqui
        }
        //dd($arr,$original,$user_level);
        
        //salva a configuração
        $r = $model->setData('config', $arr);
        if($r['success']){
            return ['success'=>true,'msg'=>'Dados salvos com sucesso'];
        }else{
            return $r;
        }
    }
    
    
}
