<?php

namespace App\Services;

use App\Utilities\ValidateUtility;
use App\Models\User;
use App\Models\LoginAttempt;
use Auth;

/**
 * Classe de serviço de usuários.
 */
class UsersService{
    
    //níveis de usuários permitidos
    public static $levels = [
        'dev'=> 'Programador',
        'superadmin'=>'Super Administador',
        'admin'=>'Administrador',
        'user'=>'Usuário'
    ];
    
    
    public function __construct(){
        $this->userModel = new \App\Models\User;
    }
    
    
    /**
     * Return model user
     */
    public function getModel(){
        return $this->userModel;
    }
    
    /**
     * Retorna a model User
     * @param int $account - id da conta. Aceita null para ignorar este parâmetro e procurar por todos os usuários
     * @param int $id
     */
    public function getById($account_id,$id){
        if($account_id){
            return $this->userModel->whereAccount($account_id)->find($user_id);
        }else{
            return $this->userModel->find($id);
        }
    }
    
    
    /**
     * Adiciona ou atualiza o usuário
     * @param $user_id - se informado é atualização, caso contrário adiciona o registro
     */
    public function updateOrCreate($account_id,$user_id,$data,$cb_filter=null,$opt=[]){
        if($user_id){
            return $this->update($account_id,$user_id,$data,$cb_filter,$opt);
        }else{
            return $this->create($account_id,$data,$cb_filter,$opt);
        }
    }
    
