<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class hdiClass {

    public function process($text){

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta','end'=>'Vigência',['sanitize'=>false]]);
        //dd($text_proposta);
        $proposta = trim(TextUtility::getSearchText($text_proposta,'Proposta','value',['side'=>'right']));
        $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta:','end'=>'HDI EM',['sanitize'=>false]]);
            $proposta = trim(TextUtility::getSearchText($text_proposta,'Proposta:','value',['side'=>'right']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        return $proposta;
    }
}
