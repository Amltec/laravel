<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        //'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //******* definindo ACLs *********
        //Estas permissão estão escritas considerando que os níveis superiores sempre terão permissões em relação aos níveis inferiores
        //Ex: se for permitido para um 'admin', então automaticamente é permitido para o 'superadmin' e 'dev' (e menos para o usuário)
        //Obs: para uma lógica correta, use sempre o comando com 'allows', ex: Gate::allows('admin')
        Gate::define('dev',function(User $userLogged){ return in_array($userLogged->user_level, ['dev']); });
        Gate::define('superadmin',function(User $userLogged){ return in_array($userLogged->user_level, ['dev','superadmin']); });
        Gate::define('admin',function(User $userLogged){ return in_array($userLogged->user_level, ['dev','superadmin','admin']); });
        Gate::define('user',function(User $userLogged){ return in_array($userLogged->user_level, ['dev','superadmin','admin','user']); });

        /*//permissão de verifica se o usuário logado por acessar a conta atual em sessão
        Gate::define('manage_account',function(User $userLogged,$account_id=null){
            return true;
        });*/
    }
}
