<?php

namespace App\Services;
use App\Models\Tax;
use App\Models\TaxRelation;
use App\Models\TaxCache;
use Exception;

/**
 * Classe de serviço dos termos e taxonomias
 */
class TaxsService{
    
    
    private static $taxRelationModel;
    private static $taxCacheModel;
    private static $taxModel;
    private static $termModel;
    
    //Retorna a model do cache das taxonomias
    private static function getTaxCacheModel(){
        if(!self::$taxCacheModel)self::$taxCacheModel = new TaxCache;
        return self::$taxCacheModel;
    }
    
    //Retorna a model de relação de taxonomias
    private static function getTaxRelationModel(){
        if(!self::$taxRelationModel)self::$taxRelationModel = new TaxRelation;
        return self::$taxRelationModel;
    }
    
    //Retorna a model de taxonomias
    private static function getTaxModel(){
        if(!self::$taxModel)self::$taxModel = new Tax;
        return self::$taxModel;
    }
    
    
    /**
     * Adiciona uma taxonomia
     * @param array $data - campos a serem informados: tax_title, tax_description, tax_id_parent, color, icon
     * @return array - [success,msg]
     */
    public static function addTax($account_id,$data,$term_id){
        $validade = validator($data,[
            'tax_title'=>'required',
            'tax_description'=>'',
            'tax_id_parent'=>'',
            'tax_order'=>'0',
        ],\App\Utilities\FieldsValidatorUtility::getMessages()) ;
        if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
        
        $data['tax_opt'] = json_encode(['color'=>array_get($data,'color'),'icon'=>array_get($data,'icon')]);
        $data['term_id']=$term_id;
        $data['tax_title'] = substr($data['tax_title'],0,50);
        
        //verifica se já tem um título igual (dentro do mesmo nível)
        for($i=1;$i<=100;$i++){//tenta 100x
            $title = $data['tax_title'] . ($i>1?' ('.($i-1).')':'');
            $taxModel = self::getTaxModel()->where('term_id',$term_id)->where('tax_title',$title);
            if($data['tax_id_parent'])$taxModel=$taxModel->where('tax_id_parent',$data['tax_id_parent']);
            $taxModel = $taxModel->first();
            if($taxModel){//encontrou registro baseado no mesmo título
                //nenhuma ação, continua normalmente o loop para tentar de novo com novo nome
            }else{//não tem título igual
                break;
            }
        }
        $data['tax_title'] = $title;
        $data['tax_hide'] = false;
        $data['account_id'] = $account_id;
        
        $n=$data['tax_order']??'';
        if(!$n){//captura a última posição do tax_order
            $n=self::getTaxModel()->where('term_id',$term_id);
            if($data['tax_id_parent'])$n = $n->where('tax_id_parent',$data['tax_id_parent']);
            $n = $n->orderBy('tax_order','desc')->value('tax_order');
            if(!is_numeric($n))$n=0;
            $n=(int)$n+1;
        }
        $data['tax_order']=$n;
        
        $data['tax_level'] = self::getLevel($data['tax_id_parent']); //atualiza o nível
        
        try{
            $taxModel = self::getTaxModel()->create($data);
            $r=[
                'success'=>true,
                'msg' => 'Registro cadastrado',
                'action'=>'add',
                'data' => $taxModel->toArray(),
            ];
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        return $r;
    }
    
    
    /**
     * Atualiza uma taxonomia
     * @param array $data - campos a serem informados: ax_title, tax_description, tax_id_parent, color, icon
     * @return array - [success,msg]
     */
    public static function editTax($account_id,$data,$term_id,$id){
        $validade = validator($data,[
            'tax_title'=>'required',
            'tax_description'=>'',
            'tax_id_parent'=>'',
        ],\App\Utilities\FieldsValidatorUtility::getMessages()) ;
        if($validade->fails()){return ['success'=>false,'msg'=>$validade->errors()->messages()];}
        
        $data['tax_opt'] = json_encode(['color'=>array_get($data,'color'),'icon'=>array_get($data,'icon')]);
        $data['tax_hide'] = $data['tax_hide']=='s';
        
        if($account_id){
            $m=self::getTaxModel()->where('account_id',$account_id)->find($id);
        }else{
            $m=self::getTaxModel()->whereNull('account_id')->find($id);
        }
        
        if($m){
            $data['tax_level'] = self::getLevel($data['tax_id_parent']); //atualiza o nível do registro atual
            
            try{
                $m->update($data);
                $r=[
                    'success'=>true,
                    'msg' => 'Registro atualizado',
                    'action'=>'edit'
                ];
            }catch(Exception $e){
                $r=['success'=>false,'msg'=>$e->getMessage()];
            }
            
            self::updateLevel('edit',$m);//verifica e atualiza o nível dos registros filhos
        }else{
            $r=['success'=>false,'msg'=>'Registro não localizado'];
        }
        
        return $r;
    }
    
    
    
    /**
     * Remove uma taxonomia
     * @return array - [success,msg]
     */
    public static function delTax($account_id,$term_id,$id){
        if($account_id){
            $m=self::getTaxModel()->where('account_id',$account_id);
        }else{
            $m=self::getTaxModel()->whereNull('account_id');
        }
        $m=$m->where('term_id',$term_id)->find($id);
        if(!$m)$r=['success'=>false,'msg'=>'Registro não localizado'];
        
        try{
            //verifica e atualiza o nível dos registros filhos
            self::updateLevel('del',$m);
            
            //remove os metadados
            \App\Services\MetadataService::del('taxs', $id);
            
            //remove as relações com as demais tabelas
            self::getTaxRelationModel()->where('tax_id',$id)->delete();
            
            //remove o registro
            $m->delete();
            $r=['success'=>true,'msg'=> 'Registro deletado'];
            
        } catch (Exception $e) {
            $r=['success'=>false,'msg'=>$e->getMessage()];
        }
        
        return $r;
    }
    
    
    /**
     * Retorna ao valor do nível do registro pai
     * @param int $tax_id_parent
     */
    private static function getLevel($tax_id_parent){
        $level = 0;
        if($tax_id_parent){
            $mp=self::getTaxModel()->find($tax_id_parent);
            if($mp)$level=$mp->tax_level+1;
        }
        return $level;
    }
    
    /**
     * Atualiza os registros filhos considerando a alteração do nível do registro pai
     * @param $action - edit, del
     * @param model $tax_parent
     * @param $fnc_sub, $fnc_level - uso interno da função
     */
    private static function updateLevel($action,$tax_parent,$fnc_sub=null,$fnc_level=0){
        //verifica se existem registros filhos associados
        if($fnc_level==0){//primeira execução
            $children = self::getTaxList($tax_parent->term_id,['tax_id_parent'=>$tax_parent->id,'account_id'=>$tax_parent->account_id],'model');
            if($children){
                $fnc_level = $tax_parent->tax_level;//captura o nível do registro pai
            }
            if($action=='del')$fnc_level=0;//como está excluindo, inicia sempre no primeiro nível os filhos
            //dd($tax_parent,$children,$fnc_level);
        }else{
            $children = $fnc_sub;
        }
        if($children){
            $level_parent = $tax_parent->tax_level ?? 0;
            
            //lógica: atualiza todos os registros filhos atualizando o campo tax_level a partir do valor do nível do registro pai $tax_parent
            foreach($children as $reg){
               if($reg->sub){
                   self::updateLevel($action,$reg,$reg->sub,$fnc_level+1);
               }
               unset($reg->tax_title_original,$reg->level,$reg->parents,$reg->sub,$reg->tax_opt);//tira estes campos para não dar erro no update abaixo
               
               $reg->update(['tax_level'=>$fnc_level+1]);
               if($fnc_level==0 && $action=='del'){//como é exclusão, limpa o nível dos primeiros registros filhos
                   $reg->update(['tax_id_parent'=>null]);
               }
            }
        }
    }
    
    
    /**
     * Adiciona um relacionamento com a taxonomia
     * Return array - [success, msg]
     */
    public static function addRelation($tax_id,$area_name,$area_id){
                $reg = self::getTaxRelationModel()->where(['tax_id'=>$tax_id,'area_name'=>$area_name,'area_id'=>$area_id]);
        
        //verifica se o id da taxonomia é referente a conta atual logada //obs: na model Tax, já está validando pela conta logada
        //importante: foi testado e esta linha é necessária para validar corretamente dentro do sistema de contas
        if(self::getTaxModel()->where('id',$tax_id)->count()==0)return ['success'=>false,'msg'=>'Tax ID não permitido'];
        
        if($reg->count()>0){
            return ['success'=>true,'msg'=>'Já adicionado'];
        }else{
            try{
                $add = self::getTaxRelationModel()->create(['tax_id'=>$tax_id,'area_name'=>$area_name,'area_id'=>$area_id]);
                return ['success'=>true,'msg'=>'Adicionado com sucesso'];
            } catch (Exception $e) {
                return ['success'=>false,'msg'=>$e->getMessage()];
            }
        }
    }
    
    
    /**
     * Remove um relacionamento com a taxonomia
     * @param array $data - valores: (integer) tax_id, (string) area_name, (integer) area_id
     * Obs - somente um dos dois grupos é requerido:
     *       tax_id, area_name, area_id - exclui o relacionamento específico
     *       tax_id - exclui pelo id de toda a taxonomia
     *       area_name, area_id - exclui pela área de referência
     * @return array - [success, msg]
     */
    public static function delRelation(Array $data){
        $data = array_merge(array(
            'tax_id'=>'',
            'area_name'=>'',
            'area_id'=>'',
        ),$data);
        
        $r=true;
        $del=null;
        
        if(!empty($data['tax_id']) && !empty($data['area_name']) && !empty($data['area_id'])){
            $del = self::getTaxRelationModel()->where('tax_id',$data['tax_id'])->where('area_name',$data['area_name'])->where('area_id',$data['area_id']);
            
        }else if(!empty($data['area_name']) && !empty($data['area_id'])){
            $del = self::getTaxRelationModel()->where('area_name',$data['area_name'])->where('area_id',$data['area_id']);
            
        }else if(!empty($data['tax_id'])){
            $del = self::getTaxRelationModel()->where('tax_id',$data['tax_id']);
        }
        
        if($del){
            try{
                $del->delete();
                 return ['success'=>true];
            } catch (Exception $e) {
                return ['success'=>false,'msg'=>$e->getMessage()];
            }
        }else{
            return ['success'=>false,'msg'=>'Parâmetros inválidos'];
        }
    }
    
    
    /**
     * Retorna as relações das áreas das taxonomias
     * Return array object
     */
    public static function getRelationByArea($term_id,$area_name,$area_id,$account_id=null){
        $r=self::getTaxRelationModel()
                ->where('area_name',$area_name)->where('area_id',$area_id)
                ->join('taxs', 'taxs.id', '=', 'tax_relations.tax_id')
                ->where(['taxs.term_id'=>$term_id,'taxs.account_id'=>$account_id])
                ->orderBy('taxs.tax_level', 'ASC')
                ->orderBy('taxs.tax_order', 'ASC')
                ->orderBy('taxs.tax_title', 'DESC')
                ;
        return $r->get();
    }
    
    
    /**
     * Retorna a um array com todos os termos registrados do registro atual
     * @param $format = (object) array object model, (array), array de ids
     */
    public static function getAllTermsByReg($area_name,$area_id,$format='object'){
        $fs=['terms.id','terms.term_title','terms.term_singular_title','terms.term_short_title','terms.term_description'];
        $r = self::getTaxRelationModel()
                ->select($fs)
                ->where('tax_relations.area_name',$area_name)->where('tax_relations.area_id',$area_id)
                ->join('taxs', 'taxs.id', '=', 'tax_relations.tax_id')
                ->join('terms', 'terms.id', '=', 'taxs.term_id')
                ->groupBy($fs)
                ->orderBy('terms.term_title', 'ASC')
                ->get();
        if($format=='object'){
            return $r;
        }else{
            return $r->toArray();
        }
    }
    
    
    /**
     * Captura os ids dos termos e taxonomias a partir dos nomes padrões do post de um formulário
     * Procura pelos campos: autofield_taxs_term_id, autofield_taxs_term_{term_id} e autofield_taxs_term_{term_id}_uncheck
     * @param array $data - o mesmo de Request::all()
     * @return array - sintaxe ['check'=>[ term_id=>[tax_1,tax_2,...] ], 'uncheck'=>..., 'term_names'=>[name_1,...]]
     */
    public static function getFieldsByPost($data){
        $checks=$unchecks=[];
        $term_ids = $data['autofield_taxs_term_id']??[];
        $term_names=[];
        foreach($term_ids as $term_id){
            $term_names[$term_id] = $data['autofield_taxs_term_'.$term_id.'_name']??null;
            
            //procura e retorna somente aos tax_ids pertencentes ao term_id
            $tax_ids = $data['autofield_taxs_term_'.$term_id]??null;
            if($tax_ids)$checks[$term_id] = self::getTaxModel()->where('term_id',$term_id)->whereIn('id',$tax_ids)->pluck('id')->toArray();
            
            //procura e retorna somente aos tax_ids pertencentes ao term_id
            $taxs_ids_off = explode(',',$data['autofield_taxs_term_'.$term_id.'_uncheck']);
            $r = self::getTaxModel()->where('term_id',$term_id)->whereIn('id',$taxs_ids_off)->pluck('id')->toArray();
            if($r)$unchecks[$term_id]=$r;
        }
        
        return ['check'=>$checks,'uncheck'=>$unchecks,'term_names'=>$term_names];
    }
    
    
    /**
     * Retorna a lista de taxonomias considerando os níveis de hierarquia.
     * @param array $params
     * @param $ret - valores: array|model       //obs: se array, caso o parâmetro $opt paginate será sempre false
     * @param $all - uso interno da função
     * Return object | array
     * Propriedades para cada item retornado:
     *      -> level - número do nível. Default 0.
     *      -> sub - array com a sublist
     *      -> parents - array com a lista pai
     *      -> tax_opt - array com opções
     */
    public static function getTaxList($term_id,$params=array(),$ret='array',&$all=[]){
         $opt = array_merge([
             'tax_id_parent'=>null,                     //id do registro pai a ser iniciado (opcional).
             'levels'=>null,                            //quantidades níveis que serão exibidos - null = todos
             'merge_list'=>false,                       //e true, indica que irá mesclar os resultados em um único object /array. Default false para retornar ao atributo ->sub.
             'level_space'=>' —',                       //separador de níveis no título
             'id_not'=>null,                            //array de ids que não devem aparecer na lista (opcional)
             'ids'=>null,                               //(array) relação de ids para filtragem de conteúdo
             'order'=>['order'=>'asc','title'=>'asc'],  //order dos campos. Campos: order, title, count. Sintaxe: (array) field=>orderby, ...  - ex: [order=>asc] (default)
             'area_name'=>null,                         //
             'area_id'=>null,                           //
             'paginate'=>false,                         //(int) paginação
             'account_id'=>null,                        //id da conta
             //parâmetros interno
             '_nlevel'=>0,
             '_parents'=>[],
         ],$params);
         //dump($opt);
         
         
         $nlevel=$opt['_nlevel'];
         
         //captura a relação de ids relacionados pela tabela tax_relations (obs: somente deve ser executado este código 1x para $opt['_nlevel']==0)
         if($opt['_nlevel']==0 && $opt['area_name'] && $opt['area_id']){
             $r=[];
             if($opt['area_name'] && $opt['area_id']){
                 $r = self::getRelationByArea()->pluck('tax_id')->toArray();
                 if($r)$opt['ids']=$r;//atualiza o var de filtro por ids
             }
         }
         
        
         $taxs = self::getTaxModel()->where('term_id',$term_id);
         
         if($opt['account_id']){
             $taxs->where('account_id',$opt['account_id']);
         }else{
             $taxs->whereNull('account_id');
         }
         
         if($opt['tax_id_parent']){
             $taxs->where('tax_id_parent',$opt['tax_id_parent']);
         }else{
             $taxs->whereNull('tax_id_parent');
         }
         
         //ordem dos campos
         foreach($opt['order'] as $f=>$v){
            if($f=='order'){
                $taxs->orderBy('tax_order',$v);
            }else if($f=='title'){
                $taxs->orderBy('tax_title',$v);
            }else if($f=='count'){
                $taxs->withCount('relations');//relacionamento
                $taxs->orderBy('relations_count',$v);
            }
         }
         
         //ids negados
         if($opt['id_not'])$taxs->whereNotIn('id', is_array($opt['id_not'])?$opt['id_not']:[$opt['id_not']] );
         
         //ids aceitos
         if($opt['ids'])$taxs->whereIn('id', is_array($opt['ids'])?$opt['ids']:[$opt['ids']] );
         
         
         //dd([$taxs->toSql(), $taxs->getBindings()]);
         $taxs=$opt['paginate'] ? $taxs->paginate($opt['paginate']) : $taxs->get();
         
         
         //lógica abaixo: var $all - usado para merge_list=true, $list - usado para merge_list=false
         $list=[];
         
         foreach($taxs as &$reg){
            $space=$nlevel ? str_repeat($opt['level_space'], $nlevel).' ' : '';
            
            $reg->tax_title_original = $reg->tax_title;
            if($opt['merge_list'])$reg->tax_title = $space . $reg->tax_title;
            $reg->level = $nlevel;
            $reg->parents = $opt['_parents'];
            $reg->tax_opt = empty($reg->tax_opt) ? ['color'=>null,'icon'=>null] : json_decode($reg->tax_opt,true);

            if($opt['merge_list']){
                $all[$reg->id]=$ret=='model' ? $reg : $reg->toArray();   
            }else{
                $list[$reg->id]=$ret=='model' ? $reg : $reg->toArray();
            }

            if($opt['levels']<=$nlevel){
                $n = self::getTaxList($term_id, array_merge($opt,['tax_id_parent'=>$reg->id,'_nlevel'=>$nlevel+1,'_parents'=>$reg->parents + [$reg->tax_title]]), $ret, $all);
                if($n && !$opt['merge_list']){
                    if($ret=='model')$n=Collect($n);
                    $list[$reg->id]['sub']=$n;
                }
            }
         }
        
         if($opt['merge_list']){
            if($ret=='model' && !$nlevel){//último nível
                $all = (new \App\Utilities\CollectionUtility($all));
                if($opt['paginate'])$all=$all->paginate($opt['paginate']);
            }
            return $all;
         }else{
            return $list;
         }
    }
    
    
    /**
     * O mesmo de self::getTaxList(), mas retorna no padrão para a view templates.components.tree.blade
     * @param $params - o mesmo de self::getTaxList()
     * @param $route_click - função da rota para o link de cada item, ex: function($reg){ return route(...); }
     * @param $sub - uso interno da função
     */
    public static function getTaxListTree($term_id,$params=array(),$route_click=null,$sub=null){
        $list = $sub ? $sub : self::getTaxList($term_id,$params,'model');
        $r=[];
        foreach($list as $reg){
            $tx_opt = $reg->tax_opt??[];
            $r[$reg->id]=[
                'title'=>$reg->tax_title,
                'icon'=>$tx_opt['icon']??null,
                'icon_color'=> isset($tx_opt['color']) ? \App\Utilities\ColorsUtility::getColor($tx_opt['color']) : null,
                'link'=> $route_click ? callstr($route_click,['reg'=>$reg],true) : '#',
            ];
            if($reg->sub){
                $r[$reg->id]['sub'] = self::getTaxListTree($term_id,$params,$route_click,$reg->sub);
            }
        }
        
        return $r;
    }
    
    
    //XXXXXXXXX analisando os recursos abaixo XXXXXXXXXXX
    /*
    /**
     * Retorna ao array em cache (original da função self::getTaxList())
     * @param $ret - valores: 
     *          null - (default) irá retornar a sintaxe: [id=>[title, [sub...], .. ]]
     *          'ids' - irá retornar a um array único de ids, ex: [1,2,..]
     * @return array
     * /
    private static function getTaxCache($term_id,$area_name,$area_id,$ret=null){
        $r = self::getTaxCacheModel()->where(['term_id'=>$term_id,'area_name'=>$area_name,'area_id'=>$area_id])->value('cache');
        return $ret=='ids' ? $r=self::onlyIdsByArr($r) : $r;
    }
    
    /**
     * Salva os dados em cache
     * /
    public static function saveTaxCache($term_id,$area_name,$area_id,$cache){
        $m = self::getTaxCacheModel()->where(['term_id'=>$term_id,'area_name'=>$area_name,'area_id'=>$area_id])->first();
        if($m){
            $m->update(['cache'=>$cache]);
        }else{
            self::getTaxCacheModel()->create(['term_id'=>$term_id,'area_name'=>$area_name,'area_id'=>$area_id,'cache'=>$cache]);
        }
    }
    
    /**
     * Captura somente a relação de ids da matriz retornada da função self::getTaxList()
     * /
    private static function onlyIdsByArr($arr){
        $r=[];
        array_walk_recursive($arr,function($v,$k) use (&$r){
            if($k==='id')$r[]=$v;
        });
        return $r;
    }*/
}