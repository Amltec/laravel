<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\mitsuiInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class mitsuiClass extends ProcessAutomovelClass{
    use mitsuiInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->splitThisText($text);
        $this->text = $this->limitText($text);
        $this->text_ws02 = \App\Utilities\FilesUtility::readPDF($opt['path'],['engine'=>'ws02','pass'=>$this->process_opt['pass']])['text'];

        $r = $this->processTipo01();
        return $this->ValidateData($r);
    }



    private function processTipo01(){


        $data = $this->getDados_tipo1();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //*** dados do veículo ***
        $veiculo_text = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Proprietário']);
        $veiculo_text = TextUtility::getPartOfStr($veiculo_text, ['end'=>'COBERTURAS']);
        //dd($veiculo_text);
        $n=$this->getData_combustivel($veiculo_text);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        //$n = str_replace(['GENERAL MOTORS','-',' E '],['G M',' ',''],$veiculo_text);
        //$fab = TextUtility::getPartOfStr($n, ['start'=>'Veículo']);
        //$fab = TextUtility::getPartOfStr($fab, ['end'=>'Ano' ]);
        $fab = $this->getX1(['start'=>'Categoria','return_type'=>'next2'],$veiculo_text);
        $fab = substr(trim(str_replace(['Veículo a','Veículo E','Ano',' E '],'',$fab)),0,-1);
        //dd($fab,$veiculo_text);
        $data['veiculo_fab_1'] = $this->getData_fab($fab);
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);
        $data['veiculo_modelo_1'] = trim(str_replace($data['veiculo_fab_1'],'',$fab));

        $anos = TextUtility::getSearchText($veiculo_text,'','ano',['limit'=>false]);
        $data['veiculo_ano_fab_1']= $anos[0];
        $data['veiculo_ano_modelo_1']=$anos[1];
        $chassi = TextUtility::getPartOfStr($veiculo_text, ['start'=>$data['veiculo_modelo_1'],'end'=>'Bônus' ]);
        $data['veiculo_chassi_1']=TextUtility::getSearchText($chassi,'',function($v){ if($this->getData_chassi($v))return $v; });
        $data['veiculo_tipo_1'] = 'a';
        $placa = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Categoria','end'=>$data['veiculo_chassi_1'],'remove'=>['-']]);
        $placa = TextUtility::getSearchText($placa,$data['veiculo_chassi_1'],'value',['side'=>'left']);
        //dd($placa,$data['veiculo_chassi_1'],$this->text_ws02);
        if(empty($placa) || $placa==$data['veiculo_ano_modelo_1']=$anos[1]){
            $data['veiculo_zero_1'] = 's';
            $data['veiculo_placa_1'] ='nd zero';
        }else{
            $data['veiculo_zero_1'] = 'n';
            $data['veiculo_placa_1'] =$placa;
        }

        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado

        //dd($veiculo_text,$this->text_ws02);
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($this->text_ws02,'Código do Veículo','number',['side'=>'right']);

        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']=TextUtility::getSearchText($veiculo_text,$data['veiculo_chassi_1'],'number');

        $nx=TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Classe','end'=>'COBERTURAS']);
        $n=$this->getData_ci($nx);
        $n = str_replace('.', '', $n);
        $data['veiculo_ci_1']=$n;
        $data['veiculo_classe_1'] =TextUtility::getSearchText($nx,$data['veiculo_ci_1'],'number',['side'=>'left']);
        $data['segurado_pernoite_cep_1']=TextUtility::getSearchText($veiculo_text,'Pernoite','cep_formated');

        $data['segurado_nome'] = trim(str_replace('|','',$data['segurado_nome']));
        if(strpos($veiculo_text,$data['segurado_nome'])!==false){
            $data['prop_nome_1']=$data['segurado_nome'];
            $data['segurado_proprietario_veiculo_1'] = 'SIM';
        }else{
            $data['segurado_proprietario_veiculo_1'] = 'NAO';
        }

        //*** dados do prêmio
        $data = $this->getPremio_tipo1($data);
        //dd($data);


        //dd($data,$dados_pgto);
        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }
}
