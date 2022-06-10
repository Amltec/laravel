<?php
/**
 * Classe trait para agregar funções de taxonomias nas Models.
 * Deve ser incluída nas classes extendidas de Illuminate\Database\Eloquent\Model;
 * Utilizada para armazenar as taxonomias (tabela: terms, taxs e tax_relations).
 */
namespace App\Models\Traits;
use App\Models\TaxRelation;
use App\Models\TaxCache;
use App\Services\TaxsService;

trait TaxTrait {
    
    
    //******** relacionamento *****/
    //a entidade atual tem muitas relações de taxonomias (model 1 - N tax_relations)
    //obrigatórios para os comandos select query: whereHas, with, Has, ...
    //relacionamento por default sendo area_name='nome-da_tabela'
    public function tax_relations($area_name=null){
        if(!$area_name)$area_name=$this->getTable();
        return $this->hasMany(TaxRelation::class,'area_id')->where('tax_relations.area_name',$area_name);//$this->getAreaName()
        
    }
    
    
    //*********** Escopos **************
    /*
     * Filtro por Taxonomia.
     * Param integer|array|string $tax_ids - (integer) id da taxonomia, (array) múltiplos ids, (string) múltiplos ids separados por virgula
     * Param array $area_name (opcional) default nome da tabela atual
     * Ex: $query->whereTax(1);   $query->whereTax([1,2,3])
     */
    public function scopeWhereTax($query,$tax_ids,$area_name=null){
        //if(!empty($area_name))$this->area_name=$area_name;//atualiza o nome da área para ser usada no relacionamento ('método tax_relations' acima)
        
        /* *** opção 1 está funcionando ***
        return $query->whereHas('tax_relations',function($q) use ($tax_ids,$area_name){
            if(!is_array($tax_ids))$tax_ids=[$tax_ids];
            $q->whereIn('tax_id',$tax_ids);
        });*/
        
        //opção 2
        if(is_string($tax_ids)){
            $tax_ids=explode(',',$tax_ids);
        }else if(!is_array($tax_ids)){
            $tax_ids=[$tax_ids];
        }
        
        return $query->join('tax_relations', $this->getTable().'.'.$this->primaryKey, '=', 'tax_relations.area_id')
                ->where('tax_relations.area_name', ($area_name ? $area_name : $this->getTable()) )
                ->whereIn('tax_relations.tax_id', $tax_ids);
    }

    
    //*********** Métodos **************
    /* Adiciona um relacionamento da taxonomia com a Model
     * Return True ok ou msg erro
     */
    public function addTaxRelation($tax_id,$area_name=null){
        if(!$area_name)$area_name=$this->getTable();
        return TaxsService::addRelation($tax_id,$area_name,$this->id);
    }

    /* Deleta o relacionamento do metadado do registro atual da Model
     * Return True ok ou msg erro
     */
    public function delTaxRelation($tax_id=null,$area_name=null){
        if(!$area_name)$area_name=$this->getTable();
        $data=['area_id'>=$this->id,'area_name'=>$area_name];
        if($tax_id)$data['tax_id']=$tax_id;
        return TaxsService::delRelation($data);
    }
    
    
    /**
     * Retorna a todos os registros da taxonomia para este model no respectivo registro
     * Return array object taxs
     */
    public function getTaxRelation($term_id,$area_name=null){
        $account_id = \Config::adminPrefix()=='super-admin' ? null : $this->attributes['account_id']; 
        return TaxsService::getRelationByArea($term_id, $area_name?$area_name:$this->getTable(), $this->id, $account_id);
    }
 
    /**
     * Retorna aos dados completos das taxonomias relacionadas
     * @param $format - object|array|ids
     * Return array
     */
    public function getTaxsData($term_id,$format='object',$area_name=null){
        if(!$area_name)$area_name=$this->getTable();
        $data = TaxsService::getRelationByArea($term_id,$area_name,$this->id);
        $r=[];
        foreach($data as $i=>$v){
            if($format=='object'){
                $r[$v->tax_id] = $v->tax;
            }elseif($format=='ids'){
                $r[] = $v->tax->id;
            }else{//array
                $r[$v->tax_id] = $v->tax->toArray();
            }
        }
        return $r;
    }
    
    /**
     * Retorna a um array com todos os termos do registro atual
     */
    public function getAllTermsByThis($format='object',$area_name=null){
        if(!$area_name)$area_name=$this->getTable();
        return TaxsService::getAllTermsByReg($area_name,$this->id,$format);
    }
}
