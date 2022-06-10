<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAuth;
use App\Models\LoginAttempt;
use Exception;
use Gate;
use Auth;
use App\Services\UsersService;

/**
 * Class UsersController.
 * O acesso a este controller é sempre usando a model UserAuth, para validar somente por usuários autenticados
 */
class UsersController extends Controller{
    
    public function __construct(UserAuth $UserModel, UsersService $userService){
        $this->userModel = $UserModel;
        $this->userService = $userService;
    }
    
    
    public function index(Request $request){
        if(Gate::denies('admin')){//é negado a permissão para não administrador
            return redirect()->route('admin.index')->send();
        }
        
        $users = $this->userModel
                ->where('user_level','<>','dev')
                ->where('id','<>',Auth::user()->id);//não exibe o usuário logado
            if(_GET('is_trash')=='s')$users->onlyTrashed();
            //dd($users->toSql(),$users->getBindings());
        $users=$users->orderBy('id', 'desc')->paginate(_GETNumber('regs')??15);
        
        return view('admin.user_index', [
            'users'=>$users,
            'user_id_Logged'=>Auth::id(),
        ]);
    }
    
    
    public function create() {
        if(Gate::denies('admin'))return 'Acesso negado';
        return view('admin.user_create', [
             'user_level_Logged'=>Auth::user()->user_level,
        ]);
    }

    
    public function store(Request $request){
        $data = $request->all();
        
        if(Auth::user()->user_level=='admin'){
            $data['user_level']='user';
        }else{
            if(!$this->checkSaveUserLevel($data['user_level']))return ['success'=>false,'msg'=>'Permissão inválida para este usuário'];
        }
        
        //respectiva conta do usuário logado, que corresponde a conta do usuário que deve ser atualizado
        $account_id=\Config::accountID();
        
        $r = $this->userService->create($account_id,$data);
        if($r['success']){
            $user = $r['model'];
            $r['url_edit'] = route('admin.app.edit',['users',$user->id]);
            
            $dataret = $user->toArray();
            unset($dataret['user_pass']);
            $r['data'] = $dataret;
            
        }
        return $r;
    }

    
    public function show($id){}

    
    public function edit($id){
        if(Gate::denies('admin'))return redirect()->route('admin.index')->send();//é negado a permissão para não administrador
        $user = $this->userModel->find($id);
        if(!$user)exit('Acesso negado');
        
        //capturas as contas que o usuário gerencia
        $accounts = \App\Models\Account::where('id',$id)->pluck('account_name','id');
        
        $isUserLogged = $user->id == Auth::user()->id;
        if($isUserLogged)exit('Acesso negado(2)');
        
        return view('admin.user_create', [
            'user'=>$user,
            'accounts'=>$accounts,
            'isUserLogged'=>$isUserLogged,
            'user_level_Logged'=>Auth::user()->user_level,
        ]);
    }
    
    
    //edita o perfil do usuário logado
    public function perfilEdit() {
        $user = Auth::user();
        if(\Config::adminPrefix()=='admin' && in_array($user->user_level,['dev','superadmin']))exit('Acesso negado');//a atualização destes dados deve ser feito pela pagina dentro do super-admin
        
        $accounts = \App\Models\Account::where('id',$user->id)->pluck('account_name','id');
                
        return view('admin.user_perfil', [
            'user'=>$user,
            'accounts'=>$accounts,
        ]);
    }
    
    //atualiza os dados do perfil do usuário logado
    public function post_perfilUpdate(Request $request) {
        return $this->updateFnc($request, Auth::user()->id, true);
    }
   
    public function update(Request $request, $id){
        return $this->updateFnc($request, $id);
    }
    
    
    //desconecta o usuário logado dos demais dispositivos
    public function post_logoffDevices(Request $request){
        return $this->userService->authLogoffDevices($request->input('user_pass'));
    }
    
    public function updateFnc(Request $request, $id, $isUpdPerfil=false){
        $data = $request->all();
        $user_level_logged = Auth::user()->user_level;
        
        //respectiva conta do usuário logado, que corresponde a conta do usuário que deve ser atualizado
        $account_id=\Config::accountID();
        
        if($isUpdPerfil==false){
            if($user_level_logged=='admin'){
                $data['user_level']='user';
            }else{
                if(!$this->checkSaveUserLevel($data['user_level']))return ['success'=>false,'msg'=>'Permissão inválida para este usuário'];
            }
            /*if($user_level_logged=='dev'){
                unset($data['user_status']);
            }*/
        }else{//é página de perfil
            unset($data['user_status']);//não precisa atualizar o status
        }
        
        $r = $this->userService->update(
                $account_id,
                $id,
                $data,
                function($data,$user) use($isUpdPerfil){
                    if(!$isUpdPerfil && in_array($user->user_level,['dev','superadmin']))return 'Registro bloqueado (dev)'; //quer dizer que não é atualização pela página do perfil e nível é programador, portanto bloqueia por segurança
                }
            );
            
        if($r['success']){
            $user = $r['model'];
            
            if(!$isUpdPerfil){//na edição do perfil, não pode ser atualizado este campo
                //permissões de login (atualiza somente se houver algum valor no campo)
                $n = $this->userService->savePermissLogin($user,$request);
                if(!$n['success'])return $n;
            }
        }
        
        return $r;
    }
    
    
    /**
     * Verifica o usuário logado tem permissão para prosseguir com o cadastro/atualização de acordo com seu nível de acesso
     * @param $user_level para comparar
     * @return boolen
     */
    private function checkSaveUserLevel($user_level, $self_level=false){
        return $this->userService::checkAllowLevel(Auth::user(),$user_level, $self_level);
    }

    
    /**
     * Remove or restore
     * Valores esperados: action:trash|restore|remove, id
     */
    public function remove(Request $request){
        $data = $request->all();
        $r = $this->userService->remove($data['action'],$data['id']);
        return $r;
    }
    
    
}
