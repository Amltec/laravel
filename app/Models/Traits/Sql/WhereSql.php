<?php
/**
 * Classe trait para agregar funções de busca em geral as models
 * Deve ser incluída nas classes extendidas de Illuminate\Database\Eloquent\Model;
 */
namespace App\Models\Traits\Sql;

trait WhereSql {
    
    /**
     * Busca em campo string. Ex: 'José Silva' é encontrado no campo 'José Carlos da Silva'
     * @param string|array $field - se array, irá considerar o operador OR entre os campos
     */
    public function scopeWhereSearch($query,$field,$search,$tbl_name=null){
        $s0='';$v=[];
        if(!$tbl_name)$tbl_name=$this->table;
        if(!is_array($field))$field=[$field];
        foreach($field as $f){
            $s1='';
            foreach(explode(' ',$search) as $a){
                $s1.=$tbl_name.'.'.$f.' like ? and ';
                $v[]='%'.$a.'%';
            }
            $s0.='('. trim($s1,'and ') .') or ';
        }
        $s0='('. trim($s0,'or ') .')';
        return $query->whereRaw($s0,$v);
    }
    
    
    /**
     * Filtro por Metadato da tabela {table_data}.
     * Param array $meta_data - [meta_name=>metavalue]. Condição AND para múltiplos parâmetros.
     *                      Operadores no nome do parâmetro. Sintaxe [meta_name__op]. Caso não informado será sempre igual (=)
     *                      Exemplos:
     *                      - ['contact_sexo'=>'m', 'address_uf'=>'SP',...]
     *                      - ['contact_sexo__!='=>'m', 'address_uf__NOT'=>'SP',...]
     *                      - ['address_cidade__LIKE'=>'landia',...]
     *                      - ['address_uf__IN'=>['SP','RJ'],...]       //o valor é um array
     *                      - ['address_uf__BETWEEN'=>[1,2]]            //o valor é um array de apenas 2 índices
     *                      Operadores aceitos: '='(default), '!=', '<', '<=', '>', '>=', 'IN', 'NOT_IN', 'LIKE', 'NOT_LIKE', 'BETWEEN', 'NOT_BETWEEN'
     * Exemplo: $query->whereTableData($query,'post_data','post_id',['name'=>'value']);
     */
    public function scopeWhereTableData($query,$table_data,$table_id,$meta_data){
        $operatorList=['=', '!=', '<', '<=', '>', '>=', 'in', 'not_in', 'like', 'not_like', 'between', 'not_between'];//lista de opradores aceitos
        
        $arr=[];
        $sql=   $this->table.'.id='.
                '(select md1.'.$table_id.' from '.$table_data.' as md1 where md1.'.$table_id.'='.$this->table.'.id and (';
                
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
                            $tmp[]='?';//:meta_value_'.$name.'_'.$i;
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
                //$sql=trim($sql,'or ') . ') ';
                $sql=FormatUtility::trim($sql,' or ') . ') ';
                
                $i=count($meta_data);
                if($i>1)$sql.='GROUP BY md1.'.$table_id.' HAVING COUNT(md1.'.$table_id.')='.$i.' ';
                
                
        $sql.= ')';
        $query->whereRaw($sql,$arr);
        
        return $query;
    }
}
