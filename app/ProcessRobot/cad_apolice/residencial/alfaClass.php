<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\alfaInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 29/07/2020
 */
class alfaClass extends ProcessResidencialClass{
    use alfaInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $text = FormatUtility::sanitizeBreakText($text);//retira somente as quebras de linha

        //corrige os caracteres que estão espaçando o texto, mas não são espaços
        $text=str_replace([chr(194),chr(160)],' ', $text);

        $this->text = $text;
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $data=[];


        $data = $this->getDados_tipo1();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //dd($data);
         //Dados do Local
         //**** dados da residência
         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Local:','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Segmento:']);


         //dd($blocktext,$this->text);

         $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Cidade:']);
         $endereco = trim(str_replace(['Endereço:','Cidade:'], '', $endereco));


         $numero   = TextUtility::getSearchText($blocktext,'Número:','value',['side'=>'right']);

         $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Complemento:','end'=>'UF:']);
         $complemento = trim(str_replace(['Complemento:','UF:'], '', $complemento));

         if($complemento==''){
             $complemento = '';
         }

         $bairro = '';

         $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Ocupação:']);
         $cidade = trim(str_replace(['Cidade:','Ocupação:'], '', $cidade));

         $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'UF:','end'=>'CEP:']);
         $estado = trim(str_replace(['CEP:','UF:'], '', $estado));
         //dd($endereco,$numero,$complemento,$cidade,$estado,$blocktext);
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

        $data = $this->getPremio_tipo1($data);
        //dd($data);


        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];



    }


}
