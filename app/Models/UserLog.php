<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Classe de registro de logs das operações do usuário no sistema
 */
class UserLog extends Model{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    protected $fillable = ['user_id','account_id','user_level','area_name','area_id','action','log_data','created_at','url','ip'];
    
    
    //com a tabela de usuários: uma relação de 'processo' tem 1 'user' - relacionamento (1-1)
    public function user(){
        return $this->belongsTo(User::class);
    }
    
    //com a tabela de contas: uma relação de 'processo' tem 1 'account' - relacionamento (1-1)
    public function account(){
        return $this->belongsTo(Account::class);
    }
}
