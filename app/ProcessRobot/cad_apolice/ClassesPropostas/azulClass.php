<?php
namespace App\ProcessRobot\cad_apolice\ClassesPropostas;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;

class azulClass {

    public function process($text){

        $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'de Renovação','end'=>'Registro:',['sanitize'=>false]]);
        $proposta = trim(TextUtility::getSearchText($text_proposta,'de Renovação','value',['side'=>'right']));
        $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Proposta:','end'=>'VIGÊNCIA',['sanitize'=>false]]);
            $proposta = trim(TextUtility::getSearchText($text_proposta,'Proposta:','value',['side'=>'right']));
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        if(empty($proposta)){
            $text_proposta = TextUtility::getPartOfStr($text, ['start'=>'Nº da Proposta','end'=>'AZUL SEGUROS',['sanitize'=>false]]);
            $tex_del = trim(TextUtility::getSearchText($text_proposta,'AZUL','value',['side'=>'left']));
            $text_proposta = str_replace($tex_del,'',$text_proposta);
            $text_proposta = TextUtility::getPartOfStr($text_proposta, ['start'=>'de ','end'=>'AZUL',['sanitize'=>false]]);
            $proposta = str_replace(['de','AZUL'],'',$text_proposta);
            $proposta = preg_replace('/[^A-Za-z0-9\-]/', '', $proposta); // remove caracteres especiais
        }

        return $proposta;
    }
}
