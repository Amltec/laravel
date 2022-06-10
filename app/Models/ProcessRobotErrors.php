<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class ProcessRobotErrors extends Model{
    
    protected $fillable = ['account_id','status_code','status','callback','msg','created_at','process_name','process_prod','show_level','broker_id','insurer_id'];
    protected $table = 'process_robot_errors';
    const UPDATED_AT = null;
    
    //********** relaciomentos ***********
    //com a tabela de corretores: uma relação de 'processo' tem 1 'broker' - relacionamento (1-1)
    public function broker(){
        return $this->belongsTo(Broker::class);
    }
    
    //com a tabela de corretores: uma relação de 'processo' tem 1 'insurer' - relacionamento (1-1)
    public function insurer(){
        return $this->belongsTo(Insurer::class);
    }
    
    //com a tabela de contas: uma relação de 'processo' tem 1 'account' - relacionamento (1-1)
    public function account(){
        return $this->belongsTo(Account::class);
    }
    
}
