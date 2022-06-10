<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\allianzInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class allianzClass extends ProcessEmpresarialClass{
    use allianzInsurer;


    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $text;

        $r = $this->processTipo01();


    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $pg = $this->getPagina1();

        $data = $this->getDados2();
        if(!$data['success'])return $data;
        $data = $data['data'];

         //Dados do Local
         //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Endereço do Risco:','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Apólice']);

        //dd($blocktext);
        $n = explode(',', $blocktext);
        $endereco = trim($n[0]);
        $endereco = trim(str_replace('Endereço do Risco:', '', $endereco));

        $n = TextUtility::getPartOfStr( $blocktext, ['start'=>'Endereço do Risco:','end'=>'Complemento:','sanitize'=>false]);
        $n = TextUtility::getSearchText($n,'Complemento:','number',['side'=>'left']);


        if(is_numeric($n)==false){
            $numero = 'S/N';
        }else{
            $numero = $n;
        }


        $complemento = TextUtility::getPartOfStr( $blocktext, ['start'=>'Complemento:','end'=>'Bairro:','sanitize'=>false]);
        $complemento = trim(str_replace(['Complemento:','Bairro:'], '', $complemento));
        if($complemento=='NAO INFORMADO'){
            $complemento = '';
        }


        $bairro = TextUtility::getPartOfStr( $blocktext, ['start'=>'Bairro:','end'=>'Cidade:','sanitize'=>false]);
        $bairro = trim(str_replace(['Bairro:','Cidade:'], '', $bairro));


        $cidade = TextUtility::getPartOfStr( $blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
        $cidade = trim(str_replace(['Estado:','Cidade:'], '', $cidade));

        if($cidade==''){
             $cidade = TextUtility::getPartOfStr( $blocktext, ['start'=>'Cidade:','end'=>'Apólice','sanitize'=>false]);
             $cidade = trim(str_replace(['Cidade:','Apólice'], '', $cidade));

             $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Endereço do Risco:','sanitize'=>false]);
             $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de cons']);


        }

        $estado = TextUtility::getPartOfStr( $blocktext, ['start'=>'Estado:','end'=>'CEP:','sanitize'=>false]);
        $estado = trim(str_replace(['Estado:','CEP:'], '', $estado));

        $cep = TextUtility::getSearchText($blocktext,'CEP:','cep',['side'=>'right']);


        $data['empresarial_endereco_1'] = strtoupper($endereco);
        $data['empresarial_numero_1'] = $numero;
        $data['empresarial_compl_1'] = $complemento;
        $data['empresarial_bairro_1'] = strtoupper($bairro);
        $data['empresarial_cidade_1'] = strtoupper($cidade);
        $data['empresarial_uf_1'] = strtoupper($estado) ;
        $data['empresarial_cep_1'] = $cep;
        // dd($data,$blocktext);
        //Premio
        $data = $this->getPremio3($data);
        //dd($data);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];

    }


    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
