<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginAttempt;
use Exception;
use Auth;
use App\Services\UsersService;

/**
 * Classe de gerenciamento de super usuários (level=superadmin)
 */
class SuperusersController extends SuperAdminBaseController{
    
    public function __construct(User $UserModel, UsersService $userService){
        parent::__construct();
        if(Auth::user()->user_level!='dev')return self::redirectUserDenied();//permissão negado para o usuário não super administrador
        $this->userModel = $UserModel::whereIn('user_level',['dev','superadmin']);
        $this->userService = $userService;
    }
    
    
    public function index(Request $request){
        $users = $this->userModel->where('id','>','1');//o id=1, é o administrador dev principal e não deve constar nesta lista
        if(_GET('is_trash')=='s')$users->onlyTrashed();
        $users=$users->orderBy('id', 'desc')->paginate(_GETNumber('regs')??15);
        
        return view('super-admin.superuser_index', ['users'=>$users,'user_id_Logged'=>Auth::id()]);
    }
    
    public function create() {
        return view('super-admin.superuser_create');
    }
    
    public function store(Request $request){return $this->userSave($request);}
    
    public function edit($id){
        if((string)$id=='1')return 'Edição negada para o user id=1';//o id=1, é o administrador dev principal e não deve constar nesta lista
        $user = $this->userModel->find($id);
        $isUserLogged = $user->id == Auth::user()->id;
        return view('super-admin.superuser_create',['user'=>$user,'isUserLogged'=>$isUserLogged]);
    }
    
    public function update(Request $request, $id){return $this->userSave($request,$id);}
    
    
    private function userSave($request,$user_id=null,$isUpdPerfil=false){
        $data = $request->all();
        $action = $user_id?'edit':'add';
        $userLogged = Auth::user();
        $show_level=true;
        if($userLogged->id>1){
            $show_level=false;//oculta o campo level
            $data['user_level']='superadmin';//quer dizer que o administrador será sempre um superadmin
        }
        
        if($isUpdPerfil){//é atualização de perfil, ignora alguns campos
             unset($data['user_level']);
        }
        
        $r = $this->userService->updateOrCreate(
                $userLogged->user_level,
                ($action=='add' ? null : $user_id),
                $data,
                null,
                ['area_name'=>'superusers']
            );
        
        if($r['success']){
            $user = $r['model'];
            
            if(!$isUpdPerfil){//na edição do perfil, não pode ser atualizado este campo
                //permissões de login (atualiza somente se houver algum valor no campo)
                $n = $this->userService->savePermissLogin($user,$request);
                if(!$n['success'])return $n;
            }
            
            if($r['action']=='add'){
                $r['url_edit'] = route('super-admin.app.edit',['superusers',$user->id]);

                $dataret = $user->toArray();
                unset($dataret['user_pass']);
                $r['data'] = $dataret;
            }
        }
        
        return $r;
    }
    
    
    //atualiza os dados do perfil do usuário logado
    public function post_perfilUpdate(Request $request){
        return $this->userSave($request,Auth::user()->id,true);
    }
    
    /**
     * Remove or restore
     * Valores esperados: action:trash|restore|remove, id
     */
    public function remove(Request $request){
        $data = $request->all();
        if((string)$data['id']=='1')return 'Edição negada para o user id=1';//o id=1, é o administrador dev principal e não deve constar nesta lista
        
        $r = $this->userService->remove($data['action'],$data['id'],function($action,$user){
                if($action=='trash'){
                    if($user->id==1)return ['success'=>false,'msg'=>'Não é possível remover este usuário (user 1 bloqueado)'];
                }
            },['area_name'=>'superusers']);
        
        return $r;
    }
    
    
    /**
     * Faz o logoff do usuário especificado conta exigindo um novo login
     */
    public function post_userIdReLogin($user_id){
        return $this->userService->reLogin($user_id);
    }
}