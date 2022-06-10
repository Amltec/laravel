<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Metadata.
 */
class UserAccountRelation extends Model
{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','account_id'];
    
    
    //********** relaciomentos ***********
    //com a tabela de contas: uma relação de 'conta' tem 1 'usuário' - relacionamento (1-1)
    public function accounts(){
        return $this->belongsTo(Account::class);
    }
    
    //com a tabela de usuários: uma relação de 'usuário' tem 1 'conta' - relacionamento (1-1)
    public function users(){
        return $this->belongsTo(User::class);
    }

}
