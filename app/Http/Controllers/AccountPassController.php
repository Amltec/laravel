<?php

namespace App\Http\Controllers;

use App\Services\AccountsService;
use App\Services\AccountPassService;
use App\Models\Account;
use Gate;


/**
 * Classe de cadastro de usuários do Quiver
 */
class AccountPassController extends Controller {
    
     public function __construct(Account $AccountModel){
        if(Gate::denies('admin')){//é negado a permissão para não administrador
            $this->Redirector->to(route('admin.index'))->send();
        }
        //filtra a model pelo usuário logado
        $this->accountModel = $AccountModel->find(\Auth::user()->getAuthAccount('id'));
        if(!$this->accountModel)exit('Erro. Registro removido ou não existe');
        if($this->accountModel->account_status!='a')return ['success'=>false,'msg'=>'Esta conta está cancelada. Contate o administrador.'];
    }
    
    
    public function index(){
        $account = $this->accountModel;
        return view('admin.account.pass-page-list', [
            'account' => $account,
            'userLogged' => \Auth::user(),
            'params'=>[
                'pass_list'=> AccountPassService::getList($account->id),
                'is_new_login_allow'=>AccountPassService::isNewLoginAllow($account->id), 
                'count_login'=> AccountPassService::countLogin($account->id), 
                'instances'=> AccountsService::getConfig($account->id,'instances',1), 
            ]
        ]);
    }
    
    
    /**
     * ***************
     * IMPORTANTE: demais ações de adição e atualização de dados, estão no arquivo \App\Http\Controllers\AccountController
     * ***************
     */
    
}
