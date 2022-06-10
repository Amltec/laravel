<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class zurichClass {

    public function process($text){
        $text = str_replace(['Filial:','Filial :'],'',$text);
        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Filial',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'CPF',['sanitize'=>false]]);
        $text_proposta = FormatUtility::sanitizeText($text_proposta);
        //dd($text_proposta);
        $proposta = trim(TextUtility::getSearchText($text_proposta,'Filial','number',['side'=>'right']));
        $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais

        return $proposta;
    }
}
