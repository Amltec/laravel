<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;

use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\alfaInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 29/07/2020
 */
class alfaClass extends ProcessAutomovelClass{
    use alfaInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $text = FormatUtility::sanitizeBreakText($text);//retira somente as quebras de linha

        //corrige os caracteres que estão espaçando o texto, mas não são espaços
        $text=str_replace([chr(194),chr(160)],' ', $text);

        $this->text = $text;
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $data=[];

        $data = $this->getDados_tipo1();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //*** dados do veículo ***

        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'Informações do Veículo Segurado','end'=>'Coberturas e Serviços']);

         $data['veiculo_tipo_1']='a';

        $text_vei = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Zero km:']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Chassi:']);


        //dd($text_vei);
        if(strpos($text_vei, 'Sim')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado



        $ano_fab_mod = TextUtility::getSearchText($veiculo_text,'Ano:','ano',['side'=>'right']);

        if($ano_fab_mod){//em alguns casos o ano está vindo com espaço entre eles
            $veiculo_text = str_replace($ano_fab_mod.' ', $ano_fab_mod, $veiculo_text);
        }

        //dd($ano_fab_mod,$veiculo_text);
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'Cód. Fipe:','value');
        $n=$this->getData_combustivel($veiculo_text);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';


        $n=TextUtility::getPartOfStr($veiculo_text, ['start'=>'Veículo:','end'=>'Ano','remove'=>['Veículo:','Ano']]);
        $n=$this->getMarcaModelo($n);

        $data['veiculo_fab_1'] = $n['marca'];//aparentemente não tem nesta função....
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($n['marca']);
        $data['veiculo_modelo_1'] = substr($n['modelo'],5,50);

        if(empty( $data['veiculo_modelo_1'])){
            $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Veículo:','end'=>'Chassi:','remove'=>['Veículo:','Chassi:']]);

            if(strpos($data['veiculo_modelo_1'],'REBOQUE')!==FALSE){
                $data['veiculo_fab_1'] = 'REBOQUE';//aparentemente não tem nesta função....
                $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);
                //dd($data['veiculo_modelo_1'],$veiculo_text);
            }


        }
        //dd($data['veiculo_modelo_1']);
        if(strpos($data['veiculo_modelo_1'],'CHASSI')!==FALSE){
            $n= explode('CHASSI',$data['veiculo_modelo_1']);
            $ini_cont = strpos($n[0],'-');
            $n = substr($n[0],$ini_cont);
            $n = trim(ltrim($n,'-'));
            $data['veiculo_modelo_1'] =$n;
        }
        $n=$this->getData_anoModFab($veiculo_text);
        $data['veiculo_ano_fab_1'] = $n[0];
        $data['veiculo_ano_modelo_1'] = $n[1];


        if(empty($data['veiculo_ano_fab_1']) && empty($data['veiculo_ano_modelo_1'])){
             $n1=TextUtility::getPartOfStr($veiculo_text, ['start'=>' Ano:','end'=>'km:','remove'=>['Veículo:','km:']]);
             $n=TextUtility::getSearchText($this->text,'Ano:','number',['side'=>'right']);
             $data['veiculo_ano_fab_1'] = $n;
             $data['veiculo_ano_modelo_1'] = $n;

        }
        //dd( $this->validate_chassi('9BD57831FKY319268'), $this->validate_chassi('9BFZB55S2J8689878'), $this->validate_chassi('9BGEB69A0LG155696'), $this->validate_chassi('adqweqwqweqwewsss'), $this->validate_chassi(FormatUtility::extractAlphaNum('wBGwB69AwLG1w5696')) );
        $data['veiculo_chassi_1']=$this->getData_chassi($veiculo_text);
        $text_placa = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Placa:']);
        $text_placa = TextUtility::getPartOfStr($text_placa, ['end'=>' Tipo de Cob']);
        //dd($text_placa);
        $data['veiculo_placa_1']=$this->getData_placa($text_placa);

        if(empty($data['veiculo_placa_1'])){
            $n = TextUtility::getSearchText($text_placa,'Placa:','value',['side'=>'right']);

            if($n=='A/C'){
                $data['veiculo_placa_1']='nd zero';
            }else{
                $data['veiculo_placa_1']=$n;
            }

        }

        if(empty($data['veiculo_chassi_1'])){
            $data['veiculo_chassi_1'] = TextUtility::getSearchText($veiculo_text,'chassi:','value',['side'=>'right']);
        }
        //dd($veiculo_text);

        if($data['veiculo_zero_1'] =='s'){
            $data['veiculo_placa_1']='nd zero';
        }

        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']=TextUtility::getSearchText($veiculo_text,'Capacidade/passageiros:','value');

        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'Código CI:']);
        $data['veiculo_ci_1']=TextUtility::getSearchText($apolice_text,'CI:','value');

        $block_text = FormatUtility::sanitizeAllText($this->text);
        $block_text = TextUtility::getPartOfStr($block_text, ['start'=>'bonus:'],['sanitize'=>true]);
        //dd($block_text);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'bonus:','number',['side'=>'right']);

        $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($veiculo_text,'CEP Pernoite:','cep');

        $data['prop_nome_1']=$data['segurado_nome'];
        if(strpos($data['prop_nome_1'],'Endereço:')!==FALSE){
            $data['prop_nome_1'] = TextUtility::getPartOfStr($data['prop_nome_1'], ['end'=>'Endere']);
            $data['prop_nome_1'] = str_replace('Endere','',$data['prop_nome_1']);
        }
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';

        //*** dados do pagamento ***
        $data = $this->getPremio_tipo1($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
