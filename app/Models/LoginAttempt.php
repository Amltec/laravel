<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model {
    protected $table = 'login_attempt';
    public $timestamp = false;
    const UPDATED_AT = null;//desabilita apenas o campo updated_at
    protected $fillable = ['user_id','created_at','ip'];
    
}
