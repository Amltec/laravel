<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class mapfreClass {

    public function process($text){

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta:',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'Tipo C',['sanitize'=>false]]);
        $text_proposta = FormatUtility::sanitizeText($text_proposta);
        $n = explode(' ', $text_proposta);
        //dd($n);
        for ($i = 0; $i < count($n); $i++) {
            if(is_numeric($n[$i]) && strlen($n[$i])>11){
                $proposta = $n[$i];
            }
        }

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Cotação nº:',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'formas de',['sanitize'=>false]]);
        $text_proposta = FormatUtility::sanitizeText($text_proposta);
        $cotacao = TextUtility::getSearchText($text_proposta,'cotacao ','number',['side'=>'right']);

        return $proposta .'|'. $cotacao;
    }
}
