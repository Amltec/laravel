<?php
namespace App\ProcessRobot\cad_apolice\residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\portoInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class portoClass extends ProcessResidencialClass{
    use portoInsurer;


     /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\tokio\pdf
    */

    private function processTipo01(){
        $pg = $this->getPagina1();

        $data = $this->getDados3();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //**** dados da residência
        $text_complet = str_replace('LOCAL SEGURAD0', 'LOCAL SEGURADO', $this->text);
        $blocktext = TextUtility::getPartOfStr($text_complet, ['start'=>'LOCAL SEGURADO','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Tipo de']);

        $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Bairro:','sanitize'=>false]);
        $endereco = trim(str_replace(['Endereço:','Bairro:','Casa'], '', $endereco));

        $numero   = TextUtility::getSearchText($blocktext,'Bairro:','number',['side'=>'left']);
        $endereco = trim(str_replace($numero, '', $endereco));

        $complemento = '';

        $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'Cidade:','sanitize'=>false]);
        $bairro = trim(str_replace(['Bairro:','Cidade:'], '', $bairro));

        $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
        $cidade = trim(str_replace(['Cidade:','Estado:'], '', $cidade));

        $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','sanitize'=>false]);
        $estado = TextUtility::getSearchText($blocktext,'Estado:','value',['side'=>'right']);
        $estado = str_replace('$','S',$estado);
        //dd($estado,$blocktext);
        $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'CEP:','end'=>'Tipo','sanitize'=>false]);
        $cep = TextUtility::getSearchText($blocktext,'Cep','cep',['side'=>'right']);
        //$cep = trim(str_replace(['CEP:','Tipo'], '', $cep));
        // dd($cep,$estado,$cidade,$bairro,$endereco,$numero,$blocktext);
        $data['residencial_endereco_1'] = strtoupper($endereco);
        $data['residencial_numero_1'] = $numero;
        $data['residencial_compl_1'] = $complemento;
        $data['residencial_bairro_1'] = strtoupper($bairro);
        $data['residencial_cidade_1'] = strtoupper($cidade);
        $data['residencial_uf_1'] = strtoupper($estado) ;
        $data['residencial_cep_1'] = $cep;

        $data = $this->getPremio2($data);
        //dd($data);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }
    /*
    private function processTipo02(){
        $pg = $this->getPagina1();

        $data = $this->getDados2($data);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }
    */

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }




}
