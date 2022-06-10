<?php

namespace App\Services;
use DB;

/**
 * Classe de serviço para funções gerais no banco de dados
 */
class DBService{
   
    /**
     * Nomes das áreas registradas.
     * Estas áreas são utilizadas como identificar para todos os valores do campo area_name 
     * Sintaxe: area_name => [
     *      name            => nome normal / plural,
     *      singular_name   => nome no singular,
     *      title           => (opcional) título / nome da área.
     *      descr           => (opcional) descrição / informações da área.
     * ]
     * Obs: tabelas de termos, taxonomias e metadados não precisam constar nesta lista.
     */
    private static $areasNames=[
        'accounts' => [
            'name'=>'Contas',
            'singular_name'=>'conta',
            'title'=>'Contas do sistema',
        ],
        'users' => [
            'name'=>'Usuários',
            'singular_name'=>'Usuário',
            'title'=>'Usuários do sistema',
        ],
        /*
        //particular de cada controller
        'brokers'=>['name'=>'Corretores','singular_name'=>'Fornecedor'],
        'insurers'=>['name'=>'Seguradoras','singular_name'=>'Fornecedor'],
        'robot'=>['name'=>'Robôs','singular_name'=>'Robô'],
        'process.cad_apolice'=>['name'=>'Cadastro de Apólices','singular_name'=>'Cadastro de Apólice'],
        'process.seguradora_files'=>['name'=>'Área de Seguradoras','singular_name'=>'Área de Seguradoras'],
         */
    ];
    
   
    /**
     * Retorna aos dados da tabela a partir do Nome e ID da área.
     * Return collection db da respectiva tabela pelo campo area_name.
     * Param (string) $area_name - nome de área (deve corresponder ao nome de uma tabela)
     * Param (integer) $area_id - valor do campo id
     * Param (string) $idKeyName - nome do campo id (default 'id')
     */
    public static function getAreaData($area_name, $area_id, $idKeyName='id'){
        return DB::select('select * from '.$area_name.' where '.$idKeyName.' =:area_id;',['area_id'=>$area_id]);
    }
    
    
    /**
     * Retorna a um array dos nomes das áreas registradas para a geração de termos e demais recursos.
     * Param $area_name - caso não informado retorna a todos os dados do array atual.
     * Param $field - se $area_name definido e caso informado, retorna a coluna específica do array. Ex de valores: name, singular_name, ...
     * Caso não encontre retorna a false.
     */
    public static function getAreaName($area_name='',$field=''){
        if($area_name==null)return '';
        
        $a=self::$areasNames;
        if($area_name==''){//return all list
            return $a;
        }else if(isset($a[$area_name])){//return array of $area_name
            $a=$a[$area_name];
            if($field!=''){
                if(isset($a[$field])){
                    $a=$a[$field];
                    return $a;
                }else{
                    return false;
                }
            }
            return $a;
        }else{
            return false;
        }
        
        if(empty($area_name)){
            return self::$areasNames;
        }else{
            $a = self::$areasNames[$area_name];
        }
    }
    
    /**
     * Retorna a um array dos nomes das áreas para um campo select.
     * Estrutura: [area_name=>name,...]
     */
    public static function getAreaFieldSelect(){
        $r=[];
        foreach(self::$areasNames as $a=>$opt){
            $r[$a]=$opt['name'];
        }
        return $r;
    }
    
    
    
    /**
     * Retorna se a área informada está registrada
     * Return boolean
     */
    public static function isAreaName($area_name){
        return isset(self::$areasNames[$area_name])===true;
    }
    
    
    /**
     * Retorna o sql completo com os respectivos valores (utilizado para orientação do programador)
     */
    public static function getSqlWithBindings($query){
        return vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function ($binding) {
            return is_numeric($binding) ? $binding : "'{$binding}'";
        })->toArray());
    }
}
