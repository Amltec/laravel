<?php

namespace App\Services;

use App\Utilities\ValidateUtility;
use Auth;
use DB;

/**
 * Classe de serviço de metadados.
 * Utilizada para armazenar dados adicionais.
 */
class MetadataService{
    
   
    /**
     * Adiciona ou atualiza o metadata
     * Return True ok ou msg erro
     * Param (string) $area_name - nome de área
     * Param (model) $area_name - objeto model da tabela
     */
    public static function set($area_name, $area_id, $name, $value){
        if(is_null($value) || $value===''){//como o campo não permite valor null, então remove o registro metadado
            $r = self::del($area_name, $area_id, $name);
        }else{
            if(is_array($value))$value = serialize($value);
            try {
                DB::select('insert into metadata (area_name,area_id,meta_name,meta_value) values(:area_name,:area_id,:meta_name,:meta_value) on duplicate key update meta_value=:upd_meta_value', [
                    'area_name' => $area_name,
                    'area_id' => $area_id,
                    'meta_name' => $name,
                    'meta_value' => $value,
                    'upd_meta_value' => $value,
                ]);
                $r = true;
            } catch (Exception $e) {
                $r = $e->getMessage();
            }
        }
        return $r;
    }

    /**
     * Deleta o metadado
     * Return True ok ou msg erro
     */
    public static function del($area_name, $area_id, $name='') {
        try{
            $meta = DB::table('metadata')->where('area_name', $area_name)->where('area_id', $area_id);
            if($name!='')$meta=$meta->where('meta_name', $name);
            $meta->delete();
            $r=true;
        } catch (Exception $e) {
            $r=$e->getMessage();
        }
        return $r;
    }
    
    
    /**
     * Retorna a todos os metadas de um registro na seguinte estrutura: [meta_name=>metavalue,...]
     * Return array
     */
    public static function get($area_name, $area_id, $name='') {
        $meta = DB::table('metadata')->where('area_name', $area_name);
        $meta=$meta->where('area_id', $area_id);
        if($name!=''){
            $v = $meta->where('meta_name', $name)->value('meta_value');
            return ValidateUtility::isSerialized($v) ? unserialize($v) : $v;
        }else{
            $data = $meta->get();
            $r=[];
            foreach($data as $f=>$v){
                $r[$v->meta_name]= ValidateUtility::isSerialized($v->meta_value) ? unserialize($v->meta_value) : $v->meta_value;
            }
            return $r;
        }
    }
    
    
    
    
    
}
