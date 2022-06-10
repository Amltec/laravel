<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class portoClass {

    public function process($text){

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'N. PROPOSTA COMPANHIA',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'TIPO DE SEGURO',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['start'=>'COMPANHIA',['sanitize'=>false]]);
        $text_proposta = TextUtility::getPartOfStr($text_proposta, ['end'=>'TIPO',['sanitize'=>false]]);
        $proposta = str_replace(['COMPANHIA','TIPO'],'',$text_proposta);
        $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta:','end'=>'VIGÊNCIA',['sanitize'=>false]]);
            $proposta = trim(TextUtility::getSearchText($text_proposta,'Proposta:','value',['side'=>'right']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Nº da Proposta','end'=>'Código',['sanitize'=>false]]);
            $text_proposta = str_replace(' - ','',$text_proposta);

            $proposta = trim(TextUtility::getSearchText($text_proposta,'Código','value',['side'=>'left']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }


        return $proposta;
    }
}
