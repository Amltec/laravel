<?php
namespace App\Utilities;
use App\Utilities\FormatUtility;

/*
 * Classe de conversão Array XML
 */
Class XMLUtility{
    /**
     * Converte um array em string XML
     * @param array $data
     * @param arrray $opt boolean
     * @return string
     */
    public static function convertArrToXml($data,$opt=[]){
        $opt=array_merge([
            'array_level'=>true,     //define se irá trabalhar com níveis de array. Default true. Se false criar as array internas no padrão $key--$index
            'node'=>'data',          //nome do nó
            'only_nodes'=>false,   //exibir somente o conteúdo
        ],$opt);
        
        if($opt['only_nodes']){
            $r='';
        }else{
            $r = '<?xml version="1.0"?>'.chr(10).'<'. $opt['node'] .'>'.chr(10);
        }
        //dd($data);
        foreach($data as $key=>$value){
                if(is_array($value)){
                        if($opt['array_level']){
                            $opt2=['array_level'=>false,'node'=>$key,'only_nodes'=>true];
                            $r.='<'.$key.'>'. chr(10). self::convertArrToXml($value,$opt2).chr(10) .'</'.$key.'>'.chr(10);
                        }else{
                            $r.='<'.$key.'--count>'. count($value) .'</'.$key.'--count>'.chr(10);
                            $i=0;
                            foreach($value as $key2=>$value2){
                                    $i++;
                                    $r.='<'.$key.'--'.$i.'>'. htmlspecialchars($value2) .'</'.$key.'--'.$i.'>'.chr(10);
                            }
                        }
                }else{
                        $r.='<'.$key.'>'. htmlspecialchars($value) .'</'.$key.'>'.chr(10);
                }
        }
        if(!$opt['only_nodes'])$r.='</'. $opt['node'] .'>';
        return $r;
    }
    
    
    
    /*
    //captura as tags de um documento de texto e retorna a sua matriz. Ex: de '<field>valor</field>...' para '[field=>valor]'
    function everything_in_tags($string,$tagname=''){
        if($tagname){
            $pattern = "#<\s*?$tagname\b[^>]*>(.*?)</$tagname\b[^>]*>#s";
            preg_match($pattern, $string, $matches);
            return $matches[1];
        }else{
            preg_match_all("'<(.*?)>(.*?)</(.*?)>'s", $string, $arr);
            //dd($string,$arr);
            $r=[];
            foreach($arr[1] as $i => $f){
                $r[$f] = trim($arr[2][$i]);
            }
            return $r;
        }
    }
    */
}