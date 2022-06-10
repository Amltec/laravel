<?php

namespace App\Models;
use App\Models\Base\ModelMultipleKeys;
use App\Services\LogsService;
use App\Models\PrCadApoliceData;
use App\Utilities\ValidateUtility;

/**
 * Classe utilizada o processo 'cad_apolice'. 
 * Calsse de serviço associado \App\Services\PrCadApoliceService
 * Respectivo ao Controller \App\Http\Controllers\ProcessCadApoliceController
 */
class PrCadApolice extends ModelMultipleKeys {
    protected $table = 'pr_cad_apolice';
    public $timestamp = false;
    const CREATED_AT = null;//desabilita apenas o campo created_at
    const UPDATED_AT = null;//desabilita apenas o campo updated_at
    protected $fillable = ['process_id','num','process','status','user_id','created_at','finished_at','is_done'];
    protected $primaryKey = ['process_id','num','process'];
    
    
    /*private $modelPrData=null;
    private function getModelPrData(){
        if(!$this->modelPrData)$this->modelPrData = new PrCadApoliceData;
        return $this->modelPrData;
    }*/

    
    //Adiciona um registro de log
    public function addLog($action,$log_data=null){
        return LogsService::add($action, 'cad_apolice.'.$this->process,$this->process_id,$log_data);
    }
    
    //*** data functions ***
    //Captura um metadado
    public function getData($modelCadApolice,$name){
        $m=PrCadApoliceData::where(['process_id'=>$this->attributes['process_id'],'num'=>$this->attributes['num'],'meta_name'=>$name])->value('meta_value');
        if(ValidateUtility::isSerialized($m))$m=unserialize($m);
        return $m;
    }
    
    //Seta metadado
    public function setData($name,$value){
        if(is_array($value))$value = serialize($value);
        PrCadApoliceData::updateOrInsert(['process_id'=>$this->attributes['process_id'],'num'=>$this->attributes['num'],'meta_name'=>$name],['meta_value'=>$value??'']);
    }
    
    //Deleta um metadado
    public function delData($name){
        ProcessRobotData::where(['process_id'=>$this->attributes['process_id'],'num'=>$this->attributes['num'],'meta_name'=>$name])->delete();
    }
    
    
    //********** relaciomentos ***********
    //com a tabela process_robot: um 'pr_seguradora_data' tem 1 'process_robot' - relacionamento (1-1)
    public function process_robot(){
        return $this->belongsTo(Base\ProcessRobot::class,'process_id');
    }
    
    //com a tabela de usuários: uma relação de 'processo' tem 1 'user' - relacionamento (1-1)
    public function user(){
        return $this->belongsTo(User::class);
    }
    
    
    
}
