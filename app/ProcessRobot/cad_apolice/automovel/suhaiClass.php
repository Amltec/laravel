<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\suhaiInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class suhaiClass extends ProcessAutomovelClass{
    use suhaiInsurer;

     protected $validate_required = ['fpgto_tipo_code'=>false];





    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);

        $r = $this->processTipo01();
        return $this->ValidateData($r);
    }


    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'1003106649733',
            'num_origem'=>true
        ];
    }

    private function processTipo01(){


        $data = $this->getDados_tipo1();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //*** dados do veículo ***
        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'VEÍCULO']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'Seguradora Anterior:']);
        //dd($veiculo_text);
        $veiculo_text = str_replace(['-','/'],[' - ',' / '],$veiculo_text);

        $vei_zero = TextUtility::getPartOfStr($veiculo_text, ['start'=>'km:','end'=>'Chassi','remove'=>['km:','Chassi']  ]);
        $vei_zero = trim($vei_zero);
        //dd($vei_zero);
        if($vei_zero=='Sim'){
            $data['veiculo_zero_1'] = 's';
        }else{
            $data['veiculo_zero_1'] = 'n';
        }

        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado

        $text_fipe = TextUtility::getPartOfStr($this->text, ['start'=>'Código FIPE:']);
        $text_fipe = TextUtility::getPartOfStr($text_fipe, ['end'=>'Região']);
        $text_fipe = str_replace('-','',$text_fipe);
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($text_fipe,'FIPE:','numberstr');

        $n=$this->getData_combustivel($veiculo_text);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        $n = str_replace(['GENERAL MOTORS','-'],['G M',' '],$veiculo_text);
        $fab = TextUtility::getPartOfStr($n, ['start'=>'Fabricação / Modelo:','end'=>'-' ]);
        $data['veiculo_fab_1'] = $this->getData_fab($fab);
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);

        $n = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Modelo:','end'=>'Código' ]);
        //dd($n,$veiculo_text);
        $n = str_replace('Código','',$n);
        $n = explode('/',$n);
        $data['veiculo_modelo_1'] = trim($n[1]);

        if(strpos($data['veiculo_modelo_1'],'Chassi')!=false){
            $n = explode('0 km',$data['veiculo_modelo_1']);
            $data['veiculo_modelo_1'] = trim($n[0]);
            //dd($data['veiculo_modelo_1']);
        }


        $n = TextUtility::getSearchText($veiculo_text,'Marca,','ano',['side'=>'right']);
        $data['veiculo_ano_fab_1']= TextUtility::getSearchText($veiculo_text,'Marca,','ano',['side'=>'right']);
        $data['veiculo_ano_modelo_1']=TextUtility::getSearchText($veiculo_text, $data['veiculo_modelo_1'],'ano',['side'=>'left']);
        //dd($data['veiculo_ano_fab_1'],$data['veiculo_ano_modelo_1'],$veiculo_text);
        $data['veiculo_chassi_1']=TextUtility::getSearchText($veiculo_text,'Chassi ',function($v){ if($this->getData_chassi($v))return $v; });
        if(empty($data['veiculo_chassi_1'])){
            $data['veiculo_chassi_1']=TextUtility::getSearchText($veiculo_text,'Chassi:','value',['side'=>'right']);
        }
        $data['veiculo_tipo_1'] = 'a';

        $placa = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Placa:','end'=>'Cor:','remove'=>['Cor:','Placa:']  ]);
        if(empty($placa)){
            $data['veiculo_placa_1']='nd zero';
        }else{
            $data['veiculo_placa_1']=str_replace('-','',$placa);
        }

        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']=TextUtility::getSearchText($veiculo_text,'Capacidade','number');
        $data['veiculo_ci_1']=TextUtility::getSearchText($veiculo_text,'Código C.I. Apól. Atual:','number',['side'=>'right']);;
        //dd($veiculo_text);
        $n = TextUtility::getPartOfStr($veiculo_text, ['start'=>'Bônus:','end'=>'Marca' ]);
        $data['veiculo_classe_1'] =TextUtility::getSearchText($n,'Bônus:','number',['side'=>'right']);

        $text_cep = TextUtility::getPartOfStr($veiculo_text, ['start'=>'CEP de Pernoite:','end'=>'Anterior' ]);
        //dd($text_cep);
        $text_cep = str_replace(' - ','-',$text_cep);
        $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($text_cep,'Pernoite:','cep_formated');

        $data['prop_nome_1']= $data['segurado_nome'];
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';
        //*** dados do prêmio
       // dd($data);
        $data = $this->getPremio_tipo1($data);
        //dd($data);


        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }
}
