<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\MetadataTrait;
use App\Models\Traits\LogTrait;
use App\Services\UsersService;
use Illuminate\Notifications\Notifiable;


//para autenticação
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Classe de usuário para todos os casos
 */
class User extends Authenticatable{
    use SoftDeletes, MetadataTrait, LogTrait;
    use Notifiable;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamp = true;
    protected $fillable = ['user_name','user_alias','user_email','user_pass','user_status','user_level','area_name','deleted_at','re_login'];
    protected $hidden = ['remember_token','user_pass'];
    protected $table = 'users';
            
    
    //este método só existe porque na tabela 'users' o campo de senha se chama 'user_pass' (ao invés do padrão laravel 'password'
    public function getAuthPassword() {
        return $this->user_pass;
    }

    
    
    //*********** Escopos **************
    /*
     * Filtra os dados da empresa que o usuário está associado
     * @param int $account_id - id da conta do usuário.
     * @param array $data - dados adicionais para filtro da tabela accounts
     */
    public function scopeWhereAccount($query,$account_id,$data=null){
        $fields='';
        $values=[$account_id];
        if($data){
            foreach($data as $f=>$v){
                $fields.=' and tA.'.$f.'=?';
                $values[]=$v;
            }
        }
        
        $query->whereRaw('users.id = ('.
                'select tR.user_id from accounts tA, user_account_relations tR '.
                    'where tA.id = tR.account_id and tR.user_id=users.id and tR.account_id=?'.
                    $fields.
                ')', $values);
        
        return $query;
    }
    /**
     * Filtra sempre pelos dados do usuário logado
     */
    public function scopeAuthAccount($query,$data=null){
        return $this->scopeWhereAccount($query, \Auth::user()->getAuthAccount('id'),$data);
    }
    
    
   
    //***** relacionamento *****
    
    //com a tabela de relações de contas que este usuário pode administrar: um 'usuário' pode ter muitas relações de contas' - relacionamento (1-N)
    public function accountRelations() {
        return $this->hasMany(UserAccountRelation::class);
    }
    
    //com a tabela de contas através da tabela intermediária user_account_relations (N-N)
    public function accounts() {
        return $this->belongsToMany(Account::class,'user_account_relations');
    }
    
    
    
    //****** ajustes de valores para a view ******
    
    //label de status
    public function getStatusLabelAttribute(){
        $status=['a'=>'Normal','0'=>'Bloqueado','c'=>'Cancelado'];
        return $status[$this->attributes['user_status']];
    }
    
    //retorna se o registro está cancelado
    public function getIsCancelAttribute(){
        return $this->attributes['user_status']=='c';
    }
    
    //retorna a uma string com os nomes das contas (que não está canceladas)
    public function getAccountNameAttribute(){
        $r=[];
        foreach($this->accounts as $account){
            if($account->account_status!='c'){
                $r[]=$account->account_name;
            }
        }
        return join(', ',$r);
    }
    
    //retorna ao label do nível de acesso
    public function getLevelNameAttribute(){
        return UsersService::$levels[$this->attributes['user_level']];
    }
    
    //retorna a um array dos IDs da tabela account (que não estão cancelados)
    public function getAccountIdsAttribute(){
        return $this->accounts()->where('account_status','!=','c')->pluck('id')->all();
    }
    //retorna a um array dos IDs da tabela account (TODOS)
    public function getAccountAllIdsAttribute(){
        return $this->accountRelations()->pluck('account_id')->all();
    }
    //retorna (boolean) se o id da conta informado, é um dos ids que o usuário logado tem permissão para acessar
    public function allowAccount($account){//$account - int|model
        if(in_array($this->attributes['user_level'],['dev','superadmin'])){
            return true;
        }else{
            $ids = $this->getAccountAllIdsAttribute();
            return in_array(gettype($account)=='object' ? $account->id : $account,$ids);
        }
    }
    
    
    //************ métodos apenas para o usuário logado (usar com Auth::user()) ************/
    
    //captura os dados da empresa atual da sessão
    public function getAuthAccount($getOnlyField=''){//se $getOnlyField=='id', retorna apena ao id   //usar esta função com a classe \Auth, ex: Auth::user()->getAuthAccount('id')
        static $account_id;
        $data = \Config::account();
        if(!$data)return false;
        if(!$account_id)$account_id = $data->id;
        if($getOnlyField=='id'){
            return $account_id;
        }else{
            return $data;
        }
    }
    
    
    //*********** funções **************
    
   /*
     * Adiciona ou remove a relação do usuário com a conta principal (tabela accounts)
     * $action = add|del
     * $account_id = só pode ser null se $action='del' (remove todas as relações de contas), 
     */
    public function setAccountRelation($action,$account_id=null){
        $user_id = $this->attributes['id'];
        if($action=='add'){
            //verifica se a conta de teste está cadastrada
            $rel = UserAccountRelation::where('account_id',$account_id)->where('user_id',$user_id)->first();
            if(!$rel){
                UserAccountRelation::create(['user_id'=>$user_id,'account_id'=>$account_id]);
            }//else //já existe
        }else{
            $model = UserAccountRelation::where('user_id',$user_id);
            if($account_id)$model->where('account_id',$account_id);
            $model->delete();
        }
        return true;
    }
    
    
    /**
     * Verifica se um usuário tem permissão para prosseguir em relação ao nível comparado de acesso
     */
    public function checkAllowLevel($compare_level,$self_level=false){
        return UsersService::checkAllowLevel($this,$compare_level,$self_level);
    }
}
