<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\zurichInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class zurichClass extends ProcessAutomovelClass{
    use zurichInsurer;

    //protected $pdf_engine = 'ait_ocr01'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
    	$this->text = FormatUtility::sanitizeBreakText($this->text);//retira somente as quebras de linha



        if($this->pdf_engine!='ws02')$this->text_ws02 = \App\Utilities\FilesUtility::readPDF($opt['path'],['engine'=>'ws02','pass'=>$this->process_opt['pass']])['text'];

        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\zurich\pdf
    */
    private function processTipo01(){
          $data=[];

        $data = $this->getDados1();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //*** dados do veículo ***
        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'Código Marca:']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'Adicionais']);
        if(strpos($this->text,'Modalidade:')!==false){
            $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'Fator de Ajuste']);
        }
        //dd($veiculo_text);
        $veiculo_text = FormatUtility::sanitizeBreakText($veiculo_text);

        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'Cócigo:','value',['side'=>'right']);
        if(empty($data['veiculo_cod_fipe_1'])){
            $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($this->text,'Código:','value',['side'=>'right']);
        }

        $n=$this->getData_combustivel($veiculo_text);

        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';
        $data['veiculo_fab_1'] = '';// não tem o fabricante do veículo
        $data['veiculo_fab_code_1'] = '';// não tem o fabricante do veículo

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Chassi:','end'=>'Proprietário']);
        $tmp = str_ireplace([':','chassi','proprietário','não','remarcado'],'',$n);
        $n = $this->getData_chassi($tmp);
        if(!$n){//as vezes vem espaços entre os caracteres do chassi
            $n = str_replace(' ','',$tmp);
            $n=$this->getData_chassi($n);
        }
        $data['veiculo_chassi_1']=$n;

        if(!$data['veiculo_chassi_1']){
            $data['veiculo_chassi_1'] = TextUtility::getSearchText($veiculo_text,'Chassi:','value',['side'=>'right']);
        }


        if(stripos($this->text_ws02, $data['veiculo_chassi_1'])===false){//não tem
//            /dd($this->text_ws02);
            $blocktext1 = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Renavam']);
            $blocktext1 = TextUtility::getPartOfStr($blocktext1, ['end'=>'Valor do veículo:']);


            $chassi = explode(chr(10), $blocktext1);

                $chassi_ok ='';
                for($i=0;$i< count($chassi); $i++) {
                        $chassi_ok = $this->getData_chassi($chassi[$i]);
                        if($chassi_ok!='')break;
                }

                $data['veiculo_chassi_1'] = $chassi_ok;
         }



        $n=TextUtility::getSearchText($this->text,'Placa: ','value',['side'=>'right']);
        $n= FormatUtility::extractAlphaNum($n);
        $data['veiculo_placa_1']=$n;
        if(strlen($data['veiculo_placa_1'])<7){
           $n = TextUtility::getSearchText($this->text,$data['veiculo_placa_1'],'value',['side'=>'right']);
           $data['veiculo_placa_1']= $data['veiculo_placa_1'].$n;
           $data['veiculo_placa_1']= FormatUtility::extractAlphaNum($data['veiculo_placa_1']);
        }
        /*if($data['veiculo_placa']){
           if(strpos($this->text_ws02,$data['veiculo_placa'])===false){//o valor capturado não existe no texto extraído em java, portanto está errado
               $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>$data['veiculo_chassi_1'],'end'=>$data['veiculo_chassi_1'],'remove'=>$data['veiculo_chassi_1'],'side_len'=>[0,60]]);
               $n = $this->getData_placa($n);
               dd($n,$this->text_ws02);
           }
        }*/
        if($data['veiculo_placa_1']='' || ($data['veiculo_placa_1']!='' && stripos($this->text_ws02, $data['veiculo_placa_1'])===false)){
            $blocktext1 = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Renavam']);
            $blocktext1 = TextUtility::getPartOfStr($blocktext1, ['end'=>'Valor do veículo:']);
            $placa = substr($blocktext1, 100,-60);
            $data['veiculo_placa_1'] = $this->getData_placa($placa);
        }
        if(!$data['veiculo_placa_1']){//tenta usando outro método
            $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>$data['veiculo_chassi_1'],'end'=>$data['veiculo_chassi_1'],'remove'=>$data['veiculo_chassi_1'],'side_len'=>[0,60]]);
            $n = $this->getData_placa($n);
            $data['veiculo_placa_1'] = $this->getData_placa($n);
        }
        $data['veiculo_zero_1'] = !$data['veiculo_placa_1'] ? 's' : 'n';


        if(empty($data['veiculo_placa_1'])){
            $data['veiculo_placa_1']='nd zero';
        }


        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'Categoria:']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'Uso']);

        if(strpos($veiculo_text,'CAMINHOES')!==false){
            //dd($veiculo_text);
            $data['veiculo_tipo_1'] = 'c';
        }else{
            $data['veiculo_tipo_1'] = 'a';
        }



        $data['veiculo_data_saida_1']= '';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado

        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'Veículo']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'Ano ']);
        if(strpos($veiculo_text,'Placa')!==false){
            $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'Placa']);
        }
        $veiculo_text = str_replace(['Veículo','Ano',':','Placa'], [''], $veiculo_text);
        //dd(trim($veiculo_text));
        $veiculo_text = trim($veiculo_text);
        $data['veiculo_modelo_1'] = $veiculo_text;
        $n = TextUtility::getPartOfStr($this->text, ['start'=>'Ano / Modelo:','end'=>'Placa:']);
        $n = TextUtility::getSearchText($n,'Ano / Modelo:','number',['side'=>'right']);
       // dd($n);

       // $data['veiculo_modelo'] = substr(TextUtility::getPartOfStr($veiculo_text, ['start'=>'Veículo: ','end'=>'Ano','remove'=>'Ano']),10);
        $data['veiculo_ano_modelo_1'] = $n;

        $data['veiculo_ano_fab_1'] = $data['veiculo_ano_modelo_1'];//não tem na apólice da sompo, portanto usa do ano modelo
        $veiculo_text = str_replace($data['veiculo_chassi_1'],'',$veiculo_text);//retira este campo da var

        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']='';//não tem

        $n = TextUtility::getPartOfStr($this->text, ['start'=>'do Bônus: ','end'=>'Mod.','remove'=>'Mod.']);
        if(empty($n)){
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'do Bônus: ','end'=>'As marca','remove'=>'Mod.']);
        }
        //dd($n);
        $data['veiculo_ci_1']=$this->getData_ci($n);

        $block_text = FormatUtility::sanitizeAllText($this->text);
        $block_text = TextUtility::getPartOfStr($block_text, ['start'=>'classe de','end'=>'Cl do']);
         //dd($block_text);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'classe de bonus:','number',['side'=>'right']);
        //dd($data['veiculo_classe_1']);
        $data['segurado_pernoite_cep_1']= TextUtility::getSearchText($this->text,'pernoite do veículo:','cep');
        if(empty($data['segurado_pernoite_cep_1'])){
            $data['segurado_pernoite_cep_1']= TextUtility::getSearchText($this->text,'do veiculo:','cep');
        }

        if(empty($data['segurado_pernoite_cep_1'])){
            $data['segurado_pernoite_cep_1']= TextUtility::getSearchText($this->text,'CEP do','cep');
        }

        if(empty($data['segurado_pernoite_cep_1'])){
            $data['segurado_pernoite_cep_1']= $this->getX1(['start'=>$data['veiculo_placa_1'],'return_type'=>'next3'],$this->text_ws02);//Nome;
        }
        //dd($data['segurado_pernoite_cep_1']);
        $data['prop_nome_1']=substr(TextUtility::getPartOfStr($veiculo_text, ['start'=>'Proprietário: ','end'=>'Renavam:','remove'=>'Renavam:']),15);

        if(empty($data['prop_nome_1'])){
            $data['prop_nome_1']= trim(TextUtility::getPartOfStr($this->text, ['start'=>'Proprietário:','end'=>'Renavam','remove'=>['Proprietário:','Renavam']]));
        }

        //dd( $n,$data['prop_nome_1']);
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';

        $data = $this->getPremio1($data);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
