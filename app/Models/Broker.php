<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\LogTrait;
use App\Models\Traits\MetadataTrait;
use Auth;

class Broker extends Model {
    use SoftDeletes, LogTrait, MetadataTrait;
    
    protected $table = 'brokers';
    
    public $timestamp = true;
    protected $fillable = ['broker_name','broker_doc','broker_cpf_cnpj','broker_alias','broker_status','broker_col_user','broker_col_login','broker_col_senha','account_id'];
    
    
    //label de status
    public function getStatusLabelAttribute(){
        $status=[
            'a'=>'Normal',
            'c'=>'Cancelado'
        ];
        return $status[$this->attributes['broker_status']];
    }
    
    //Retorna a um array do relacionamento de dada a partir do id da seguradora
    public function getInsurerData($insurer_id){
        $model=$this->brokerData;
        $r=[];
        if($model){
            foreach($model as $f=>$v){
                if($v->insurer_id==$insurer_id)
                    $r[$v->meta_name]= ValidateUtility::isSerialized($v->meta_value) ? unserialize($v->meta_value) : $v->meta_value;
            }
        }
        return $r;
    }
    
    
    //********** relaciomentos ***********
    //com a tabela de dados gerais de brokers_insurers_data: uma 'seguradora' tem muitos 'dados' - relacionamento (1-N)
    public function brokerData(){
        return $this->hasMany(BrokerInsurerData::class,'broker_id','id');
    }
    
}
