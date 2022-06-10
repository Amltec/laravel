<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\alfaInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 29/07/2020
 */
class alfaClass extends ProcessEmpresarialClass{
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
         //**** dados do local de risco
         $n=$this->getTextWS02();
         $n=$n['text'];
         $blocktext = TextUtility::getPartOfStr($n, ['start'=>'Local:','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Ocupa']);

         $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Complemento:']);
         $endereco = trim(str_replace(['Endereço:','Complemento:'], '', $endereco));


         $numero   = TextUtility::getSearchText($blocktext,'mero:','value',['side'=>'right']);

         $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Complemento:','end'=>'Cidade:']);
         $complemento = trim(str_replace(['Cidade:','Complemento:'], '', $complemento));

         if($complemento==''){
             $complemento = '';
         }

         $bairro = '';

         $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'UF:']);
         $cidade = trim(str_replace(['Cidade:','UF:'], '', $cidade));

         $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'UF:','end'=>'CEP:']);
         $estado = trim(str_replace(['CEP:','UF:'], '', $estado));
         $estado = preg_replace('/[^a-z0-9]/i', '', $estado);//retira caracteres especias

         //dd($endereco,$numero,$complemento,$cidade,$estado,$blocktext);
         $cep = TextUtility::getSearchText($blocktext,'CEP:','value',['side'=>'right']);
         $cep = preg_replace('/[^a-z0-9]/i', '', $cep);//retira caracteres especias
         //dd($cep,$blocktext);

         $data['empresarial_endereco_1'] = strtoupper($endereco);
         $data['empresarial_numero_1'] = $numero;
         $data['empresarial_compl_1'] = $complemento;
         $data['empresarial_bairro_1'] = strtoupper($bairro);
         $data['empresarial_cidade_1'] = strtoupper($cidade);
         $data['empresarial_uf_1'] = strtoupper($estado) ;
         $data['empresarial_cep_1'] = $cep;
         // dd($data,$blocktext);

        //*** dados do prêmio

        $data = $this->getPremio_tipo1($data);
        //dd($data);


        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];



    }

}
