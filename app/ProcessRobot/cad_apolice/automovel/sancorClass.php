<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\Utilities\FilesUtility;

use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Insurers\sancorInsurer;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 27/07/2020
 */
class sancorClass extends ProcessAutomovelClass{
    use sancorInsurer;



    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){//$opt=[path,url]
        $this->process_opt = $opt;
        $this->text = $this->limitText($text);
        $this->text = FormatUtility::sanitizeBreakText( $this->text);//retira somente as quebras de linha


        $r = $this->processTipo01();
    	$r = $this->ValidateData($r);

        return $r;
    }


    private function processTipo01(){
        $data = $this->getDados1();
        if(!$data['success'])return $data;
        $data = $data['data'];


        //*** dados do veículo ***
        $text0 = TextUtility::getPartOfStr($this->text, ['start'=>'Marca','end'=>'COBERTURAS']);

        //$text0 = FormatUtility::extractAlphaNum($text0);
        //dd($text0);
        $this->getData_veiculo($text0,$data);

        $text_ci = FormatUtility::sanitizeAllText($this->text);
        $data['veiculo_ci_1'] = TextUtility::getSearchText($text_ci,'ci atual','number',['side'=>'right']);
        $data['veiculo_chassi_1']='';
        if(!$data['veiculo_chassi_1']){
            $data['veiculo_chassi_1'] = $this->getData_chassi($text0);
        }
        if(!$data['veiculo_chassi_1']){
            $data['veiculo_chassi_1'] = TextUtility::getSearchText($text0,'Cl atual','value',['side'=>'left']);
        }
        if(!$data['veiculo_chassi_1']){
            //dd($text0);
            $data['veiculo_chassi_1'] = TextUtility::getSearchText($text0,'Renavam','value',['side'=>'right']);
        }

        $n=$data['veiculo_chassi_1'];

        $modelo = str_replace('Ano modelo', '', $text0);
        $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($modelo, ['start'=>'Modelo','end'=>'Combustivel','sanitize'=>true,'remove'=>['modelo','Combustivel']]);

        if(strpos($data['veiculo_modelo_1'], 'passageiros')!==false){
            //dd(123);
            $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($modelo, ['start'=>'Modelo','end'=>'passageiros','sanitize'=>true,'remove'=>['modelo','passageiros']]);
        }

        $data['veiculo_modelo_1'] = str_replace('gm - chevrolet', '', $data['veiculo_modelo_1']);
        $data['veiculo_modelo_1'] = strtoupper(substr($data['veiculo_modelo_1'],0,50));
         $data['veiculo_tipo_1'] = 'a';
        //dd( $text0);
        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'N° Item','end'=>'Marca']);
        $text_vei = str_replace('-', '', $text_vei);
        $data['veiculo_cod_fipe_1'] = TextUtility::getSearchText($text_vei,'Código','value',['side'=>'right']);

        $data['veiculo_ano_fab_1'] = TextUtility::getSearchText($this->text,'Ano Modelo','ano',['side'=>'right']);
        $data['veiculo_ano_modelo_1'] = $data['veiculo_ano_fab_1'];


        $data['veiculo_n_portas_1']='';//não tem
        $data['veiculo_n_lotacao_1']='';//não tem

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Classe bônus','end'=>'Categoria']);
        //dd($text_vei);
        $text_vei = str_replace($data['veiculo_ci_1'], '', $text_vei);
        //dd($bonus);
        $data['veiculo_classe_1'] = TextUtility::getSearchText($text_vei,'Classe bônus','number',['side'=>'right']);


        $veiculo_text = TextUtility::getPartOfStr($this->text, ['start'=>'Zero km','end'=>'Classe','remove'=>'Data de saída']);
        $zero = TextUtility::getSearchText($text_vei,'km','value',['side'=>'right']);
        if(strpos($veiculo_text, 'Sim')!==false || strpos($veiculo_text, 'sim')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

        if($data['veiculo_zero_1']=='s'){
            $data['veiculo_data_saida_1']= TextUtility::getSearchText($veiculo_text,'Zero km','datebr',['side'=>'right']);
        }else{
            $data['veiculo_data_saida_1']= '';
        }

        $data['veiculo_nf_1']='';//não tem esse dado

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'isenção fiscal','end'=>'Reside(m)']);

        $r=TextUtility::execFncInStr($text_vei,9,function($v){//return array: 0 find, 1 left, 2 right
            if(TextUtility::isCep($v))return true;
        });

        if($r){
             $data['segurado_pernoite_cep_1']= $r[0];
        }

        $data['prop_nome_1']=$data['segurado_nome'];
        $data['segurado_proprietario_veiculo_1'] = $data['prop_nome_1']==$data['segurado_nome']?'SIM':'NÂO';

        $data = $this->getPremio($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

}
