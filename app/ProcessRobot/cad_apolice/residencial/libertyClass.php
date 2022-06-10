<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\libertyInsurer;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class libertyClass extends ProcessResidencialClass{
     use libertyInsurer;

    protected $pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
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

        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'- DADOS DO LOCAL SEGURADO','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Renova Apólice']);
        //dd($this->text_xpdfr);
        $remove = TextUtility::getPartOfStr($blocktext, ['start'=>'Liberty','sanitize'=>false]);
        $remove = TextUtility::getPartOfStr($remove, ['end'=>'segurado','sanitize'=>false]);
        $blocktext = str_replace($remove, '',$blocktext);

        $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço','end'=>',','sanitize'=>false]);
        $endereco = trim(str_replace(['Endereço',','], '', $endereco));

        $blocktext2 = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO(A) SEGURADO(A)','sanitize'=>false]);
        $blocktext2 = TextUtility::getPartOfStr($blocktext2, ['end'=>'Telefone']);

        $endereco2 = TextUtility::getPartOfStr($blocktext2, ['start'=>'Endereço','end'=>',','sanitize'=>false]);
        $endereco2 = trim(str_replace(['Endereço',','], '', $endereco2));

        //verifica se o endereço do segurado é o mesmo do local segurado, caso seja igual pega os dados do endereço do segurado pois em alguns casos a quebra de página estava atrapalhando
        if($endereco==$endereco2){//utiliza os dados do endereço do segurado
            $endereco = $endereco2;

            $blocktext=$blocktext2;
            $numero   = $this->getX1([$blocktext,'start'=>'Endereço','return_type'=>'next']);
            $numero   = TextUtility::getPartOfStr($numero, ['start'=>',','sanitize'=>false]);
            $numero   = trim(str_replace([',','-',''], '', $numero));
            $numero   = FormatUtility::sanitizeAllText($numero);

            $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço','end'=>'Bairro','sanitize'=>false]);
            $complemento = explode('-',$complemento);
            if(count($complemento)<=1){
               $complemento='' ;
            }else{
                $complemento = trim(str_replace(['Bairro'], '', $complemento[1]));
            }
            $complemento = FormatUtility::sanitizeBreakText($complemento);

            $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro','end'=>'Cidade','sanitize'=>false]);
            $bairro = trim(str_replace(['Bairro','Cidade'], '', $bairro));

            $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade','end'=>'UF','sanitize'=>false]);
            $cidade = trim(str_replace(['Cidade','UF'], '', $cidade));

            $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'UF','end'=>'CEP','sanitize'=>false]);
            $estado = trim(str_replace(['UF','CEP'], '', $estado));

            $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'CEP','end'=>'Telefone','sanitize'=>false]);
            $cep = trim(str_replace(['CEP','Telefone'], '', $cep));

        }else{//utiliza os dados do endereço do local segurado
            //dd($blocktext);
            $numero   = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço','end'=>'-','sanitize'=>false]);
            $numero   = TextUtility::getPartOfStr($numero, ['start'=>',','end'=>'-','sanitize'=>false]);
            $numero   = trim(str_replace([',','-',''], '', $numero));
            $numero   = FormatUtility::sanitizeAllText($numero);

            $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço','end'=>'Bairro','sanitize'=>false]);
            $complemento = explode('-',$complemento);
            if(count($complemento)<=1){
               $complemento='' ;
            }else{
                $complemento = trim(str_replace(['Bairro'], '', $complemento[1]));
            }
            $complemento = FormatUtility::sanitizeBreakText($complemento);

            $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro','end'=>'Cidade','sanitize'=>false]);
            $bairro = trim(str_replace(['Bairro','Cidade'], '', $bairro));

            $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade','end'=>'UF','sanitize'=>false]);
            $cidade = trim(str_replace(['Cidade','UF'], '', $cidade));

            $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'UF','end'=>'CEP','sanitize'=>false]);
            $estado = trim(str_replace(['UF','CEP'], '', $estado));

            $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'CEP','end'=>'Renova','sanitize'=>false]);
            $cep = trim(str_replace(['CEP','Renova'], '', $cep));
        }

        $numero = explode(' ',$numero);

        $data['residencial_endereco_1'] = strtoupper($endereco);
        $data['residencial_numero_1'] = $numero[0];
        $data['residencial_compl_1'] = $complemento;
        $data['residencial_bairro_1'] = strtoupper($bairro);
        $data['residencial_cidade_1'] = strtoupper($cidade);
        $data['residencial_uf_1'] = strtoupper($estado) ;
        $data['residencial_cep_1'] = $cep;

        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }



    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
