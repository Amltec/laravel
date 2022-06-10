<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\mitsuiInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class mitsuiClass extends ProcessEmpresarialClass{
    use mitsuiInsurer;

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


        $data = $this->getDados_tipo2();
        if(!$data['success'])return $data;
        $data = $data['data'];

         //dd($data);
         //Dados do Local
         //**** dados da residência
         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Local do Risco','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Atividade']);
         $blocktext = TextUtility::getPartOfStr( $blocktext, ['start'=>'Risco','end'=>'Bairro','sanitize'=>false]);
         $blocktext = trim(str_replace(['Risco','Bairro'], '', $blocktext));

         //dd($blocktext);
         $n = explode(',', $blocktext);
         $endereco = trim($n[0]);

         if(is_numeric($n[1])==false){
             $numero = 'S/N';
         }else{
             $numero = $n[1];
         }
         $complemento = $n[2];
         if($complemento==''){
             $complemento = '';
         }

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Local do Risco','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de']);

        //dd($blocktext);

         $bairro = $this->getX1(['start'=>'UF','return_type'=>'next']);
         $cidade = $this->getX1(['start'=>'Tipo de','return_type'=>'prev2']);
         $estado = $this->getX1(['start'=>'Tipo de','return_type'=>'prev']);

         $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Risco','sanitize'=>false]);
         $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Local do']);
         $cep = TextUtility::getSearchText($blocktext,'Local do','cep',['side'=>'left']);


         $data['empresarial_endereco_1'] = strtoupper($endereco);
         $data['empresarial_numero_1'] = $numero;
         $data['empresarial_compl_1'] = $complemento;
         $data['empresarial_bairro_1'] = strtoupper($bairro);
         $data['empresarial_cidade_1'] = strtoupper($cidade);
         $data['empresarial_uf_1'] = strtoupper($estado) ;
         $data['empresarial_cep_1'] = $cep;
         // dd($data,$blocktext);

        //*** dados do prêmio

        $data = $this->getPremio_tipo2($data);
        //dd($data);


        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }
}
