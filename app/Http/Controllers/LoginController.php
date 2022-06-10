<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Account as AccountModel;
use App\Models\LoginAttempt;
use App\Models\User;

class LoginController extends Controller{
    
    private static $max_login_attempt = 10;//número máximo de tentativas de login antes de bloquear o acesso do usuário
    
    //redireciona caso seja acessado diretamente um rota /admin
    public function rdFromUrlAdmin(){
        if(Auth::check()){
            $user=Auth::user();
            $account=$user->getAuthAccount();
            if($account){
                return \Redirect::to( route('admin.index_account',$account->account_login) );
            }else if(in_array($user->user_level,['dev','superadmin'])){
                return redirect()->route('super-admin.index');
            }else{
                $last_account_id = $user->getMetaData('auth_account_id');//id da última conta logada
                if($last_account_id){
                    $account_login=\App\Models\Account::select('account_login')->find($last_account_id)->value('account_login');
                    if($account_login){
                        return \Redirect::to( route('admin.index_account',$account_login) );
                    }
                }
                return redirect()->route('login');
            }
        }else{
            return redirect()->route('login');
        }
    }
    
    //carrega a página de login
    public function login($account_name=null){
        $account=null;
        if(session('redir_home_login')=='ok'){
            return view('auth.login',['account'=>$account]);
        }else{
            if($account_name)$account=AccountModel::where('account_login',$account_name)->first();
            if(!$account && !$account_name){//redireciona para a página de login sem conta
                session()->flash('redir_home_login','ok');
                return \Redirect::to(route('login'))->send();
            }else{
                return view('auth.login',['account'=>$account]);
            }
        }
    }
    
    //carrega a página de login via ajax
    public function loginAjax(){
        return view('auth.login-ajax');
    }
    
