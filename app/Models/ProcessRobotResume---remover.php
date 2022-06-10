<?php
CLASSE DESABILITADA

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\AccountTrait;
use Auth;

class ProcessRobotResume---remover extends Model{
    use AccountTrait;
    
    protected $fillable = ['broker_id','insurer_id','process_date','process_name','process_count','process_count_o','process_count_0','process_count_p','process_count_a','process_count_f','process_count_e','process_count_c','process_count_1','process_count_i','process_count_w','process_duration','account_id'];
    protected $table = 'process_robot_resume';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    
}

