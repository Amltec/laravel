<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\Account;
use App\Models\User;
use App\Models\UserAccountRelation;
use App\Utilities\ValidateUtility;
use Auth;
use App\Services\UsersService;
use App\Services\LogsService;
use App\Services\AccountsService;
use App\Services\AccountPassService;

use App\Http\Controllers\Traits\AccountsTrait;

/**
 * Class AccountsController.
 */
class AccountsController extends SuperAdminBaseController{

    use AccountsTrait;

    public function __construct(Account $AccountModel,User $UserModel, UserAccountRelation $UserAccountRelationModel, UsersService $userService){
        parent::__construct();
        $this->accountModel = $AccountModel;
        $this->userModel = $UserModel;
        $this->UserAccountRelation=$UserAccountRelationModel;
        $this->userService = $userService;
    }


    /**
     * Lista as contas
     */
    public function index(Request $request){
        $accounts = $this->accountModel;
        $filter_status=$request->input('status');
        if(empty($filter_status)){
            $accounts->where('account_status','!=','c');
        }else{
            $accounts->where('account_status',$filter_status);
        }
        if(_GET('is_trash')=='s')$accounts=$accounts->onlyTrashed();

        $accounts = $accounts->orderBy('account_name', 'asc')->paginate(_GETNumber('regs')??15);

        $account_id_Logged=\Auth::user()->getAuthAccount('id');

        return view('super-admin.accounts.index', [
            'accounts' => $accounts,
            'account_id_Logged'=>$account_id_Logged
        ]);
    }


    public function create() {
        $users = $this->userModel::where('user_status','!=', 'c')->pluck('user_name','id');
        //dd($users);
        return view('super-admin.accounts.create', [
            //'users_list'=>$users,
        ]);
    }



    public function store(Request $request,$id=null){
        $data = $request->all();
        if($id){//edit
            $action='edit';
            $param1 = [
                'account_name'=>'required|max:100',
                'account_email'=>'required|max:150',
                'account_status'=>'required',
            ];
            unset($data['account_login']);
        }else{//add
            $action='add';
            $param1 = [
                'account_name'=>'required|max:100',
                'account_email'=>'required|max:150',
                'account_login'=>'required|max:50|unique:accounts,account_login',
            ];
            $data['account_status']='a';//cadastro normal
            $data['account_key']=md5(time());
        }
        $msgValidator = \App\Utilities\FieldsValidatorUtility::getMessages();
        $validade = validator($data, $param1, $msgValidator);
        if($validade->fails()){
            return ['success'=>false,'msg'=>$validade->errors()->messages()];
        }

        if(in_array($data['account_login']??'',['system']))return ['success'=>false,'msg'=>['account_login'=>'Palavra reservada do sistema']];

        try{
            if($id){
                $model = $this->accountModel->find($id);
                $model->update($data);
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit'
                ];
            }else{
                $model = $this->accountModel->create($data);
                $id = $model->id;
                $r=[
                    'success'=>true,
                    'msg' => 'Registro cadastrado',
                    'action'=>'add',
                    'url_edit' => route('super-admin.app.edit',['brokers',$id]),
                    'data' => $model->toArray(),
                ];
            }

        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }

        //captura as configurações padrões forçadas $this->getAccountConfig() e grava-as novamente
        //motivo: caso esteja alguma configuração faltando, esta função irá atualizar de acordo com um padrão para gravar novamente
        $config = AccountsService::getAccountConfig($model,true);//true para forçar a leitura no db
        $model->setData('config', $config);
        $model->setData('robot_start', 'off');

        //$user_current = Auth::user();
        //desconecta todas as sessões do usuário desta conta
        if($data['account_status']=='c')$this->userService->reLoginAllUsers($model->id);

        //adiciona a ação no log
        $model->addFieldsLog($action,$data,'accounts-edit', ($action=='edit'?'denied:account_login':'') );

