<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\zurichInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class zurichClass extends ProcessResidencialClass{
    use zurichInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->splitThisText($text);
        $this->text = $this->limitText($text);

        $r = $this->processTipo01();
        return $this->ValidateData($r);
    }



    private function processTipo01(){


        $data = $this->getDados2();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //dd($data);
         //Dados do Local
         //**** dados da residência
         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Local de Risco','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de']);
         $blocktext = str_replace(' :',':',$blocktext);

         //dd($blocktext,$this->text);
         $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Número:','sanitize'=>false]);
         $endereco = trim(str_replace(['Endereço:','Número:'], '', $endereco));
         //dd($endereco,$blocktext);

         $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','sanitize'=>false]);
         $bairro = TextUtility::getPartOfStr($bairro, ['end'=>'Cidade:','sanitize'=>false]);
         //dd($bairro,$blocktext);
         $bairro = trim(str_replace(['Bairro:','Cidade:'], '', $bairro));

         $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'UF:','sanitize'=>false]);
         $cidade = trim(str_replace(['Cidade:','UF:'], '', $cidade));

         if(empty($cidade)){
            $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'CEP:','sanitize'=>false]);
            $cidade = trim(str_replace(['Cidade:','CEP:'], '', $cidade));
         }

         $numero = TextUtility::getPartOfStr($blocktext, ['start'=>'Número:','end'=>'Complemento:','sanitize'=>false]);
         $numero = trim(str_replace(['Número:','Complemento:'], '', $numero));

         $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Complemento:','end'=>'Bairro:','sanitize'=>false]);
         $complemento = trim(str_replace(['Complemento:','Bairro:'], '', $complemento));

         if(empty($complemento)){
            $complemento = '';
         }

         $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'UF:','end'=>'CEP:','sanitize'=>false]);
         $estado = trim(str_replace(['UF:','CEP:'], '', $estado));

         $blocktext = TextUtility::getPartOfStr($blocktext, ['start'=>'CEP:','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo']);
         $blocktext = str_replace([' - '], '-', $blocktext);
         $cep = TextUtility::getSearchText($blocktext,'CEP:','cep',['side'=>'right']);


         $data['residencial_endereco_1'] = strtoupper($endereco);
         $data['residencial_numero_1'] = $numero;
         $data['residencial_compl_1'] = $complemento;
         $data['residencial_bairro_1'] = strtoupper($bairro);
         $data['residencial_cidade_1'] = strtoupper($cidade);
         $data['residencial_uf_1'] = strtoupper($estado) ;
         $data['residencial_cep_1'] = $cep;
        // dd($data,$blocktext);

        //*** dados do prêmio

        $data = $this->getPremio2($data);
        //dd($data);


        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }
}
