<?php

/*************
 * IMPORTANTE: esta classe está funcionando, mas ainda não está sendo usada dentro da programação!!!
*************/

namespace App\ProcessRobot;
use App\Utilities\FormatUtility;

/* 
 * Classe de funções para detectar o tipo da apólice, ex: automovel, residencial, ...
 */
class DetectTypeProcessRobot{
    //sintaxe [ramo => [text1, text2, [text3, text4], ...], ... ]
    
    private static $ramo_list=[
        'automovel'=>[
            '0520 - Automóvel',
            '0524 - Automóvel',
            '0525 - Automóvel',
            '0526 - Automóvel',
            '0531 - Automóvel',
            '0542 - Automóvel',
            '0553 - Automóvel',
            '0588 - Automóvel',
            '31 - AUTOMÓVEIS',
            '31 Automoveis',
            '31 Veiculos',
            'Ramo 53 R.C.F. - Veiculos',
            'Tokio Marine Auto',
            'Tokio Marine Caminhão',
            'SulAmérica Auto',
            'Allianz Auto',
            'Azul Seguro Auto',
            'Porto Seguro Auto',
            'Porto Seguro Moto',
            'Itaú Seguro Auto',
            '0531-AUTOMÓVEL',
            'Ramo: AUTOMOVEL',
            'RAMO: 0531',
            'Ramo: 31',
            'Ramo: 53',
            'Alfa Auto',
            '0531 Automóvel',
            '31 - Casco',
            '53 - RCF Veículos',
            ['Histórico da Apólice','Auto + Residencial']
        ],
        'residencial'=>[
            'Ramo 14 Compreensivo Residencial',
            '0114 - Compreensivo Residencial',
            'Ramo: 01.14',
        ],
        'empresarial'=>[
            'Ramo 18 Compreensivo Empresarial',
            '0118 - Compreensivo Empresarial',
            'Ramo: 01.18',
        ],
        'condominio'=>[
            'Ramo: 01.16',
        ]
    ];
    
    /**
     * Identifica qual o ramo a partir da var $text.
     * @return com o nome do ramo, ex: 'automovel'. Caso não encontrado, ou encontrado mais de 1, retorna a vazio.
     */
    public static function getRamo($text){
        $list = self::$ramo_list;
        $text = FormatUtility::sanitizeAllText($text);
        
        $ramo=[];
        foreach($list as $prod => $items){
            $t=false;
            foreach($items as $item){
                if(is_array($item)){//várias strings, todas precisam existir neste caso
                    $x=0;
                    foreach($item as $item_x){
                        $item_x = FormatUtility::sanitizeAllText($item_x);
                        if(stripos($text,$item_x)!==false)$x++;
                    }
                    if(count($item)==$x){
                        $t=true;break;
                    }
                    
                }else{
                    $item = FormatUtility::sanitizeAllText($item);
                    if(stripos($text,$item)!==false){
                        $t=true;break;
                    }
                }
            }
            if($t)$ramo[]=$prod;//achou o ramo
        }
        //dd($ramo);
        if(count($ramo)!=1)$ramo='';//vazio ou encontrou mais de um, portanto retorna a vazio
        return join('',$ramo);
    }
    
}