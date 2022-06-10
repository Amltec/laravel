<?php
namespace App\ProcessRobot\cad_apolice\Residencial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessResidencialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\tokioInsurer;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class tokioClass extends ProcessResidencialClass{
    use tokioInsurer;


    //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
    protected $validate_required = ['residencial_bairro'=>false];//sintaxe field=>boolean


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



    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    */
    private function processTipo01(){
        $pg = $this->getPagina1();

        //*** dados do seguro
        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //dd($data);
        //**** dados da residência
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'dados do item','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'coberturas']);


        $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'local do risco:','end'=>'numero logradouro:','sanitize'=>true]);
        $endereco = trim(str_replace(['local do risco:','numero logradouro:'], '', $endereco));

        $numero   = TextUtility::getPartOfStr($blocktext, ['start'=>'numero logradouro:','end'=>'complemento:','sanitize'=>true]);
        $numero = trim(str_replace(['complemento:','numero logradouro:'], '', $numero));

        $complemento = TextUtility::getPartOfStr($blocktext, ['start'=>'complemento:','end'=>'bairro:','sanitize'=>true]);
        $complemento = trim(str_replace(['complemento:','bairro:'], '', $complemento));

        $bairro = TextUtility::getPartOfStr($blocktext, ['start'=>'bairro:','end'=>'cidade:','sanitize'=>true]);
        $bairro = trim(str_replace(['cidade:','bairro:'], '', $bairro));

        $cidade = TextUtility::getPartOfStr($blocktext, ['start'=>'cidade:','end'=>'cep:','sanitize'=>true]);
        $cidade = trim(str_replace(['cidade:','cep:'], '', $cidade));

        $estado = TextUtility::getPartOfStr($blocktext, ['start'=>'uf:','end'=>'coberturas','sanitize'=>true]);
        $estado = trim(str_replace(['uf:','coberturas'], '', $estado));

        $cep = TextUtility::getPartOfStr($blocktext, ['start'=>'cep:','end'=>'uf:','sanitize'=>true]);
        $cep = trim(str_replace(['uf:','cep:'], '', $cep));

        $data['residencial_endereco_1'] = strtoupper($endereco);
        $data['residencial_numero_1'] = $numero;
        $data['residencial_compl_1'] = $complemento;
        $data['residencial_bairro_1'] = strtoupper($bairro);
        $data['residencial_cidade_1'] = strtoupper($cidade);
        $data['residencial_uf_1'] = strtoupper($estado) ;
        $data['residencial_cep_1'] = $cep;

        //*** dados do prêmio
        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
