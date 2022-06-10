<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\tokioInsurer;



/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class tokioClass extends ProcessAutomovelClass{
    use tokioInsurer;




    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $this->limitText($text);

        $n = $this->getX1(['start'=>'versão de 30 de maio de 2021']);
        //dd($n);
        if(strpos($n,'30 de maio de 2021')!==false){
            $r = $this->processTipo02();
        }else{
            $r = $this->processTipo01();
        }

    	return $this->ValidateData($r);
    }




    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\tokio\pdf
    */
    private function processTipo01(){
        //dd(123);
        $pg = $this->getPagina1();

        $data = $this->getDados();

        if(!$data['success'])return $data;
        $data = $data['data'];

        $n=$this->getX1(['start'=>'AUTO']);//verifica se é frota

        if(strpos($n,'FROTA')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        if(empty($data['segurado_nome'] )){
            if($data['tipo_pessoa']=='FISICA'){
                $n1 = $this->getX1(['start'=>'Nome:','remove'=>'Nome:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
                //dd($n);
                $data['segurado_nome'] = trim($n1);//Nome Segurado
            }else{
                $n1 = $this->getX1(['start'=>'Razão Social:','remove'=>'Razão Social:','cb'=>function($v){  $n=explode(':',$v); return $n[0];  }]);//Nome Segurado
                //dd($n);
                $data['segurado_nome'] = trim($n1);//Nome Segurado
            }
        }


         //Dados do Proprietário
          $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         if($data['apolice_prod_ref']=='Tokio Marine Auto' || $data['apolice_prod_ref']=='TokioMarineAuto'){
                 $n = $this->getX1(['start'=>'pernoite','cb'=>function($v){ return explode(":",$v)[1];  }]);//Cep de Pernoite
                 $n = $n=str_replace(['-'], [''], $n);

         }elseif($data['apolice_prod_ref']=='Tokio Marine Caminhão'){
                if(strpos($this->text,'CEP Pernoite:')!==false){
                     $n = $this->getX1(['start'=>'CEP Pernoite:','cb'=>function($v){ return explode(" ",$v)[2];  }]);//Cep de Pernoite
                     $n = $n=str_replace(['-'], [''], $n);
                }else{
                    $n='';
                }

         }

         $data['segurado_pernoite_cep_1'] = trim($n);
         //dd(strlen($data['segurado_pernoite_cep_1']));
         if($data['segurado_pernoite_cep_1']=='FISICA' || $data['segurado_pernoite_cep_1']=='JURIDICA' || strlen($data['segurado_pernoite_cep_1'])>8){
             $tmp = TextUtility::getPartOfStr($this->text, ['start'=>'Fipe:'],['sanitize'=>true]);
             //dd($tmp);
             $data['segurado_pernoite_cep_1'] = TextUtility::getSearchText($tmp,'pernoite','cep',['side'=>'right']);

             if(empty($tmp)){
                $tmp = TextUtility::getPartOfStr($this->text, ['start'=>'CEP de pernoite'],['sanitize'=>true]);
                //dd($tmp);
                $data['segurado_pernoite_cep_1'] = TextUtility::getSearchText($tmp,'veículo:','cep',['side'=>'right']);
             }


         }

         $data['veiculo_fab_1'] = trim($this->getX1(['start'=>'Fabricante','cb'=>function($v){ return explode(":",$v)[1];  }]));// Fabricante do Veiculo
         $data['veiculo_fab_1'] = trim(str_replace('Ano Modelo','',$data['veiculo_fab_1']));
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
         //dd($data['veiculo_fab_1']);

        if(strpos($this->text, 'Bem Segurado')!==false){
            $data['veiculo_modelo_1'] = trim($this->getX1(['start'=>'Bem Segurado','return_type'=>'next','cb'=>function($v){ return explode(":",$v)[1];  }]));// Modelo do Veiculo
            $data['veiculo_modelo_1'] = trim(str_replace(['Fabricante:','Veículo:','fabricante'], '', $data['veiculo_modelo_1']));
        }else{
            $data['veiculo_modelo_1'] = trim(TextUtility::getPartOfStr($this->text, ['start'=>'Veículo:','end'=>'Fabricante:','remove'=>['Fabricante:','Veículo:']  ]));// Modelo do Veiculo
            $data['veiculo_modelo_1'] = trim(str_replace(['Fabricante:','Veículo:','fabricante'], '', $data['veiculo_modelo_1']));
        }


          $data['veiculo_tipo_1'] = 'a';
         $text = TextUtility::getPartOfStr($this->text, ['start'=>'Fabricante:','end'=>'Tipo de']);

         if(strpos($text,'Ano de Fabri')!==false){
            $n = trim($this->getX1(['start'=>'Ano de Fabri','cb'=>function($v){ return explode(":",$v)[1];  }]));
         }else{
             $n = trim($this->getX1(['start'=>'Ano Modelo','cb'=>function($v){ return explode(":",$v)[1];  }]));
             $n = explode(' ', $n);
             $n = $n[0];
         }
        // dd($n);
         $data['veiculo_ano_fab_1'] =self::clearText($n);//Ano Fab
         $n = trim($this->getX1(['start'=>'Ano Modelo','cb'=>function($v){ return explode(":",$v)[1];  }]));
         $data['veiculo_ano_modelo_1'] = self::clearText($n);   //Ano Model

         $text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Bem','end'=>'Coberturas']); //bloco de texto com dados do veículo
         if(empty($text)){
            $text = TextUtility::getPartOfStr($this->text, ['start'=>'Veículo:','end'=>'O principal ']); //bloco de texto com dados do veículo
         }

         //$data['veiculo_chassi'] = trim($this->getX1(['start'=>'Chassi:','remove'=>'Chassi:']));//Chassi
         $n=$this->getData_chassi($text);
         if(!$n){
             $text0 = TextUtility::getPartOfStr($this->text, ['start'=>'chassi:','sanitize'=>false]);
             $n=$this->getData_chassi($text0);
         }
         $data['veiculo_chassi_1'] = $n;


         $text_vei_zero = TextUtility::getSearchText($text,'0km:','value',['side'=>'right']);
         //dd($text_vei_zero);

         if(strpos($text_vei_zero, 'Sim')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

         $data['veiculo_data_saida_1'] =TextUtility::getSearchText($text,'Data de saída','datebr',['side'=>'right']);
         $data['veiculo_nf_1']='';//não tem esse dado


         $n = trim($this->getX1(['start'=>'Fipe','cb'=>function($v){ return explode(" ",$v)[1]??'';  }]));
         if(!$n){
             $n=TextUtility::getSearchText($this->text,'Código Fipe:','numberstr',['max_words'=>1]);
         }
         $n = str_replace(['-'], '', $n);
         $data['veiculo_cod_fipe_1'] = $n; // Codigo Fipe
         //dd($this->text);
         $data['veiculo_placa_1'] = trim($this->getX1(['start'=>'Placa:','cb'=>function($v){ return explode(" ",$v)[1]??'';  }])); // Placa
         //dd($data['veiculo_placa_1'],$data['veiculo_zero_1']);
         if(strpos($data['veiculo_placa_1'],'AVISAR')!==false || $data['veiculo_zero_1']=='s'){
             $data['veiculo_placa_1'] = 'nd zero';
              //dd($data['veiculo_placa_1']);
         }


        // $n = trim($this->getX1(['start'=>'Combus','cb'=>function($v){ return explode(":",$v)[1];  }]));      //Combustível
         $text = FormatUtility::sanitizeAllText($text);
         $n = $this->getData_combustivel($text);     //Combustível
         //dd($data);
         $data['veiculo_combustivel_1'] = $n[0]??'';
         $data['veiculo_combustivel_code_1']= $n[1]??'';

         //$data['veiculo_n_lotacao_1'] = trim($this->getX1(['start'=>'Lotação Veículo:','remove'=>'Lotação Veículo:']));         //lotação
         $data['veiculo_n_lotacao_1'] = TextUtility::getSearchText($text,'Lotação Veículo:','number',['side'=>'right']);

         $text_ci = TextUtility::getPartOfStr($this->text, ['start'=>'CI:','end'=>'Classe']);

         if(strpos($text_ci,'CI:')!==false){
            $data['veiculo_ci_1'] = TextUtility::getSearchText($text_ci,'CI:','number',['side'=>'right']); //C.I
         }else{
            $data['veiculo_ci_1'] = trim($this->getX1(['start'=>'(CI)','end'=>'Classe','cb'=>function($v){ return explode(" ",$v)[1];  }]));         //C.I
         }


         $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Bônus:','end'=>'Tipo']);

         $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'Bônus:','number',['side'=>'right']);
         //dump($data['veiculo_classe_1']);
         $data['veiculo_n_portas_1'] = ''; //na apólice da Tokio não tem esta informação

        $data = $this->getPremio($data);


        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

    //utilizado para o novo layout Junho/2021
    private function processTipo02(){

        $pg = $this->getPagina1();

        $data = $this->getDados3();

        if(!$data['success'])return $data;
        $data = $data['data'];

        $n=$this->getX1(['start'=>'AUTO']);//verifica se é frota
        //dd($n);
        if(strpos($n,'FROTA')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

         //Dados do Proprietário
          $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         if($data['apolice_prod_ref']=='Tokio Marine Auto' || $data['apolice_prod_ref']=='TokioMarineAuto'){
                 $n = $this->getX1(['start'=>'pernoite','cb'=>function($v){ return explode(":",$v)[1];  }]);//Cep de Pernoite
                 $n = $n=str_replace(['-'], [''], $n);

         }elseif($data['apolice_prod_ref']=='Tokio Marine Caminhão'){
                if(strpos($this->text,'CEP Pernoite:')!==false){
                     $n = $this->getX1(['start'=>'CEP Pernoite:','cb'=>function($v){ return explode(" ",$v)[2];  }]);//Cep de Pernoite
                     $n = $n=str_replace(['-'], [''], $n);
                }else{
                    $n='';
                }

         }

         $data['segurado_pernoite_cep_1'] = trim($n);
         //dd(strlen($data['segurado_pernoite_cep_1']));
         if($data['segurado_pernoite_cep_1']=='FISICA' || $data['segurado_pernoite_cep_1']=='JURIDICA' || strlen($data['segurado_pernoite_cep_1'])>8){
             $tmp = TextUtility::getPartOfStr($this->text, ['start'=>'Fipe:'],['sanitize'=>true]);
             //dd($tmp);
             $data['segurado_pernoite_cep_1'] = TextUtility::getSearchText($tmp,'pernoite','cep',['side'=>'right']);

             if(empty($tmp)){
                $tmp = TextUtility::getPartOfStr($this->text, ['start'=>'CEP de pernoite'],['sanitize'=>true]);
                //dd($tmp);
                $data['segurado_pernoite_cep_1'] = TextUtility::getSearchText($tmp,'veículo:','cep',['side'=>'right']);
             }


         }
         $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Veículo:'],['sanitize'=>false]);
         $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Blindado:']);

         if($textVei==''){// é caminhão
             $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Veículo:'],['sanitize'=>false]);
             $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Condição Exclusiva:']);
             $data['veiculo_tipo_1'] = 'c';
         }else{
             $data['veiculo_tipo_1'] = 'a';
         }

         $n= TextUtility::getPartOfStr($textVei, ['start'=>'Fabricante:','end'=>'Ano Modelo'],['sanitize'=>false]);// Fabricante do Veiculo
         $data['veiculo_fab_1'] = trim(str_replace(['Fabricante:','Ano Modelo'], '', $n));
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
         $data['veiculo_modelo_1'] = TextUtility::getPartOfStr($textVei, ['start'=>'Veículo:','end'=>'Fabricante:'],['sanitize'=>false]);;// Modelo do Veiculo
         $data['veiculo_modelo_1'] = trim(str_replace(['Fabricante:','Veículo:','fabricante'], '', $data['veiculo_modelo_1']));


         $text = TextUtility::getPartOfStr($this->text, ['start'=>'Fabricante:','end'=>'Tipo de']);
         if(strpos($text,'Ano de Fabri')!==false){
            $n = trim($this->getX1(['start'=>'Ano de Fabri','cb'=>function($v){ return explode(":",$v)[1];  }]));
         }else{
             $n = trim($this->getX1(['start'=>'Ano Modelo','cb'=>function($v){ return explode(":",$v)[1];  }]));
             $n = explode(' ', $n);
             $n = $n[0];
         }

         $data['veiculo_ano_fab_1'] =self::clearText($n);//Ano Fab
         $n = trim($this->getX1(['start'=>'Ano Modelo','cb'=>function($v){ return explode(":",$v)[1];  }]));
         $data['veiculo_ano_modelo_1'] = self::clearText($n);   //Ano Model

         $text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Bem','end'=>'Coberturas']); //bloco de texto com dados do veículo

         //$data['veiculo_chassi'] = trim($this->getX1(['start'=>'Chassi:','remove'=>'Chassi:']));//Chassi
         $n=$this->getData_chassi($text);
         if(!$n){
             $text0 = TextUtility::getPartOfStr($this->text, ['start'=>'chassi:','sanitize'=>false]);
             $n=$this->getData_chassi($text0);
         }
         $data['veiculo_chassi_1'] = $n;

         $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Veículo Blindado: '],['sanitize'=>false]);
         $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Veículo com Kit']);
         //dd($text_vei_zero);

         if(strpos($textVei, 'Data de saída do veículo 0 km:')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

         $data['veiculo_data_saida_1'] =TextUtility::getSearchText($text,'Data de saída','datebr',['side'=>'right']);
         $data['veiculo_nf_1']='';//não tem esse dado

         $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Código Fipe:'],['sanitize'=>false]);
         $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Dispositivo em']);
         $fipe = str_replace(['Código Fipe:','Dispositivo em','-'], '', $textVei);
         $data['veiculo_cod_fipe_1'] = trim($fipe); // Codigo Fipe

         $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Placa:'],['sanitize'=>false]);
         $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Combustível:']);
         $data['veiculo_placa_1'] = $this->getData_placa($textVei); // Placa
         //dd($data['veiculo_placa_1'],$data['veiculo_zero_1'],$textVei);
         if(strpos($data['veiculo_placa_1'],'AVISAR')!==false || $data['veiculo_zero_1']=='s' || $data['veiculo_zero_1']=='AAVISAR'){
             $data['veiculo_placa_1'] = 'nd zero';
              //dd($data['veiculo_placa_1']);
         }
         $text = TextUtility::getPartOfStr($this->text, ['start'=>'Combustível:','end'=>'Chassi Remarcado:'],['sanitize'=>false]);


         //dd($n);
         if(strpos($text,'Híbrido')!==false){
            $data['veiculo_combustivel_1'] = 'HIBRIDO';
            $data['veiculo_combustivel_code_1']= '05';
         }else{
             $text = FormatUtility::removeAcents($text);
             $n = $this->getData_combustivel($text);     //Combustível
             //dd($n,$text);
             if(strpos($text,'Sem combustivel')!==false){
                $data['veiculo_combustivel_1'] ='OUTROS';
                $data['veiculo_combustivel_code_1']= '11';
             }else{
                $data['veiculo_combustivel_1'] = $n[0];
                $data['veiculo_combustivel_code_1']= $n[1];
             }
         }


         //$data['veiculo_n_lotacao_1'] = trim($this->getX1(['start'=>'Lotação Veículo:','remove'=>'Lotação Veículo:']));         //lotação
         $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'Lotação:'],['sanitize'=>false]);
         $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Zero KM']);
         $data['veiculo_n_lotacao_1'] = TextUtility::getSearchText($textVei,'Lotação:','number',['side'=>'right']);

         $textVei = TextUtility::getPartOfStr($this->text, ['start'=>'CI:'],['sanitize'=>false]);
         $textVei = TextUtility::getPartOfStr($textVei, ['end'=>'Classe']);
         $data['veiculo_ci_1'] = TextUtility::getSearchText($textVei,'CI:','number',['side'=>'right']);;         //C.I
         $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Bônus:','end'=>'Tipo']);
         $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'Bônus:','number',['side'=>'right']);
         $data['veiculo_n_portas_1'] = ''; //na apólice da Tokio não tem esta informação

        $data = $this->getPremio($data);
        //dd($data,$this->text);

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
