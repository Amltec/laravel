<?php

namespace App\ProcessRobot\cad_apolice\Classes\Insurers;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;



/**
 * Classe trait de funções funções gerais para leitura de apólices pdf de qualquer ramo da Seguradora Sompo
 * Deve ser incorporada a partir da uma classe de um ramo específico, como a classe ex: App\ProcessRobot\cad_apolice\automovel\hdiClass.php
 */
trait hdiInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'pdfparser'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
        $this->validate_premio_margem=73;//(R$) limite de diferença por causa de possíveis juros das parcelas

        //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
        $this->validate_iof_margem=1.60;
    }


     /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'01059005027177',
            'not_dot_traits'=>true
        ];
    }


    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados(){
        $data=[];
        //dd(123);
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();

        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        if(strpos($this->text,'Dados do Veículo - Item 000002')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }
        if(substr_count($this->text, 'Dados do Veículo - Item 0000')>1){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }
        //dd(substr_count($this->text, 'Dados do Veículo - Item 0000'));
         //verifica os dados dos corretores
         //Lógica: se hovuer mais de um corretor, então captura apenas o primeiro
         $n = TextUtility::getPartOfStr($this->text,['start'=>'Interno','end'=>'A HDI SEGUROS S.A.',
             'remove'=>['Interno','A HDI SEGUROS S.A.']
         ]);
           //$count=0;

         $lines  = explode(chr(10),$n);
         //dd($lines);
         foreach($lines as $n){
             $n=trim($n);
             if(substr(strtolower($n),0,2)=='c-' || substr(strtolower($n),0,2)=='f-'){//sintaxe - ex: C-00001234567890 - RAZAO SOCIAL DO CORRETOR - 99,99%
                 $x = str_replace(' - ','-',$n);
                 $x = explode('-',$x);
                 if(count($x)>=4){
                    $data['corretor_susep'] = (string)(int)$x[1];
                    $data['corretor_nome'] = trim($x[2]);
                    //$count++;//conta quantos corretores existem
                    break;
                 }
             }
         }
         //if($count>1)return ['success'=>false,'data'=>$data,'msg'=>'Apólice com mais de 1 corretor não programado'];



         //dados da apólice
         $data['data_type'] = stripos($this->getX1(['start'=>'HDI SEGUROS','end'=>'Segurado','split'=>false]),'Endosso')!==false ? 'endosso' : 'apolice';
         if($data['data_type']=='endosso')return ['success'=>false,'msg'=>'Apólice do tipo Endosso - não processado','data'=>$data,'ignore'=>true,'code'=>'read03'];

         $data['seguradora_doc'] = $this->getX1(['start'=>'HDI SEGUROS S.A. - 6572','return_type'=>'next','cb'=>function($v){ return explode(" ",$v)[1];  }]);//CNPJ Seguradora

         $n = $this->getX1(['start'=>'Informações do Seguro','return_type'=>'next','cb'=>function($v){ return explode(":",$v)[2];  }]);//Numero da Proposta Seguradora
         $n = trim($n,' ');
         $data['proposta_num'] = ltrim($n,0);//Numero da Proposta Seguradora

       //  $data['apolice_num'] = $this->getX1(['start'=>'Apólice','remove'=>'Apólice','cb'=>function($v){  $n=explode('.',$v); return $n[ count($n)-1 ];  }]);//Número da Apólice na Seguradora
         $data['apolice_num'] = $this->getX1(['start'=>'Apólice','remove'=>'Apólice']);//Número da Apólice na Seguradora
         $n = $this->getX1(['start'=>'Apólice','remove'=>'Apólice']);
         //atualizado para ser capturado pela função numQuiverConfig()
         //$data['apolice_num_quiver'] = self::clearText($n);//Número da Apólice Quiver (sem formatação)
         $data['apolice_num'] = self::clearText($n);//Número da Apólice (sem formatação)

         $data['data_emissao'] = $this->getX1(['start'=>'emitido em','remove'=>'emitido em','cb'=>function($v){  $n=explode(',',$v); return $n[0];  }]);//Data de Emissão
         if(empty($data['data_emissao'])){
             $text_emiss = TextUtility::getPartOfStr($this->text,['start'=>'emitido  as','end'=>'a  SEGURADORA']);
             $text_emiss = TextUtility::getSearchText($text_emiss,'emitido  as','datebr',['side'=>'right']);
             $data['data_emissao'] = $text_emiss;//Data de Emissão
         }

         $y = $this->getX1(['start'=>'Apólice Anterior','remove'=>'Apólice Anterior','cb'=>function($v){  $n=explode('.',$v); return $n[ count($n)-1 ];  }]);
         $data['apolice_re_num'] = trim(str_replace(':','',$y));  //Numero da apólice renovada
       //$data['apolice_file_url'] = $this->getX1(['start'=>'HDI SEGUROS S.A. - 6572','return_type'=>'next','cb'=>function($v){ return explode(" ",$v)[1];  }]);//caminho do arquivo da apólice
         $data['inicio_vigencia'] = $this->getX1(['start'=>'Das 24h do dia ','remove'=>'Das 24h do dia ','cb'=>function($v){  $n=explode(' ',$v); return $n[0];  }]);//Data Início de vigência


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'das 24h do dia ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'ramo']);
        $n=[];
        TextUtility::execFncInStr($blocktext,10,function($v) use(&$n){
            if(ValidateUtility::isDate($v) && strlen(trim($v))==10){
                $n[]=$v;
            }
        });
        $data['termino_vigencia'] = $n[1];
        //dd($data['termino_vigencia'], $blocktext);
        //dd($blocktext,$n);

        //*** Dados do Segurado ***
        //Nome Segurado
         $n = $this->getX1(['start'=>'Segurado','remove'=>'Segurado','cb'=>function($v){  $n=explode(':',$v); return $n[1];  }]);//Nome Segurado
         $n = trim($n);
         $n = substr($n,0,strlen($n)-4);
         $data['segurado_nome'] = trim($n);


         //documento segurado
         $n= $this->getX1(['start'=>'segurado']);



         $n=explode(':',$n);
         $n = trim($n[ count($n)-1 ]);

         if($n){
            $data['segurado_doc']=$n;
         }else{
             $n = $this->getX1(['start'=>'Segurado','end'=>'rg','split'=>false]);
             $n = TextUtility::getSearchText($n,'', 'document');
             $data['segurado_doc']=$n;
         }

         if($data['segurado_doc']==''){
             $n = TextUtility::getPartOfStr($this->text, ['start'=>'segurado']);
             $n = FormatUtility::sanitizeBreakText($n);
             $data['segurado_doc'] =TextUtility::getSearchText($n,'CPF','document',['side'=>'right']);
             $n = $data['segurado_doc'];

         }
         //dd($data['segurado_doc']);
         $data['tipo_pessoa'] = ValidateUtility::isCNPJ($n) ? 'JURIDICA' : (ValidateUtility::isCPF($n)?'FISICA':'');

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){
        //premio_liquido
        //premio_servico
        //premio_custo
        //premio_adicional
        //premio_iof???
        //premio_total

        //forma de pagamento
        $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'Parcelamento do Prêmio']);
        $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'tabela fipe']);
        $blocktext = preg_replace('/(Pagamento irregular em)(.*)(Boleto)/','',$blocktext, 1);
        //dd($blocktext);
        $text_pgto = TextUtility::getPartOfStr($this->text,['start'=>'Tipo de Cobrança:']);
        $text_pgto = TextUtility::getPartOfStr($text_pgto,['end'=>'Parcela']);
        $pagamento = PgtoData::getPgtoTipo($text_pgto);
        $data['fpgto_tipo']=$pagamento;
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode($pagamento)[1];
        //dd($pagamento);
        $r=PgtoData::getPgtoAuto($blocktext,$data,$this->validate_premio_margem,['full_text'=>$this->text,'thisClass'=>$this]);
        //dd($r);
        if(!isset($data['fpgto_tipo'])){//este campo não foi gerado
            $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'tipo de cobranca','sanitize'=>true]);
            $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'parcela']);
            $n = PgtoData::getPgtoTipo($blocktext);
            $data['fpgto_tipo']=$n;
            $data['fpgto_tipo_code']=PgtoData::getPgtoCode($n)[1];
        }

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio da apolice','end'=>'parcelamento do premio','sanitize'=>true]);


        $n = TextUtility::getPartOfStr($this->text, ['start'=>'melhor data','sanitize'=>true]);
        $juros_md = TextUtility::getSearchText($n,'', 'number_formated');

        if(!$juros_md)$juros_md='0,00';


        $n = TextUtility::getPartOfStr($blocktext, ['start'=>'juros','sanitize'=>true]);

        $juros = TextUtility::getSearchText($n,'', 'number_formated');
        if(!$juros)$juros='0,00';

        if($juros=='100.000,00'){
            $juros='0,00';
        }
        $r=PgtoData::getFielsPremioAdd($blocktext,$data,['juros'=>$juros,'juros_md'=>$juros_md]);

        $data = $data + $r;

        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
