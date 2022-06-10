<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Utilities\ValidateUtility;
use App\Models\Traits\LogTrait;
use App\Models\Traits\MetadataTrait;

class Insurer extends Model
{
    use SoftDeletes, LogTrait, MetadataTrait;
    
   
    public $timestamp = true;
    protected $fillable = ['insurer_name','insurer_razaosocial','insurer_doc','insurer_alias','insurer_status','insurer_basename','insurer_find_rule'];
    
    
    //label de status
    public function getStatusLabelAttribute(){
        $status=[
            'a'=>'Normal',
            'c'=>'Cancelado',
            '0'=>'Não Ativado',
        ];
        return $status[$this->attributes['insurer_status']];
    }
    
    //Retorna a um array do relacionamento de dada a partir do id do corretor
    public function getBrokerData($broker_id){
        $model=$this->brokerData;
        $r=[];
        if($model){
            foreach($model as $f=>$v){
                if($v->broker_id==$broker_id)
                    $r[$v->meta_name]= ValidateUtility::isSerialized($v->meta_value) ? unserialize($v->meta_value) : $v->meta_value;
            }
        }
        return $r;
    }
    
    /*
    //Retorna a configuração json do processo do robô
    public function getConfigProcessRobot(){
        $n=\App\Services\MetadataService::getValue($this->getTable(),$this->attributes['id'],'process_robot');
        if(\App\Utilities\ValidateUtility::isJson($n)){
            return json_decode($n, true);
        }else{
            return [];
        }
    }
    */
    
    //********** relaciomentos ***********
    //com a tabela de dados gerais de brokers_insurers_data: uma 'seguradora' tem muitos 'dados' - relacionamento (1-N)
    public function brokerData(){
        return $this->hasMany(BrokerInsurerData::class,'insurer_id','id');
    }
    
}
