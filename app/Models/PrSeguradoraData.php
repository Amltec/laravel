<?php

namespace App\Models;
use App\Models\Base\ModelMultipleKeys;
use App\Services\LogsService;

/**
 * Classe utilizada o processo 'seguradora_data'. 
 * Controller \App\Http\Controllers\ProcessSeguradoraDataController
 */
class PrSeguradoraData extends ModelMultipleKeys {
    protected $table = 'pr_seguradora_data';
    public $timestamp = false;
    const CREATED_AT = null;//desabilita apenas o campo created_at
    const UPDATED_AT = null;//desabilita apenas o campo updated_at
    protected $fillable = ['process_id','process_rel_id','process_prod','status','status_code','created_at','finished_at','process_next_at'];
    
    protected $primaryKey = ['process_id','process_rel_id','process_prod'];

    
    //Adiciona um registro de log
    public function addLog($action,$log_data=null){
        return LogsService::add($action, 'seguradora_data.'.$this->process_prod ,$this->process_rel_id,$log_data);
    }
    
    
    //********** relaciomentos ***********
    //com a tabela process_robot (rel): um 'pr_seguradora_data' tem 1 'process_robot' - relacionamento (1-1)
    public function process_robot(){
        return $this->belongsTo(Base\ProcessRobot::class,'process_rel_id');
    }
    
    //com a tabela process_robot (main): um 'pr_seguradora_data' tem 1 'process_robot' - relacionamento (1-1)
    public function process_seguradora_data(){
        return $this->belongsTo(Base\ProcessRobot::class,'process_id');
    }
    
    
    
}
