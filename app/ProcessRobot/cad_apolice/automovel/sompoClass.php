<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\sompoInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class sompoClass extends ProcessAutomovelClass{
    use sompoInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
        $this->text0 = FormatUtility::sanitizeAllText($text);
       // dd( $this->text);
        if(strpos($this->text,'DATA EMISSÃO')!==false || strpos($this->text,'DATA/HORA')!==false){
            $r = $this->processTipo02();
        }else{
            $r = $this->processTipo01();
        }
        return $this->ValidateData($r);
    }



    private function processTipo01(){
        $data = $this->getDados_tipo1();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //*** dados do veículo ***
        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO VEÍCULO']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'APÓLICE DE SEGURO']);
        $data['veiculo_zero_1'] = strpos($veiculo_text, 'A/C ')!==false ? 's' : 'n';
        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'CÓDIGO FIPE','numberstr');
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'MARCA','value',['side'=>'right']);

        $n=$this->getData_combustivel($veiculo_text);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        $n = str_replace(['GENERAL MOTORS','-'],['G M',' '],$veiculo_text);
        $data['veiculo_fab_1'] = $this->getData_fab($n);
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);
        $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($n, ['start'=>$data['veiculo_fab_1'],'end'=>'ANO/MODELO','remove'=>['ANO/MODELO',$data['veiculo_fab_1']]  ]);

        $data['veiculo_ano_modelo_1'] = TextUtility::getSearchText($veiculo_text,'ANO/MODELO','ano');
        $data['veiculo_ano_fab_1'] = $data['veiculo_ano_modelo_1'];//não tem na apólice da sompo, portanto usa do ano modelo
        $data['veiculo_chassi_1']=TextUtility::getSearchText($veiculo_text,'UTILIZAÇÃO DO VEÍCULO',function($v){ if($this->getData_chassi($v))return $v; });
        $data['veiculo_tipo_1'] = 'a';

        if(strpos($veiculo_text, 'A/C')!==false){
            $data['veiculo_placa_1']='nd zero';
        }else{
            $data['veiculo_placa_1']=TextUtility::getSearchText($veiculo_text,'chassi',function($v){ if($this->getData_placa($v))return $v; });
        }



        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']=TextUtility::getSearchText($veiculo_text,'ANO/MODELO','number02');

        $n=TextUtility::getPartOfStr($this->text, ['start'=>'24 HORAS DE','end'=>'CLASSE']);
        $n=$this->getData_ci($n);
        $n = str_replace('.', '', $n);
        $data['veiculo_ci_1']=$n;

        $n = FormatUtility::sanitizeAllText($this->text);
        $n = TextUtility::getPartOfStr($n, ['start'=>'CLASSE BONUS','end'=>'pagamento','side_len'=>[0,1]],['sanitize'=>true]);//lógica: espera uma string + ou - assim: "classe bonus categoria tarifaria ramo compreensiva forma de pagamento {num classe}"
        //dd($n);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($n,'','number',['side'=>'right']);

        $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($veiculo_text,'PERNOITE','cep_formated');
        $data['prop_nome_1']=$data['segurado_nome'];
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';

        //*** dados do prêmio

        $data = $this->getPremio_tipo1($data);

        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }




    private function processTipo02(){
        $data = $this->getDados_tipo2();
        if(!$data['success'])return $data;
        $data = $data['data'];

        //*** dados do veículo ***
        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO VEICULO']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'APÓLICE DE SEGURO']);

        $veiculo_text0 = FormatUtility::sanitizeBreakText($veiculo_text);
        $data['veiculo_zero_1'] = strpos($veiculo_text, 'A/C')!==false ? 's' : 'n';
        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($veiculo_text,'CÓDIGO FIPE','numberstr');

        $n=$this->getData_combustivel($veiculo_text);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        $marca_vei = TextUtility::getPartOfStr($veiculo_text0, ['start'=>'MARCA','end'=>'MODELO']);
        $marca_vei = str_replace(['GENERAL MOTORS','-'],['G M',' '],$marca_vei);
        $data['veiculo_fab_1'] = $this->getData_fab($marca_vei);

        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);
        $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($veiculo_text0, ['start'=>'MODELO','end'=>'ANO/MODELO','remove'=>['ANO/MODELO','MODELO']]);
        $data['veiculo_ano_modelo_1'] = TextUtility::getSearchText($veiculo_text,'ANO/MODELO','ano');
        $data['veiculo_ano_fab_1'] = $data['veiculo_ano_modelo_1'];//não tem na apólice da sompo, portanto usa do ano modelo
        $data['veiculo_chassi_1']=TextUtility::getSearchText($veiculo_text,'chassi',function($v){ if($this->getData_chassi($v))return $v; });

        $veiculo_placa = TextUtility::getPartOfStr($this->text, ['start'=>'MODELO']);
        $veiculo_placa = TextUtility::getPartOfStr($veiculo_placa, ['end'=>'CHASSI']);
        //dd($veiculo_placa);
        if(strpos($veiculo_placa, 'A/C')!==false){
            $data['veiculo_placa_1']='nd zero';
        }else{
            $data['veiculo_placa_1']=TextUtility::getSearchText($veiculo_placa,'placa',function($v){ if($this->getData_placa($v))return $v; });
        }
         $data['veiculo_tipo_1'] = 'a';
        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']=TextUtility::getSearchText($veiculo_text,'LOTAÇÃO','number02');

        //dd($apolice_text,$this->text);
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA APÓLICE','end'=>'DADOS DO SEGURADO','remove'=>['DADOS DA APÓLICE','DADOS DO SEGURADO']]);
        $n=TextUtility::getSearchText($apolice_text,'IDENTIFICAÇÃO- CI','numberstr');
        if(!$n)$n=TextUtility::getSearchText($apolice_text,'IDENTIFICAÇÃO − CI','numberstr');
        $n = str_replace('.', '', $n);
        $data['veiculo_ci_1']=$n;

        $data['veiculo_classe_1'] =TextUtility::getSearchText($apolice_text,'CLASSE BÔNUS','number',['max_words'=>2]);
        $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($veiculo_text,'PERNOITE','cep_formated');
        $data['prop_nome_1']=$data['segurado_nome'];
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';

        //*** dados do prêmio
        $data = $this->getPremio_tipo2($data);
        //dd(123);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
