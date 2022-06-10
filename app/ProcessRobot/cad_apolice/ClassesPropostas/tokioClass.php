<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class tokioClass {

    public function process($text){

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta nº.',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'Versão',['sanitize'=>false]]);
        $text_proposta = FormatUtility::sanitizeText($text_proposta);

        $proposta = trim(TextUtility::getSearchText($text_proposta,'.','value',['side'=>'right']));
        $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta/',['sanitize'=>false]]);
            $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'Item',['sanitize'=>false]]);
            $text_proposta = FormatUtility::sanitizeText($text_proposta);

            $proposta = trim(TextUtility::getSearchText($text_proposta,'item','number',['side'=>'left']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Negócio:',['sanitize'=>false]]);
            $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'Proponente',['sanitize'=>false]]);
            $text_proposta = FormatUtility::sanitizeText($text_proposta);

            $proposta = trim(TextUtility::getSearchText($text_proposta,':','number',['side'=>'right']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        return $proposta;
    }
}
