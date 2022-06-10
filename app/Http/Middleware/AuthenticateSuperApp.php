<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Autenticação padrão realizadas por todas as rotas autenticadas no sistema
 */
class AuthenticateSuperApp extends AuthenticateApp{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        //verifica se o usuário logado tem permissão para este tipo de autenticação
        if(auth()->check() && !in_array(auth()->user()->user_level, ['dev','superadmin'])){//obs: o nome do nível 'superadmin' deve ser escrito exatamente desta forma 'superadmin'
            return $this->retNotLogged($request,null);
        }
        
        return parent::handle($request, $next);
    }
}