    /**
     * Insere um usuário
     * @param $account_id - id da conta. Aceita as strings 'dev' ou 'superadmin' para indicar um super adminsitrador
     * @param array $data - campos
     *        requeridos: user_name, user_email, user_level, user_pass, user_pass2
     *        opcionais: user_alias, user_status
     * @param function $cb_filter - função a ser executada antes da atualização. 
     *              Se tiver retorno, interrompe a função. Sintaxe Return array: [success, msg].
     *              Recebe os parâmetros: $data, $user
     *              Ex: function($data){return false;}
     * @param array opts - valores:
     *      area_name - superusers, users (default)
     */
    public function create($account_id,$data,$cb_filter=null,$opt=[]){
        $opt = array_merge([
            'area_name'=>'users'
        ],$opt);
        
        $strvalid = [
            'user_name'=>'required',
            'user_email'=>'required|max:150|unique:users,user_email',
            'user_level'=>'required',
            'user_pass'=>'required|min:6|max:20',
            'user_pass2'=>'required|required_with:user_pass|same:user_pass',
        ];
        
        if(in_array($account_id,['dev','admin'])){
            $data['user_level']=$account_id;
        }else{
            if(!is_numeric($account_id))return ['success'=>false,'msg'=>'Id da conta inválido'];
        }
        
        $msgValidator = \App\Utilities\FieldsValidatorUtility::getMessages();
        $validade = validator($data, $strvalid, $msgValidator);
        //dd($data, $strvalid, $msgValidator);
        if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
        if(!in_array($data['user_level'],array_keys(self::$levels)))return ['success'=>false,'msg'=>'Nível de usuário '. strtoupper($data['user_level']) .' inválido'];
        
        $data['user_pass'] = \Hash::make($data['user_pass']);//criptografa a senha
        if(empty($data['user_alias']))$data['user_alias'] = $data['user_name'];
        if(!in_array(($data['user_status']??''),['0','a','c']))$data['user_status'] = 'a';//cadastro normal
        
        if($cb_filter){
            $r = callstr($cb_filter,[$data],true);
            if($r)return $r;
        }
        
        //adiciona o usuário
        $user = $this->userModel->create($data);
        
        if(is_numeric($account_id)){
            //vincula o usuário a conta padrão do sistema
            $user->setAccountRelation('add',$account_id);
        }
        
        //adiciona o logo
        $user->addFieldsLog('add',$data,$opt['area_name'],'denied:user_pass2');//adiciona a ação no log
        
        return ['success'=>true,'msg' => 'Usuário cadastrado','action'=>'add','model'=>$user];
    }
    
    
    /**
     * Atualiza um usuário
     * @param $account_id - id da conta. Aceita as strings 'dev' ou 'superadmin' para indicar um super adminsitrador
     * @param array $data - campos
     *        requeridos: user_name, user_email, user_level (aceita null), user_pass, user_pass2
     *        opcionais: user_alias, user_status
     * @param function $cb_filter - função a ser executada antes da atualização. 
     *              Se tiver retorno, interrompe a função. Sintaxe Return array: [success, msg].
     *              Recebe os parâmetros: $data, $user
     *              Ex: function($data,$user){return false;}
     * @param array opts - valores:
     *      area_name - superusers, users (default)
     */
    public function update($account_id,$user_id,$data,$cb_filter=null,$opt=[]){
        $opt = array_merge([
            'area_name'=>'users'
        ],$opt);
        
        $strvalid = [
            'user_name'=>'required',
            'user_email'=>'required|max:150|unique:users,user_email,'.$user_id,//ignora o registro do id atual
            //'user_level,
            //user_status
            //user_level
            //user_pass
            //user_pass2
        ];
        
        if(in_array($account_id,['dev','admin'])){
            //nenhuma ação
        }else{
            if(!is_numeric($account_id))return ['success'=>false,'msg'=>'Id da conta inválido'];
        }
        
        $msgValidator = \App\Utilities\FieldsValidatorUtility::getMessages();
        $validade = validator($data, $strvalid, $msgValidator);
        if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
        
        if(!empty($data['user_pass'])){//foi definida uma senha
            $r=$this->passValidator($data['user_pass'],$data['user_pass2']);
            if(!$r['success'])return $r;
        }
        
        if(isset($data['user_status']) && !in_array($data['user_status'],['0','a','c']))return ['success'=>false,'msg'=>'Status '. strtoupper($data['user_status']) .' inválido'];
        if(isset($data['user_level']) && !in_array($data['user_level'],array_keys(self::$levels)))return ['success'=>false,'msg'=>'Nível de usuário '. strtoupper($data['user_level']) .' inválido'];
        
        if(in_array($account_id,['dev','admin'])){
            $user = $this->userModel->find($user_id);
        }else{
            $user = $this->userModel->whereAccount($account_id)->find($user_id);
        }
        if($user){
            if($cb_filter){
                $r = callstr($cb_filter,[$data,$user],true);
                if($r)return $r;
            }
            if($user->user_status=='0' && $data['user_status']=='a'){//quer dizer que está bloqueado e foi setado um status normal
                $this->clearLoginAttempt($user_id);//limpa as tentativas de login
            }
            
            $user_pass=$data['user_pass']??'';
            unset($data['user_pass']);
            
            try{
                $user->update($data);
                $user->addFieldsLog('edit',$data,$opt['area_name'],'denied:user_pass2');//adiciona a ação no log
                $r=['success'=>true,'msg' =>'Registro atualizado','action'=>'edit','model'=>$user];
            }catch (Exception $e){
                $r=['success'=>false,'msg'=>$e->getMessage()];
            }
            
            if($user_pass){
                $n=$this->passUpdate($user,$user_pass);
                if(!$n['success']){
                    $r=['success'=>façse,'msg' =>'Registro atualizado, mas ocorreu um erro ao gravar a senha'];
                }
            }
            
        }else{
            $r=['success'=>false,'msg'=>'Erro ao localizar cadastro de usuário'];
        }
        
        return $r;
    }
    
    
    /**
     * Valida a senha do usuário
     */
    public function passValidator($user_pass,$user_pass2){
        $msgValidator = \App\Utilities\FieldsValidatorUtility::getMessages();
        $data=[
            'user_pass'=>$user_pass,
            'user_pass2'=>$user_pass2
        ];
        $rules=[
            'user_pass'=>'required|min:6|max:20',
            'user_pass2'=>'required|required_with:user_pass|same:user_pass'
        ];        
        $validade = validator($data,$rules, $msgValidator);
        if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
        return ['success'=>true];
    }
    