    //Faz a autenticação do login
    public function auth(Request $request,$account_login=null){//
        $data = [
            'user_email'=>$request->get('email'),
            'password'=>$request->get('senha')//obs: aqui a var $data['password'] tem que se chamar 'password', pois é o padrão do laravel. Em \App\Models\User -> getAuthPassword() contém a alteração do nome para senha
        ];
        $remember = $request->get('remember')=='s';//se true, mantém a conexão ativa em cache (manter conectado)
        
        if(!$account_login)$account_login = $request->get('account_login');
        $account_login=trim($account_login);
        
        $ret = ['account_login'=>$account_login,'email'=>$data['user_email'],'remember'=>$remember];
        //dd($request->all(),$account_login);
        
        
        //proteção força bruta
        $user = $this->getUserByLogin($account_login,$data['user_email']);
        //dd($user,$account_login,$data['user_email']);
        if(!$user){
            return $this->authResponse($account_login,$request,false,'Login ou senha inválido',$ret);
            
        }else if($user->user_status=='0' || $user->user_status=='c'){
            $m = [
                '0'=>'Usuário bloqueado. Contate o administrador.',
                'c'=>'Usuário cancelado. Contate o administrador.',
            ];
            return $this->authResponse($account_login, $request, false, $m[$user->user_status], $ret);
            
        }else if(!$this->isAlllowLoginAttempt($user->id)){//usuário excedeu as tentativas de login
            $user->update(['user_status'=>'0']);//bloqueia o cadastro do usuário
            return $this->authResponse($account_login,$request,false,'Usuário bloqueado. Contate o administrador.',$ret);
        }
        
        
        /*//verifica as permissões de login
        $lp = $user->getMetaData('login_permiss');
        if($lp){
            //verifica o ip
            if($lp['ip']){
                $ip = $request->ip();
                $t=false;
                foreach(explode(',',$lp['ip']) as $ipx){
                    if(trim($ipx)==$ip)$t=true;
                }
                if(!$t)return $this->authResponse($account_login,$request,false,'Seu IP atual não tem permissão de acesso.',$ret);
            }
            
            //verifica a tabela de horários
            if($lp['dt_active']=='s'){
                $sem = (int)date('w')+1;
                $table = $lp['dt_table'];
                $n = $table[$sem]??null;
                if($n){
                    if($n['day']==2){//parte do dia
                        try{
                                $h_curr     = date('H:i');
                                $h_curr     = \DateTime::createFromFormat('H:i', $h_curr);

                                //verifica o horário - sintaxe esperada: hh:mm-hh:mm,...
                                $hslist = explode(',',$n['time']);

                                //intervalo 1
                                $hs = explode('-',$hslist[0]);
                                $h_start    = $hs[0];
                                $h_end      = $hs[1];
                                $h_start    = \DateTime::createFromFormat('H:i', $h_start);
                                $h_end      = \DateTime::createFromFormat('H:i', $h_end);

                                if($h_curr > $h_start && $h_curr < $h_end){
                                    //passou / nenhuma ação
                                }else{
                                    if($hslist[1]??false){//existe um intervalo 2
                                        $hs         = explode('-',$hslist[1]);
                                        $h_start    = $hs[0];
                                        $h_end      = $hs[1];
                                        $h_start    = \DateTime::createFromFormat('H:i', $h_start);
                                        $h_end      = \DateTime::createFromFormat('H:i', $h_end);

                                        if($h_curr > $h_start && $h_curr < $h_end){
                                            //passou / nenhuma ação
                                        }else{
                                            return $this->authResponse($account_login,$request,false,'Login fora do horário permitido',$ret);
                                        }
                                    }else{
                                        return $this->authResponse($account_login,$request,false,'Login fora do horário permitido',$ret);
                                    }
                                }
                        }catch(\Exception $e){
                                return $this->authResponse($account_login,$request,false,'Dados de horários permitido inválidos. Contate o administrador.',$ret);
                        }
                        
                    }elseif($n['day']==1){//dia inteiro
                        //passou
                    }else{//day==0 //bloqueado
                        return $this->authResponse($account_login,$request,false,'Login fora do dia permitido',$ret);
                    }
                }else{
                    return $this->authResponse($account_login,$request,false,'Erro ao identificar horário permitido',$ret);
                }
            }
        }
        dd('passou');*/
        
        
        //adiciona a tentativa de login
        $this->addLoginAttempt($user->id);
        
        
        try{
            $isAuth = Auth::attempt($data, $remember);
            //if($account_login=='dev')dd('b',$isAuth,$account_login,$data,$remember);
            
            if($isAuth){
                //$user = Auth::user();
                
                //verifica as permissões de login
                $r = $this->checkLoginPermiss($user,$request);
                if(!$r['success'])return $this->authResponse($account_login,$request,false,$r['msg'],$ret);
                
                if($user->re_login){//quer dizer que o usuário foi forçado a fazer login novamente
                    $user->update(['re_login'=>false]);
                    Auth::logoutOtherDevices($data['password'],'user_pass');//desconecta de todas as outras sessões/dispositivos
                    Auth::login($user, $remember);//mantém o usuário logado
                }
                
                if(in_array($user->user_level,['dev','superadmin'])){//é dev ou superadmin
                    if(!in_array($account_login,['dev','superadmin','super-admin'])){
                        Auth::logout();
                        return $this->authResponse($account_login,$request,false,'Login da conta inválido.',$ret);
                    }
                    
                }else{//não é dev ou superadmin (é admin ou user)
                    //captura os dados da conta
                    $accountModel = AccountModel::where(['account_login'=>$account_login,'account_status'=>'a'])->first();
                    if(!$accountModel){
                        Auth::logout();
                        return $this->authResponse($account_login,$request,false,'Login inválido.',$ret);
                    }
                    
                    //verifica se o usuário tem permissão para acessar esta conta
                    if(!$accountModel->userRelations->where('user_id',$user->id)->count()){//usuário sem conta
                        Auth::logout();
                        return $this->authResponse($account_login,$request,false,'Usuário sem conta. Contate o administrador.',$ret);
                    }
                    
                    //seta a conta do usuário logado no momento
                    $user->setMetadata('auth_account_id',$accountModel->id);
                }
                
                $user->addLog('login');//adiciona a ação no log
                $this->clearLoginAttempt($user->id);
                
                return $this->authResponse($account_login,$request,true,'',$ret + ['csrf_token'=>csrf_token()]);//sucesso
                
            }else{
                return $this->authResponse($account_login,$request,false,'Login ou senha inválido(2)',$ret);
            }
            
        } catch (Exception $e) {    
            return $this->authResponse($account_login,$request,false,$e->getMessage(),$ret);
        }
    }
    
    
    /**
     * Verifica as permissões de login
     */
    private function checkLoginPermiss($user,$request){
        //verifica as permissões de login
        $lp = $user->getMetaData('login_permiss');
        if($lp){
            //verifica o ip
            if($lp['ip']){
                $ip = $request->ip();
                $t=false;
                foreach(explode(',',$lp['ip']) as $ipx){
                    if(trim($ipx)==$ip)$t=true;
                }
                if(!$t)return ['success'=>false,'msg'=>'Seu IP atual não tem permissão de acesso.'];
            }
            
            //verifica a tabela de horários
            if($lp['dt_active']=='s'){
                $sem = (int)date('w')+1;
                $table = $lp['dt_table'];
                $n = $table[$sem]??null;
                if($n){
                    if($n['day']==2){//parte do dia
                        try{
                                $h_curr     = date('H:i');
                                $h_curr     = \DateTime::createFromFormat('H:i', $h_curr);

                                //verifica o horário - sintaxe esperada: hh:mm-hh:mm,...
                                $hslist = explode(',',$n['time']);

                                //intervalo 1
                                $hs = explode('-',$hslist[0]);
                                $h_start    = $hs[0];
                                $h_end      = $hs[1];
                                $h_start    = \DateTime::createFromFormat('H:i', $h_start);
                                $h_end      = \DateTime::createFromFormat('H:i', $h_end);

                                if($h_curr > $h_start && $h_curr < $h_end){
                                    //passou / nenhuma ação
                                }else{
                                    if($hslist[1]??false){//existe um intervalo 2
                                        $hs         = explode('-',$hslist[1]);
                                        $h_start    = $hs[0];
                                        $h_end      = $hs[1];
                                        $h_start    = \DateTime::createFromFormat('H:i', $h_start);
                                        $h_end      = \DateTime::createFromFormat('H:i', $h_end);

                                        if($h_curr > $h_start && $h_curr < $h_end){
                                            //passou / nenhuma ação
                                        }else{
                                            return ['success'=>false,'msg'=>'Login fora do horário permitido'];
                                        }
                                    }else{
                                        return ['success'=>false,'msg'=>'Login fora do horário permitido'];
                                    }
                                }
                        }catch(\Exception $e){
                                return ['success'=>false,'msg'=>'Dados de horários permitido inválidos. Contate o administrador.'];
                        }
                        
                    }elseif($n['day']==1){//dia inteiro
                        //passou
                    }else{//day==0 //bloqueado
                        return ['success'=>false,'msg'=>'Login fora do dia permitido'];
                    }
                }else{
                    return ['success'=>false,'msg'=>'Erro ao identificar horário permitido'];
                }
            }
        }
        
        return ['success'=>true];
    }
    
    
    /**
     * Função de retorno do método auth()
     * @param Request $request
     * @param boolean $success
     * @param string $msg
     * @param array $ret - [email, (boolean)remember, ...]
     */
    private function authResponse($account_login,$request,$success,$msg='',$ret=null){
        //dd($request->ajax(),$success,route('admin.index_account',$account_login),Auth::user());
        if($request->ajax()){//requisição por ajax
            $r=['success'=>$success,'msg'=>$msg];
            if(is_array($ret))$r=$r+$ret;
            return $r;
            
        }else{//requisição por redirecionamento / rota
            if($success){
                //cookie para controle de cada vez que a página expira
                $cookie = cookie('login_ctrl', time(), 0);//0 - para ser excluído ao fechar o navegador
                
                if(in_array(Auth::user()->user_level,['dev','superadmin'])){
                    return redirect()->route('super-admin.index')->withCookie($cookie);
                }else{
                    return redirect()->route('admin.index_account',$account_login)->withCookie($cookie);
                }
            }else{
                session()->flash('msg',$msg);
                session()->flash('fields',$ret);
                if($account_login){
                    return redirect()->route('account_login',$account_login);
                }else{
                    return redirect()->route('login');
                }
            }
        }
    }
    
    
    //encerra a sessão
    public function logout() {
        $user = Auth::user();
        $user->addLog('logoff');//adiciona a ação no log
        return self::redirectLogout($user);
    }
    
