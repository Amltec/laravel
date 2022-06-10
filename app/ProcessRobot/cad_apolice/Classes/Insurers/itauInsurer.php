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
trait itauInsurer{

    //método de inicialização
    public function initInsurer(){
        $this->pdf_engine = 'ait_tessrct'; //nome do interpretador de pdf (valores em  \App\Utilities\FilesUtility::readPDF)
        $this->text0='';
        $this->process_opt=[];
        //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
        $this->validate_iof_margem=0.06;
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
        //captura o tipo da apólice - para verificar se é do tipo automovel
        $data['apolice_prod_ref'] = $this->checkRamo();
        if(!$data['apolice_prod_ref'])return ['success'=>false,'msg'=>'Ramo inválido','data'=>[],'ignore'=>true,'code'=>'read02'];

        //verifica se é endosso
        $n = TextUtility::getSearchText($this->text0, 'Nº do Endosso','number');

        if(!$n)$n = TextUtility::getSearchText($this->text0, 'Nr. Endosso','number');
        if(!$n)$n = TextUtility::getSearchText($this->text0, 'apolice endossada:','number');

        $data['data_type'] = $n=='' || $n=='0'?'apolice':'endosso';
        if($data['data_type']=='endosso')return ['success'=>false,'data'=>[],'msg'=>'endosso','ignore'=>true,'code'=>'read03'];


        //dados da seguradora
        $data['seguradora_doc'] = TextUtility::getSearchText($this->text0, 'Processo SUSEP','numberstr');


        //dados do corretor
        $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'Corretor','end'=>'CEP']);
            $data['corretor_nome'] = FormatUtility::getPartOfStr($this->text,['start'=>'Nome:','end'=>'Telefone']);//nome do corretor
            $data['corretor_nome'] = trim(str_replace(['Nome:','Telefone'],'',$data['corretor_nome']));

            if(strpos($data['corretor_nome'],'Susep')!==false){
                $data['corretor_nome'] = explode('Susep',$data['corretor_nome']);
                $data['corretor_nome'] = $data['corretor_nome'][0];
            }
            $n=TextUtility::getSearchText($this->text0, 'susep oficial','numberstr');
            $n=str_replace('.','',$n);
            $data['corretor_susep'] = $n;


        //dados da apólice
        $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'da apólice:','end'=>'Endosso:']);
        $blocktext = trim(str_replace(['No do Endosso:'],'',$blocktext));
        //dd($blocktext);
            //número da apólice
            $n=$this->getX1(['start'=>'da apólice:','remove'=>'da apólice:'],$blocktext);
            $data['apolice_num'] = str_replace([' ','NºdoEndosso:'],'',$n);
            //dd($data['apolice_num'],$n);
            //$data['apolice_num_quiver'] = explode(' ',$n)[2]??'';

            //número da apólice anterior
            $n=$this->getX1(['start'=>'da apólice anterior:','remove'=>'da apólice anterior:'],$blocktext);
            $data['apolice_re_num'] =explode(' ',$n)[2]??'';

            //data emissão

            //dd($blocktext);
            //$data['data_emissao'] = TextUtility::getSearchText($blocktext, 'Data de emissão:','datebr');
            $data['data_emissao'] = TextUtility::getSearchText($this->text, 'emissão:','datebr');

            //num proposta
            $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'Proposta:','end'=>'da apólice']);
            $n = $this->getX1(['start'=>'Proposta:','remove'=>'Proposta:'],$blocktext);
            $n = str_replace(' ','',$n);
            $n = str_replace('N',' N',$n);
            $n = str_replace(['Nodaapóliceanterior:'],'',$n);
            $n = TextUtility::getSearchText($n,'N','number',['side'=>'left']);
            $data['proposta_num'] = $n;

            //vigência
            $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'Proposta:','end'=>'da apólice']);
            $n = $this->getX1(['start'=>'Vigência das 24h'],$blocktext);
            $data['inicio_vigencia'] = TextUtility::getSearchText($blocktext, '','datebr');
            $data['termino_vigencia'] = TextUtility::getSearchText($blocktext, '','datebr',['limit'=>2])[1]??'';

            //C.I
             $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'da apólice:','end'=>'Classe']);
             $blocktext = str_replace('Cl:','ci:',$blocktext);
             //dd( $blocktext);
             $data['veiculo_ci_1'] =TextUtility::getSearchText($blocktext,'ci:','value');
             $data['veiculo_ci_1'] = str_replace('.','',$data['veiculo_ci_1']);

            //classe bonus
            $data['veiculo_classe_1'] =TextUtility::getSearchText($this->text,'classe de bônus','number');


        //dados do segurado
        $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'Segurado:']);
        $blocktext = FormatUtility::getPartOfStr($blocktext,['end'=>'Endereço:']);
        $exc1 = TextUtility::getSearchText($blocktext,'Endereço:','value',['side'=>'left']);
        $blocktext = trim(str_replace([$exc1,'CNPJ:','CPF:','Endereço:'],'',$blocktext));

        $data['segurado_nome'] = $this->getX1(['start'=>'Segurado:','remove'=>'Segurado:'],$blocktext);
        $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'Segurado:']);
        $blocktext = FormatUtility::getPartOfStr($blocktext,['end'=>'Bairro']);
        $n = TextUtility::getSearchText($blocktext,'Endereço:','value',['side'=>'left']);
        //dd(strlen($n));
        if(strlen($n)>18){
            $n = substr($n,1);
        }

        $data['segurado_doc'] = TextUtility::getSearchText($n,'','document');

        $data['segurado_nome'] = trim(str_replace(['Segurado:',$data['segurado_doc'],'CPF:','CNPJ:'],'',$data['segurado_nome']));
        $data['tipo_pessoa'] = ValidateUtility::isCNPJ($data['segurado_doc'])?'JURIDICA':'FISICA';

        return ['success'=>true,'data'=>$data];
    }


    /**
     * Retorna os dados do premio
     */
    public function getPremio($data){
        //dados do pagamento
        $blocktext = FormatUtility::getPartOfStr($this->text,['start'=>'de pagamento:']);
        $blocktext = FormatUtility::getPartOfStr($blocktext,['end'=>'Central de']);
        $pgto_tipo = PgtoData::getPgtoTipo($blocktext);
        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'do seguro:','end'=>'Forma de']);
        //dd($blocktext);

        $data['fpgto_tipo']=$pgto_tipo;
        $data['fpgto_tipo_code']= PgtoData::getPgtoCode($pgto_tipo)[1];
        $data['fpgto_premio_total']= TextUtility::getSearchText($blocktext,'preço total do seguro','number_formated');

        //valores adicionais
        $data['fpgto_premio_liquido']   = TextUtility::getSearchText($blocktext,'do seguro:','number_formated');
        $data['fpgto_premio_liq_serv']  = 0;//não tem nos modelos já analisados até o momento
        $data['fpgto_juros']            = TextUtility::getSearchText($blocktext,'Juros:','number_formated');
        $data['fpgto_juros_md']         = '0,00';
        $data['fpgto_iof']              = TextUtility::getSearchText($blocktext,'IOF:','number_formated');
        $data['fpgto_adicional']        = TextUtility::getSearchText($blocktext,'financeiros:','number_formated');

        $n = TextUtility::getSearchText($blocktext,'custo de ap','number_formated');
        $data['fpgto_custo'] = $n?$n:'0,00';


    //dados do pagamento - bloco das parcelas
        $blocktext = FormatUtility::getPartOfStr($blocktext,['start'=>'Vencimento']);
        $blocktext = FormatUtility::getPartOfStr($blocktext,['end'=>'Forma']);
        $n = str_replace('(R$)','',$blocktext);
        //dd($blocktext);
        //troca a expressão QUITADA por uma data válida
        $blocktext = str_replace('QUITADA','01/01/9999',$blocktext);

        $valores = TextUtility::getSearchText($blocktext,'','number_formated',['limit'=>false]);
        $datavenc = TextUtility::getSearchText($blocktext,'','datebr',['limit'=>false]);
        $datavenc = $this->orderArrayDate($datavenc);


        $r=PgtoData::makeTable(count($valores),$datavenc,$valores,1,$data['inicio_vigencia']);
        $data = $data + $r;

        //dd($data);
        $data['fpgto_1_prestacao_valor']    = $valores[0];
        $data['fpgto_1_prestacao_venc']     = $datavenc[0];
        $data['fpgto_dem_prestacao_valor']  = $valores[1]??$valores[0];
        $data['fpgto_venc_dia_1parcela']    = substr($data['fpgto_1_prestacao_venc'],0,2);

        $n=$datavenc[0]??$data['fpgto_venc_dia_1parcela'];
        $data['fpgto_venc_dia_2parcela']    = substr($n,0,2);

        /*
        if($pgto_tipo=='debito' || $pgto_tipo=='boleto' || $pgto_tipo=='carne'){
        }elseif($pgto_tipo=='cartao'){
        }*/


    //dd($pgto_tipo,$data,$blocktext);

        if($data['fpgto_1_prestacao_venc']){
            $d1=FormatUtility::convertDate($data['fpgto_1_prestacao_venc']);
            $d2=FormatUtility::convertDate($data['inicio_vigencia'] . ' +7 day' );
            $d1 = strtotime($d1);
            $d2 = strtotime($d2);
            if($d1<=$d2){
                $data['fpgto_avista']='avista';
            }else{
                $data['fpgto_avista']='30dias';
            }
        }else{
            $data['fpgto_avista']='';
        }
        //dd($data);
        return $data;
    }

    private static function clearText($text){
        $text =  preg_replace('/[^\d\-]/', '',$text);
        return $text;
    }

        /**
     * Retorna a matriz de datas ordenadas em ordem crescente
     * @param $arr_data - array de datas
     */
    private function orderArrayDate($arr_data){
        foreach($arr_data as $data){
            $timestamps[] = strtotime(str_replace('/', '-', $data));
        }

        // ordena
        sort($timestamps);

        // converte timestamp para datas
        foreach($timestamps as $timestamp){
            $datavencx[] = date('d/m/Y', $timestamp);

        }
        return $datavencx;
    }

}
