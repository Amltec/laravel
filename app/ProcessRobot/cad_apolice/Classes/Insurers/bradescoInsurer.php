<?php

namespace App\ProcessRobot\cad_apolice\Classes\Insurers;
use App\Utilities\TextUtility;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;
use Hamcrest\Type\IsNumeric;

/**
 * Classe trait de funções funções gerais para leitura de apólices pdf de qualquer ramo da Seguradora Bradesco
 * Deve ser incorporada a partir da uma classe de um ramo específico, como a classe ex: App\ProcessRobot\cad_apolice\automovel\bradescoClass.php
 */
trait bradescoInsurer{


    //método de inicialização
    public function initInsurer(){
         $this->pdf_engine = 'pdfparser'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)

        //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
        $this->validate_iof_margem=0.35;

        //limite de caracteres considerados para a extração do texto
        $this->limite_text = 60000;
    }



    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'0642.990.0244.053393',
            'last_dot'=>true,
            'not_zero_left'=>true
        ];
    }


    /**
     * Detecta do tipo de apólice
     * @return string - tipo1, tipo2
     */
    public function detectTipo(){
        $layoutModelo = TextUtility::getPartOfStr($this->text,[
            'start'=>'Dados da sua apólice', 'remove'=>[chr(10),chr(13)],
            'end'=>'Número da apólice'
        ]);
        //dd($layoutModelo);
    	if($layoutModelo=='Dados da sua apólice Número da apólice'){
            return 'tipo2';

    	}else{// No padrão tipo01 a variavel $layoutModelo tem que vir vazio
            return 'tipo1';
    	}
    }



    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados($pg=null){
        $tipo = $this->detectTipo();
        $c='getDados_'.$tipo;
        if($tipo=='1'){
             return $this->$c($pg);
        }else{
             return $this->$c();
        }

    }

    /**
     * Retorna os dados do premio
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getPremio($data){
        $tipo = $this->detectTipo();
        $c='getPremio_'.$tipo;
        return $this->$c($data);
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo1($pg=null){
        //$pg = $this->getPagina1();
        //dd(123);
        //Teste no arquivo pdfs-exemplo/Bradesco Apólice.pdf
        $data=[];


        //captura o tipo da apólice - para verificar se é do tipo automovel
        $n = $this->checkRamo();

        if(!$n){//quer dizer que não achou ramo
            //portanto procura o valor do ramo para que o texto apareça na tela de erro desse registro
            $n = $this->getX1(['start'=>'Ramo SUSEP','return_type'=>'next','count'=>2]);

            return ['success'=>false,'msg'=>'Ramo inválido: '.$n,'data'=>[],'ignore'=>true,'code'=>'read02'];
        }
        $data['apolice_prod_ref']=$n;

        //dados do corretor
        $data['corretor_nome'] =	$this->getX1(['start'=>'Código SUSEP','return_type'=>'prev','page'=>[$pg+4,$pg+5]]);//nome do corretor
        $data['corretor_susep'] =	$this->getX1(['start'=>'Código SUSEP','return_type'=>'next','page'=>[$pg+4,$pg+5],'cb'=>function($v){ return explode(" ",ltrim($v,"0"))[0]; }]);//código susep
        if(!$data['corretor_susep'])$data['corretor_susep'] = TextUtility::getSearchText($this->text,'Código SUSEP','value');
        if(strlen($data['corretor_susep'])>10){
            $blocktext = $block_text = TextUtility::getPartOfStr($this->text, ['start'=>'Dados do Corretor']);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Código CPD']);
            $data['corretor_susep'] = TextUtility::getSearchText($blocktext,'Código SUSEP','value');
            //dd($data['corretor_susep']);
        }

        //dados da apólice
        $n = stripos($this->text,'Demonstrativo do Endosso')!==false ? 'endosso' : null;
        if(!$n)$n = stripos($this->text,'ENDOSSO DE CANCELAMENTO')!==false ? 'endosso' : null;
        if(!$n){
            $blocktext = $this->getX1(['start'=>'item','return_type'=>'next']);
            $x = explode('.', $blocktext);
            if(trim($x[5]>0)){
                $n='endosso';
            }
        }
        if(!$n)$n = 'apolice';
        $data['data_type'] = $n;
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


        $data['seguradora_doc'] =$this->getX1(['start'=>'Processo SUSEP','return_type'=>'prev2']);
        if(!ValidateUtility::isCNPJ($data['seguradora_doc']))$data['seguradora_doc'] = TextUtility::getSearchText($this->text,'COMPANHIA DE SEGUROS, CNPJ','value');


        $data['produto_nome'] =$this->getX1(['start'=>'Bradesco Seguro Auto']);
        $data['proposta_num'] =$this->getX1(['start'=>'Data da Emissão','return_type'=>'next3']);
        $data['apolice_num'] =$this->getX1(['start'=>'Data da Emissão','return_type'=>'next']);
        if(is_numeric(str_replace(['H','-','R','U'], '', $data['apolice_num']))==false){//número inválido
            $n = TextUtility::getSearchText($this->text,'Número da apólice','value');
            $data['apolice_num'] = ltrim(explode(".",$n)[3],'0');
        }
        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = (string)(int)$data['apolice_num'];
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num_quiver'], '0');

        $data['data_emissao'] =$this->getX1(['start'=>'Renovação da Apólice N','return_type'=>'prev']);
        if(!$data['data_emissao'])$data['data_emissao'] = TextUtility::getSearchText($this->text,'Data da Emissão','value');


        $data['apolice_re_num'] =$this->getX1(['start'=>'Renovação da Apólice N','return_type'=>'next']);
        //$data['inicio_vigencia'] =$this->getX1(['start'=>'Vigência do Seguro','return_type'=>'prev2','cb'=>function($v){ return explode(" ",$v)[10];}]);

        $n=trim($this->getX1(['start'=>'das 24:00 horas do dia']));
        if(empty($n))$n=trim($this->getX1(['start'=>'das  24:00  horas  do  dia']));
        $n=trim(str_replace(['  ','das 24:00 horas do dia'],[' ',''],$n));
        if(!empty($n))$n=explode(" ",$n)[0];
        if(ValidateUtility::isDate($n)){
            $data['inicio_vigencia']=$n;
        }else{
            $n=trim($this->getX1(['start'=>'das 24:00 horas do dia','return_type'=>'next']));//a vigência está na linha seguinte
            if(ValidateUtility::isDate($n)){
                $data['inicio_vigencia']=$n;
            }else{
                $data['inicio_vigencia']='';//deixa vazio para análise futura do programador
            }
        }

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'das 24:00 ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Nome']);

        $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'Nome','datebr',['side'=>'left']);
        //dd($data['termino_vigencia']);
        //dados do segurado
        $text_dados_segurado = $this->getX1(['start'=>'Dados do segurado','split'=>false]);
        $data['segurado_nome']=$this->getX1(['start'=>'Vigência do Seguro','return_type'=>'next3']);
        if(!$data['segurado_nome']){
            $data['segurado_nome'] = $this->getX1(['end'=>'Data de Nascimento','split'=>false,'remove'=>['Dados do segurado','Data de Nascimento','_','Nome']],$text_dados_segurado);
        }

        $data['segurado_doc']=$this->getX1(['start'=>'CPF/CNPJ ','return_type'=>'next']);
        if(!$data['segurado_doc']){
            $data['segurado_doc'] = TextUtility::getSearchText($text_dados_segurado,'CPF/CNPJ','value');
        }

        $data['tipo_pessoa']= FormatUtility::removeAcents($this->getX1(['start'=>'Tipo de Pessoa','return_type'=>'next']));

        return ['success'=>true,'data'=>$data];

    }



    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo2(){
        //Teste no arquivo pdfs-exemplo/Bradesco Apólice.pdf
        $data=[];

        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        //dd($data['apolice_prod_ref']);
         if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //dados do corretor
        $n=$this->getX1(['start'=>'nome','end'=>'Código CPD','split'=>false,'page'=>[7,8]]);
        $n = TextUtility::getPartOfStr($n,['start'=>'nome','split'=>chr(10),'return_type'=>'next']);//nome do corretor
        if(!$n){
            $n=$this->getX1(['start'=>'Dados do segurado','split'=>false,'remove'=>'Dados do segurado']);
            $n=TextUtility::getPartOfStr($n,['start'=>'nome','split'=>chr(10),'return_type'=>'next']);//nome do corretor
        }
        $data['corretor_nome'] = $n;

        $data['corretor_susep'] =	(string)(int)trim($this->getX1(['start'=>'Código SUSEP','end'=>'Código CPD',//código susep //obs: é redirerado os zeros a esquerda
            'split'=>false,'remove'=>['{page-end:7}','{page-start:8}','Código SUSEP','Código CPD']]));

        //dados da apólice

        $n = stripos($this->text,'Demonstrativo do Endosso')!==false ? 'endosso' : null;
        if(!$n)$n = stripos($this->text,'ENDOSSO DE CANCELAMENTO')!==false ? 'endosso' : null;
        if(!$n)$n = 'apolice';
        $data['data_type'] = $n;

        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        $data['seguradora_doc'] =$this->getX1(['start'=>'Processo SUSEP','return_type'=>'prev','cb'=>function($v){ return explode(" ",$v)[6]??'';}]);
        if(empty($data['seguradora_doc'])){
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'Companhia de Seguros CNPJ:','end'=>'Nº do processo']);
            $data['seguradora_doc'] = TextUtility::getSearchText($n,'CNPJ:','cnpj',['sanitize'=>false]);
            //dd($n,$this->text);
        }
        //dd($data['seguradora_doc']);

        $data['produto_nome'] =$this->getX1(['start'=>'Bradesco Seguro Auto']);
        $data['proposta_num'] =$this->getX1(['start'=>'Data da Emissão','return_type'=>'prev']);
        $data['apolice_num'] =$this->getX1(['start'=>'Número da apólice','return_type'=>'next']);

        //atualizado para ser capturado pela função numQuiverConfig()
        //$n  = explode(".", $data['apolice_num'])[3];
        //$data['apolice_num_quiver'] = ltrim($n, '0');

        $data['data_emissao'] =$this->getX1(['start'=>'Data da Emissão','return_type'=>'next']);
        $data['apolice_re_num'] =$this->getX1(['start'=>'Renovação da apólice','return_type'=>'next']);
        $data['inicio_vigencia'] =TextUtility::getPartOfStr($this->text,['start'=>'Vigência', 'remove'=>chr(10),'end'=>'Cosseguro','cb'=>function($v){ return explode(" ",$v)[6];}]);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'das 24:00 ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'ramo']);
        $data['termino_vigencia'] = TextUtility::getSearchText($blocktext,'ramo','datebr',['side'=>'left']);
        //dd($data['termino_vigencia']);

        //dados do segurado
        $data['segurado_nome']=$this->getX1(['start'=>'Dados do segurado','return_type'=>'next3']);
        $data['segurado_doc']=$this->getX1(['start'=>'CPF/CNPJ ','return_type'=>'next']);


        $data['tipo_pessoa'] =$this->getX1(['start'=>'Tipo de Pessoa','return_type'=>'next']);
        $data['tipo_pessoa'] = in_array(strtoupper($data['tipo_pessoa']),['JURÍDICA','JURIDICA'])?'JURIDICA':'FISICA';




        return ['success'=>true,'data'=>$data];
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo3(){// residencial
        //Teste no arquivo pdfs-exemplo/Bradesco Apólice.pdf
        $data=[];

        //captura o tipo da apólice - para verificar se é do tipo residencial
        $data['apolice_prod_ref'] = $this->checkRamo();

         if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //dados do corretor
        $n =$this->getX1(['start'=>'Dados do Corretor','end'=>'Código SUSEP','split'=>false]);
        $n = FormatUtility::sanitizeBreakText($n);
        $n = trim(TextUtility::getPartOfStr($n, ['start'=>'Corretor Corretor','end'=>'Código SUSEP','remove'=>['Corretor Corretor','Código SUSEP']]));//nome do corretor
        $data['corretor_nome'] = $n;

        $data['corretor_susep'] =	(string)(int)trim($this->getX1(['start'=>'Código SUSEP','end'=>'Código CPD',//código susep //obs: é redirerado os zeros a esquerda
            'split'=>false,'remove'=>['{page-end:7}','{page-start:8}','Código SUSEP','Código CPD']]));

        //dados da apólice

        $n = stripos($this->text,'Demonstrativo do Endosso')!==false ? 'endosso' : null;
        if(!$n)$n = stripos($this->text,'ENDOSSO DE CANCELAMENTO')!==false ? 'endosso' : null;
        if(!$n)$n = 'apolice';
        $data['data_type'] = $n;

        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Companhia Emissora']);

        $data['seguradora_doc'] = TextUtility::getSearchText($blocktext,'Código 531-2','cnpj',['side'=>'left']);

        if(empty($data['seguradora_doc'])){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'531-2 BRADESCO','end'=>'Nome do ']);
            $data['seguradora_doc'] = TextUtility::getSearchText($blocktext,'531-2','cnpj',['side'=>'right']);
        }



        //dd($data['seguradora_doc'],$blocktext,$this->text);
        $data['produto_nome'] =$this->getX1(['start'=>'BRADESCO SEGURO RESIDENCIAL']);
        $data['proposta_num'] =$this->getX1(['start'=>'Data da Emissão','return_type'=>'next2']);
        $data['apolice_num'] =$this->getX1(['start'=>'Data da Emissão','return_type'=>'next']);

        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim( $data['apolice_num'], '0');

        $data['data_emissao'] =TextUtility::getSearchText($this->text,'Data da Emissão','datebr',['side'=>'right']);
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Apólice Anterior','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Rubrica']);
        $data['apolice_re_num'] =$this->getX1(['start'=>'Anterior','return_type'=>'next2'],$blocktext);
        if($data['apolice_re_num']=='Número'){
            $data['apolice_re_num']='';
        }
        $data['inicio_vigencia'] =TextUtility::getSearchText($this->text,'horas do dia','datebr',['side'=>'right']);
        $data['termino_vigencia'] = TextUtility::getSearchText($this->text,' às 24:00 horas do','datebr',['side'=>'right']);

        //dados do segurado
        $data['segurado_nome']=$this->getX1(['start'=>'Endereço de Correspondência','return_type'=>'prev']);
        $data['segurado_doc']=$this->getX1(['start'=>'CPF/CNPJ ','return_type'=>'next']);
        //dd(ValidateUtility::isCPF($data['segurado_doc']));
        if(ValidateUtility::isCPF($data['segurado_doc'])){
            $data['tipo_pessoa'] ='FISICA';
        }else{
            $data['tipo_pessoa'] ='JURIDICA';
        }
        return ['success'=>true,'data'=>$data];
    }

    /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo1($data){

        //forma de pagamento
        $r=PgtoData::getPgtoAuto(true,$data,$this->validate_premio_margem,['full_text'=>$this->text,'thisClass'=>$this]);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo de premio','end'=>'tipo de Cobranca','sanitize'=>true]);
        //dd($blocktext);
        $r=PgtoData::getFielsPremioAdd($blocktext,$data['fpgto_premio_total'],['get_juros'=>false]);
        //dd($data);
        $data = $data + $r;
        //dd($this->getData_veiculo( $this->getX1(['start'=>'Marca/Tipo Veículo','end'=>'VALOR MERCADO REFERENCIADO','split'=>false]), $data));

        return $data;
    }

    public function getPremio_tipo2($data){

         //forma de pagamento
       //dd($data);
       //dd(123);
       PgtoData::getPgtoAuto(true,$data,$this->validate_premio_margem,['full_text'=>$this->text,'thisClass'=>$this]);
       //dd($data);
       $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premio auto:','end'=>'formas de pagamento ','sanitize'=>true]);


       if(!$blocktext){
           $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'adicional de fracionamento:','end'=>'premio total: ','sanitize'=>true]);
       }

       //dd($blocktext);
       $r=PgtoData::getFielsPremioAdd($blocktext,$data);
       // dd($data);
       $data = $data + $r;

        return $data;
    }

    public function getPremio_tipo3($data){//utilizado no residencial
        //forma de pagamento
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'forma de pagamento ','sanitize'=>true]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'local de risco','sanitize'=>true]);
        PgtoData::getPgtoAuto($blocktext,$data,$this->validate_premio_margem,['full_text'=>$this->text,'thisClass'=>$this]);


        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Pagamento','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Datas de Vencimento ','sanitize'=>false]);

        $data['fpgto_tipo']=PgtoData::getPgtoTipo($blocktext);
        if($data['fpgto_tipo']==''){
            $data['fpgto_tipo']='boleto';
        }

        $text_debito = TextUtility::getPartOfStr($this->text, ['start'=>'Débito C/C ','sanitize'=>false]);
        $text_debito = TextUtility::getPartOfStr($text_debito, ['end'=>'Agência ','sanitize'=>false]);
        $text_debito = TextUtility::getSearchText($text_debito,'BRADESCO S.A. ','value',['side'=>'right']);
        if($text_debito!='Agência'){//verifica se é debito em conta
            $data['fpgto_tipo']='debito';
        }
        //dd($text_debito);
        $n = PgtoData::getPgtoCode($data['fpgto_tipo']);
        $data['fpgto_tipo']= $n[0];
        $data['fpgto_tipo_code']=$n[1];

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DEMONSTRATIVO DE PRÊMIO','sanitize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Dados do Corretor','sanitize'=>false]);
        $blocktext = str_replace('.', '', $blocktext);
        $n=PgtoData::getArrayFromData($data);
        //$data['fpgto_premio_total'] = PgtoData::getPremioTotalParcela($blocktext,$n['valor'],$this->validate_premio_margem);
        //dd($data);
        //if(!$data['fpgto_premio_total']){
         $data['fpgto_premio_total'] = TextUtility::getSearchText($this->text,'TOTAL:','number_formated',['side'=>'right']);
       // }


       //dd($data,$blocktext,$this->text);
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'DEMONSTRATIVO DE PRÊMIO','sanitize','end'=>'Dados de Cobrança','sanitize'=>false]);
        $premio_liqui = TextUtility::getSearchText($this->text,'Prêmio Tarifa:','number_formated',['side'=>'right']);
        $adicional = TextUtility::getSearchText($this->text,'Adicional de Fracionamento:','value',['side'=>'right']);
        $desc = TextUtility::getSearchText($this->text,'Desconto:','value',['side'=>'right']);
        $iof = TextUtility::getSearchText($this->text,'IOF:','value',['side'=>'right']);

        if($adicional=='Custo'){
             $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'premio_liquido'=>$premio_liqui,'adicional'=>'0,00']);

        }else{
            $blocktext = TextUtility::getPartOfStr($blocktext, ['start'=>'Fracionamento:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Custo','sanitize'=>false]);
            $adicional = TextUtility::getSearchText($this->text,'Adicional de Fracionamento:','value',['side'=>'right']);
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'premio_liquido'=>$premio_liqui,'adicional'=>$adicional]);
        }

        if($desc=='Adicional'){
             $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'premio_liquido'=>$premio_liqui,'adicional'=>'0,00','desc'=>'0,00']);

        }else{
            $blocktext = TextUtility::getPartOfStr($blocktext, ['start'=>'Fracionamento:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Custo','sanitize'=>false]);
            $adicional = TextUtility::getSearchText($this->text,'Adicional de Fracionamento:','value',['side'=>'right']);
            $desconto  = TextUtility::getSearchText($this->text,'Desconto:','value',['side'=>'right']);
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'premio_liquido'=>$premio_liqui,'adicional'=>$adicional,'desc'=>$desconto]);
        }

        if($r["fpgto_iof"]==''){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Fracionamento:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Custo','sanitize'=>false]);
            $adicional = TextUtility::getSearchText($this->text,'Adicional de Fracionamento:','value',['side'=>'right']);
            $desconto  = TextUtility::getSearchText($this->text,'Desconto:','value',['side'=>'right']);
            $iof  = TextUtility::getSearchText($this->text,'IOF:','value',['side'=>'right']);
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'premio_liquido'=>$premio_liqui,'adicional'=>$adicional,'desc'=>$desconto,'iof'=>$iof]);

        }else{
            $r=PgtoData::getFielsPremioAdd($blocktext,$data,['tem_juros'=>false,'premio_liquido'=>$premio_liqui,'adicional'=>'0,00','desc'=>'0,00']);
        }

        $data = $data + $r;
        //dd($data);
        if($data['fpgto_tipo']=='cartao'){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Demais Parcelas','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Bradesco Seguro','sanitize'=>false]);
            $n_parc  = TextUtility::getSearchText($this->text,'DE CREDITO EM','number',['side'=>'right']);
            $data['fpgto_n_prestacoes']=$n_parc;

            if($data['fpgto_n_prestacoes']=='01'){
                $data['fpgto_n_prestacoes'] = '1';
                $data['fpgto_valorparc_1'] = $data['fpgto_premio_total'];
                $data['fpgto_datavenc_1'] = $this->getDate1aParc('cartao',$data);
            }else{
                //dd($data['fpgto_n_prestacoes']);
                 $r = PgtoData::makeTable3($data['fpgto_premio_total'], $data['fpgto_n_prestacoes'], $this->getDate1aParc('cartao',$data));
                  $data = $data + $r;
            }

        }


        if($data['fpgto_tipo']=='boleto' || $data['fpgto_tipo']=='debito'){

            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Forma de Pagamento','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Bradesco Seguro','sanitize'=>false]);
            $blocktext = str_replace('00PAGAMENTO A VISTA', 'PAGAMENTO A VISTA', $blocktext);

            if($blocktext==''){

                $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Este bilhete foi quitado pela CCB Nº','sanitize'=>false]);
                $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Sob pena de','sanitize'=>false]);

                $data['fpgto_n_prestacoes'] = '1';
                $data['fpgto_valorparc_1'] = TextUtility::getSearchText($blocktext,'valor de R$','number_formated',['side'=>'right']);
                $data['fpgto_datavenc_1'] = $this->getDate1aParc('cartao',$data);

                /* Estava em desenvolvimento para o modelo Bilhete Residencial - por enqunato tratado como exceção
                    $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio Líquido Total (R$)','sanitize'=>false]);
                    $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Franquias','sanitize'=>false]);
                    $data['fpgto_premio_liquido'] = TextUtility::getSearchText($blocktext,'Prêmio Líquido Total','number_formated',['side'=>'right']);
                    $data['fpgto_iof'] = TextUtility::getSearchText($blocktext,'IOF','number_formated',['side'=>'right']);
                    $data['fpgto_premio_total'] = TextUtility::getSearchText($blocktext,'Prêmio Total (R$)','number_formated',['side'=>'right']);
                    $data['fpgto_premio_liq_serv'] = '0,00';
                    $data['fpgto_custo'] = '0,00';
                    $data['fpgto_adicional'] = '0,00';
                    $data['fpgto_juros'] = '0,00';
                    $data['fpgto_juros_md'] = '0,00';
                    $data['fpgto_desc'] = '0,00';

                */

            }else{

                if(strpos($blocktext, 'PAGAMENTO A VISTA' )){
                    $data['fpgto_n_prestacoes'] = '1';
                    $data['fpgto_valorparc_1'] = TextUtility::getSearchText($this->text,'VALOR R$','number_formated',['side'=>'right']);
                    $data['fpgto_datavenc_1'] = TextUtility::getSearchText($this->text,'Demais Parcelas','datebr',['side'=>'right']);

                    if($data['fpgto_datavenc_1']==''){
                        $data['fpgto_datavenc_1'] = $this->getDate1aParc('cartao',$data);
                    }

                }else{
                    //dd(123);
                    $data['fpgto_n_prestacoes']=TextUtility::getSearchText($this->text,'PRESTAÇÕES','value',['side'=>'left']);
                    $venc_parcelas=TextUtility::getSearchText($blocktext, '', 'datebr',['limit'=>false]);
                    $blocktext = str_replace(')', ' )', $blocktext);
                    //dd($blocktext);
                    $valores=[$data['fpgto_n_prestacoes']];
                    $data['fpgto_dem_prestacao_valor'] = TextUtility::getSearchText($blocktext,'DEMAIS','number_formated',['side'=>'right']);

                    for($i=0;$i<$data['fpgto_n_prestacoes'];$i++){
                        $valores[$i] = $data['fpgto_dem_prestacao_valor'];
                    }

                    $r = PgtoData::makeTable($data['fpgto_n_prestacoes'], $venc_parcelas, $valores);

                    $data = $data + $r;
                }
            }

        }
      // dd(is_numeric($data['fpgto_adicional']));
        if(empty($data['fpgto_adicional']) || $data['fpgto_adicional']=="" || is_numeric($data['fpgto_adicional'])==false){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Adicional de Fracionamento:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Circ ','sanitize'=>false]);
            $n = TextUtility::getSearchText($blocktext,'Fracionamento:','number_formated',['side'=>'right']);

            if(empty($n)){
                $data['fpgto_adicional']='0,00';
            }else{
                $data['fpgto_adicional']=$n;
            }
        }
        if($data['fpgto_adicional']=='0,00' ){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Adicional de Fracionamento:','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'Custo','sanitize'=>false]);
            $n = TextUtility::getSearchText($blocktext,'Fracionamento:','number_formated',['side'=>'right']);

            if(empty($n)){
                $data['fpgto_adicional']='0,00';
            }else{
                $data['fpgto_adicional']=$n;
            }
        }

        if($data['fpgto_desc']=="Adicional"){
            $data['fpgto_desc']='0,00';
        }

        $data = $data + PgtoData::addFields1($data);

        if($data['fpgto_tipo']=='cartao'){
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Datas de Vencimento','sanitize'=>false]);
            $blocktext = TextUtility::getPartOfStr($blocktext, ['end'=>'PARCELA ','sanitize'=>false]);
            //dd(strpos($blocktext,'EM 01'));
            if(strpos($blocktext,'EM 01')==false){
                if(is_numeric($data['fpgto_valorparc_2'])){
                    $data['fpgto_valorparc_2'] = FormatUtility::numberFormat($data['fpgto_valorparc_2']);
                    $data['fpgto_dem_prestacao_valor'] = FormatUtility::numberFormat($data['fpgto_dem_prestacao_valor']);
                }
            }
        }


        return $data;

    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
