<?php

namespace App\Services;
use App\Config;
use App\Models\Account as AccountModel;
use App\Utilities\FormatUtility;

/**
 * Classe de serviços gerais da conta
 */
class AccountsService{
    
    /**
     * Retorna ao id da conta do usuágio logado
     * Por returnar a null, caso esteja no painel superadmin
     */
    public static function getLoggedId(){
        return Config::isSuperAdminPrefix() ? null : Config::accountID();
    }
    
    /**
     * Verifica se conta informado é o mesmo que está logado
     * @param model|int $account_id
     */
    public static function isAccountLogged($accont_id){
        $a = self::get($accont_id);
        return $a && $a->model->id == Config::accountID();
    }
    
    /**
     * Retorna a model da conta
     * @param model|int $account_id
     * @param boolean $force - se true, força a leitura ao invés de pegar do cache (seta $merge=true automaticamente)
     * Return object [model->,data->,config->]
     */
    private static $data_get_account=[];
    public static function get($account_id,$force=false){
        if(is_object($account_id)){
            $model = $account_id;
            $account_id=$account_id->id;
        }else{
            $model = null;
        }
        if(!isset(self::$data_get_account[$account_id]) || $force==true){
            if(!$model)$model = AccountModel::find($account_id);
            if($model){
                $data = $model->getData('',$force);
                self::$data_get_account[$account_id] = (object)['model'=>$model,'data'=>$data,'config'=>$data['config']??null];
            }
        }
        return self::$data_get_account[$account_id];
    }
    
    /**
     * Retorna a um dado
     */
    public static function getData($account_id,$field,$def=''){
         return array_get(self::get($account_id)->data,$field,$def);
    }
    
    /**
     * Retorna a uma configuração
     */
    public static function getConfig($account_id,$field,$def=''){
         return array_get(self::get($account_id)->config,$field,$def);
    }
    
    /**
     * Retorna se a conta tem permissão para o respectivo processo / serviço
     * @param model|int $account_id
     * @return boolean
     */
    public static function isProcessActive($account_id,$process_name,$process_prod=null,$ramo=null){
        $a = self::get($account_id);
        if($a){
            $config = $a->config;
            $t1 = ($config[$process_name]['active']??null) == 's';
            $t2 = true;
            $t3 = true;
            if($t1 && $process_prod)$t2 = ($config[$process_name]['active_'.$process_prod]??null) == 's';
            if($t1 && $process_prod && $ramo){
                $n = $config[$process_name]['active_'.$process_prod.'_prods']??null;
                if($n && !in_array($ramo,$n))$t3=false;//verifica se existe algum $ramo em $n informado para confirmar se está autorizado (obs: se $n for vazio, quer dizer que está habilitado para todos os ramos)
            }
            return $t1 && $t2 && $t3;
        }else{
            return false;
        }
    }
    
    
     /**
     * Valor padrão de configuração desta conta
     * @param $model - string|int id ou $model Account
     * @param boolean $force - se true, força a leitura ao invés de pegar do cache (seta $merge=true automaticamente)
     * @param boolean $merge - se true, mescla a configuração com os dados padrões
     */
    public static function getAccountConfig($model,$force=false,$merge=false){
        if(!is_object($model)){
            $model = self::get($account_id,$force);
            $data = $model->config;
        }else{
            $data = $model->getData('config',$force);
        }
        if($force===true)$merge=true;
        if($merge){
            //valores padrões de configuração
            $def = [
                'instances'=>'2', //número de instâncias permitidas
                'cad_apolice'=>[
                    'active' => 'n',
                    'products_active' => '',//todos
                    'login_mode' => 'unique',
                    //'quiver_user' => '',
                    //'quiver_login' => '',
                    //'quiver_senha' => '',
                    //'quiverX_Xsenha' => '',
                ],
                'seguradora_files'=>[
                    'active' => 'n',
                    'show_cli' => 'n',
                    //'quiver_user' => '',
                    //'quiver_login' => '',
                    //'quiver_senha' => '',
                ],
                'seguradora_data'=>[
                    'active' => 'n',
                    'active_apolice_check' => 'n',
                    'active_boleto_seg' => 'n',
                    'active_boleto_seg_prods' => [],
                    'active_boleto_quiver' => 'n',
                ]
            ];
            //retorna mesclando com os dados de configuração
            
            $data = $data ? FormatUtility::array_merge_recursive_distinct($def,$data) : $def;
        }
        
        return $data;
    }
    
    
    /**
     * Valor padrão de configuração para o cadastro de apólice
     * @param $account - model Account
     */
    public static function getCadApoliceConfig($account) {
        $config = $account->getData('config_cad_apolice');
        
        
        //valores padrões caso não vazio nas configurações
        $def = [
            'venc_1a_parc_cartao'=>'vigencia',
            'venc_1a_parc_debito'=>'vigencia',
            'names_fpgto'=>[
                'carne'         =>'CARNÊ',
                'boleto'        =>'BOLETO',
                'debito'        =>'DÉBITO EM CONTA',
                'cartao'        =>'CARTÃO DE CRÉDITO',
                '1boleto_debito'=>'1ª BOLETO - DEMAIS DEBITO EM CONTA',
                '1boleto_cartao'=>'1ª BOLETO - DEMAIS CARTÃO CREDITO',
            ],
            'names_anexo'=>[
                'apolice'   => 'APÓLICE',
                'historico' => 'HISTÓRICO',
                'boleto'    => 'BOLETO',
            ],
            'num_quiver'=>[],   //null para capturar o padrão de cada classe
            'search_products'=> 'AUTOMOVEL|RESIDENCIAL|EMPRESARIAL',
            'down_apo_ramo'=>[
                'automovel' => 'auto|automovel|outros arquivos',
                'residencial' => 'residencial|outros arquivos',
                'empresarial' => 'empresarial|outros arquivos',
            ]
        ];
        
        $config = $config ? FormatUtility::array_merge_recursive_distinct($def,$config) : $def;
        
        return $config;
    }
    
    
    
    /**
     * Start / Pausa o robô
     * @param $st - on, off
     * Sem retorno
     */
    public static function setRobotStart($account_id,$st){
        self::get($account_id)->model->setData('robot_start',$st);
    }
    
    
}