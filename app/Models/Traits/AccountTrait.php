<?php
/**
 * Classe trait para agregar funções relacionado a conta do usuário logado nas Models.
 * Deve ser incluída nas classes extendidas de Illuminate\Database\Eloquent\Model;
 * Utilizada para filtrar as requisições da model e inserir registros sempre considerando a conta do usuário logado
 */
namespace App\Models\Traits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Auth;

trait AccountTrait {
    
    //*** inicialização da model ****
    protected static function boot(){
        parent::boot();
        
        //por padrão, filtra pelo id da conta, e no superadmin exibe todos os registros
        self::whereAccountAll();
    }
    
    //filtra pelo id da conta, e no superadmin exibe todos os registros
    protected static function whereAccountAll(){
        $account_id=self::getAccountId();
        if($account_id){
            //filtra pelo id da conta do usuário logado para toda requisição da Model
            static::addGlobalScope('account_user',function(Builder $builder){
                $builder->where('account_id', self::getAccountId());
            });
        }
    }
    
    //valor padrão ao criar um registro
    public function create(array $attributes){
        return $this->createUnique($attributes,false);
    }
    public function updateOrCreate(array $attributes, array $values = []){
        return $this->updateOrCreateUnique($attributes,$values,false);
    }
    
    //Retorna ao id da conta considerando se está no painel superadmin ou admin apenas
    //@param $allow_qs - se true permite captura o account_id pelo querystring
    private static function getAccountId($allow_qs=true){
        $prefix = \Config::adminPrefix();
        if($prefix=='super-admin'){
            //id por parâmetro get
            return $allow_qs ? _GET('account_id') : null;
        }else if($prefix=='admin'){
            //id do usuário logado
            $user = Auth::user();
            $id=$user ? $user->getAuthAccount('id') : null;
            return empty($id) ? '-1' : $id;//return 0 para que seja anulado a query na instrução sql (ex: where account_id='-1')
        }else{//ex de valores possívelmente retornados: wsrobot, login, etc...
            return null;
        }
    }
    
    
    //***************** 
    //      As funções abaixo são como as funções acima, mas se estiver no painel de 'superadmin', considera o account_id=null 
    //      Elas precisam serem incluídas manualmente na model desejada (veja a model Tax.php de exemplo)
    //******************
    
    
    //filtra pelo id da conta, e no superadmin exibe apenas os account_id=null
    protected static function whereAccountUnique(){
        static::addGlobalScope('account_user',function(Builder $builder){
            $account_id=self::getAccountId(false);//seta false para que no painel superadmin retorne a null
            if($account_id){
                $builder->where('account_id', self::getAccountId());
            }else{
                $builder->whereNull('account_id');
            }
        });
    }
    
    //valor padrão ao criar um registro
    public function createUnique(array $attributes,$accountNullError=false){
        //adicion ao id da conta do usuário logado
        $account_id=self::getAccountId();
        if($attributes['account_id']??false){
            return parent::create($attributes);
        }else if($account_id && (string)$account_id!='-1'){
            $attributes['account_id'] = $account_id;
            return parent::create($attributes);
        }else{
            if($accountNullError){
                //error 
                throw new \ErrorException('ID da conta vazio (AccountTratit Create).');
            }else{
                $attributes['account_id'] = null;
                return parent::create($attributes);
            }
        }
        
    }
    public function updateOrCreateUnique(array $attributes, array $values = [], $accountNullError=false){
        //adiciona ao id da conta do usuário logado
        $account_id=self::getAccountId();
        if($attributes['account_id']??false){
            return parent::updateOrCreate($attributes,$values);
        }else if($account_id && (string)$account_id!='-1'){
            $attributes['account_id'] = $account_id;
            $values['account_id'] = $attributes['account_id'];
            return parent::updateOrCreate($attributes,$values);
        }else{
            if($accountNullError){
                //error
                throw new \ErrorException('ID da conta vazio (AccountTratit UpdateOrCreate).');
            }else{
                $values['account_id'] = null;
                return parent::updateOrCreate($attributes,$values);
            }
        }
    }
    
}
