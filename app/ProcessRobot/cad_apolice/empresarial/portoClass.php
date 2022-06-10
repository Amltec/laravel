<?php
namespace App\ProcessRobot\cad_apolice\empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\portoInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class portoClass extends ProcessEmpresarialClass{
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
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'COBERTURAS']);
        //dd($blocktext,$text_complet);

        $block_segurado = TextUtility::getPartOfStr($this->text, ['start'=>'Endereço:','sanitize'=>false]);
        $block_segurado = TextUtility::getPartOfStr($block_segurado, ['end'=>'Telefone:','remove'=>'Telefone:']);

        $endereco_seg = TextUtility::getPartOfStr($block_segurado, ['start'=>'Endereço:','end'=>'Bairro:','sanitize'=>false]);
        $endereco_seg = trim(str_replace(['Endereço:','Bairro:'], '', $endereco_seg));
        $n = explode(',', $endereco_seg);
        $endereco_seg =$n[0];
        $numero_seg = TextUtility::getSearchText($n[1],'','number',['side'=>'right']);  ;
        if($numero_seg==''){
            $numero_seg='s/n';
        }

        $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Endereço:','end'=>'Bairro:','sanitize'=>false]);
        $endereco = trim(str_replace(['Endereço:','Bairro:','Casa'], '', $endereco));
        //dd($endereco_seg,$endereco);
        if(strpos($endereco, $endereco_seg)!==false){//significa que o endereço é igual ao do segurado
            $endereco = $endereco_seg;
            $numero = $numero_seg;
            $complemento = TextUtility::getPartOfStr($block_segurado, ['start'=>'Endereço:','end'=>'Bairro:','sanitize'=>false]);
            if(strpos($complemento, '-')!==false){
                $complemento = trim(str_replace(['Endereço:','Bairro:'], '', $complemento));
                $complemento = explode('-', $complemento);
                $complemento = trim($complemento[1]);
            }else{
                $complemento = '';
            }
        }else{
            $numero   = TextUtility::getSearchText($blocktext,'Bairro:','number',['side'=>'left']);
            $endereco = trim(str_replace($numero, '', $endereco));
            $complemento = '';
        }


        $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'Bairro:','end'=>'Cidade:','sanitize'=>false]);
        $bairro = trim(str_replace(['Bairro:','Cidade:'], '', $bairro));

        $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'Cidade:','end'=>'Estado:','sanitize'=>false]);
        $cidade = trim(str_replace(['Cidade:','Estado:'], '', $cidade));

        $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'Estado:','end'=>'CEP:','sanitize'=>false]);
        $estado = trim(str_replace(['Estado:','CEP:'], '', $estado));

        $cep = TextUtility::getSearchText($blocktext,'CEP:','cep',['side'=>'right']);

         //dd($cep,$estado,$cidade,$bairro,$endereco,$numero,$blocktext);
        $data['empresarial_endereco_1'] = strtoupper($endereco);
        $data['empresarial_numero_1'] = $numero;
        $data['empresarial_compl_1'] = $complemento;
        $data['empresarial_bairro_1'] = strtoupper($bairro);
        $data['empresarial_cidade_1'] = strtoupper($cidade);
        $data['empresarial_uf_1'] = strtoupper($estado) ;
        $data['empresarial_cep_1'] = $cep;

        $data = $this->getPremio2($data);

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
