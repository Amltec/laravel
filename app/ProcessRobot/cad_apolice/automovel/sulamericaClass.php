<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use App\ProcessRobot\cad_apolice\Classes\Insurers\sulamericaInsurer;


/**
 * Classe responsável por fazer a leitura do texto do pdf e separar corretamente as informações em campos para um array php
 * Última atualização 19:45 17/02/2020
 */
class sulamericaClass extends ProcessAutomovelClass{
    use sulamericaInsurer;



    protected $pdf_engine = 'ws02'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)


    protected $validate_iof_margem=0.07; //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data



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
    	Pasta: C:\xampp\htdocs\robo-gc\_test-pdf-php\aw_test\Files\cad_apolice\automovel\tokio\pdf
    */
    private function processTipo01(){
        $text_full = str_replace('Prêmio Total à Vista', '', $this->text);

        $text0 = FormatUtility::sanitizeAllText($text_full);
        $pg = $this->getPagina1();
        $data=[];

       //dd(123);

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $n=$text_full;//verifica se é frota
        if(strpos($n,'COLETIVA')!==false || strpos($n,'ITEM:')!==false || strpos($n,'ALTERAÇÕES DO MÊS')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];


        //dados do corretor
        $n = $this->getX1(['start'=>'Nome do Corretor:','remove'=>'Nome do Corretor:']);//nome do corretor
        $n = FormatUtility::sanitizeText($n);
        $data['corretor_nome'] = $n;//nome do corretor

        $n = TextUtility::getPartOfStr(str_replace('  ',' ',$text_full), ['start'=>'Susep: SEGURADORA E APÓLICE','remove'=>'Susep: SEGURADORA E APÓLICE','split'=>chr(10)] );
        if(empty($n)){
            $n = TextUtility::getSearchText($this->text,'Telefone Corretora:','value',['side'=>'left']);
        }
        //dd($n);
        $data['corretor_susep'] = $n;//susep do corretor


        //dados da apólice
         $data['data_type'] = $this->getX1(['start'=>'SEGURADORA E ENDOSSO','remove'=>'SEGURADORA E ','cb'=>function($v){ return explode(" ",$v)[0];  }])=='ENDOSSO' ? 'endosso' : 'apolice';
         //dd($data['data_type']);
         if($data['data_type']=='endossos' || $data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice não identificada','ignore'=>true,'code'=>'endosso'];


         $n = $this->getX1(['start'=>'CNPJ','cb'=>function($v){ return explode(" ",$v)[1];  }]);//CNPJ Seguradora
        // $n = str_replace(['-','.','/'], ['','',''], $n);
         if(strlen($n)==19){

                $n=substr($n, 1);
         }
         $data['seguradora_doc'] = $n;//CNPJ Seguradora


         $n = $this->getX1(['start'=>'Proposta n','cb'=>function($v){ return explode(":",$v)[1];  }]);//Numero da Proposta Seguradora
         $n = explode(" ",$n);
         $n = trim($n[1]);
         $data['proposta_num'] = $n;//Numero da Proposta Seguradora

         $n = $this->getX1(['start'=>'Apólice n','cb'=>function($v){ return explode(":",$v)[1];  }]);//Número da Apólice na Seguradora
         $data['apolice_num'] = trim($n);//Número da Apólice na Seguradora

         $n = str_replace('-', '', $data['apolice_num']);
        // $data['apolice_num_quiver'] =$n;//Número da Apólice Quiver

         $n =trim($this->getX1(['start'=>'Data de Emissão','cb'=>function($v){ return explode(":",$v)[1];  }]));//Data de Emissão
         $n = explode(" ",$n);
         $n = trim($n[0]);
         $data['data_emissao'] = $n;//Data de Emissão

         $data['apolice_re_num'] = "";//Numero da apólice renovada - no PDF da SulAmerica não vem o número da apólice renovada
         $n = trim($this->getX1(['start'=>'dia','cb'=>function($v){ return explode(" ",$v)[1]??'';  }]));//Data Início de vigência
         if(empty($n)){
             $n=$this->getX1(['start'=>'Vigência','split'=>false,'end'=>'termina','remove'=>[chr(10),chr(13),'Vigência: Esta apólice vigora a partir das 24:00 horas do dia']]);
             $n=explode(' ',$n)[0];
         }
         $data['inicio_vigencia']=$n;

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'a partir das','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'CORRETOR DE SEGUROS']);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        $data['termino_vigencia'] = $n[1];
        //dd($data['termino_vigencia'], $blocktext);
        //dd($blocktext,$n);

         //Dados do Segurado
         $n = $this->getX1(['start'=>'DADOS D','return_type'=>'next','cb'=>function($v){ return explode(":",$v)[1]??'';  }]);//Nome Segurado
         if(empty($n)){
             $n = $this->getX1(['start'=>'Segurado:','remove'=>'Segurado:']);//Nome Segurado
         }
         $n = FormatUtility::sanitizeText($n);
         $n = htmlentities($n);
         $data['segurado_nome'] =trim($n);//Nome Segurado

         $n= $this->getX1(['start'=>'End Seg','return_type'=>'prev','cb'=>function($v){ return explode(" ",$v)[1];  }]);
         $n=str_replace(['-','.','/'], ['','',''], $n);
         $n=trim($n);
         if(strlen($n)==15)$n=substr($n,1,strlen($n));
         $data['segurado_doc'] = $n;//documento segurado

         if(strlen($data['segurado_doc'])<=11){$n='FISICA';}else{$n='JURIDICA';}
         $data['tipo_pessoa'] = $n;//tipo segurado


         //Dados do Proprietário
          $data['prop_nome_1'] = $data['segurado_nome'];//Data

         if ($data['prop_nome_1'] == $data['segurado_nome']){$data['segurado_proprietario_veiculo_1']='SIM';}else{$data['segurado_proprietario_veiculo_1']='NÃO';};

         //Endereço de Pernoite
         $n = $this->getX1(['start'=>'UF Município','return_type'=>'next','cb'=>function($v){ return explode(":",$v)[1];  }]);//Cep de Pernoite
         $n = $n=str_replace(['-'], [''], $n);
         $data['segurado_pernoite_cep_1'] = trim($n);


         $dadosVei = TextUtility::getPartOfStr($text_full,['start'=>'Veículo: ','remove'=>'Veículo: ','split'=>chr(10)]);//Contem os dados do veiculo

         //dd($dadosVei);
        //veiculo zero
        $text_vei_zero = $dadosVei;
        if(strpos($text_vei_zero, '0KM')!==false){
             $data['veiculo_zero_1'] ='s';
         }else{
             $data['veiculo_zero_1'] ='n';
         }

        $text_vei = TextUtility::getPartOfStr($this->text, ['start'=>'Data de saída ']);
        $text_vei = TextUtility::getPartOfStr($text_vei, ['end'=>'Chassi:']);
        $text_vei = str_replace('.', '/', $text_vei);
        //dd($text_vei);
        $data['veiculo_data_saida_1']=TextUtility::getSearchText($text_vei,'Data de saída','datebr',['side'=>'right']);
        $data['veiculo_nf_1']='';//não tem esse dado

         if(empty($dadosVei)){
             $dadosVei = $this->getX1(['start'=>'VEÍCULO SEGURADO','end'=>'Chassi:','remove'=>['VEÍCULO SEGURADO','Chassi:',chr(10),chr(13)],'split'=>false,]);//Contem os dados do veiculo
             //remove possíveis caracteres como: 'V eícul o'  'Ano - ...',
             $dadosVei = TextUtility::getPartOfStr($dadosVei,['start'=>':','remove'=>[':',' ANO '],'end'=>' ANO ']);
             $dadosVei = trim(trim($dadosVei,'-'));
         }
         $dadosVei = str_replace(' - 0KM', '', $dadosVei);
         $dadosVei = explode('-',$dadosVei);

         $tipo_vei = TextUtility::getPartOfStr($this->text, ['start'=>'categoria tarifaria:','sanitize'=>true]);
         $tipo_vei = TextUtility::getPartOfStr($tipo_vei, ['end'=>'Pernoite']);
        // dd($tipo_vei);
         if(strpos($tipo_vei,'transporte de carga')!==false){
             $data['veiculo_tipo_1'] = 'c';
         }else{
             $data['veiculo_tipo_1'] = 'a';
         }

       // dd($n);


         $data['veiculo_fab_1'] =  trim($dadosVei[0]);// Fabricante do Veiculo
         $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
         $data['veiculo_modelo_1'] = trim($dadosVei[1]);// Modelo do Veiculo



         //campo ano fab
         $y =  count($dadosVei)-1;
         //dd($dadosVei);
         $n = trim(str_replace("ANO ", "", $dadosVei[$y]));

         if($n=='' || (strlen($n)!=4 && is_numeric($n)==false)){//pegou outro texto
             $n=$this->getX1(['start'=>$data['veiculo_modelo_1']]);//texto esperado ex 'MONT ANA LS 1.4 ECONOFLEX -  ANO 2013'
             $n=explode('-',trim($n));
             $n=$n[count($n)-1];
             $n=trim(str_replace('ANO','',$n));
         }
         //dump($n);return;
         $data['veiculo_ano_fab_1'] = $n;//Ano Fab
         $data['veiculo_ano_modelo_1'] = $data['veiculo_ano_fab_1'];   //Ano Modelo


         $data['veiculo_chassi_1'] = trim($this->getX1(['start'=>'Chassi: ','cb'=>function($v){ return explode(" ",$v)[1];  }]));//Chassi
         $n = $this->getX1(['start'=>'GARANTIAS CONTRATADAS','return_type'=>'next']);

         //dd($n);

         if(strpos($n,'FIPE')!==false){
           $n = $this->getX1(['start'=>'(Tabela FIPE)','cb'=>function($v){ return explode(":",$v)[1];  }]);
            $n = explode(' ',$n);
            $n = $n[1];
            $n = str_replace(['-'], [''], $n);
            $data['veiculo_cod_fipe_1'] = $n; // Codigo Fipe

         }else{

             $n = TextUtility::getPartOfStr($this->text, ['start'=>'FIPE):'],['sanitize'=>true]);
             $n = TextUtility::getSearchText($n,'FIPE):','value',['side'=>'right']);
             $n = str_replace('-', '', $n);
             $data['veiculo_cod_fipe_1'] = $n; // Codigo Fipe
         }

         $data['veiculo_placa_1'] = trim($this->getX1(['start'=>'Chassi: ','cb'=>function($v){ return explode(" ",$v)[3];  }]));// Placa

         $n = trim($this->getX1(['start'=>'Tipo de Combustível: ','end'=>'Quantidade de Passageiros','remove'=>'Tipo de Combustível: ']));      //Combustível
         $n = trim(str_replace('Quantidade de Passageiros', '', $n));

              //Combustível
         $data['veiculo_combustivel_1'] = strtoupper(FormatUtility::sanitizeText($n));

         $data['veiculo_combustivel_code_1'] = $this->getCombustivelCode($data['veiculo_combustivel_1']);


         $data['veiculo_n_lotacao_1'] = trim($this->getX1(['start'=>'Quantidade de Passageiros','cb'=>function($v){ return explode(":",$v)[1]??'';  }]));         //lotação

         $n = trim($this->getX1(['start'=>'Identificação - CI','cb'=>function($v){ return explode(":",$v)[1];  }]));         //C.I
         $n = str_replace(['.'], [''], $n);
         $data['veiculo_ci_1'] = $n;
         $data['veiculo_n_portas_1'] = ''; //na apólice da SulAmerica não tem esta informação

         $block_text = TextUtility::getPartOfStr($text_full, ['start'=>'Bônus:','end'=>'Renovação:']);
            //dd($block_text);
         $data['veiculo_classe_1'] =TextUtility::getSearchText($block_text,'Bônus:','number',['side'=>'right']);

        //Forma de Pagamento
            $block_text = TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento do premio','end'=>'questionario']);
            $pgto_tipo =  PgtoData::getPgtoTipo($block_text);
            //dd($pgto_tipo);
            if($pgto_tipo=='debito' || $pgto_tipo=='boleto'){
                //primeira parcela


                //procura uma data, que será a data da primeira parcela
                TextUtility::execFncInStr($block_text,10,function($v) use(&$dt_parcela_1){
                    $v=trim($v);

                    if(ValidateUtility::isDate($v) && strlen($v)==10){
                        $dt_parcela_1=$v;
                        return true;
                    }
                });

                //dd($pgto_tipo,$dt_parcela_1);
                //segunda parcela
                $r=TextUtility::getPartOfStr($text0,['start'=>'melhor dia:','remove'=>'melhor dia:']);//é epserado que na string seguinte venha um dia (2 digitos)
                //dd($r);
                $dt_parcela_2=explode(' ',$r)[0];//aqui tem somente o dia da parcela
                $n = explode('/',$dt_parcela_1);
                $dt_parcela_2 = $dt_parcela_2.'/'.$n[1].'/'.$n[2];
               // dd($dt_parcela_1);
                $dt_parcela_2 = FormatUtility::addDate(FormatUtility::convertDate($dt_parcela_2),'m', 1,'datebr');
               // dd($dt_parcela_2);
                $r=TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento do premio']);


                $blocktext = TextUtility::getPartOfStr($text_full, ['start'=>'premio total -','end'=>'taxa','sanitize'=>true]);

                if(strpos($blocktext, 'total a vista')){
                    $blocktext = str_replace('total a vista', '', $blocktext);
                }

                $premio = TextUtility::getSearchText($blocktext,'premio total -','number_formated',['side'=>'right']);
                //dd($blocktext,$premio);
                PgtoData::getPgtoAuto($r,$data,$this->validate_premio_margem,[
                    'pgto_tipo'=>$pgto_tipo,
                    'dt_parcela_1'=>$dt_parcela_1,
                    'dt_parcela_2'=>$dt_parcela_2,
                    'full_text'=>$text_full,
                    'premio'=>$premio,
                    'thisClass'=>$this,
                ]);
            }else{
                $r=TextUtility::getPartOfStr($text0,['start'=>'forma de pagamento do premio']);

                $r=PgtoData::getPgtoAuto($r,$data,$this->validate_premio_margem,['full_text'=>$text_full,'thisClass'=>$this]);

            }
           // dd($pgto_tipo,$dt_parcela_1,$dt_parcela_2);
        $blocktext = TextUtility::getPartOfStr($text_full, ['start'=>'premio liquido','end'=>'franquias e descontos','sanitize'=>true]);

        //dd($blocktext);
        if(strpos($blocktext, 'juros')){
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>true]);
        }else{
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false]);
        }



        // dd($data);
        $data = $data + $r;

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }



    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
