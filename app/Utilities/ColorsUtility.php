<?php
namespace App\Utilities;
/*
 * Classe de relação de cores para serem utilizados no sistema)
 * 
 */
Class ColorsUtility{
    
    /**
     * Retorna a uma matriz de cor com a sintaxe: '[id=>[color_bg=>color_text]],...'
     * Obs: color_text é considerando que a cor principal, seja adicionada como objeto de fundo
     */
    public static $colors=[
        '1' =>['#880E4F','#ffffff'],
        '2' =>['#A52714','#ffffff'],
        '3' =>['#E65100','#ffffff'],
        '4' =>['#F9A825','#ffffff'],
        '5' =>['#817717','#ffffff'],
        '6' =>['#558B2F','#ffffff'],
        '7' =>['#097138','#ffffff'],
        '8' =>['#006064','#ffffff'],
        '9' =>['#01579B','#ffffff'],
        '10' =>['#1A237E','#ffffff'],
        '11' =>['#673AB7','#ffffff'],
        '12' =>['#4E342E','#ffffff'],
        '13' =>['#C2185B','#ffffff'],
        '14' =>['#FF5252','#ffffff'],
        '15' =>['#F57C00','#ffffff'],
        '16' =>['#FFEA00','#ffffff'],
        '17' =>['#AFB42B','#ffffff'],
        '18' =>['#7CB342','#ffffff'],
        '19' =>['#0F9D58','#ffffff'],
        '20' =>['#0097A7','#ffffff'],
        '21' =>['#0288D1','#ffffff'],
        '22' =>['#3949AB','#ffffff'],
        '23' =>['#9C27B0','#ffffff'],
        '24' =>['#795548','#ffffff'],
        '25' =>['#BDBDBD','#ffffff'],
        '26' =>['#757575','#ffffff'],
        '27' =>['#424242','#ffffff'],
        '28' =>['#000000','#ffffff'],
    ];
    
    /**
     * Retorna ao style: background-color da cor
     * @param $id - o mesmo de id de self::$colors
     * @return string
     */
    public static function getBgColor($id){
        $r=self::$colors[$id]??['',''];
        return $r[0] ? 'background-color:'.$r[0].';color:'.$r[1].';' : '';
    }
    
    /**
     * Retorna ao style: color cor pelo ID da cor
     * @param $id - o mesmo de id de self::$colors
     * @return string
     */
    public static function getTextColor($id){
        $r=self::$colors[$id]??['',''];
        return $r[0] ? 'color:'.$r[0].';' : '';
    }
    
    /**
     * Retorna a cor base pelo ID (cor de fundo)
     * @param $id - o mesmo de id de self::$colors
     * @return array [bg,text] 
     */
    public static function getColor($id){
        return (self::$colors[$id]??['',''])[0];
    }
    
    
}
   