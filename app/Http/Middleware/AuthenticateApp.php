<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Autenticação padrão realizadas por todas as rotas autenticadas no sistema
 */
class AuthenticateApp{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        $user=auth()->user();
        if($user && $user->re_login){//foi solicitado que deve fazer login novamente;
            auth()->logout();
        }
        if(!auth()->check()){//não está logado
            return $this->retNotLogged($request,$user);
        }else{//está logado
            if(!$request->ajax() && !$request->cookie('login_ctrl')){
                $user->addLog('logina');//adiciona a ação no log
            }
        }
        
        $s=$user->user_status;
        if($s=='0' || $s=='c'){//usuário bloqueado ou cancelado
            return $this->retNotLogged($request,$user);
        }
        
        //valida pelo login da url
        $n=\Config::accountPrefix();
        if(\Config::adminPrefix()!='super-admin' && $user->allowAccount($user->getAuthAccount())==false){//o endereço de login da url é diferente do associado a conta do usuário
            return $this->retNotLogged($request,$user);
        };
        
        //verifica se está no modo de manutenção
        if(env('APP_MAINTENANCE')=='on' && $user->user_level!='dev'){//não tem permissão para acessar
            abort(503);exit;
        }
        
        return $next($request);
    }
    
    /**
     * Padrão de retorno do handle
     */
    protected function retNotLogged($request,$user=null){
        if($request->ajax()){
            if($request->wantsJson()){
                return response()->json(['authenticated'=>false]);
            }else{//html
                return response()->make('[authenticated=false]');
            }
        }else{
            if($user){
                return \App\Http\Controllers\LoginController::redirectLogout($user,false);//false - para não fazer o logout (pois até aqui não está mais logado)
            }else{
                return redirect()->route('login');
            }
        }
    }

}
