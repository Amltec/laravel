<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\allianzInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class allianzClass extends ProcessResidencialClass{
    use allianzInsurer;


    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $this->limitText($text);

        $r = $this->processTipo01();


    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $pg = $this->getPagina1();

        $data = $this->getDados3();
        if(!$data['success'])return $data;
        $data = $data['data'];

         //Dados do Local
         //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'local segurado:','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Produto']);
        $blocktext = str_replace('W-CASA', 'CASA', $blocktext);
       // dd($blocktext);
        $n = explode(',', $blocktext);
        $endereco = trim($n[0]);
        $endereco = str_replace('local segurado:', '', $endereco);

        if(strpos('S/N', $n[1])==true){
            $blocktext = str_replace('0 - S/N', ',S/N', $n[1]);
            $numero   = TextUtility::getPartOfStr( $blocktext, ['start'=>',','end'=>'-','sanitize'=>false]);
            $numero = trim(str_replace([',','-'], '', $numero));

        }else{
            $numero   = TextUtility::getPartOfStr( $blocktext, ['start'=>',','end'=>'-','sanitize'=>false]);
            $numero = trim(str_replace([',','-'], '', $numero));
            $numero = TextUtility::getSearchText($numero,'','number',['side'=>'right']);

        }

        $x = explode($numero, $blocktext);
        $text1 = str_replace('-', '', $x[0]);
        $blocktext = str_replace($x[0], $text1, $blocktext);
        $n = explode('-', $blocktext);

        //dd($n, $blocktext);
        if(count($n)>6){
            $complemento = trim($n[2]);
            $bairro = trim($n[3]);
            $n = str_replace('Produto', '', $n[6]);
            $n = explode('/', $n);
            $cidade = trim($n[0]);
            $estado = trim($n[1]);
        }else{
            $complemento = trim($n[1]);
            $bairro = trim($n[2]);
            $n = str_replace('Produto', '', $n[5]);
            $n = explode('/', $n);
            $cidade = trim($n[0]);
            $estado = trim($n[1]);
        }

        if(strlen($bairro)>20){
            $bairro = \Illuminate\Support\Str::ascii($bairro);
        }

        $cep = TextUtility::getSearchText($blocktext,'local segurado:','cep',['side'=>'right']);



        $data['residencial_endereco_1'] = strtoupper($endereco);
        $data['residencial_numero_1'] = $numero;
        $data['residencial_compl_1'] = $complemento;
        $data['residencial_bairro_1'] = strtoupper($bairro);
        $data['residencial_cidade_1'] = strtoupper($cidade);
        $data['residencial_uf_1'] = strtoupper($estado) ;
        $data['residencial_cep_1'] = $cep;
        //dd($data,$blocktext);
        //Premio
        $data = $this->getPremio1($data);
        //dd($data);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];

    }


    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
