<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\hdiInsurer;

/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class hdiClass extends ProcessAutomovelClass{
    use hdiInsurer;

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $this->limitText($text);
        $r = $this->processTipo01();
    	return $this->ValidateData($r);
    }



    /* Padrão para os arquivos de boleto, cartão e débito - versão anterior a 2020:
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\hdi\pdf
    */
    private function processTipo01(){
        $pg = $this->getPagina1();

        $data = $this->getDados();
        if(!$data['success'])return $data;
        $data = $data['data'];

         //dd($data);
         //Endereço de Pernoite
         $n = $this->getX1(['start'=>'CEP Pernoite']);//Cep de Pernoite
         if($n!=''){
            $n = explode('CEP',$n);
            $n = explode(':',$n[1]);
            $n = trim(str_replace('-','',$n[1]));
            if(strlen($n)!=8){
               //lógica: se o cep estiver errado, captura o cep de cicrulação para comparar
               $n2 = $this->getX1(['start'=>'CEP Circulação','remove'=>['Cep Circulação',':']]);//Cep de circulação
               $n2 = trim(str_replace('-','',$n2));
               //lógica: tira os cacteres da esquerda/direita de $n1 para comparar com n2 e verificar se sõo iguais (ex: n1=123456000 e n2-23456000 - então n1=n2 )
               if(substr($n,1,strlen($n2))==$n2){//tirando o primeiro caractere, é igual
                   $n = $n2;
               }
            }
         }
         $data['segurado_pernoite_cep_1'] = $n;


         //Dados do Proprietário
         //dd($this->getX1(['start'=>'Proprietário','remove'=>'Proprietário']));
         $data['prop_nome_1'] = $this->getX1(['start'=>'Proprietário','remove'=>'Proprietário','cb'=>function($v){  $n=explode(':',$v); return Trim($n[1]);  }]);//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

        //Dados do Veículo
       // $vei_data = $this->getData_veiculo($this->getX1(['start'=>'Dados do veiculo','split'=>false]));

         $marcaMod = $this->getMarcaModelo(  trim($this->getX1(['start'=>'Modelo','remove'=>'Modelo','cb'=>function($v){ return explode("- ",$v)[1];  }]))  );//Fabricante Veiculo
         $data['veiculo_fab_1'] = $marcaMod['marca'];
         $data['veiculo_modelo_1'] = $marcaMod['modelo'];
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
         $n = $this->getX1(['start'=>'Fabr./Modelo','cb'=>function($v){ return explode(":",$v)[1];}]);      //Ano Fab
         $n = explode("Combustível",$n);//Ano Fab
         $n = explode("/",$n[0]);//Ano Fab
        // dd($n);
         $data['veiculo_ano_fab_1'] = self::clearText($n[0]);//Ano Fab
         $data['veiculo_ano_modelo_1'] = self::clearText($n[1]);   //Ano Model
          $data['veiculo_tipo_1'] = 'a';
         $n = trim($this->getX1(['start'=>'Categoria','return_type'=>'prev','cb'=>function($v){ return explode(":",$v)[2]??'';  }]));         //Chassi
         if(strlen($n)==7)$n.='0000000000';

         if(strlen($n)==14)$n.='000';
         if(strlen($n)==15)$n.='00';
         $data['veiculo_chassi_1']=$n;
         //000
         //$data['veiculo_chassi_1'] = $vei_data['veiculo_chassi_1'];
         $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Ano Fa']);

         $text_vei_zero = TextUtility::getPartOfStr($text_vei, ['end'=>'Combust']);
         //dd($text_vei_zero);

         if(strpos($text_vei_zero, '(Zero km)')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }


         $data['veiculo_cod_fipe_1'] = trim($this->getX1(['start'=>'Código FIPE','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));            //Código FIPE

         if($data['veiculo_zero_1'] =='n'){
              $n = $this->getX1(['start'=>'Placa/UF','cb'=>function($v){ return explode(" ",explode(":",$v)[1]);  }]);         //Placa
              $data['veiculo_placa_1'] = trim($n[1],'-');//Placa
                //dd(  $n);
         }else{
             $data['veiculo_placa_1'] = "nd zero ";//Placa
         }


         $n = trim($this->getX1(['start'=>'Combustível','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));      //Combustível
         $n =substr($n, 2, strlen($n));      //Combustível
         if(empty($n)){
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Combustível	:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Placa']);
            $n = TextUtility::getSearchText($blocktext,'-','value',['side'=>'right']);
         }
         $data['veiculo_combustivel_1'] = strtoupper($n);
         $data['veiculo_combustivel_code_1'] = $this->getCombustivelCode($data['veiculo_combustivel_1']);

         $data['veiculo_n_lotacao_1'] = trim($this->getX1(['start'=>'Passageiros','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));         //lotação
         $data['veiculo_n_portas_1'] = ''; //na apólice da HDI não tem esta informação
         $data['veiculo_ci_1'] = trim($this->getX1(['start'=>'Código CI','cb'=>function($v){ return explode(":",$v)[1];  }]));         //C.I

         $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Classe']);
         //dd($block_text);
         $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'Bônus	:','value',['side'=>'right']);

         $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Ano Fa']);



         $data['veiculo_data_saida_1'] =TextUtility::getSearchText($text_vei,'Data de Saída','datebr',['side'=>'right']);

         $data['veiculo_nf_1'] =TextUtility::getSearchText($text_vei,'Nota Fiscal	:','value',['side'=>'right']);


        $data = $this->getPremio($data);


        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }


}
