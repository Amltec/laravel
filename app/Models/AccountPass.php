<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AccountPass extends Model{
    protected $table = 'account_pass';
    public $timestamp = false;
    const UPDATED_AT = null;
    protected $fillable = ['account_id','pass_area','pass_user','pass_login','pass_pass','pass_status','pass_type','process_id','status_code','acessed_at','created_at'];
    
    //label de status
    public function getStatusLabelAttribute(){
        $status=['a'=>'Normal','0'=>'Bloqueado','c'=>'Cancelado'];
        return $status[$this->attributes['pass_status']];
    }
}
