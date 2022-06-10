<?php

namespace App\Models\Posts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostFolder extends Model{
    use SoftDeletes;
    
    protected $fillable = ['post_type','folder_title','folder_resume','folder_version','folder_version_id','folder_status','folder_name','created_at','deleted_at','user_id','account_id','area_name'];
    protected $table = 'post_folder';
    
    
    public function getStatusLabelAttribute(){
        return ['a'=>'Normal','c'=>'Cancelado'][$this->attributes['folder_status']]??null;
    }
    
    
    //********** relaciomentos ***********
    
    //com a tabela de contas: uma relação de 'post' tem 1 'account' - relacionamento (1-1)
    public function account(){
        return $this->belongsTo(\App\Models\Account::class);
    }
    
    //com a tabela de usuários: uma relação de 'processo' tem 1 'user' - relacionamento (1-1)
    public function user(){
        return $this->belongsTo(\App\Models\User::class);
    }
    
    
    
    
}