    /**
     * Redireciona o logout considerando a conta logada (obs: deve ser chamado antes de fazer o logout)
     * Return redirect route
     * Ex: return self::redirectLogout(Auth::user());
     */
    public static function redirectLogout($user,$isLogout=true){
        if(!$user){//não está logado
            if($isLogout)Auth::logout();
            $account_login = \Config::accountPrefix();
            if($account_login){
                return redirect()->route('account_login',$account_login);
            }else{
                return redirect()->route('login');
            }
            
        }elseif(in_array($user->user_level,['dev','superadmin'])){
            if($isLogout)Auth::logout();
            return redirect()->route('login');
        }else{
            $account_login = $user->getAuthAccount();
            if($account_login){
                $account_login=$account_login->account_login;
                if($isLogout)Auth::logout();
                return redirect()->route('account_login',$account_login);
            }else{
                if($isLogout)Auth::logout();
                return redirect()->route('login');
            }
        }
    }
    
    
    /**
     * Retorna ao user_id pelo e-mail de login
     * @return null|model User
     */
    private function getUserByLogin($account_login,$user_email){
        if(in_array($account_login,['dev','superadmin','super-admin'])){//dev, superadmin
            if($account_login=='dev'){
                $user = User::where('user_level','dev');
            }else{//superadmin
                $user = User::whereIn('user_level',['dev','superadmin']);
            }
            $user = $user->where('user_email',$user_email)->first();
        }else{//admin, user
            $accountModel = is_object($account_login) ? $account_login : AccountModel::where(['account_login'=>$account_login,'account_status'=>'a'])->first();
            $user = User::where('user_email',$user_email)->whereIn('user_level',['admin','user'])->first();
            if($user && $accountModel && !$accountModel->userRelations->where('user_id',$user->id)->count())$user=null;//usuário sem conta
        }
        return $user;
    }
    
    
    
    /*********** tentativas de login ********/
    /**
     * Armazena as tentativas de login
     */
    private function addLoginAttempt($user_id){
        LoginAttempt::create([
            'user_id'=>$user_id,
            'created_at'=>date('Y-m-d H:i:s'),
            'ip'=>\Request::ip()
        ]);
    }
    
    /**
     * Verifica se usuário está liberado para tentar um novo login
     * @return boolean
     */
    private function isAlllowLoginAttempt($user_id){
        $count = LoginAttempt::where('user_id',$user_id)->count();
        return $count<self::$max_login_attempt;
    }
    
    /**
     * Limpa as tentativas de login
     */
    private function clearLoginAttempt($user_id){
        LoginAttempt::where('user_id',$user_id)->delete();
    }
    
}