    /**
     * Altera a senha do usuário
     */
    public function passUpdate($user,$user_pass){
        $model = is_object($user) ? $user : $this->userModel->find($user);
        $user_pass = \Hash::make($user_pass);//criptografa a senha
        $model->update(['user_pass'=>$user_pass]);
        
        if($model->id == Auth::id()){//é o usuário logado que alterou a senha
            Auth::login($model);//mantém o usuário logado
        }
        return ['success'=>true];
    }
    
    
    /**
     * Limpa as tentativas de login
     */
    private function clearLoginAttempt($user_id){
        LoginAttempt::where('user_id',$user_id)->delete();
    }
    
    
    /**
     * Remove um usuário
     * @param $action - remove, restore, trash
     * @param function $cb_filter - função a ser executada antes da exclusão. 
     *              Se tiver retorno, interrompe a função. Sintaxe Return array: [success, msg].
     *              Recebe os parâmetros: $action, $user
     *              Ex: function($data,$user){return false;}
     * @param array opts - valores:
     *      area_name - superusers, users (default)
     */
    public function remove($action,$id,$cb_filter=null,$opt=[]){
        $opt = array_merge([
            'area_name'=>'users'
        ],$opt);
        
        if($action=='remove'){
            $r = $this->destroy($id,$cb_filter,$opt);
            
        }else if($action=='restore'){
            $user=$this->userModel->onlyTrashed()->find($id);
            if($cb_filter){
                $r = callstr($cb_filter,[$action,$user],true);
                if($r)return $r;
            }
            $user->restore();
            $user->addLog('restore',null,$opt['area_name']);//adiciona a ação no log
            
            $r=['success'=>true,'msg' => 'Usuário restaurado'];
            
        }else if($action=='trash'){
            $user = $this->userModel->find($id);
            if($user){
                if($cb_filter){
                    $r = callstr($cb_filter,[$action,$user],true);
                    if($r)return $r;
                }
                if(!$this->checkAllowRemove($id))return ['success'=>false,'msg'=>'Não é possível remover usuário (related)'];
                $user->delete();//irá mandar para a lixeira
                $user->addLog('trash',null,$opt['area_name']);//adiciona a ação no log
            }
            $r=['success'=>true,'msg' => 'Usuário movido para a lixeira'];
        }
        return $r;
    }
    
    
    /**
     * Deleta o registro.
     * Obs: o registro precisa estar na lixeira para prosseguir com esta ação
     */
    private function destroy($id,$cb_filter=null,$opt=[]){
        $user = $this->userModel->onlyTrashed()->find($id);
        if($user){
            if($cb_filter){
                $r = callstr($cb_filter,['remove',$user],true);
                if($r)return $r;
            }
            //deleta o usuário
            try{
                LoginAttempt::where('user_id',$id)->delete();//deleta as tentativas de login
                $user->setAccountRelation('del');//deleta a relação com a tabela account
                $user->delMetadata();//deleta os metadados registro
                $user->forceDelete();//deleta o registro principal
                $user->addLog('remove',null,$opt['area_name']);//adiciona a ação no log
                $r=['success'=>true,'msg' => 'Usuário removido'];
            } catch (Exception $e) {
                $r=['success'=>false,'msg'=>$e->getMessage()];
            }
        }else{
            $r=['success'=>false,'msg'=>'Não é possível remover usuário'];
        }
        return $r;
    }
    
    
    /**
     * Salva as permissões de login
     */
    public function savePermissLogin($user,$request){
        $data = $request->login_permiss;
        if($data){
            $data = json_decode($data,true);
            if(json_last_error() == JSON_ERROR_NONE){
                $user->setMetaData('login_permiss',$data);
            }else{
                return ['success'=>false,'msg'=>'Dados da permissões de login inválidos'];
            }
        }
        return ['success'=>true];
    }
    
    
    /**
     * Faz o logoff do usuário especificado conta exigindo um novo login
     */
    public function reLogin($user_id){
        $this->userModel->find($user_id)->update(['re_login'=>true]);
        return ['success'=>true];
    }
    
    /**
     * Faz o logoff do usuário especificado conta exigindo um novo login
     */
    public function reLoginAllUsers($account_id,$id_user_not=null){
        $user = $this->userModel->WhereAccount($account_id);
        if($id_user_not)$user->where('id','<>',$id_user_not);
        $user->update(['re_login'=>true]);
        return ['success'=>true];
    }
    
    /**
     * Desconecta o usuário logado dos demais dispositivos (mas mantém o atual)
     */
    public function authLogoffDevices($user_pass){
        $validade = validator(['user_pass'=>$user_pass],['user_pass'=>'required|min:6|max:20']);
        if($validade->fails())return ['success'=>false,'msg'=>'Senha inválida'];
        Auth::logoutOtherDevices($user_pass,'user_pass');//desconecta de todas as outras sessões/dispositivos
        return ['success'=>true];
    }
    
