<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

/**
 * Classe de usuários somente contas logadas (filtra usuário por conta logada).
 * Lógica: para todas as operações de filtros (where), é filtrado considerando o id da conta logada 
 */
class UserAuth extends User{
    
    //*** inicialização da model ****
    protected static function boot(){
        parent::boot();
        
        //filtra pelo id da conta do usuário logado para toda requisição da Model
        static::addGlobalScope('account_user',function(Builder $builder){
            $builder->AuthAccount();//filtra pelo id da conta do usuário logado
        });
    }
    
}
