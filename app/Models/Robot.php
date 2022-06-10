<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogTrait;
use App\Models\Traits\MetadataTrait;
use App\Models\Account;

class Robot extends Model{
    use SoftDeletes, LogTrait, MetadataTrait;

    public $timestamp = true;
    protected $fillable = ['robot_name','robot_name_cli','robot_status','robot_config','key_active','key_robot','conn_last','account_ids'];

    //label de status
    public function getStatusLabelAttribute(){
        $status=[
            '0'=>'Aguardando integração',
            'a'=>'Ativado',
            'e'=>'Erro de integração',
            'c'=>'Cancelado'
        ];
        return $status[$this->attributes['robot_status']]??'';
    }


    //********** relaciomentos ***********

    private $account_model=null;
    //com a tabela de contas: uma relação de 'processo' tem 1 'account' - relacionamento (1-1)
    public function getAccounts(){
        if(!$this->account_model)$this->account_model=new Account;
        $ids = $this->attributes['account_ids'];//sintaxe esperada: 1,2,...
        if(!$ids)return null;

        $m = $this->account_model->whereIn('id',explode(',',$ids))->get();
        return $m->count() ? $m : null;
    }
}
