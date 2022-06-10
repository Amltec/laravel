<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProcessRobotData extends Model{
    
    protected $fillable = ['process_id','meta_name','meta_value'];
    protected $table = 'process_robot_data';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    
    
}

