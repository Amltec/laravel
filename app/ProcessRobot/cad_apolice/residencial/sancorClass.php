<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;

use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\sancorInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class sancorClass extends ProcessResidencialClass{
    use sancorInsurer;



    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->text = FormatUtility::sanitizeBreakText($text);//retira somente as quebras de linha
        $this->text1 = $text;


        $r = $this->processTipo01();
    	$r = $this->ValidateData($r);

        return $r;
    }


    private function processTipo01(){
        $data = $this->getDados2();
        if(!$data['success'])return $data;
        $data = $data['data'];



        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'LOCAIS DE RISCO','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Prêmio']);
        $cep = TextUtility::getSearchText($blocktext,'','cep',['side'=>'right']);

        $text_seg = TextUtility::getPartOfStr($this->text1, ['start'=>'Nome','sanitize'=>false]);
        $text_seg = TextUtility::getPartOfStr($text_seg, ['end'=>'DADOS DO BENE']);
        $cep_seg = TextUtility::getSearchText($text_seg,'','cep',['side'=>'right']);


        if($cep==$cep_seg){
             $estado = TextUtility::getSearchText($this->text,'DADOS DO BENE','value',['side'=>'left']);
             $cidade = $this->getX1(['start'=>$estado,'return_type'=>'prev2'],$text_seg);
             $bairro = $this->getX1(['start'=>'UF','return_type'=>'next2'],$text_seg);
             $endereco = $this->getX1(['start'=>$cep,'return_type'=>'next2'],$text_seg);
             $numero = $this->getX1(['start'=>$endereco,'return_type'=>'next2'],$text_seg);
             $complemento = $this->getX1(['start'=>'Bairro','return_type'=>'prev2'],$text_seg);
        }else{
            $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço','end'=>'Bairro','sanitize'=>false]);
            $endereco = trim(str_replace(['Endereço','Bairro'], '', $endereco));

            $numero   = TextUtility::getSearchText($blocktext,'Nº','value',['side'=>'right']);

            $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Complemento','end'=>'Tipo de Res','sanitize'=>false]);
            $complemento = trim(str_replace(['Complemento','Tipo de Res'], '', $complemento));

            $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro','end'=>'Cidade','sanitize'=>false]);
            $bairro = trim(str_replace(['Bairro','Cidade'], '', $bairro));

            $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade','end'=>'CEP','sanitize'=>false]);
            $cidade = trim(str_replace(['Cidade','CEP'], '', $cidade));

            $estado = TextUtility::getSearchText($blocktext,$cep,'value',['side'=>'right']);

            //dd($endereco,$bairro,$cidade,$cep,$estado,$numero,$complemento,$blocktext);

        }

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
