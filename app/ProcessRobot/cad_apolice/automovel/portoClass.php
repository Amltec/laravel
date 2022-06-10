<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\portoInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class portoClass extends ProcessAutomovelClass{
    use portoInsurer;


    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
        $this->splitThisText($text);
        $this->text_ws02 = \App\Utilities\FilesUtility::readPDF($opt['path'],['engine'=>'ws02','pass'=>$this->process_opt['pass']])['text'];

        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }


    private function processTipo01(){


        //detecta qual o tipo layout padrão da apólice, valores esperados:
        if(strpos($this->text,'Código C.I:')!==false){
            $tipo_lay='lay2022';
        }else{
            $tipo_lay='lay2021';
        }


        if($tipo_lay=='lay2022'){
            $data = $this->getDados2();
        }else{
            $data = $this->getDados1();
        }
        if(!$data['success'])return $data;
        $data = $data['data'];



         //Dados do Proprietário
          $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         $n = $this->getX1(['start'=>'pernoita: ','remove'=>'pernoita: ']);//Cep de Pernoite
         if(!$n){
             $n = $this->getX1(['start'=>'permoita: ','remove'=>'permoita: ']);//Cep de Pernoite
         }
         if(!$n){
             $n = TextUtility::getSearchText($this->text_ws02,'pernoita:','cep');
         }
         $n= str_replace('-', '', $n);
         $data['segurado_pernoite_cep_1'] = substr(trim($n), 0,8);

         if(empty($data['segurado_pernoite_cep_1'])){
             $n= TextUtility::getPartOfStr($this->text,['start'=>'veiculo pernoita']);
             $n= TextUtility::getPartOfStr($n,['end'=>'principal']);
             $data['segurado_pernoite_cep_1'] = TextUtility::getSearchText($n,'pernoita','number',['side'=>'right']);
             //dd($n,$data['segurado_pernoite_cep_1']);
         }
         $text0 =
         $n = $this->getX1(['start'=>'Veículo:','remove'=>'Veículo:']);
         //dd($n);
         if($n=='Outros' || !$n || $n=='Não'){
             $n = $this->getX1(['start'=>'Veíulo:','remove'=>'Veíulo:']);
         }

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Modelo:']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Código']);
        $text_vei = str_replace('Veíulo:', 'Veículo', $text_vei);


        if(strpos($text_vei, '(0km)')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

        $data['veiculo_data_saida_1']='';//não tem esse dado
        $data['veiculo_nf_1']='';//não tem esse dado

         $n = trim($n);
         $n = $this->getMarcaModelo($n);


         $data['veiculo_fab_1'] = $n['marca'];// Fabricante do Veiculo
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
         $data['veiculo_modelo_1'] = $n['modelo'];// Modelo do Veiculo
         if(!$data['veiculo_modelo_1']){
            $n = trim(TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Veículo:','end'=>'Ano','remove'=>['Veículo:']]));
            $n = trim(TextUtility::getPartOfStr($n, ['end'=>'Ano','remove'=>['Ano']]));
            $data['veiculo_modelo_1']=$n;
             //dd($n);
         }

         if(!$data['veiculo_modelo_1']){
            $n = trim(TextUtility::getPartOfStr($this->text_ws02, ['start'=>'VEÍCULO','end'=>'NOME','remove'=>['VEÍCULO','NOME']]));
            $n = $this->getMarcaModelo($n);
            $data['veiculo_fab_1'] = $n['marca'];
            $data['veiculo_modelo_1']=$n['modelo'];
            //dd($data['veiculo_modelo_1'],$this->text_ws02);
         }

         if(!$data['veiculo_modelo_1']){
            $n = trim(TextUtility::getPartOfStr($this->text, ['start'=>'Veículo:','end'=>'Ano:','remove'=>['Veículo:','Ano:']]));
            $data['veiculo_fab_1'] = '';
            $data['veiculo_modelo_1']=$n;
            //dd($data['veiculo_modelo_1'],$this->text_ws02);
         }

          $data['veiculo_tipo_1'] = 'a';

         $n = trim($this->getX1(['start'=>'Ano:','cb'=>function($v){ return explode(":",$v)[1];  }]));
         $data['veiculo_ano_fab_1'] =self::clearText($n);//Ano Fab
         $n = trim($this->getX1(['start'=>'Modelo:','cb'=>function($v){ return explode(":",$v)[1];  }]));
         $n = str_replace('(0km)', '', $n);

         $data['veiculo_ano_modelo_1'] = self::clearText($n);   //Ano Model

        // $data['veiculo_chassi_1'] = trim($this->getX1(['start'=>'Chassi:','remove'=>'Chassi:']));//Chassi
         $text0 = TextUtility::getPartOfStr($this->text, ['start'=>'Chassi:']);
         $text0 = TextUtility::getPartOfStr($text0, ['end'=>'Renavam:','remove'=>['Chassi:','Renavam:',' ']]);
         $text0 = str_replace([']',')'], 'J', $text0);
        //dd($text0,$this->text);
         $n = $text0;
         if(strlen($n)==14)$n.='000';
         if(strlen($n)==15)$n.='00';

         $data['veiculo_chassi_1'] = $this->getData_chassi($n) ;//Chassi
         if(empty($data['veiculo_chassi_1']) || strpos($this->text_ws02,$data['veiculo_chassi_1'])===false){
            $text0 = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Combustível:']);
            $text0 = TextUtility::getPartOfStr($text0, ['end'=>'SUSEP Porto:','remove'=>['SUSEP Porto:','Combustível:']]);
            $text0 = str_replace(['/','(',')','.'],'/ ',$text0);
            $text0 = strtoupper(FormatUtility::sanitizeText($text0));
            $text0 = explode(' ',$text0);
            //dd($text0);
            for($i=0;$i<count($text0);$i++){
                if(strlen($text0[$i])==17){
                    $data['veiculo_chassi_1']=$text0[$i];
                }
            }
         }
         //dd($data['veiculo_chassi_1']);

         $n = trim($this->getX1(['start'=>'FIPE:','cb'=>function($v){ return explode(":",$v)[1];  }]));
         $data['veiculo_cod_fipe_1'] = $n; // Codigo Fipe
         //dd($data['veiculo_cod_fipe_1']);
         if(empty($data['veiculo_cod_fipe_1']) || strlen($data['veiculo_cod_fipe_1'])<=2){
            $data['veiculo_cod_fipe_1']= '';
         }
         $data['veiculo_cod_fipe_1'] = str_replace('/','7',$data['veiculo_cod_fipe_1']);

        // $data['veiculo_placa_1'] = trim($this->getX1(['start'=>'Placa:','cb'=>function($v){ return explode(" ",$v)[1]??'';  }])); // Placa
         $data['veiculo_placa_1'] = TextUtility::getSearchText($this->text_ws02,'Placa:','value',['side'=>'right']); // Placa
         if($data['veiculo_placa_1']=='Chassi:'){
             $data['veiculo_placa_1']='nd zero';
         }

         if(stripos($this->text, $data['veiculo_placa_1'])!==false){//achou o texto
            //não faz nada, pois já foi confirmado

         }else if(stripos($this->text_ws02, $data['veiculo_placa_1'])!==false || $data['veiculo_placa_1']!=''){//não tem
            $n = FormatUtility::sanitizeAllText($this->text_ws02) ;
            $n = TextUtility::getPartOfStr($n, ['start'=>'Placa:']);
            $data['veiculo_placa_1'] = $this->getData_placa($n);
            //dd($data['veiculo_placa_1'],$n);
         }
        if($data['veiculo_placa_1']=='Renavam:'){
                $n = TextUtility::getPartOfStr($this->text, ['start'=>'Placa:','end'=>'Chassi:','remove'=>['Chassi:',' ']]);
                $data['veiculo_placa_1'] = $this->getData_placa($n);
                if(!empty($data['veiculo_placa_1'])){
                    if(strpos($this->text_ws02,$data['veiculo_placa_1'])!==false){
                        $data['veiculo_placa_1']=='';
                    }
                }

         }

         if($data['veiculo_placa_1']==''){
            $n = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Tabela FIPE:','end'=>'SUSEP Porto','remove'=>['SUSEP Porto']]);
            $n = str_replace($data['veiculo_chassi_1'],'',$n);
            if(strpos($n,'0km')!==false){
                $data['veiculo_placa_1'] = 'nd zero';
            }else{
                $data['veiculo_placa_1'] = $this->getData_placa($n);
            }

         }

         //dd($data['veiculo_placa_1']);

         $n = trim($this->getX1(['start'=>'Combustível:','cb'=>function($v){ return explode(":",$v)[1];  }]));      //Combustível
              //Combustível
         $data['veiculo_combustivel_1'] = strtoupper($n);

            if($data['veiculo_combustivel_1']=='GASOLINA'){
                $data['veiculo_combustivel_code_1']='01';
            }elseif($data['veiculo_combustivel_1']=='ALCOOL'){
                $data['veiculo_combustivel_code_1']='02';
            }elseif($data['veiculo_combustivel_1']=='DIESEL'){
                $data['veiculo_combustivel_code_1']='03';
            }elseif($data['veiculo_combustivel_1']=='GAS'){
                $data['veiculo_combustivel_code_1']='04';
            }elseif($data['veiculo_combustivel_1']=='FLEX'){
                $data['veiculo_combustivel_code_1']='05';
            }elseif($data['veiculo_combustivel_1']=='GASOLINA / ALCOOL'){
                $data['veiculo_combustivel_code_1']='05';
            }elseif($data['veiculo_combustivel_1']=='GASOLINA / ALCOOL / GAS'){
                $data['veiculo_combustivel_code_1']='07';
            }elseif($data['veiculo_combustivel_1']=='GASOLINA / GAS'){
                $data['veiculo_combustivel_code_1']='08';
            }elseif($data['veiculo_combustivel_1']=='ELETRICO'){
                $data['veiculo_combustivel_code_1']='09';
            }elseif($data['veiculo_combustivel_1']=='TETRAFUEL'){
                $data['veiculo_combustivel_code_1']='10';
            }else{
                $data['veiculo_combustivel_code_1']='11';
            }

         $data['veiculo_n_lotacao_1'] = trim($this->getX1(['start'=>'Capacidade:','remove'=>' Passageiros','cb'=>function($v){ return explode(":",$v)[1];  }]));         //lotação

        $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Código C.l'],['sanitize'=>false]);
        $block_text = TextUtility::getPartOfStr($block_text, ['end'=>'Data de'],['sanitize'=>false]);
        $block_text = str_replace('.','',$block_text);
        $n = TextUtility::getSearchText($block_text,'Código','number',['side'=>'right']);

         if(strpos($this->text,'escolher o MOTO')!=false){
            $data['veiculo_n_lotacao_1'] = '2';
         }

         if($data['veiculo_n_lotacao_1']=='O'){
            $data['veiculo_n_lotacao_1'] = '5';
         }
         //dd($data['veiculo_n_lotacao_1']);
         $data['veiculo_ci_1'] = $n;  //C.I

         if(empty($data['veiculo_ci_1'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Código','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Classe']);
            $blocktext = trim(str_replace(['Classe','.'], [''], $blocktext));
            $data['veiculo_ci_1'] = TextUtility::getSearchText($blocktext,'Código','number',['side'=>'right']);;
         }

         if(empty($data['veiculo_ci_1'])){
            $blocktext = TextUtility::getPartOfStr($this->text_ws02, ['start'=>'Data de emissão:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Vigência:']);
            $blocktext = $this->getX1(['start'=>'Data de emissão:','return_type'=>'next2'],$blocktext);
            $blocktext = trim(str_replace(['Classe','.'], [''], $blocktext));
            $data['veiculo_ci_1'] = $blocktext;
         }
         $block_text = FormatUtility::sanitizeAllText($this->text);
         $block_text = TextUtility::getPartOfStr($block_text, ['start'=>'bonus'],['sanitize'=>true]);
          //dd($block_text);
         $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'bonus','number',['side'=>'right']);


         $data['veiculo_n_portas_1'] = trim($this->getX1(['start'=>'Portas:','remove'=>' Capacidade','cb'=>function($v){ return explode(":",$v)[1];  }]));; //na apólice da Tokio não tem esta informação
         $data['veiculo_n_portas_1'] = trim(str_replace('Placa','',$data['veiculo_n_portas_1']));

         if($data['veiculo_n_portas_1']=='O'){
           $data['veiculo_n_portas_1']='0';
         }

         //dd($data);
        if($tipo_lay=='lay2022'){
            $data = $this->getPremio3($data);
        }else{
            //dd(123);
            $data = $this->getPremio1($data);
        }

         if(isset($data['success']) && $data['success']==false)return $data;


        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

    /*private function processTipo02(){
        $pg = $this->getPagina1();

        $data = $this->getDados2($data);
        if(!$data['success'])return $data;

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }*/


    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }




}

