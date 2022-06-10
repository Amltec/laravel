<?php
namespace App\ProcessRobot\cad_apolice\Empresarial;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessEmpresarialClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\tokioInsurer;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class tokioClass extends ProcessEmpresarialClass{
    use tokioInsurer;


    //Campos obrigatórios adicionais para personalização na classe filha (obs: neste caso segue as regras existentes no validate da classe filha, ex ProcessAutomovelClass.php)
    protected $validate_required = ['empresarial_bairro'=>false];//sintaxe field=>boolean


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



    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    */
    private function processTipo01(){
        $pg = $this->getPagina1();

        //*** dados do seguro
        $data = $this->getDados2();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //**** dados dos locais
        $total_local = substr_count($this->text, 'Descrição do Item -');
        for($i=1;$i<=$total_local;$i++){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Descrição do Item - '.$i,'sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Atividade: ']);

            $endereco = TextUtility::getPartOfStr($blocktext, ['start'=>'Local de Risco:','end'=>',','sanitize'=>false]);
            $endereco = trim(str_replace(['Local de Risco:',','], '', $endereco));

            $text_end = TextUtility::getPartOfStr($blocktext, ['start'=>'Local de Risco:','sanitize'=>false]);
            $text_end = trim(str_replace(['Local de Risco:','Atividade:'], '', $text_end));
            $text_end = FormatUtility::sanitizeBreakText($text_end);


            $numero   = TextUtility::getPartOfStr($text_end, ['start'=>',','end'=>'-','sanitize'=>false]);
            $numero = trim(str_replace([',','-'], '', $numero));

            if(substr_count($text_end, '-')==6){
                $n = explode('-', $text_end);
                $complemento = trim($n[1]);
                $bairro = trim($n[2]);
            }elseif(substr_count($text_end, '-')==5){
                $n = explode('-', $text_end);
                $complemento = '';
                $bairro = trim($n[1]);
            }elseif(substr_count($text_end, '-')==7){
                $n = explode('-', $text_end);
                $complemento = trim($n[1]);
                $bairro = trim($n[2]);
            }else{
                $complemento ='';
                $bairro = '';
            }

            $text_cep = str_replace('- ','-',$text_end);
            $cep = TextUtility::getSearchText($text_cep,'','cep',['side'=>'right']);
            $cep = trim($cep,'-');

            $estado = substr($text_end, -2);


            $cidade = TextUtility::getPartOfStr($text_cep, ['start'=>$cep,'sanitize'=>false]);
            //dd($text_cep,$cep,$cidade);
            $cidade = explode('-', $cidade);
            $cidade = trim($cidade[2]);

            $data['empresarial_endereco_1'] = strtoupper($endereco);
            $data['empresarial_numero_1'] = $numero;
            $data['empresarial_compl_1'] = $complemento;
            $data['empresarial_bairro_1'] = strtoupper($bairro);
            $data['empresarial_cidade_1'] = strtoupper($cidade);
            $data['empresarial_uf_1'] = strtoupper($estado) ;
            $data['empresarial_cep_1'] = $cep;
        }
       // dd($data);
        //*** dados do prêmio
        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
