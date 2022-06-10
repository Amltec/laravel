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
trait sompoInsurer{

    //método de inicialização
    public function initInsurer(){

         $this->pdf_engine = 'ait_xpdfr'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
         $this->process_opt=[];

         //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
         $this->validate_iof_margem=0.11;
    }


      /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'3102709932',
            'not_dot_traits'=>true,
            'not_zero_left'=>true
        ];
    }

    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo1(){
        $this->text = FormatUtility::sanitizeBreakText($this->text);//retira somente as quebras de linha

        $data=[];
        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n= $this->text;//verifica se é frota
        //dd($n);
        if(strpos($n,'AUTO FROTA')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        //verifica se é via do corretor
        if(strpos($n,'VIA CORRETOR')!==false){
            return ['success'=>false,'msg'=>'Não processado -  Via Corretor','data'=>[],'ignore'=>true,'code'=>'read16'];
        }

        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'ENDOSSO Nº','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        //dd($data['data_type']);
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        //*** dados do corretor ***
        //$corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'dados do corretor','end'=>'E por ser verdade','remove'=>'dados do corretor']);
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'SEU CORRETOR DE SEGUROS','end'=>'TEL.']);

        $data['corretor_susep'] = TextUtility::getSearchText($corretor_text,'SUSEP:','number',['side'=>'right']);
        $n=TextUtility::getPartOfStr($corretor_text,['end'=>'www.sompo','remove'=>['www.sompo','SEU CORRETOR DE SEGUROS']]);
        $n=trim(str_replace('1','',$n));//possível número da página do pdf junto do nome
        $data['corretor_nome'] = $n;


        //*** dados da apólice ***
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'SOMPO SEGUROS S.A. CNPJ','cnpj',['sanitize'=>false]);
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA APÓLICE','end'=>'DADOS DO SEGURADO']);
        //dd($data['seguradora_doc']);
        $data['apolice_num'] = TextUtility::getSearchText($this->text,'ITEM  PROPOSTA  RENOVA Nº','numberstr');

        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);
        //$data['apolice_num_quiver'] = str_replace('-','',$data['apolice_num_quiver']);

        $n = TextUtility::getSearchText($this->text,'INÍCIO DA VIGÊNCIA - A PARTIR',function($v){
            $x=strlen($v);
            if($x>=4 && $x<=10 && (int)$v!=1 && ValidateUtility::isNumberStr($v))return $v;
        },['max_words'=>2]);
        $data['proposta_num'] = $n;

        $data['apolice_re_num'] = TextUtility::getSearchText($apolice_text,'RMINO DE VIGÊNCIA - ATÉ','numberstr');
        if($data['apolice_re_num']=='0000000')$data['apolice_re_num']='';
        $data['inicio_vigencia'] = TextUtility::getSearchText($apolice_text,'CÓDIGO IDENTIFICAÇÃO','datebr',['side'=>'left','max_words'=>2]);
        $data['termino_vigencia'] = TextUtility::getSearchText($apolice_text,'CÓDIGO IDENTIFICAÇÃO','datebr',['max_words'=>7]);

        $n=TextUtility::getPartOfStr($this->text, ['start'=>'desconsidere essa mensagem','sanitize'=>true]);
        $data['data_emissao'] = TextUtility::getDateExtenso($n,'datebr');

        if(ValidateUtility::isDate($data['data_emissao'])==false){
            $n = TextUtility::getPartOfStr($this->text, ['start'=>'neste ato,']);
            $n = TextUtility::getPartOfStr($n, ['end'=>'SOMPO SEGUROS']);
            $n = TextUtility::getDateExtenso($n);
            $n = TextUtility::getDateExtenso($n, 'datebr');
            $data['data_emissao'] = $n;
            //dd($n);
        }



        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURADO','end'=>'DADOS DO VEÍCULO']);
        $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'NOME','end'=>'ENDEREÇO','remove'=>['NOME','ENDEREÇO']]);
        $data['segurado_nome'] = substr($data['segurado_nome'], 0,50);
        $tmp = TextUtility::getPartOfStr($this->text,['start'=>'NOME:','end'=>'TELEFONE:']);
        $data['segurado_doc']= TextUtility::getSearchText($tmp,'CPF/CNPJ:','document');
        $n=$data['segurado_doc'];$data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo1($data){
        //dd('rsrs');
        //*** dados do pagamento ***
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'FORMA DE PAGAMENTO']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'FORMA DE PAGAMENTO','end'=>'DADOS']);

        $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];

        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'disposto no quadro']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'R$','end'=>'Taxa']);
        $pgto_text = str_replace(['0, ','1, ','2, ','3, ','4, ','5, ','6, ','7, ','8, ','9, '],['0,','1,','2,','3,','4,','5,','6,','7,','8,','9,'], $pgto_text);

        $r = PgtoData::getTableVencParc_mixed($pgto_text);

        if(!$r){//não conseguiu criar a tabela de valores,
            //tenta novamente desconsiderando as datas
            $r = PgtoData::getTableVencParc_noDate($pgto_text, $this->getDate1aParc($data['fpgto_tipo'],$data),null,null,null,false,$this->validate_premio_margem);
        }
        if(!$r){//não conseguiu, é provável que a var $pgto_text esteja com muito texto, portanto, tenta limita o conteúdo para tentar novamente
            $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'APÓLICE DE SEGURO AUTO']);
            $r = PgtoData::getTableVencParc_noDate($pgto_text, $this->getDate1aParc($data['fpgto_tipo'],$data),null,null,null,false,$this->validate_premio_margem);
        }

        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);


        $block_text = FormatUtility::sanitizeAllText($this->text);
        $dados_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo do','end'=>'Fica entendido e ajustado','sanitize'=>true]);

        //captura os valores conforme encontrada a combinação abaixo
        if(strpos($dados_pgto,'casco rcfv danos materiais rcfv danos corporais rcfv danos morais app morte app invalidez permanente premio liquido imposto (i.o.f.) premio total')!==false){
            //espera uma matriz de 10 resultados
            $dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
            $data['fpgto_premio_liquido'] = $dados_pgto[6]??'';
            $data['fpgto_custo'] = '0,00';
            $data['fpgto_adicional'] = '0,00';
            $data['fpgto_iof'] = $dados_pgto[7]??'';
            $data['fpgto_premio_total'] = $dados_pgto[8]??'';
            $data['fpgto_juros'] = '0,00';
            $data['fpgto_premio_liq_serv'] = '0,00';
            $data['fpgto_juros_md'] = '0,00';
        }else if(strpos($dados_pgto,'casco rcfv danos materiais rcfv danos corporais rcfv danos morais app morte app invalidez permanente premio liquido juros de fracionamento imposto (i.o.f.) premio total')!==false){
            $dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
            //dd($dados_pgto);
            $data['fpgto_premio_liquido'] = $dados_pgto[6]??'';
            $data['fpgto_custo'] = '0,00';
            $data['fpgto_adicional'] = $dados_pgto[7]??'';
            $data['fpgto_iof'] = $dados_pgto[8]??'';
            $data['fpgto_premio_total'] = $dados_pgto[9]??'';
            $data['fpgto_juros'] = '0,00';
            $data['fpgto_premio_liq_serv'] = '0,00';
            $data['fpgto_juros_md'] = '0,00';
        }else if(strpos($dados_pgto,'casco despesa extraordinaria rcfv danos materiais rcfv danos corporais rcfv danos morais app morte app invalidez permanente premio liquido imposto (i.o.f.) premio total')!==false){
            $dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
           // dd($dados_pgto);
            $data['fpgto_premio_liquido'] = $dados_pgto[7]??'';
            $data['fpgto_custo'] = '0,00';
            $data['fpgto_adicional'] = '0,00';
            $data['fpgto_iof'] = $dados_pgto[8]??'';
            $data['fpgto_premio_total'] = $dados_pgto[9]??'';
            $data['fpgto_juros'] = '0,00';
            $data['fpgto_premio_liq_serv'] = '0,00';
            $data['fpgto_juros_md'] = '0,00';
        }

        return $data;
    }


    /**
     * Retorna os dados do segurado
     * @return error:   [success,msg,data,ignore,code]
     *         ok:      [sucess,data]
     */
    public function getDados_tipo2(){
         $data=[];
        //dd(123);
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();

        //verifica se é frota
        if(stripos($this->text,'AUTO FROTA')!==false){
            return ['success'=>false,'msg'=>'Ignorado apólice do tipo frota','data'=>[],'ignore'=>true,'code'=>'read04'];
        }

        //verifica se é via do corretor
        if(stripos($this->text,'VIA CORRETOR')!==false){
            return ['success'=>false,'msg'=>'Não processado -  Via Corretor','data'=>[],'ignore'=>true,'code'=>'read16'];
        }

        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'ENDOSSO Nº','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        $tmp = trim(TextUtility::getSearchText($this->text,'ENDOSSO:','number',['max_words'=>1]));
        $data['data_type'] = $tmp?'endosso':'apolice';
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];


        //*** dados do corretor ***
        $corretor_text = substr(TextUtility::getPartOfStr($this->text, ['start'=>'DADOS CORRETOR']),0,280);
        $corretor_text = TextUtility::getPartOfStr($corretor_text, ['end'=>'SOMPO']);
        //dd( $corretor_text);

        $corretor_text = str_replace('DADOS CORRETOR','',$corretor_text);

        $data['corretor_nome'] = TextUtility::getPartOfStr($corretor_text, ['start'=>'CORRETOR','split'=>chr(10),'return_type'=>'next']);
        $data['corretor_susep'] = TextUtility::getSearchText($corretor_text,'SUSEP','number');


        //*** dados da apólice ***
        $n=\Illuminate\Support\Str::ascii( TextUtility::getSearchText($this->text0,'SOMPO SEGUROS CNPJ','cnpj') );
        $data['seguradora_doc']=$n;

        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA APÓLICE','end'=>'DADOS DO SEGURADO','remove'=>['DADOS DA APÓLICE','DADOS DO SEGURADO']]);
        if(!$apolice_text)$apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA APÓLICE','end'=>'FORMA DE PAGAMENTO','side_len'=>[0,10]]);
        $n = TextUtility::getSearchText($this->text,'PROPOSTA',function($v){
            $x=strlen($v);
            if($x>=4 && $x<=10 && (int)$v!=1 && ValidateUtility::isNumberStr($v))return $v;
        },['max_words'=>2]);
        $data['proposta_num'] = $n;

        $n = TextUtility::getSearchText($this->text,'APÓLICE DE SEGURO - N°','number');
        if(!$n)$n = TextUtility::getSearchText($this->text,'APÓLICE DE SEGURO − N°','number');//obs: este texto de pesquisa é diferente do texto acima
        $data['apolice_num']=$n;

        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);
        //$data['apolice_num_quiver'] = str_replace('-','',$data['apolice_num_quiver']);

        $data['apolice_re_num'] = TextUtility::getSearchText($apolice_text,'RENOVA Nº','number');
        if($data['apolice_re_num']=='0000000')$data['apolice_re_num']='';
        $data['inicio_vigencia'] = TextUtility::getSearchText($apolice_text,'INÍCIO DE VIGÊNCIA','datebr',['max_words'=>10]);
        $data['termino_vigencia'] = TextUtility::getSearchText($apolice_text,'TÉRMINO DE VIGÊNCIA','datebr',['max_words'=>10]);
        $data['data_emissao'] = TextUtility::getSearchText($this->text,'DATA EMISSÃO','datebr');
        if(empty($data['data_emissao'])){
            $data['data_emissao'] = TextUtility::getSearchText($this->text,'DATA/HORA','datebr');
        }

        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURADO','end'=>'DADOS DO VEICULO','remove'=>['DADOS DO SEGURADO','DADOS DO VEICULO']]);
        if(!$segurado_text)$segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DO SEGURADO','end'=>'CÓDIGO FIPE']);
        $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'NOME','end'=>'ENDEREÇO','remove'=>['NOME','ENDEREÇO']]);
        $data['segurado_nome'] = substr($data['segurado_nome'], 0,50);
        $data['segurado_doc']= TextUtility::getSearchText($segurado_text,'CPF/CNPJ','document');
        $n=$data['segurado_doc'];$data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }

     /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo2($data){
        //*** dados do pagamento ***
        //dd(123);
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'FORMA DE PAGAMENTO']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'FORMA DE PAGAMENTO','end'=>'DADOS']);
        $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];

        if($data['fpgto_tipo']==false){
            $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'FORMA DE PAGAMENTO']);
            $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'FORMA DE PAGAMENTO','end'=>'NOME']);
            $pgto_text = str_replace('CARTÃO', 'cartao', $pgto_text);
            //dd($pgto_text);
            $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);
            $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];
        }

        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'disposto no quadro']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'Em atendimento']);

        if(strpos($pgto_text,'Taxa')!==false){
            $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'disposto no quadro','end'=>'Taxa']);
        }

        $r = PgtoData::getTableVencParc_mixed($pgto_text);
        //dd($pgto_text,$r);
        if(!$r){//não conseguiu criar a tabela de valores,
            //tenta novamente desconsiderando as datas
            $r = PgtoData::getTableVencParc_noDate($pgto_text, $this->getDate1aParc($data['fpgto_tipo'],$data),null,null,null,false,$this->validate_premio_margem);
        }

        if($r)$data = $data + $r;
        $data = $data + PgtoData::addFields1($data);
        $block_text = FormatUtility::sanitizeAllText($this->text);
        //dd($block_text);
        $dados_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo do','end'=>'fica entendido e ajustado','sanitize'=>true]);

        if($dados_pgto=='' || strpos($dados_pgto,'ajustado')!==true){
             $dados_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'demonstrativo do','end'=>'fica entendido','sanitize'=>true]);
        }
        $dados_pgto = str_replace('(1.o.f.)', '(i.o.f.)', $dados_pgto);
        //captura os valores conforme encontrada a combinação abaixo
        //dd($dados_pgto,$this->text);
        if(strpos($dados_pgto,'cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez despesas extras premio liquido imposto (i.o.f.) premio total')!==false){
            //espera uma matriz de 10 resultados
            $dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
             //dd('1');
            $data['fpgto_premio_liquido'] = $dados_pgto[7]??'';
            $data['fpgto_custo'] = '0,00';
            $data['fpgto_adicional'] = '0,00';
            $data['fpgto_iof'] = $dados_pgto[8]??'';
            $data['fpgto_premio_total'] = $dados_pgto[9]??'';
            $data['fpgto_juros'] = '0,00';
            $data['fpgto_premio_liq_serv'] = '0,00';
            $data['fpgto_juros_md'] = '0,00';

        }else if(strpos($dados_pgto,'cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez premio liquido imposto (i.o.f.) premio total')!==false){
             //espera uma matriz de 10 resultados
            $dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
           // dd('2');
            $data['fpgto_premio_liquido'] = $dados_pgto[6]??'';
            $data['fpgto_custo'] = '0,00';
            $data['fpgto_adicional'] = '0,00';
            $data['fpgto_iof'] = $dados_pgto[7]??'';
            $data['fpgto_premio_total'] = $dados_pgto[8]??'';
            $data['fpgto_juros'] = '0,00';
            $data['fpgto_premio_liq_serv'] = '0,00';
            $data['fpgto_juros_md'] = '0,00';

        }else if(strpos($dados_pgto,'cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais premio liquido imposto (i.o.f.) premio total')!==false){

            $dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
           //dd('3');
            $data['fpgto_premio_liquido'] = $dados_pgto[4]??'';
            $data['fpgto_custo'] = '0,00';
            $data['fpgto_adicional'] = '0,00';
            $data['fpgto_iof'] = $dados_pgto[5]??'';
            $data['fpgto_premio_total'] = $dados_pgto[6]??'';
            $data['fpgto_juros'] = '0,00';
            $data['fpgto_premio_liq_serv'] = '0,00';
            $data['fpgto_juros_md'] = '0,00';

        }else{

            //provavelmente a string $dados_pgto está + ou - assim: "rcf-v danos materiais 594,88 rcf-v danos corporais 70,22" (texto valor texto valor);
            $dados_pgto_repl = $dados_pgto;
           // dd($dados_pgto);
            //retira todos os valores da string
            $valores=TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);
            foreach($valores as $valor){
                $dados_pgto_repl = str_replace($valor,'',$dados_pgto_repl);
            }

            $dados_pgto_repl = str_replace(['  ','  ','  ','complementaress'],[' ',' ',' ','complementares'],$dados_pgto_repl);
            //dd($dados_pgto_repl);



            if(
                strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) rcf-v danos materiaisrcf-v danos corporaisrcf-v danos moraisapp morteapp invalidezpremio liquidoimposto (i.o.f.)premio total')!==false
                ||
                strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez premio liquido imposto (i.o.f.) premio total')!==false
            ){

                //dd('4');
                $data['fpgto_premio_liquido'] = $valores[5]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[6]??'';
                $data['fpgto_premio_total'] = $valores[7]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';

            }else if(
                strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura cascorcf-v danos materiaisrcf-v danos corporaisrcf-v danos moraispremio liquidoimposto (i.o.f.)premio total')!==false
                ||
                strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais premio liquido imposto (i.o.f.) premio total')!==false || strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais premio liquido imposto (i.o.f.) premio total condicoes de pagamento/vencimentos/devolucao fica entendido e ajustado')!==false
            ){
              //dd('5');

                $data['fpgto_premio_liquido'] = $valores[4]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[5]??'';
                $data['fpgto_premio_total'] = $valores[6]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';

            }else if(
                strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura cascorcf-v danos materiaisrcf-v danos corporaisrcf-v danos moraisapp morteapp invalidezpremio liquidoimposto (i.o.f.)premio total')!==false
                ||
                strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez premio liquido imposto (i.o.f.) premio total')!==false
            ){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[6]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[7]??'';
                $data['fpgto_premio_total'] = $valores[8]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez despesas extras premio liquido imposto (i.o.f.) premio total')!==false){
               //dd('7');
                $data['fpgto_premio_liquido'] = $valores[7]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[8]??'';
                $data['fpgto_premio_total'] = $valores[9]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';

            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais kit gas premio liquido imposto (i.o.f.) premio total')!==false){
               //dd('8');
                $data['fpgto_premio_liquido'] = $valores[5]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[6]??'';
                $data['fpgto_premio_total'] = $valores[7]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';

            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) rcf-v danos materiais rcf-v danos corporais rcf-v danos morais premio liquido imposto (i.o.f.) premio total')!==false){
                //dd($valores);
                //dd('9');
                $data['fpgto_premio_liquido'] = $valores[3]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[4]??'';
                $data['fpgto_premio_total'] = $valores[5]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais despesas extras premio liquido imposto (i.o.f.) premio total')!==false){
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[5]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[6]??'';
                $data['fpgto_premio_total'] = $valores[7]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco  rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez premio liquido imposto (i.o.f.) premio total')!==false){
               // dd($valores);
                $data['fpgto_premio_liquido'] = $valores[6]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[7]??'';
                $data['fpgto_premio_total'] = $valores[8]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez carroc.madeira abert premio liquido imposto (i.o.f.) premio total')!==false){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[7]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[8]??'';
                $data['fpgto_premio_total'] = $valores[9]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez carroceria bau premio liquido imposto (i.o.f.) premio total')!==false){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[7]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[8]??'';
                $data['fpgto_premio_total'] = $valores[9]??'';
                $data['fpgto_juros'] = '0,00';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez despesas extras premio liquido juros de fracionamento imposto (i.o.f.) premio total')!==false){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[7]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[9]??'';
                $data['fpgto_premio_total'] = $valores[10]??'';
                $data['fpgto_juros'] = $valores[8]??'';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez premio liquido juros de fracionamento imposto (i.o.f.) premio total')!==false){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[6]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[8]??'';
                $data['fpgto_premio_total'] = $valores[9]??'';
                $data['fpgto_juros'] = $valores[7]??'';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'cobertura casco rcf-v danos materiais rcf-v danos corporais rcf-v danos morais premio liquido juros de fracionamento imposto (i.o.f.) premio total')!==false){ //dd('6');
               // dd($valores);
                $data['fpgto_premio_liquido'] = $valores[4]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[6]??'';
                $data['fpgto_premio_total'] = $valores[7]??'';
                $data['fpgto_juros'] = $valores[5]??'';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) rcf-v danos materiais rcf-v danos corporais rcf-v danos morais app morte app invalidez premio liquido juros de fracionamento imposto (i.o.f.) premio total ')!==false){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[5]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[7]??'';
                $data['fpgto_premio_total'] = $valores[8]??'';
                $data['fpgto_juros'] = $valores[6]??'';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }else if(strpos($dados_pgto_repl,'demonstrativo do premio / coberturas e servicos complementares (r$) rcf-v danos materiais rcf-v danos corporais rcf-v danos morais premio liquido juros de fracionamento imposto (i.o.f.) premio total')!==false){ //dd('6');
                //dd($valores);
                $data['fpgto_premio_liquido'] = $valores[3]??'';
                $data['fpgto_custo'] = '0,00';
                $data['fpgto_adicional'] = '0,00';
                $data['fpgto_iof'] = $valores[5]??'';
                $data['fpgto_premio_total'] = $valores[6]??'';
                $data['fpgto_juros'] = $valores[4]??'';
                $data['fpgto_premio_liq_serv'] = '0,00';
                $data['fpgto_juros_md'] = '0,00';
            }

        }
              return $data;
    }

    public function getDados_tipo3(){//utilizado para residencial
        // $this->text = FormatUtility::sanitizeBreakText($this->text);//retira somente as quebras de linha

        $data=[];
        //*** captura o tipo da apólice - para verificar se é do tipo automovel ***
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        $n= $this->text;//verifica se é frota


        //verifica se é via do corretor
        if(strpos($n,'VIA CORRETOR')!==false){
            return ['success'=>false,'msg'=>'Não processado -  Via Corretor','data'=>[],'ignore'=>true,'code'=>'read16'];
        }

        //verifica se é endosso
        $tmp = trim(TextUtility::getSearchText($this->text,'endosso:','number',['max_words'=>1]));
        if($tmp>0){
             $data['data_type'] = 'endosso';
        }else{
            $data['data_type'] = 'apolice';
        }

        //dd($data['data_type']);

        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'Apólice do tipo Endosso - não processado','ignore'=>true,'code'=>'read03'];

        //*** dados do corretor ***
        //$corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'dados do corretor','end'=>'E por ser verdade','remove'=>'dados do corretor']);
        $corretor_text = TextUtility::getPartOfStr($this->text, ['start'=>'SEU CORRETOR DE SEGUROS','end'=>'TEL.']);
        $data['corretor_susep'] = TextUtility::getSearchText($corretor_text,'SUSEP:','number',['side'=>'right']);
        $n=TextUtility::getPartOfStr($corretor_text,['end'=>'www.sompo','remove'=>['www.sompo','SEU CORRETOR DE SEGUROS']]);
        $n=trim(str_replace('1','',$n));//possível número da página do pdf junto do nome
        $data['corretor_nome'] = $n;


        //*** dados da apólice ***
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text,'Sompo Seguros S/A - CNPJ:','cnpj',['sanitize'=>false]);
        $apolice_text = TextUtility::getPartOfStr($this->text, ['start'=>'DADOS DA APÓLICE','end'=>'HAVENDO ALGUMA ']);
        $data['apolice_num'] = TextUtility::getSearchText($apolice_text,'Apólice:','number',['side'=>'right']);

        //atualizado para ser capturado pela função numQuiverConfig()
        //$data['apolice_num_quiver'] = ltrim($data['apolice_num'],0);
       // $data['apolice_num_quiver'] = str_replace('-','',$data['apolice_num_quiver']);
        $data['proposta_num'] = TextUtility::getSearchText($apolice_text,'Proposta:','number',['side'=>'right']);

        $data['apolice_re_num'] = TextUtility::getSearchText($this->text,'Renova n.o','numberstr');
        if($data['apolice_re_num']=='0000000')$data['apolice_re_num']='';
        $data['inicio_vigencia'] = TextUtility::getSearchText($this->text,'Início da vigência','datebr',['sanitize'=>false]);
        $data['termino_vigencia'] = TextUtility::getSearchText($this->text,'Término de vigência','datebr',['sanitize'=>false]);

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'Data Emissão:','end'=>'Código']);
        $n=TextUtility::getPartOfStr($blocktext, ['start'=>'Data Emissão:','sanitize'=>false]);
        $data['data_emissao'] = TextUtility::getDateExtenso($n,'datebr');

        //*** dados do segurado ***
        $segurado_text = TextUtility::getPartOfStr($this->text, ['start'=>'Nome:','end'=>'Cpf/Cnpj:']);
        $data['segurado_nome'] = TextUtility::getPartOfStr($segurado_text,['start'=>'Nome:','end'=>'Cpf/Cnpj:','remove'=>['Nome:','Cpf/Cnpj:']]);
        $data['segurado_nome'] = substr($data['segurado_nome'], 0,50);
        $tmp = TextUtility::getPartOfStr($this->text,['start'=>'NOME:','end'=>'TELEFONE:']);
        $data['segurado_doc']= TextUtility::getSearchText($tmp,'CPF/CNPJ:','document');
        $n=$data['segurado_doc'];$data['tipo_pessoa'] = ValidateUtility::isCPF($n) ? 'FISICA' : (ValidateUtility::isCNPJ($n)?'JURIDICA':'');

        return ['success'=>true,'data'=>$data];
    }

      /**
     * Retorna os dados do premio
     */
    public function getPremio_tipo3($data){//utilizado no residencial

        //*** dados do pagamento ***
        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'FORMA DE PAGAMENTO']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'FORMA DE PAGAMENTO','end'=>'DADOS']);
        $data['fpgto_tipo']=PgtoData::getPgtoTipo($pgto_text);
        $data['fpgto_tipo_code']=PgtoData::getPgtoCode( $data['fpgto_tipo'] )[1];

        $pgto_text = TextUtility::getPartOfStr($this->text, ['start'=>'dispositivo no quadro a seguir:']);
        $pgto_text = TextUtility::getPartOfStr($pgto_text, ['start'=>'seguir:','end'=>'Taxa']);
        $pgto_text = str_replace(['0, ','1, ','2, ','3, ','4, ','5, ','6, ','7, ','8, ','9, '],['0,','1,','2,','3,','4,','5,','6,','7,','8,','9,'], $pgto_text);

        $r = PgtoData::getTableVencParc_mixed($pgto_text);

        if(!$r){//não conseguiu criar a tabela de valores,
            //tenta novamente desconsiderando as datas
            $r = PgtoData::getTableVencParc_noDate($pgto_text, $this->getDate1aParc($data['fpgto_tipo'],$data),null,null,null,false,$this->validate_premio_margem);
        }
        if(!$r){//não conseguiu, é provável que a var $pgto_text esteja com muito texto, portanto, tenta limita o conteúdo para tentar novamente
            $pgto_text = TextUtility::getPartOfStr($pgto_text, ['end'=>'APÓLICE DE SEGURO AUTO']);
            $r = PgtoData::getTableVencParc_noDate($pgto_text, $this->getDate1aParc($data['fpgto_tipo'],$data),null,null,null,false,$this->validate_premio_margem);
        }

        if($r)$data = $data + $r;

        $data = $data + PgtoData::addFields1($data);


        $dados_pgto = TextUtility::getPartOfStr($this->text, ['start'=>'Prêmio líquido','end'=>'Renova n.o','sanitize'=>false]);
        $dados_pgto = str_replace('líquido total', 'líquido', $dados_pgto);
        $dados_pgto = str_replace('Total', 'Premio Total', $dados_pgto);
        //dd($dados_pgto,$this->text);

        //$dados_pgto = TextUtility::getSearchText($dados_pgto,'','number_formated',['limit'=>false]);

        $data['fpgto_premio_liquido'] = TextUtility::getSearchText($dados_pgto,'líquido','number_formated',['side'=>'right']);
        $data['fpgto_custo'] = TextUtility::getSearchText($dados_pgto,'Custo de','number_formated',['side'=>'right']);
        $data['fpgto_adicional'] = '0,00';
        $data['fpgto_iof'] = TextUtility::getSearchText($dados_pgto,'I.O.F','number_formated',['side'=>'right']);
        $data['fpgto_premio_total'] = TextUtility::getSearchText($dados_pgto,'Premio Total','number_formated',['side'=>'right']);
        $data['fpgto_juros'] = TextUtility::getSearchText($dados_pgto,'Juros de','number_formated',['side'=>'right']);
        $data['fpgto_premio_liq_serv'] = '0,00';
        $data['fpgto_juros_md'] = '0,00';
       //dd($dados_pgto);

        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

}
