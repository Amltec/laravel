<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use App\Utilities\ValidateUtility;
use App\Services\LogsService;
use Gate;
use App\Models\Account;
use App\Services\AccountsService;
use App\Services\AccountPassService;

use App\Http\Controllers\Traits\AccountsTrait;

/**
 * Classe de configuração do cliente / somente da conta do usuário logado
 */
class AccountController extends Controller {
    //somente usuários autenticados podem acessar este controle (autenticação pelo controller)
     
    use AccountsTrait;
    
    
    public function __construct(Account $AccountModel){
        if(Gate::denies('admin')){//é negado a permissão para não administrador
            $this->Redirector->to(route('admin.index'))->send();
        }
        //filtra a model pelo usuário logado
        $this->accountModel = $AccountModel->find(\Auth::user()->getAuthAccount('id'));
        if(!$this->accountModel)exit('Erro. Registro removido ou não existe');
        if($this->accountModel->account_status!='a')return ['success'=>false,'msg'=>'Esta conta está cancelada. Contate o administrador.'];
    }
    
    
    //configurações gerais
    public function index(Request $request){
        $model = $this->accountModel;
        $data = $model->getData();
        //dd($model,$data);
        $pag = $request->input('pag');if(!$pag)$pag='edit';
        
        if(in_array($pag, ['edit','images'])){//,'pass','config'
            $method='edit_'.$pag.'Index';
            if(method_exists($this,$method)){
                $pagClass = $this->$method($model,$data);
            }else{
                $pagClass = [];
            }
            
            return view('admin.account.'.$pag, [
                'account' => $model,
                'dataAccount' => $data,
                'userLogged' => \Auth::user(),
                'params' => $pagClass,  //parâmetros de cada $pag
            ]);
        }else{
            return 'view não encontrada';
        }
    }
    
    
    public function post_datasave(Request $request){
        $data = $request->all();
        $param1 = [
            'account_name'=>'required|max:100',
            'account_email'=>'required|max:150',
        ];
        $msgValidator = \App\Utilities\FieldsValidatorUtility::getMessages();
        $validade = validator($data, $param1, $msgValidator);
        if($validade->fails())return ['success'=>false,'msg'=>$validade->errors()->messages()];
        
        try{
            $this->accountModel->update($data);
            LogsService::addFields('save','config',$this->accountModel->id,$data);
            $r=['success'=>true,'msg' => 'Registro atualizado','action'=>'edit'];
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        return $r;
    }
    
    
    
    //Atualização dos logotipos
    public function post_filesUpdate(Request $request){
        return $this->setFilesUpdate($request->all(),$this->accountModel);
    }
    
    
    /**
     * Salva as configurações
     */
    public function post_configSave(Request $request){
        //temporariamente desabilitado (06/04/2021), pois aparentemente esta configuração é necessária paenas para o superadmin
        /*$r=$this->defaultConfigSave($this->accountModel,$request,'admin');
        LogsService::addFields('save','config-config',$this->accountModel->id,$request->all());
        return $r;*/
    }
    
    
    /**
     * Retorna a lista das senhas cadastradas na tabela account_pass
     */
    private function edit_passIndex($account,$dataAccount){
        return [
            'pass_list'=> AccountPassService::getList($account->id),
            'is_new_login_allow'=>AccountPassService::isNewLoginAllow($account->id), 
            'count_login'=> AccountPassService::countLogin($account->id), 
            'instances'=> AccountsService::getConfig($account->id,'instances',1), 
        ];
    }
    
    
    /**
     * Carrega a view de edição da senha do Quiver
     */
    public function get_passEditAjax(Request $request){
        return AccountPassService::editAjax($this->accountModel->id,$request->input('pass_id'),'admin');
    }
    
    /**
     * Salva os dados da senha do quiver
     */
    public function post_passSaveAjax(Request $request){
        return AccountPassService::save($this->accountModel->id,$request->all());
    }
    
    /**
     * Remove a senha do quiver
     */
    public function post_passRemoveAjax(Request $request){
        return AccountPassService::remove($this->accountModel->id,$request->input('id'));
    }
    
    
    
}
