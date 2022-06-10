<?php
namespace App;
use Illuminate\Support\Facades\Cache;
use App\Services\MetadataService;
use App\Services\AccountsService;

/**
 * Classe de acesso as configurações globais / gerais do sistema
 * Include valores do controller \App\Http\Controllers\ConfigGlobalController e demais dados padrões
 * Obs: usar esta classe como base para recuperar as configurações do sistema
 */
class Config{
    //configurações gerais do sistema
    public static $system = [
        //'app_name'=>'AW Robô',
        //'app_version'=>'3.0',
        'company_name'=>'AurlWeb - Sistemas Web',
        'author_name'=>'Aurélio de Morais',
    ];
    
    //labels dos campos
    public static $labels = [
        //'app_name'=>'Sistema',
        //'app_version'=>'Versão',
        'company_name'=>'Empresa',
        'author_name'=>'Author',
    ];
    
    
    private static $data=[];
    //Retorna as configurações salvos no metadado 'config'
    public static function data($name=null,$area='config'){
        if(!isset(self::$data[$area])){
            if(Cache::has($area)){
                $r = Cache::get($area);
            }else{
                $r = MetadataService::get($area, 0);
            }
            $r['title'] = env('APP_NAME');//este campo será sempre o padrão do arquivo ENV('APP_NAME')
            self::$data[$area]=$r;
        }
        return $name ? self::$data[$area][$name]??'' : self::$data[$area];
    }
    /*public static function system($name=null){
        return self::data($name,'system');
    }*/
    
    
    private static $account_logged;
    /**
     * Retorna a model da conta logada (captura a partir do login informado na url)
     */
    public static function account(){
        if(!self::$account_logged)self::$account_logged = \App\Models\Account::where('account_login',self::accountPrefix())->first();
        return self::$account_logged;
    }
    
    /**
     * Retorna ao id da conta logada
     */
    public static function accountID(){
        $model=self::account();
        return $model?$model->id:null;
    }
    
    /**
     * Retorna as configurações dos dados da conta logado
     * Válido somente se o usuário estiver logado
     * Return object [model->,data->,config->]
     */
    public static function accountData(){
        static $r;
        if(!$r){
            $model=self::account();
            if($model)$r = AccountsService::get($model);
        }
        return $r;
    }
    
    
    /**
     * get service class from account user logged
     */
    public static function accountService(){return new AccountsService;}
    
    /**
     * get config by account logged
     */
    public static function accountConfig(){
        return AccountsService::getAccountConfig( self::account() );
    }
    
    
   
    
    private static $prefix;
    private static $prefix_account='';
    /**
     * Retorna ao prefixo de acesso a área administrativa.
     * (capturando o primeiro diretório depois da urlbase do site)
     * Ex 'admin, 'super-admin', ...
     */
    public static function adminPrefix(){
        if(!self::$prefix){
            $url_full=\URL::current();
            $url_base=\URL::to('/');
            $u=trim(str_replace($url_base,'',$url_full),'/');
            $u=explode('/',$u);
            if($u[0]=='super-admin'){
                self::$prefix=$u[0];
            }else{
                self::$prefix_account=$u[0]??'';
                self::$prefix=$u[1]??'';
            }
        }
        return self::$prefix;
    }
    /**
     * Retorna ao login da conta com base na url da requisição
     */
    public static function accountPrefix(){
        if(!self::$prefix_account)self::adminPrefix();//apenas executa a função para atualizar as veriáveis
        return self::$prefix_account;
    }
    /**
     * Retorna (boolean) se está em um ambiente super-admin
     */
    public static function isSuperAdminPrefix(){
        return self::adminPrefix()=='super-admin';
    }


    //Retorna as labels dos campos
    public static function label($name=null){
        return $name ? self::$labels[$name]??'' : self::$labels;
    }
    
    //Seta um item do menu lateral para ficar com foco / marcardo selecionado
    //@param $name - o nome do item do menu (atributo data-name no html / chave da matriz AdminClass/BaseClass::getMenus())
    public static function setItemMenu($name){
        \App\Http\Controllers\AdminClass\BaseClass::$menuSelected=$name;
    }
    
}