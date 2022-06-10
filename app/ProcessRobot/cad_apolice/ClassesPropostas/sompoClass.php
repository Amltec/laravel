<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class sompoClass {

    public function process($text){

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'PI:',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'- Data:',['sanitize'=>false]]);
        $text_proposta = FormatUtility::sanitizeText($text_proposta);

        $proposta = trim(TextUtility::getSearchText($text_proposta,'PI:','value',['side'=>'right']));
        $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Nº PROPOSTA',['sanitize'=>false]]);
            $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>' Nº',['sanitize'=>false]]);
            $text_proposta = FormatUtility::sanitizeText($text_proposta);

            $proposta = trim(TextUtility::getSearchText($text_proposta,'PROPOSTA','value',['side'=>'right']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        return $proposta;
    }
}