    /**
     * Verifica se o registro do usuário pode ser excluído
     * Return boolean
     */
    public function checkAllowRemove($user_id){
        $tbls=$this->userModel->selectRaw(
                //'(select count(1) from user_account_relations where user_account_relations.user_id=users.id) as users_count,'. //usários da conta
                '(select count(1) from files where files.user_id=users.id) as files_count,'.                                     //arquivos
                '(select count(1) from posts where posts.user_id=users.id) as posts_count,'.                                     //posts
                '(select count(1) from process_robot where process_robot.user_id=users.id) as process_count '                    //processos do robô
                //'(select count(1) from user_logs where user_logs.user_id=users.id) as logs_count'                              //registro de logs (verifica e não deixa excluir, pois precisa ter registro do que o usuário fez)
           )->where('id',$user_id)->first();
        foreach($tbls->attributesToArray() as $f=>$v){
            if($v>0)return false;
        };
        return true;
    }
    
    
    /**
     * Retorna a lista separada de usuários da conta e superadmins
     * @return array [...]  ||  [users=>..., superadmins=>...]
     */
    public function getListByAdmin($opt=null){
        $opt = array_merge([
            'account_id'=>null,         //filtro de usuário por conta
            'superadmins'=>true,        //inclui na lista os usuários superadmin e dev
            'merge'=>false,             //se true irá retorna todos os usuários em uma só lista, se false irá retornar em [users,superadmins
            'key_names'=>['users','superadmins'],    //nomes da chaves no retorno (válido para merge=false)
            'key_empty'=>true,          //se true exibe o campo no retorno mesmo vazio
        ],$opt);
        
        $users=[];
        $supers=[];
        
        //usuários da conta
        if($opt['account_id']){//usuários da conta
            $usersAccount = $this->userModel->whereAccount($opt['account_id'])->whereNotIn('user_level',['dev','superadmin'])->orderBy('user_alias','asc')->get();
            foreach($usersAccount as $reg){
                $s=$reg->user_status;
                $users[$reg->id] = $reg->user_alias . ($s=='c'?' - Cancelado':($s=='0'?' - Bloqueado':''));
            }
        }

        //usuários superadmin
        if($opt['superadmins']){
            $userSuperadmin = $this->userModel->whereIn('user_level',['dev','superadmin'])->orderBy('user_alias','asc')->get();
            foreach($userSuperadmin as $reg){
                $s=$reg->user_status;
                $supers[$reg->id] = $reg->user_alias . ($s=='c'?' - Cancelado':($s=='0'?' - Bloqueado':''));
            }
        }
        
        if($opt['merge']){
            return $supers + $users;
        }else{
            $r=[];
            if($users || $opt['key_empty'])$r[$opt['key_names'][0]] = $users;
            if($supers || $opt['$key_empty'])$r[$opt['key_names'][1]] = $supers;
            return $r;
        }
    }
    
    
    /**
     * Verifica se um usuário tem permissão para prosseguir em relação ao nível comparado de acesso
     * @param $user
     * @param $compare_level para comparar
     * @param $self_level - se true irá incluir o próprio nível para comparação, false (default) inclui os níveis abaixo apenas
     * @return boolen
     */
    public static function checkAllowLevel($user, $compare_level, $self_level=false){
        $level_logged = $user->user_level;
        $allows=[];
        if($level_logged=='dev'){
            $allows=['superadmin','admin','user'];
        }elseif($level_logged=='superadmin'){
            $allows=['admin','user'];
        }else if($level_logged=='admin'){
            $allows=['user'];
        }//user - nenhum
        if($self_level)$allows[]=$level_logged;
        //dd($level_logged,$compare_level,$allows);
        return in_array($compare_level,$allows);
    }
    
    
    
    
    /**
     * Retorna aos níveis dos usuários logados
     * @param boolean $restrict_auth - se true irá filtrar a lista com base no nível do usuário logado. Ex: se o usuário for admin, não poderá setar o nível superadmin
     * @return array
     */
    public static function getLevels($restrict_auth=false){
        if($restrict_auth){
            $user=Auth()->user;
            $r=[];
            foreach(self::$levels as $l=>$t){
                if(self::checkAllowLevel($user,$l))$r[$l]=$t;
            }
            return $r;
        }else{
            return self::$levels;
        }
    }
            
    
    
}