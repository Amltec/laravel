<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\bradescoInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class bradescoClass extends ProcessEmpresarialClass{
    use bradescoInsurer;


    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
        //dd('***', strlen($this->text), $this->text);


       /* $tipo = $this->detectTipo();

        if($tipo=='tipo2'){
            $r = $this->processTipo02();

    	}else{//$tipo1
            $r = $this->processTipo01();
    	}
        */
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }

    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\bradesco\pdf
    	Arquivos:
    		Bradesco Apólice - boleto.pdf
    		Bradesco Apólice - cartao.pdf
    		Bradesco Apólice - debito.pdf
    */
    private function processTipo01(){
        //*** dados do seguro
        $data = $this->getDados_tipo3();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Apólice Anterior','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Rubrica']);

        $endereco = $this->getX1(['start'=>'Anterior','return_type'=>'next'],$blocktext);
        $numero   = $this->getX1(['start'=>'Número','return_type'=>'next'],$blocktext);

        $n = TextUtility::getSearchText($blocktext,'Complemento','value',['side'=>'right']);

        if($n=='UF'){
             $complemento = '';
             $bairro = $this->getX1(['start'=>'Bairro','return_type'=>'next'],$blocktext);
             $cidade = $this->getX1(['start'=>'Cidade','return_type'=>'prev'],$blocktext);
             $estado = $this->getX1(['start'=>'UF ','return_type'=>'next2'],$blocktext);
        }elseif($n=='CASA'){
             $complemento = 'CASA';
             $bairro = $this->getX1(['start'=>'Bairro','return_type'=>'next'],$blocktext);
             $estado = $this->getX1(['start'=>'UF','return_type'=>'next2'],$blocktext);
             $cidade = $this->getX1(['start'=>$estado,'return_type'=>'next2'],$blocktext);

        }elseif($n=='AP' || 'APARTAMENTO'){
             $complemento = $this->getX1(['start'=>'Complemento','return_type'=>'next'],$blocktext);;
             $bairro = $this->getX1(['start'=>'Bairro','return_type'=>'next'],$blocktext);
             $estado = $this->getX1(['start'=>'UF','return_type'=>'next2'],$blocktext);
             $cidade = $this->getX1(['start'=>$estado,'return_type'=>'next2'],$blocktext);
             //dd($estado,$endereco,$numero,$complemento,$bairro,$cidade,$blocktext);
        }else{
             $complemento = $this->getX1(['start'=>'Complemento','return_type'=>'next'],$blocktext);
             $bairro = $this->getX1(['start'=>'Bairro','return_type'=>'next'],$blocktext);
             $cidade = $this->getX1(['start'=>'Cidade','return_type'=>'prev'],$blocktext);
             $estado = $this->getX1(['start'=>$cidade,'return_type'=>'prev2'],$blocktext);
        }

        $cep = '';

        $data['empresarial_endereco_1'] = $endereco;
        $data['empresarial_numero_1'] = $numero;
        $data['empresarial_compl_1'] = $complemento;
        $data['empresarial_bairro_1'] = $bairro;
        $data['empresarial_cidade_1'] = $cidade;
        $data['empresarial_uf_1'] = $estado ;
        $data['empresarial_cep_1'] = $cep;
       //dd($estado,$data,$blocktext);
        //Dados do Prêmio

        $data = $this->getPremio_tipo3($data);

        //dd($data);
       //dd($data, $this->text);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

}
