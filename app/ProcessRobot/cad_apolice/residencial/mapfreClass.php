<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\mapfreInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 29/07/2020
 */
class mapfreClass extends ProcessResidencialClass{
    use mapfreInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
        $this->text = FormatUtility::sanitizeBreakText($this->text);//retira somente as quebras de linha
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $data = $this->getDados();

        if(!$data['success'])return $data;
        $data = $data['data'];

        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'QUESTIONÁRIO DE AVALIAÇÃO DE RISCO','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tempo de']);
        $not_complemento = '';
        if($blocktext==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'QUESTIONÁRIO DE AVALIAÇÃO DE RISCO','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de Seguro:']);
            $not_complemento = 'ok';
        }
        //dd($blocktext,$this->text);

        $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço do Risco:','end'=>'Nº:','sanitize'=>false]);
        $endereco = trim(str_replace(['Endereço do Risco:','Nº:'], '', $endereco));

        if($not_complemento=='ok'){
             $numero  = TextUtility::getPartOfStr($blocktext, ['start'=>'Nº:','sanitize'=>false]);
             $numero  = TextUtility::getPartOfStr($numero, ['end'=>'Bairro:','sanitize'=>false]);
             $numero = trim(str_replace(['Nº:','Bairro:'], '', $numero));
             //dd($numero);
        }else{
             $numero   = TextUtility::getPartOfStr($blocktext, ['start'=>'Nº:','end'=>'Complemento:','sanitize'=>false]);
             $numero = trim(str_replace(['Nº:','Complemento:'], '', $numero));
        }

        if($numero==''){
            $numero   = TextUtility::getPartOfStr($blocktext, ['start'=>'Nº:','end'=>' Bairro:','sanitize'=>false]);
            $numero = trim(str_replace(['Nº:',' Bairro:'], '', $numero));
        }

        $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Complemento:','end'=>'Bairro:','sanitize'=>false]);
        $complemento = trim(str_replace(['Complemento:','Bairro:'], '', $complemento));

        $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'CEP:','sanitize'=>false]);
        $bairro = trim(str_replace(['Bairro:','CEP:'], '', $bairro));

        $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
        $cidade = trim(str_replace(['Cidade:','Estado:'], '', $cidade));


        if($not_complemento=='ok'){
            $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Tipo de','sanitize'=>false]);
            $estado = trim(str_replace(['Estado:','Tipo de'], '', $estado));
        }else{
            $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'Tempo de','sanitize'=>false]);
            $estado = trim(str_replace(['Estado:','Tempo de'], '', $estado));
        }


        $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'CEP:','end'=>'Cidade:','sanitize'=>false]);
        $cep = trim(str_replace(['CEP:','Cidade:'], '', $cep));

        $data['residencial_endereco_1'] = strtoupper($endereco);
        $data['residencial_numero_1'] = $numero;
        $data['residencial_compl_1'] = $complemento;
        $data['residencial_bairro_1'] = strtoupper($bairro);
        $data['residencial_cidade_1'] = strtoupper($cidade);
        $data['residencial_uf_1'] = strtoupper($estado) ;
        $data['residencial_cep_1'] = $cep;


        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }



}
