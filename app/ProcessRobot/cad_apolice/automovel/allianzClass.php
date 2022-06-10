<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\allianzInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class allianzClass extends ProcessAutomovelClass{
    use allianzInsurer;



    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){

        $this->process_opt = $opt;
    	$this->text = $text;
        $n = $this->getX1(['start'=>'Caminhão']);
        $n1 = $this->getX1(['start'=>'Produto: Moto']);

        if(strpos($n,'Caminhão')!==false){
            $r = $this->processTipo02();
        }elseif(strpos($n1,'Moto')!==false){
            $r = $this->processTipo02();
        }elseif(strpos($this->text,'Texto Complementar')!==false){
            $r = $this->processTipo03();
        }else{
            $r = $this->processTipo01();
        }

    	return $this->ValidateData($r);
    }


    private function processTipo01(){
        $pg = $this->getPagina1();

        $data = $this->getDados1();
        if(!$data['success'])return $data;
        $data = $data['data'];


         //Dados do Proprietário
         $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         $n = $this->getX1(['start'=>'PERNOITE:','cb'=>function($v){ return explode(" ",$v)[1];  }]);//Cep de Pernoite
         $n = $n=str_replace(['-'], [''], $n);
         $data['segurado_pernoite_cep_1'] = trim($n);


         //veiculo zero

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'ZERO KM']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'MODELO']);


        if(strpos($text_vei, 'ZERO KM: Sim')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

        $data['veiculo_data_saida_1']=TextUtility::getSearchText($text_vei,'DATA DE SAÍDA','datebr',['side'=>'right']);;
        $data['veiculo_nf_1']='';//não tem esse dado


         $marcaMod = $this->getMarcaModelo(  trim($this->getX1(['start'=>'VEÍCULO:','remove'=>'VEÍCULO:']))  );//Fabricante Veiculo
         $data['veiculo_fab_1'] = $marcaMod['marca'];
         $data['veiculo_modelo_1'] = substr($marcaMod['modelo'], 0,50);

         $data['veiculo_tipo_1'] = 'a';
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante


         $n = trim($this->getX1(['start'=>'ANO/MODELO:','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));
         $data['veiculo_ano_fab_1'] =self::clearText($n);//Ano Fab
         $data['veiculo_ano_modelo_1'] = $data['veiculo_ano_fab_1'];   //Ano Model

         $data['veiculo_chassi_1'] = trim($this->getX1(['start'=>'Chassi:','cb'=>function($v){ return explode(" ",$v)[1];  }]));//Chassi
         $n = trim($this->getX1(['start'=>'FIPE','cb'=>function($v){ return explode(" ",$v)[1];  }]));
         $n = str_replace(['-'], [''], $n);
         $data['veiculo_cod_fipe_1'] = $n; // Codigo Fipe
         $data['veiculo_placa_1'] = trim($this->getX1(['start'=>'Placa:','cb'=>function($v){ return explode(" ",$v)[1];  }])); // Placa

         $data['veiculo_placa_1'] = str_replace('-', '', $data['veiculo_placa_1']);

         if($data['veiculo_placa_1']=='PRODUTO:'){
            $data['veiculo_placa_1']='nd zero';
         }

         if($data['veiculo_zero_1']=='s'){
            $data['veiculo_placa_1']='nd zero';
         }

         $data['veiculo_combustivel_1'] ='';//vazio porque na apólice não tem essa informação

         $data['veiculo_combustivel_code_1'] = '';//vazio porque não tem essa informação na apólice

         $data['veiculo_n_lotacao_1'] = '';//vazio porque não tem essa informação na apólice - lotação

         $text_ci= TextUtility::getPartOfStr($this->text, ['start'=>'CEP PERNOITE'] );
         $text_ci= TextUtility::getPartOfStr($text_ci, ['end'=>'COBERTURAS']);

         $n= TextUtility::getPartOfStr($text_ci, ['start'=>'CI:'] );
         $n= explode(' ', $n);
         $n= substr($n[1], 0,14);
         //dd(substr($n[1], 0,14));
         $data['veiculo_ci_1'] = trim($n);         //C.I

         $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'BÔNUS:']);
            //dd($block_text);
         $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'BÔNUS:','number',['side'=>'right']);

         $data['veiculo_n_portas_1'] = ''; //na apólice da Allianz não tem esta informação

        $data = $this->getPremio1($data);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];

    }


    private function processTipo02(){// para caminhão
        $pg = $this->getPagina1();

        $data = $this->getDados2();
        if(!$data['success'])return $data;
        $data = $data['data'];

         //veiculo zero

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'ZERO KM']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'MODELO']);

        if(stripos($text_vei, 'ZERO KM: Sim')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
            $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'ZERO KM']);
            $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'CEP pernoite']);
            $text_vei = str_replace(['(',')'],'',$text_vei);
            if(stripos($text_vei, 'ZERO KM: Sim')!==false){
                $data['veiculo_zero_1'] ='s';
            }else{
                $data['veiculo_zero_1'] ='n';
            }
         }

        $data['veiculo_data_saida_1']=TextUtility::getSearchText($text_vei,'DATA DE SAÍDA','datebr',['side'=>'right']);;
        $data['veiculo_nf_1']='';//não tem esse dado




         //Dados do Proprietário
          $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         $n = $this->getX1(['start'=>'PERNOITE:','cb'=>function($v){ return explode(" ",$v)[1];  }]);//Cep de Pernoite
         $n = $n=str_replace(['-'], [''], $n);
         $data['segurado_pernoite_cep_1'] = trim($n);


         //$marcaMod =
         $data['veiculo_fab_1'] = $this->getX1(['start'=>'Marca:','cb'=>function($v){ return explode(" ",$v)[1];  }]);//Fabricante Veiculo
         $n = $this->getX1(['start'=>'Modelo:','end'=>'Ano Modelo:','remove'=>'Modelo:','split'=>false]);//Modelo Veiculo
         $n = str_replace('Ano', '', $n);
         $data['veiculo_modelo_1'] =substr(trim($n), 0,50);
         $data['veiculo_modelo_1'] = trim(explode("Mod.:", $data['veiculo_modelo_1'])[0]);
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante


         $n = trim($this->getX1(['start'=>'Ano Modelo:','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));
         //dd($n);
         if(empty($n)){
             $n = trim($this->getX1(['start'=>'Ano Mod.:','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));
         }

         $data['veiculo_ano_fab_1'] =self::clearText($n);//Ano Fab
         $data['veiculo_ano_modelo_1'] = $data['veiculo_ano_fab_1'];   //Ano Model

         $text_vei2 = TextUtility::getPartOfStr($this->text, ['start'=>'Ramo:']);
         $text_vei2 = TextUtility::getPartOfStr($text_vei2, ['end'=>'Itens:']);
         $n = TextUtility::getSearchText($text_vei2,'Produto:','value',['side'=>'right']);
         //dd( $n);
         if($n=='Moto'){
             $data['veiculo_tipo_1'] = 'm';
         }elseif($n=='Caminhão'){
             $data['veiculo_tipo_1'] = 'c';
         }else{
             $data['veiculo_tipo_1'] = 'a';
         }

         $data['veiculo_chassi_1'] = trim($this->getX1(['start'=>'Chassi:','cb'=>function($v){ return explode(" ",$v)[1];  }]));//Chassi
       //  $n = trim($this->getX1(['start'=>'FIPE','cb'=>function($v){ return explode(" ",$v)[1];  }]));
      //   $n = str_replace(['-'], [''], $n);
         $data['veiculo_cod_fipe_1'] = ''; // Codigo Fipe
         $n = trim($this->getX1(['start'=>'Placa:','cb'=>function($v){ return explode(" ",$v)[1]??'';  }])); // Placa
         $n = str_replace('Chassi:', '', $n);

         $x = $this->getData_placa($n);

         if($data['veiculo_zero_1']=='s' && $x==''){
            $data['veiculo_placa_1']='nd zero';
         }else{
             $data['veiculo_placa_1'] = $n;
         }

         $data['veiculo_combustivel_1'] ='';//vazio porque na apólice não tem essa informação

         $data['veiculo_combustivel_code_1'] = '';//vazio porque não tem essa informação na apólice

         $data['veiculo_n_lotacao_1'] = '';//vazio porque não tem essa informação na apólice - lotação

         $data['veiculo_ci_1'] = trim($this->getX1(['start'=>'CI:','cb'=>function($v){ return explode(":",$v)[1];  }]));         //C.I

         $text0 = FormatUtility::sanitizeAllText($this->text);
         $block_text = TextUtility::getPartOfStr($text0, ['start'=>'bonus:']);
         //dd($block_text);

         if(!$block_text){
             $block_text = TextUtility::getPartOfStr($text0, ['start'=>'Tipo de seguro:']);
             $block_text = TextUtility::getPartOfStr($block_text, ['end'=>'CEP pernoite']);
             //dd(123,$block_text);
             if(strpos($block_text, 'seguro novo')!==false){
                 $data['veiculo_classe_1'] = '0';
             }


         }else{
             $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'bonus:','number',['side'=>'right']);
         }


         //dd();

         $data['veiculo_n_portas_1'] = ''; //na apólice da Allianz não tem esta informação

         $data = $this->getPremio2($data);

        return ['success'=>true,'data'=>$data];
    }

    private function processTipo03(){// layout 2022 com dados do veículo no bloco "Texto Complementar"


        $data = $this->getDados2();
        if(!$data['success'])return $data;
        $data = $data['data'];

         //veiculo zero

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'ZERO KM']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'CONDIÇÕES']);
        $n = TextUtility::getSearchText($text_vei,'ZERO KM:','value',['side'=>'right']);

        if($n=='Sim'){
            $data['veiculo_zero_1'] ='s';
        }else{
            $data['veiculo_zero_1'] ='n';
        }

        $data['veiculo_data_saida_1']=TextUtility::getSearchText($text_vei,'DATA DE SAÍDA','datebr',['side'=>'right']);
        $data['veiculo_nf_1']='';//não tem esse dado


         //Dados do Proprietário
          $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         $data['segurado_pernoite_cep_1'] = '';

         //$marcaMod =
         $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Texto Complementar']);
         $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'TIPO DE SEGURO:']);
         $data['veiculo_fab_1'] = '';//Fabricante Veiculo
         $n = TextUtility::getPartOfStr($text_vei, ['start'=>'ANO/MODELO:']);
         $n = TextUtility::getPartOfStr($n, ['end'=>'CLASSE BÔNUS:']);
         $n = trim(str_replace('CLASSE BÔNUS:','',$n));
         $n = explode('/',$n);

         $data['veiculo_modelo_1'] = $n[2]??'';
         $data['veiculo_fab_code_1'] = ''; //código do fabricante


         $data['veiculo_ano_fab_1'] =TextUtility::getSearchText($n[1],'MODELO:','number',['side'=>'right']);//Ano Fab
         $data['veiculo_ano_modelo_1'] = $data['veiculo_ano_fab_1'];   //Ano Model

         $data['veiculo_tipo_1'] = 'a';

         $n = TextUtility::getPartOfStr($text_vei, ['start'=>'CHASSI:']);
         $n = TextUtility::getPartOfStr($n, ['end'=>'ZERO KM:']);
         $data['veiculo_chassi_1'] = TextUtility::getSearchText($n,'CHASSI:','value',['side'=>'right']);//Chassi
         $data['veiculo_cod_fipe_1'] = ''; // Codigo Fipe

         $n = TextUtility::getPartOfStr($text_vei, ['start'=>'PLACA:']);
         $n = TextUtility::getPartOfStr($n, ['end'=>'CHASSI:']);
         $n = TextUtility::getSearchText($n,'PLACA:','value',['side'=>'right']);

         if($data['veiculo_zero_1']=='s'){
            $data['veiculo_placa_1']='nd zero';
         }else{
             $data['veiculo_placa_1'] = $n;
         }

         $data['veiculo_combustivel_1'] ='';//vazio porque na apólice não tem essa informação

         $data['veiculo_combustivel_code_1'] = '';//vazio porque não tem essa informação na apólice

         $data['veiculo_n_lotacao_1'] = '';//vazio porque não tem essa informação na apólice - lotação

         $data['veiculo_ci_1'] = '';         //C.I

         $n = TextUtility::getPartOfStr($text_vei, ['start'=>'CLASSE BÔNUS:']);
         $n = TextUtility::getPartOfStr($n, ['end'=>'CATEGORIA']);
         $n = TextUtility::getSearchText($n,'CLASSE BÔNUS:','number',['side'=>'right']);
         $data['veiculo_classe_1'] =trim($n);

         $data['veiculo_n_portas_1'] = ''; //na apólice da Allianz não tem esta informação

         $data = $this->getPremio4($data);
         //dd($data);
        return ['success'=>true,'data'=>$data];
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
