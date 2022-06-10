<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\mapfreInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 29/07/2020
 */
class mapfreClass extends ProcessAutomovelClass{
    use mapfreInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
        $this->text = FormatUtility::sanitizeBreakText( $this->text);//retira somente as quebras de linha
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //*** dados do veículo ***
        $n=substr_count($this->text,'DADOS DO VEÍCULO');
        if($n>1)return ['success'=>false,'msg'=>'Apólice tipo Frota ('. $n .' veículos) - inválido','data'=>[],'ignore'=>true,'code'=>'read04'];




        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'do modelo:','end'=>'Chassi:']);

        //dd($veiculo_text);
        if(strpos($veiculo_text, 'SIM ')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado

        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO VEÍCULO','end'=>'OPCIONAIS']);

        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'Código na Tabela de Referência:','value',['side'=>'right']);
        if(empty($data['veiculo_cod_fipe_1'])){
            $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO VEÍCULO','end'=>'Cobertura Valor']);
            $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'Código na Tabela de Referência:','value',['side'=>'right']);
        }
        //dd($this->text);
        $data['veiculo_cod_fipe_1'] = str_replace('-', '', $data['veiculo_cod_fipe_1']);


        $n=$this->getData_combustivel($veiculo_text);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO VEÍCULO','end'=>'VALOR DA INDENIZAÇÃO']);
       // dd($veiculo_text);
        $data['veiculo_fab_1'] = $this->getData_fab($veiculo_text);//aparentemente não tem nesta função....
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);
        $veiculo_text = str_replace('Ano do','Ano de',$veiculo_text);
        $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Marca/Modelo:','end'=>'Ano de','remove'=>[$data['veiculo_fab_1'],'Marca/Modelo:','Ano de']  ]);

        $n=$this->getData_anoModFab($veiculo_text);
        $data['veiculo_ano_fab_1'] = $n[0];
        $data['veiculo_ano_modelo_1'] = $n[1];
        if($data['veiculo_ano_fab_1']=='' && $data['veiculo_ano_modelo_1']==''){
             $n = TextUtility::getPartOfStr($this->text, ['start'=>'Ano do modelo:','end'=>'Placa:']);
             $n=TextUtility::getSearchText($n,'Ano do modelo:','ano',['side'=>'right']);
             $data['veiculo_ano_fab_1'] = $n;
             $data['veiculo_ano_modelo_1'] = $n;
            // dd($n);
        }
        $data['veiculo_tipo_1'] = 'a';
        $text_chassi = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Chassi','end'=>'Categoria']);
        $data['veiculo_chassi_1']=$this->getData_chassi($text_chassi);

        $placa_text = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Placa:','end'=>'Chassi']);
        $data['veiculo_placa_1']=$this->getData_placa($placa_text);
        //dd($data['veiculo_placa_1'],$veiculo_text);
        if($data['veiculo_zero_1'] =='s'){
            $data['veiculo_placa_1']='nd zero';
        }


        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']=TextUtility::getSearchText($veiculo_text,'Capacidade/passageiros:','value');
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS GERAIS','end'=>'DADOS DA SEGURADORA']);
        $data['veiculo_ci_1']=TextUtility::getSearchText($apolice_text,'CI:','value');

        $block_text = FormatUtility::sanitizeAllText($this->text);
        $block_text = TextUtility::getPartOfStr($block_text, ['start'=>'bonus'],['sanitize'=>true]);
        //dd($block_text);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'bonus','number',['side'=>'right']);

        $tmp=TextUtility::getPartOfStr($this->text, ['start'=>'CEP do local onde o veículo pernoita:','end'=>'DADOS DO VEÍCULO']);
        $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($tmp,'','cep');
        $data['segurado_pernoite_cep_1']= str_replace('-', '', $data['segurado_pernoite_cep_1']);
        $data['prop_nome_1']='';
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==''?'SIM':'NÂO';

        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }



}
