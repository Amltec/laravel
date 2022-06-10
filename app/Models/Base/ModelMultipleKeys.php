<?php

namespace App\Models\Base;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Exception;

/**
 * Classe base Model para múltiplas chaves primárias
 * Deve ser extendida para a model principal
 */
class ModelMultipleKeys extends Model{
    
    //É necessário informar na classe da model principal as chaves primárias
    //protected $primaryKey = ['field_key1', 'field_key2', ...];
    
    public $incrementing = false;
    
    
    //***** atualiza os métodos para ficar compatível com mais chaves primárias ******
    //Set the keys for a save update query
    protected function setKeysForSaveQuery(Builder $query){
        $keys = $this->getKeyName();
        if(!is_array($keys)){
            return parent::setKeysForSaveQuery($query);
        }
        foreach($keys as $keyName){
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }
        return $query;
    }
    //Get the primary key value for a save query.
    protected function getKeyForSaveQuery($keyName = null){
        if(is_null($keyName)){
            $keyName = $this->getKeyName();
        }
        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }
        return $this->getAttribute($keyName);
    }
    //Find by multiple keys 
    //@param array $ids - [key1=>val1, key2=>val2, ...]
    public function find($ids, $columns = ['*']){
        if(!is_array($ids))throw new Exception('query->find() - id '.$ids.' need be array');
        return $this->where($ids)->first();
    }
}