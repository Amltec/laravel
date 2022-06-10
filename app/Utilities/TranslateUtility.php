<?php
namespace App\Utilities;

/*
 * Classe com funções de tradução de alguns padrões de textos
 */
Class TranslateUtility{

    //atualiza os nomes dos meses e semanas
    public static function date($v){
        $r=str_ireplace(
            ['January','February','March','April','May','June','July','August','September','October','November','December'],
            ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
        $v);
        
        $r=str_ireplace(
            ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Set','Oct','Nov','Dec'],
            ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
        $r);
 
        //weekend
        $r=str_ireplace(
            ['dom','seg','ter','qua','qui','sex','sáb','sab'],
            ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','sab'],
        $r);
        
        return $r;
    }
}