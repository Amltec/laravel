<?php
/**
 * Classe trait para agregar funções de metada nas Models.
 * Deve ser incluída nas classes extendidas de Illuminate\Database\Eloquent\Model;
 * Utilizada para armazenar dados adicionais (table metadata).
 */
namespace App\Models\Traits;
use App\Utilities\FormatUtility;
use App\Services\MetadataService;

trait MetadataTrait {
    
    //*********** Escopos **************
    /*
     * Filtro por Metadata.
     * Param array $meta_data - [meta_name=>metavalue]. Condição AND para múltiplos parâmetros.
     *                      Operadores no nome do parâmetro. Sintaxe [meta_name__op]. Caso não informado será sempre igual (=)
     *                      Exemplos:
     *                      - ['contact_sexo'=>'m', 'address_uf'=>'SP',...]
     *                      - ['contact_sexo__!='=>'m', 'address_uf__NOT'=>'SP',...]
     *                      - ['address_cidade__LIKE'=>'landia',...]
     *                      - ['address_uf__IN'=>['SP','RJ'],...]       //o valor é um array
     *                      - ['address_uf__BETWEEN'=>[1,2]]            //o valor é um array de apenas 2 índices
     *                      Operadores aceitos: '='(default), '!=', '<', '<=', '>', '>=', 'IN', 'NOT_IN', 'LIKE', 'NOT_LIKE', 'BETWEEN', 'NOT_BETWEEN'
     */
    public function scopeWhereMetadata($query,$meta_data){
        $arr=['area_name'=>$this->getTable()];
        $operatorList=['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in', 'like', 'not_like', 'between', 'not_between'];//lista de opradores aceitos
        
        $sql=   $this->getTable().'.id='.
                '(select md1.area_id from metadata as md1 where md1.area_name=? and md1.area_id='.$this->getTable().'.'.$this->primaryKey.' and (';
                
                
                foreach($meta_data as $name=>$value){
                    
                    $op='=';
                    if(strpos($name,'__')!==false){//tem operador no nome do campo
                        $tmp = explode('__', $name);
                        $name = $tmp[0];
                        if(in_array(strtolower($tmp[1]), $operatorList)){//encontrou um operador válido
                            $op= str_replace('_',' ', strtolower($tmp[1]));//converte o '_' por ' ' e deixa minúsculo para comparar corretamente abaixo
                        }
                    }
                    
                    if(($op=='in' || $op=='not in') && is_array($value)){
                        //aqui o $value possui um array de valores, ex: [value1, value2, ...]
                        $i=0;$tmp=[];
                        foreach($value as $v){
                            $tmp[]='?';
                            $i++;
                        }
                        $sql.='(md1.meta_name=? and md1.meta_value '.$op.' ('. join(',',$tmp) .')) or ';
                        
                    }else if(($op=='between' || $op=='not between') && is_array($value)){
                        //aqui o $value possui um array de 2 posições, ex: [value1, value2]
                        $sql.='(md1.meta_name=? and md1.meta_value '.$op.' ? and ?) or ';
                        
                    }else{
                        $sql.='(md1.meta_name=? and md1.meta_value '.$op.' ?) or ';
                    }
                    
                    //atribui os valores corretamente para o bind sql
                    $arr['meta_name_'.$name]=$name;
                    if(is_array($value)){
                        $i=0;
                        foreach($value as $v){
                            $arr['meta_value_'.$name.'_'.$i]=$v;
                            $i++;
                        }
                    }else{
                        $arr['meta_value_'.$name]=$value;
                    }
                }
                $sql=FormatUtility::trim($sql,' or ') . ') ';
                
                $i=count($meta_data);
                //if($i>1)$sql.='GROUP BY md1.area_id HAVING COUNT(DISTINCT md1.meta_name)='.$i.' ';
                if($i>1)$sql.='GROUP BY md1.area_id HAVING COUNT(md1.area_id)='.$i.' ';
                
        $sql.= ')';
        $query->whereRaw($sql,$arr);
        //dump([$query->toSql(),$query->getBindings()]);
        return $query;
    }
    
    
    
    
    //*********** Métodos **************
    /**
     * Adiciona ou atualiza o metadata
     * Param (string) $name, $value - nome e valor do metaddo
     * Return True ok ou msg erro
     */
    public function setMetadata($name, $value){
        return MetadataService::set($this->getTable(), $this->id ,$name, $value);
    }

    /**
     * Deleta o metadado
     * Param (string) $name - nome do metadado. Se não informado, será removido todos os metadados do registro atual
     * Return True ok ou msg erro
     * Obs: para deletar todos os metadados, deve-se executar: 
     *      self:delMetadata([id]);     - remove todos do registro especificado
     *      self:delMetadata(0);        - remove todos da tabela deste model se registro especificado
     */
    public function delMetadata($name='') {
        return MetadataService::del($this->getTable(), $this->id ,$name);
    }
    
    
    /**
     * Retorna a todos os metadas de um registro no fomato array [meta_name=>metavalue,...]
     * Param (string) $name - se informado, retorna apenas ao respectivo valor
     * Return array metadata
     */
    public function getMetadata($name='') {
        return MetadataService::get($this->getTable(), $this->id ,$name);
    }
    
    
}
