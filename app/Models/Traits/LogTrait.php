<?php
/**
 * Classe trait para agregar funções de logs nas Models (tabela user_logs).
 * Deve ser incluída nas classes extendidas de Illuminate\Database\Eloquent\Model;
 */
namespace App\Models\Traits;
use App\Services\LogsService;

trait LogTrait {
    /**
     * Adiciona um registro de log
     */
    public function addLog($action,$log_data=null,$area_name=''){
        return LogsService::add($action, ($area_name?$area_name:$this->getTable()) ,$this->id,$log_data);
    }

    /**
     * Adiciona um registro de log no padrão de campos de formulário
     */
    public function addFieldsLog($action,$log_data=null,$area_name='',$opt=null){
        return LogsService::addFields($action, ($area_name?$area_name:$this->getTable()) ,$this->id,$log_data,$opt);
    }
    
    /**
     * Deleta todos os dados de logs associados a um registro considerando o usuário e conta atual logado
     */
    public function delLog(){
        return LogsService::del(['area_name'=>$this->getTable(), 'area_id'=>$this->id,'user_id'=>true,'account_id'=>true]);
    }
    
    /**
     * Deleta todos os dados de logs associados a um registro considerando o usuário e conta atual logado
     */
    public function delLogAll($where){
        return LogsService::del($where);
    }
    
    
}
