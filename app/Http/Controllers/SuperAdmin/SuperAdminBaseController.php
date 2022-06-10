<?php

namespace App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\Controller;
use Gate;

class SuperAdminBaseController extends Controller{
    
    public function __construct(){
        if(!self::allowRouteSuperAdmin())return self::redirectUserDenied();//permissão negado para o usuário não super administrador
    }
    
    /**
     * Verifica se permite o acesso da rota ao usuário logado super admin
     * Return boolen
     */
    public static function allowRouteSuperAdmin(){
        return \Config::adminPrefix()=='super-admin' && Gate::allows('superadmin');
    }
    
    /**
     * Redireciona caso o usuário logado não ser um superadmin
     */
    public static function redirectUserDenied(){
        $account_login = \Config::accountPrefix();
        if($account_login){
            return \Redirect::to(route('admin.index_account',$account_login))->send();
        }else{
            return \Redirect::to(route('admin.index'))->send();
        }
    }
}