        return $r;
    }


    public function edit(Request $request,$id){
        $account = $this->accountModel->find($id);
        if(!$account)$account = $this->accountModel->onlyTrashed()->find($id);//verifica na lixeira
        $data = $account->getMetadata();
        $pag = $request->input('pag');if(!$pag)$pag='info';

        if(in_array($pag, ['info','edit','images','users','config','pass','actions'])){
            $method='edit_'.$pag.'Index';
            if(method_exists($this,$method)){
                $pagClass = $this->$method($account,$data);
            }else{
                $pagClass = [];
            }

            return view('super-admin.accounts.'.$pag, [
                'account' => $account,
                'dataAccount' => $data,
                'userLogged' => \Auth::user(),
                'params' => $pagClass,  //parâmetros de cada $pag
            ]);
        }else{
            return 'view não encontrada';
        }
    }

    public function update(Request $request, $id){
        return $this->store($request,$id);
    }


    /**
     * Remove or restore
     * Valores esperados: action:trash|restore|remove, id
     */
    public function remove(Request $request){
        $data = $request->all();

        if($data['action']=='remove'){
            $r = $this->destroy($data['id']);

        }else if($data['action']=='restore'){
            $model = $this->accountModel->onlyTrashed()->find($data['id']);
            $model->restore();
            $model->addLog('restore');//adiciona a ação no log
            $r=['success'=>true,'msg' => 'Conta restaurada'];


        }else if($data['action']=='trash'){
            $account = $this->accountModel->find($data['id']);
            if($account===1){
                return ['success'=>false,'msg'=>'Não é possível remover a conta principal (blocked)'];
            }
            if(!$this->checkAllowRemove($data['id']))return ['success'=>false,'msg'=>'Não é possível remover conta (related)'];

            $account->delete();//irá mandar para a lixeira
            $account->addLog('trash');//adiciona a ação no log

            $r=['success'=>true,'msg' => 'Conta movida para a lixeira'];
        }
        return $r;
    }


    /**
     * Deleta o registro.
     * Obs: o registro precisa estar na lixeira para prosseguir com esta ação
     */
    public function destroy($id){
        if(!$this->checkAllowRemove($id))return ['success'=>false,'msg'=>'Não é possível remover conta (related)'];

        $account = $this->accountModel->onlyTrashed()->find($id);
        if(!$account){
            $r=['success'=>false,'msg'=>'Registro inválido para exclusão'];
        }else if($account->id>1){//não pode excluir a conta principal
            try{
                $this->UserAccountRelation->where('account_id',$id)->delete();//deleta o registro
                $account->delMetadata();//deleta todos os metadados deste registro
                //dd(12,$id,$account);
                $account->delLog();//deleta dos os dados do log com ações para esta conta (usa area_name e area_id)
                $account->delLogAll(['account_id'=>$id]);//deleta dos os dados referente a esta conta
                $r=['success'=>true,'msg' => 'Conta removido'];
                $account->addLog('del');//adiciona a ação no log
                $account->forceDelete();//deleta o registro
            } catch (Exception $e) {
                $r=['success'=>false,'msg'=>$e->getMessage()];
            }
        }else{
            $r=['success'=>false,'msg'=>'Não é possível remover conta'];
        }

        return $r;
    }


    //Atualização dos logotipos
    public function post_filesUpdate(Request $request){
        $data = $request->all();
        return $this->setFilesUpdate($data,$this->accountModel->find($data['id']));
    }


    /**
     * Verifica se o registro da conta pode ser excluído
     * Return boolean
     */
    private function checkAllowRemove($account_id){
        $tbls=\DB::table('accounts')->selectRaw(
                '(select count(1) from user_account_relations where user_account_relations.account_id=accounts.id) as users_count,'.    //usários da conta
                '(select count(1) from files where files.account_id=accounts.id) as files_count,'.                                      //arquivos
                '(select count(1) from brokers where brokers.account_id=accounts.id) as brokers_count,'.                                //corretores
                '(select count(1) from process_robot where process_robot.account_id=accounts.id) as process_count,'.                    //processos do robô
                '(select count(1) from account_pass where account_pass.account_id=accounts.id) as account_pass_count,'.                 //senhas associadas a conta
                '(select count(1) from robots where CONCAT(",",robots.account_ids,",") like CONCAT("%,", accounts.id,",%")) as robots_count'   //instâncias do robô
           )->where('id',$account_id)->first();
        foreach((array)$tbls as $f=>$v){
            if($v>0)return false;
        };
        return true;
    }






    //*********** account edit - complementary functions *************


    /**
     * Salva as configurações
     */
    public function post_configSave(Request $request){
        $model = $this->accountModel->find($request->input('account_id'));

        $model->update(['process_single'=>$request->input('process_single')=='s']);

        $r=$this->defaultConfigSave($model,$request,'superadmin');
        if($r['success'])\App\Services\LogsService::addFields('save','accounts-config',$model->id,$request->all());
        return $r;
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
        return AccountPassService::editAjax($request->input('account_id'),$request->input('pass_id'),'super-admin');
    }

    /**
     * Salva os dados da senha do quiver
     */
    public function post_passSaveAjax(Request $request){
        return AccountPassService::save($request->input('account_id'),$request->all());
    }

    /**
     * Tira a trava do cadastro de login
     */
    public function post_setPassNotBusy(Request $req){
        AccountPassService::setLoginNotBusy($req->id);
        return ['success'=>true];
    }


    /**
     * Remove a senha do quiver
     */
    public function post_passRemoveAjax(Request $request,$account_id){
        return AccountPassService::remove($account_id,$request->input('id'));
    }


    /**
     * Retorna a lista dos usuários cadastrados da respectiva conta
     */
    private function edit_usersIndex($account,$dataAccount){
        $users=$this->userModel->whereIn('user_level',['admin','user'])->whereAccount($account->id)->orderBy('users.id', 'desc');
            if(_GET('is_trash')=='s')$users->onlyTrashed();
            //;dd($users->toSql(), $users->getBindings());
        $users=$users->paginate(_GETNumber('regs')??15);

        return [
            'users_list'=>$users
        ];
    }


    /**
     * Carrega a view de edição do usuário
     */
    public function get_userEditAjax(Request $request){
        $data = $request->all();
        extract($data);//esperado: user_id, account_id

        if($user_id??false){//edit
            $user=$this->userModel->whereAccount($account_id)->find($user_id);
            if(!$user)return 'Erro ao localizar registro';
            if(in_array($user->user_level,['dev','superadmin']))return 'Registro bloqueado (level)';
        }else{//add
            $user=null;
        }

        //utiliza a view templates.ajax_load para carregar os recursos javascript corretamente
        return view('templates.ajax_load',['view'=>'super-admin.accounts.user_edit_ajax','data'=>[
            'user'=>$user,
            'account_id'=>$account_id,
            'user_level_Logged'=>\Auth::user()->user_level
        ]]);
    }

    /**
     * Salva os dados do usuário
     */
    public function post_userSaveAjax(Request $request){
        $data = $request->all();
        $user_id = $data['user_id'];
        $account_id = $data['account_id'];
        $action = $user_id?'edit':'add';

        $r = $this->userService->updateOrCreate(
                $account_id,
                ($action=='add' ? null : $user_id),
                $data,
                function($data){
                    if(!$this->checkSaveUserLevel($data['user_level']))return ['success'=>false,'msg'=>'Permissão inválida para este usuário'];
                }
            );

        if($r['success'] && $action=='edit'){
            $user = $r['model'];
            //permissões de login (atualiza somente se houver algum valor no campo)
            $n = $this->userService->savePermissLogin($user,$request);
            if(!$n['success'])return $n;
        }

        return $r;
    }

    /**
     * Faz o logoff do usuário especificado conta exigindo um novo login
     */
    public function post_userIdReLogin($user_id){
        return $this->userService->reLogin($user_id);
    }

    /**
     * Verifica o usuário logado tem permissão para prosseguir com o cadastro/atualização de acordo com seu nível de acesso
     * @param $user_level para comparar
     * @return boolen
     */
    private function checkSaveUserLevel($user_level){
        return $this->userService::checkAllowLevel(Auth::user(),$user_level);
    }

    /**
     * Remove o usuário
     */
    public function post_userRemoveAjax(Request $request){
        $data = $request->all();
        $r = $this->userService->remove($data['action'],$data['id'],function($action,$user){
                if($action=='trash'){
                     if(in_array($user->user_level,['dev','superadmin']))return ['success'=>false,'msg'=>'Não é possível remover este usuário (nível bloqueado)'];
                }
            });
        return $r;
    }


    //********** ações da conta **********
    /**
     * Faz o login a partir da área administrativa para um user superadmin
     */
    public function post_doLogin(Request $request){
        $account_id = $request->input('id');
        //apenas armazena o id da conta no campo que indica a respectiva conta do usuário logado
        $user = Auth::user();
        $user->setMetadata('auth_account_id',$account_id);//seta a conta do usuário logado no momento
        $user->addLog('login');//adiciona a ação no log

        $model = $this->accountModel->find($account_id);
        return ['url'=>route('admin.index_account',$model->account_login)];
    }
    /**
     * Faz o logoff a partir da área administrativa para um user superadmin
     */
    public function post_doLogoff(Request $request){
        $account_id = $request->input('id');
        return ['success'=>true];
    }
    /**
     * Faz o logoff de todos os usuários da conta exigindo um novo login
     */
    public function post_doUsersReLogin($account_id){
        return $this->userService->reLoginAllUsers($account_id,Auth::user()->id);
    }
}
