<?php

namespace App\Services;

/**
 * Classe de serviço dos termos
 */
class TermService{
    
    private static $termModel;
    
    //Retorna a model dos termos
    private static function getTermModel(){
        if(!self::$termModel)self::$termModel = new \App\Models\Term;
        return self::$termModel;
    }

    /**
     * Adiciona um termo
     * @param array $data - campos a serem informados: term_title, term_singular_title, term_short_title, area_name, area_id
     * @return array - [success,msg]
     */
    public static function add($data,$id=null){
        $param=[
            'term_title'=>'required',
            'term_singular_title'=>'required',
            'term_short_title'=>'required',
        ];
        
        $validade = validator($data,$param,\App\Utilities\FieldsValidatorUtility::getMessages()) ;
        if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
        
        try{
            $m = self::getTermModel();
            if($id){//edit
                $m->find($id)->update($data);
                $r=['success'=>true,'msg' => 'Registro atualizado','action'=>'edit'];
            }else{//add
                $m->create($data);
                $r=['success'=>true,'msg' => 'Registro cadastrado','action'=>'add','data'=>$m->toArray()];
            }
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        return $r;
    }
    
    /**
     * Edita um termo
     * @return array - [success,msg]
     */
    public static function edit($id,$data){
        return self::add($data, $id);
    }
    
    /**
     * Remove um termo
     * @return array - [success,msg]
     */
    public static function del($id){
        //não deixa remover o registro se houver dados relacionados
        $tax_count = \App\Models\Tax::where('term_id',$id)->count();
        
        if($tax_count>0){
            $term = self::getTermModel()->find($id);
            $r = ['success'=>false,'msg'=>'Não é possível excluir "'.$term->term_title.' <small>#'.$id.'</small>". <br>Motivo: registro relacionado.'];
        }else{
            try{
                //remove os metadados
                \App\Services\MetadataService::del('terms', $id);
                //remove o registro
                $delete = self::getTermModel()->find($id)->delete();
                $r=['success'=>true,'msg' => 'Registro deletado'];
            } catch (\Exception $e) {
                $r=['success'=>false,'msg'=>$e->getMessage()];
            }
        }
        
        return $r;
    }
    
    /**
     * Encontra um termo
     * @param array $opt - valores: id || area_name e area_id
     * @returm model | null
     */
    public static function find($opt){
        $m = self::getTermModel();
        if(isset($opt['id'])){
            return $m->find($opt['id']);
        }elseif(isset($opt['area_name']) && isset($opt['area_id'])){
            $m=$m->where($opt)->get();
            return $m->count()>0 ? $m : null;
        }else{
            return null;
        }
    }
    
}