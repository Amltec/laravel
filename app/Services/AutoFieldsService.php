<?php

namespace App\Services;
use App\Utilities\FormatUtility;

/**
 * Classe de de autofield para parametrização de tabelas do sistema e salvamento de dados automático
 */
class AutoFieldsService{
    
    /*
     * Complemento de configuração - Retorna a configuração para geração de campos automáticos pela view templates.ui.auto_fields.blade.
     * Esta função deve ser aplicado dentro das views na geração de formulários.
     * array $params:
     *      string prefix   - prefixo para todos os campos. Default ''.
     *      string title    - título da janela metabox. Default ''.
     *      string area_name- campo area_name. Opcional. Default ''. 
     *      string area_id  - campo area_id. Opcional. Default ''.
     *      array metabox   - parâmetros do metabox (veja em templates.componentes.metabox).
     *      array fields    - campos aceitos e suas opções - sintaxe: 'field-name'=>[ option_name=>option_value ]   OU      'field-name'=>true (assim somente os campos com true serão exibidos)
     *                          Nomes dos campos (ex): tipo,cpf,cnpj,name,razaosocial,apelido,fantasia,rg,ie,sexo,dtnasc,status.
     *                          Opções: 
     *                              value   - (string) valor padrão incial
     *                              //para campos do tipo select (tipo, sexo, status)
     *                              blank   - (booelan) True indica que a primeira opção será vazia. Default True.
     *                              list    - (array) valores para o parâmetro 'list' (sintaxe [val=>text,...]). Os valores são mesclados da seguinte forma:
     *                                              'val'=>'text'   - adiciona ou altera o texto caso exista
     *                                              'val'=>false    - remove
     *                              ... demais parâmetros mesclam os parãmetro dos campos/componentes usando pelo template autofield (ex, label, require, etc)
     *      array add_columns  - array de campos adicionais a serm adicionados (utiliza o comando array_merge)
     *      array|object autodata - (opcional) deve conter os parâmetros 'autodata' com os nomes dos campos conforme tabela orignal, e nesta função serão formatados conforme parâmetro $fields_all para preenchimento automático dos campos do form
     * Obs: 
     *  1) esta função é auxiliar dos controllers das tabelas padrões, ex: ContatsController ->configAutoFields()
     *  2) alguns campos como cidade_uf pode usar o separador '|' para os nomes de campos, portando o campo 'prefix' deve evitar o uso deste separador.
     */
    public static function complementConfig($param,$fields_all,$fields_names_def=[]){
        $param = array_merge([
            'prefix'=>'',
            'title'=>'',
            'area_name'=>'',
            'area_id'=>'',
            'metabox'=>['title'=>''],
            'block_dinamic'=>false,
            'fields'=>[],
            'add_columns'=>false,
            'autodata'=>null
        ],$param);
        $autodata=$param['autodata'];
        
        //monta a matriz de campos com os nomes corretos dos formulários
        $fields_ok=[];
        foreach($fields_all as $fname=>$fopt){
            $allowed=true;
            
            if(isset($param['fields']) && !empty($param['fields'])){
                $a=$param['fields'][$fname]??false;
                if($a){
                    if(isset($a['value']))$fopt['value']=$a['value'];//seta o valor inicial
                    
                    //válido somente para campos de listas como: tipo, sexo, status
                    if(isset($a['blank']) && $a['blank']===false){
                        unset($fopt['list']['']);
                        unset($a['blank']);//remove para poder mesclar os campos abaixo
                    }
                    if(isset($a['blank']) && $a['blank']===true){
                        $fopt['list']=[''=>'']+$fopt['list'];
                        unset($a['blank']);//remove para poder mesclar os campos abaixo
                    }
                    if(isset($a['list']) && !empty($a['list'])){
                        foreach($a['list'] as $x1 => $x2){
                            if($x2===false){
                                if(isset($fopt['list'][$x1]))unset($fopt['list'][$x1]);//remove
                            }else{
                                $fopt['list'][$x1]=$x2;//adiciona ou atualiza
                            }
                        }
                        unset($a['list']);//remove para poder mesclar os campos abaixo
                    }
                    
                    //mescla os demais atributos dos campos
                    if(!empty($a) && is_array($a)){
                        if($fname=='status'){
                            $fopt = array_merge($fopt,$a);
                            //dd($fname,$fopt,$a,$fopt2);
                        }
                    }
                    
                 }else{
                     $allowed=false;//não exibe o campo
                 }
            }
            if($allowed && isset($fields_names_def[$fname])){
                $tmp = $fields_names_def[$fname];
                
                if(strpos($tmp,'|')!==false){//o campo tem um nome composto, ex: cidade|uf, e portanto separa para adicionar o prefixo
                    $tmp = explode('|',$tmp);
                    foreach($tmp as $i=>$f1){
                        $tmp[$i]=$param['prefix'].$f1;
                    }
                    $fields_ok[ join('|',$tmp) ] = $fopt;
                    
                }else{//apenas o nome normal de campo
                    $fields_ok[ $param['prefix'] . $fields_names_def[$fname] ] = $fopt;
                }
                
            }
        }
        
        if($autodata){
            //*** obs: abaixo faz a mesma coisa da função, mas código está um pouco diferente devido aos ajustes por causa das vars: $fields_all e $fields_names_def ***
            if(is_array($autodata))$autodata=(object)$autodata;
            foreach($autodata as $ad_f=>$ad_v){
                if(is_object($ad_v)){//quer dizer que o loop atual, é um array de vários objetos
                    foreach($ad_v as $ad_f2=>$ad_v2){
                        $t=false;
                        foreach($fields_names_def as $f => $v){
                            //formata para o exemplo: de 'contact_cpf_cnpj--f' para 'contact_cpf_cnpj'  //obs: pela lógica, sempre que houver ex '--' quer será única no nome do campo e para separar apenas os tipos
                            $n=strpos($v,'--')===false ? $v : explode('--',$v)[0];
                            if($f==$ad_f2 || $n==$ad_f2){//verifica pelo nome do campo sem ajuste ou já ajustado
                                $ad_v->{$param['prefix'] . $v} = $ad_v2;
                                $t=true;
                                break;
                            }
                        }
                        if(!$t){//não achou o campo, portanto apenas adiciona o prefixo
                            if(substr($ad_f2,0,strlen($param['prefix']))!==$param['prefix'])$ad_v->{$param['prefix'] . $ad_f2} = $ad_v2;
                        }
                    }
                    
                }else{
                    foreach($fields_names_def as $f => $v){
                        //formata para o exemplo: de 'contact_cpf_cnpj--f' para 'contact_cpf_cnpj'  //obs: pela lógica, sempre que houver ex '--' quer será única no nome do campo e para separar apenas os tipos
                        $n=strpos($v,'--')===false ? $v : explode('--',$v)[0];
                        if($f==$ad_f || $n==$ad_f){//verifica pelo nome do campo sem ajuste ou já ajustado
                            $autodata->{$param['prefix'] . $v} = $ad_v;
                            break;
                        }
                    }
                }
            }
        }
        //dd($param['prefix'],$autodata,$fields_ok,$fields_names_def);
        
        
        //dd($fields_ok);
        //if($autodata && isset($autodata->{0}))dump($autodata->{0});
        if($param['area_name'])$fields_ok[$param['prefix'] . 'area_name']=['type'=>'hidden','value'=>$param['area_name']];
        if($param['area_id'])$fields_ok[$param['prefix'] . 'area_id']=['type'=>'hidden','value'=>$param['area_id']];
        
        
        //mescla os campos adicionais
        if($param['add_columns']){
            $fields_ok= array_merge($fields_ok,$param['add_columns']);
        }
        
        
        $config=[
            'prefix'=>$param['prefix'],
            'metabox'=>[],
            'layout_type'=>'horizontal',
            'autocolumns'=>$fields_ok,
            'block_dinamic'=>$param['block_dinamic'],
        ];
        if($param['metabox']===false){
            $config['metabox']=false;
        }else{
            if($param['metabox'])$config['metabox']=array_merge($config['metabox'],$param['metabox']);
            if(isset($param['title']))$config['metabox']['title']=$param['title'];
            if(empty($config['metabox']['title']))$config['metabox']['header']=false;
        }
        
        if(!empty($autodata))$config['autodata']=$autodata;
        
        
        return $config;
    }
    
    
    /**
     * Ajusta os nomes dos campos da variável $data com o prefixo informado para a correta inserção de dados dentro do db
     * Ex: adjusteData([field1,field2],'tmp_') // retorna [tmp_field1, tmp_field2]
     * @param $data - object data (stdclass).
     * @param $prefix - string prefix
     * @param $onlyNew - boolean se true retorna somente aos novos resultados. Defautl false
     * @param $ret - object|array
     */
    public static function adjusteData($data,$prefix,$recursive=false,$onlyNew=false,$ret='object'){
        
        if(gettype($data)=='object'){
            $data=(array)$data;
        }else if(gettype($data)!='array'){
            return $data;
        }
        
        foreach($data as $k=>$v){
            $n = is_numeric($k) ? $k : $prefix.$k;
            if((gettype($v)=='array' || gettype($v)=='object')){
                if(is_numeric($k) && $recursive==false){
                    $data[$n] = self::adjusteData($v,$prefix,false);
                }else if($recursive){
                    $data[$n] = self::adjusteData($v,$prefix,true);
                }else{
                    $data[$n] = $v;
                    if($onlyNew)unset($data[$k]);
                }
            }else{
                $data[$n] = $v;
                if($onlyNew)unset($data[$k]);
            }
            //if(!is_numeric($k))unset($data[$k]);//obs: não pode remover, deve manter os campos antigos
        }
        //dd($data);
        return $ret=='object' ? (object) $data : $data;
    }
    
    
    /**
     * Ajusta a matriz no padrão do request do templates.ui.auto_fields para matriz.
     * Ex: de ['prefix{n}|_autofield_count'=>{count}, 'prefix{n}|field1'=>..., 'prefix{n}|field2'=>...]     para [ {n} => [field1=>...,field2=>...] ]...
     */
    public static function getDataByPrefix($data,$prefix){
        return FormatUtility::filterPrefixArrayList($data,$prefix);
    }
    
    /**
     * Filtra a array considerando uma relação de nomes de campos e sufixos numéricos.
     * Ex: De filterNamesArrayList([fieldA_1=>, fieldB_1=>, inputA_1=>],'field');       //retorna a [1=>[fieldA=>,fieldB=>...], ... ]
     */
    public static function getDataByNamesSufix($data,$fields=null){
        return FormatUtility::filterNamesArrayList($data,$fields);
    }
}