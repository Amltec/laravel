<?php
namespace App\ProcessRobot\cad_apolice\automovel;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;
use App\ProcessRobot\cad_apolice\ProcessAutomovelClass;
use App\ProcessRobot\cad_apolice\Classes\Data\PgtoData;



/**
 * Classe responsável por fazer a leitura do texto do histórico em pdf corretamente as informações em campos para um array php
 * Última atualização 15:39 23/03/2020
 */
class bradescoHistClass extends ProcessAutomovelClass{

    protected $validate_required = ['segurado_pernoite_cep'=>false];//sintaxe field=>boolean

    /* Arquivo principal para inicialização desta classe
     * @param string $text = texto extraído do arquivo pdf da apólice
     * @return array[success,msg,array data);
     */
    public function process($text,$opt=[]){
        $this->process_opt = $opt;
    	$this->text = $this->limitText($text);
        $r = $this->modelo01();
    	return $this->ValidateData($r);
    }


    /**
     * Modelo histórico pdf arquivo 01
     */
    private function modelo01(){
        //Obs: campos com {auto} são preenchidos automaticamente pelo classe ProcessAutomovelClass()
        $data=[];
        //dd('123');
        //Dados do corretor
            $data['corretor_nome']='{auto}';//é informado '{auto}' para que não dê erro na verificação do método ValidateData(). Esta string será processada pela classe ProcessRobotController
            $data['corretor_susep']='{auto}';
            //dd($data);

        //Dados da apólice
            $data['apolice_prod_ref']=$this->getX1(['start'=>'Auto + Residencial','remove'=>['NÃO','SIM'],'cb'=>function($v){ return $v=='Auto + Residencial'?'0531 - Automóvel':''; }]);
            $data['data_type']='historico';
            $data['seguradora_doc']='{auto}';
            $data['proposta_num']=$this->getX1(['start'=>'Nº da Proposta Bradesco','remove'=>'Nº da Proposta Bradesco']);

            $n=$this->getX1(['start'=>'Sucursal','end'=>'Item','split'=>false]);
            $n=TextUtility::getPartOfStr($n,['start'=>'Apólice','remove'=>['Apólice','Item']]);
            $data['apolice_num']=$n;

            $data['apolice_num_quiver'] = (string)(int)$data['apolice_num'];
            $data['data_emissao']=$this->getX1(['start'=>'Data da Emissão','remove'=>'Data da Emissão']);

            $n=$this->getX1(['start'=>'Chave da Apólice Anterior','end'=>'Item','split'=>false]);
            $n=TextUtility::getPartOfStr($n,['start'=>'Cia','end'=>'Item']);
            $n=TextUtility::getPartOfStr($n,['start'=>'Apólice','remove'=>['Apólice','Item']]);
            $data['apolice_re_num']=$n;

            $data['inicio_vigencia']=$this->getX1(['start'=>'Data Inicio Vigência','end'=>'Data Fim','remove'=>['Data Inicio Vigência','Data Fim']]);
            $data['termino_vigencia'] = TextUtility::getSearchText($this->text,'Fim Vigência','datebr',['max_words'=>10]);

        //Dados do segurado
        $tmp=$this->getX1(['start'=>'Informações do Segurado','split'=>false,'Informações do Segurado']);
            $n=TextUtility::getPartOfStr($tmp,['start'=>'CPF/CNPJ','end'=>'Data de Nascimento']);
            $n=TextUtility::getPartOfStr($n,['start'=>'segurado','remove'=>['Segurado','Data de Nascimento']]);
            if(stripos($n,'email')!==false)$n=TextUtility::getPartOfStr(FormatUtility::trimAll($n) ,['end'=>'email','remove'=>['email']]);
            $data['segurado_nome']= FormatUtility::sanitizeBreakText($n);

            //documento
            $n=strtoupper(TextUtility::getPartOfStr($tmp,['start'=>'CPF/CNPJ','split'=>chr(10),'remove'=>['CPF/CNPJ']]));
            $n=explode(chr(9),$n)[0];//divide por tab e pega a primeira ocorrencia (pois as vezes na frente do documento vem outro texto)

            if(strlen($n)>11){
                if(strlen($n)<15){
                   $n = str_pad($n , 14 , '0' , STR_PAD_LEFT);
                }
            }
            //dd(strlen($n));
            if(strlen($n)==9){
                $n = '00' . $n;
            }elseif(strlen($n)==10){
                 $n = '0' . $n;
            }

            $data['segurado_doc']= $n;


            //Campo tipo pessoa
            $n=strtoupper(TextUtility::getPartOfStr($tmp,['start'=>'Tipo de Segurado','end'=>'CPF/CNPJ','remove'=>['CPF/CNPJ','Tipo de Segurado']]));

            $n2=strtoupper(FormatUtility::removeAcents($n,true));
            $n2= FormatUtility::sanitizeAllText($n2);
            //dd($n2);
            if($n2=='fisica' || $n2=='juridica'){
                $n=$n2;
            }else{
                $n2=explode(chr(10),$n)[0];
                $n2=strtoupper(FormatUtility::removeAcents($n2,true));
                $n2=trim(str_replace('PESSOA','',$n2));
                $n2= FormatUtility::sanitizeAllText($n2);
                if($n2=='fisica' || $n2=='juridica'){
                    $n=$n2;
                }else{
                    $n='';
                }
            }
            //dd($n);
            if(!$n){
               $n2 = TextUtility::getSearchText($this->text,'Tipo de Segurado','value',['side'=>'right']);
               if($n2=='fisica' || $n2=='juridica'){
                    $n=$n2;
                }else{
                    $n='';
                }
            }
            $data['tipo_pessoa']=$n;

            $n=TextUtility::getPartOfStr($tmp,['start'=>'Segurado é'.chr(10).'proprietário do'.chr(10).'veículo','split'=>false,'remove'=>['Segurado é'.chr(10).'proprietário do'.chr(10).'veículo']]);
            $n=trim(strtoupper(substr($n,0,4)));
            $data['segurado_proprietario_veiculo_1']=($n=='SIM'?'SIM':'NÂO');


        //Endereço de pernoite do veículo
            $n=$this->getX1(['start'=>'Cep de pernoite','split'=>false,'end'=>'Atividade que','remove'=>['Cep de pernoite','do veiculo','Atividade que']]);
            $data['segurado_pernoite_cep_1']=($n=='Não se aplica'?'':$n);

        //Dados do proprietário
        if(strpos(strtolower($this->text),'proprietário')!==false){
            $tmp=$this->getX1(['start'=>'Informações do Proprietário','split'=>false,'Informações do Proprietário']);
                $n=TextUtility::getPartOfStr($tmp,['start'=>'Nome do proprietário','end'=>'Informações do Condutor', 'remove'=>['Nome do proprietário','Informações do Condutor']]);
                $n=explode('{',$n);
                $data['prop_nome_1']=trim($n[0]);
        }else{//não tem informações do proprietário, e neste caso é o próprio segurado
                $data['prop_nome_1']=$data['segurado_nome'];
        }
        //dd($data['prop_nome_1']);

        //Dados do veículo
        $tmp=$this->getX1(['start'=>'Informações do Bem Segurado','split'=>false,'Informações do Bem Segurado']);
            $data['veiculo_fab_1']=TextUtility::getPartOfStr($tmp,['start'=>'Fabricante','end'=>'Código Fipe','remove'=>['Fabricante','Código Fipe']]);
            $data['veiculo_modelo_1']=TextUtility::getPartOfStr($tmp,['start'=>'Marca do Veículo','end'=>'Fabricante','remove'=>['Marca do Veículo','Fabricante'],'cb'=>function($v){return str_replace(chr(10),' ',$v);}]);

            $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']); //código do fabricante
            $n=TextUtility::getPartOfStr($tmp,['start'=>'Ano Modelo','split'=>chr(10)]);
                $data['veiculo_ano_fab_1']=TextUtility::getPartOfStr($n,['start'=>'Ano Fabricação','remove'=>['Ano Fabricação']]);
                $data['veiculo_ano_modelo_1']=TextUtility::getPartOfStr($n,['start'=>'Ano Modelo','end'=>'Ano Fabricação','remove'=>['Ano Fabricação','Ano Modelo']]);
            $data['veiculo_chassi_1']=TextUtility::getPartOfStr($tmp,['start'=>'Nº Chassi','end'=>'Tipo Combustível','remove'=>['Nº Chassi','Tipo Combustível']]);
            $data['veiculo_cod_fipe_1']=TextUtility::getPartOfStr($tmp,['start'=>'Código Fipe','end'=>'Nº da Placa','remove'=>['Código Fipe','Nº da Placa']]);
            $n = TextUtility::getPartOfStr($tmp,['start'=>'Nº da Placa','end'=>'Ano Modelo','remove'=>['Nº da Placa','Ano Modelo']]);
            $data['veiculo_placa_1']=str_replace(' ', '', $n);
            $data['veiculo_combustivel_1']=TextUtility::getPartOfStr($tmp,['start'=>'Tipo Combustível','end'=>'Qtd. Passageiro','remove'=>['Nº Chassi','Tipo Combustível','Qtd. Passageiro']]);
            $data['veiculo_combustivel_code_1'] = $this->getCombustivelCode($data['veiculo_combustivel_1']);
            $data['veiculo_n_portas_1']=TextUtility::getPartOfStr($tmp,['start'=>'Nº Portas','end'=>'Tipo Veículo','remove'=>['Nº Portas','Tipo Veículo']]);
            $data['veiculo_n_lotacao_1']=TextUtility::getPartOfStr($tmp,['start'=>'Qtd. Passageiros','end'=>'Nº Portas','remove'=>['Qtd. Passageiros','Nº Portas']]);
            $data['veiculo_ci_1']=$this->getX1(['start'=>'Nº C I','xend'=>'Item','remove'=>['Nº C I']]);
             $data['veiculo_tipo_1'] = 'a';
            $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'classe bonus','end'=>'Tipo de','sanitize'=>true]);
            //dd($blocktest);
            $data['veiculo_classe_1'] = TextUtility::getSearchText($blocktext,'bonus','number',['side'=>'right']);



            $data['veiculo_zero_1'] ='n';//não existe esse dado
            $data['veiculo_data_saida_1'] ='';//não existe esse dado
            $data['veiculo_nf_1'] ='';//não existe esse dado


        //Forma de pagamento

        $tmp = $this->getX1(['start'=>'Dados Básicos da Apólice','split'=>false]);
            $data['fpgto_tipo']=TextUtility::getPartOfStr($tmp,['start'=>'Descrição Tipo Cobrança','end'=>'Data da Consulta','remove'=>['Descrição Tipo Cobrança','Data da Consulta']]);
            $n=explode(' ',$data['fpgto_tipo']);
            $data['fpgto_tipo'] = $n[0];
            if(substr($data['fpgto_tipo'],0,3)=='DEB'){
                $data['fpgto_tipo'] = 'debito';
            }

            $n=PgtoData::getPgtoCode($data['fpgto_tipo']);

            $data['fpgto_tipo']=$n[0];
            $data['fpgto_tipo_code']=$n[1];

            //cria os campos vazios
            $data['fpgto_n_prestacoes']='';
            $data['fpgto_1_prestacao_valor']='';
            $data['fpgto_1_prestacao_venc']='';
            $data['fpgto_dem_prestacao_valor']='';
            $data['fpgto_venc_dia_2parcela']='';
            $data['fpgto_venc_dia_1parcela']='';

            if($data['fpgto_tipo']=='cartao'){
                $tmp = $this->getX1(['start'=>'Nº daPrestação','split'=>false,'end'=>'Informações do Sinistro','remove'=>['Nº daPrestação','Informações do Sinistro']]);
                $tmp = TextUtility::getPartOfStr($tmp,['start'=>'DataControle','remove'=>'DataControle']);
                $tmp = explode(chr(10),$tmp);
                $n=[];
                foreach($tmp as $line){
                    $d = explode(chr(9),$line);//0 num prestação, 1 vencimento, 2 fpgto, 3 num DV, 4 valor
                    if(count($d)>=4){//para garantir que seja retornado apenas a tabela de dados e não a outros strings que possam vir depois da tabela
                        $n[]= ['data'=>$d[1], 'valor'=>FormatUtility::numberFormat($d[4])];
                    }
                }
                //dd($n);
                $data['fpgto_n_prestacoes']=count($n);
                $data['fpgto_1_prestacao_valor']=$n[0]['valor'];
                $data['fpgto_1_prestacao_venc']=$n[0]['data'];
                $data['fpgto_dem_prestacao_valor']= count($n)>1 ? $n[1]['valor'] : $n[0]['valor'];
                $data['fpgto_venc_dia_2parcela']= substr( count($n)>1 ? $n[1]['data'] : $n[0]['data'] ,0,2);
                $data['fpgto_venc_dia_1parcela']= $n[0]['data'];
                $data['fpgto_premio_total']='';

            }else if($data['fpgto_tipo']=='carne'){

            }else if($data['fpgto_tipo']=='debito'){
                $tmp = $this->getX1(['start'=>'Nº daPrestação','split'=>false,'end'=>'Informações do Sinistro','remove'=>['Nº daPrestação','Informações do Sinistro']]);
                $tmp = TextUtility::getPartOfStr($tmp,['start'=>'DataControle','remove'=>'DataControle']);

                //ajusta caso a string venha com mais caracteres e se extenda até a próxima página
                if(strpos($tmp,'Sinistro')!==false)$tmp = TextUtility::getPartOfStr($tmp,['end'=>'Sinistro','remove'=>'sinistro']);
                if(strpos($tmp,'{page-')!==false)$tmp = preg_replace('/{page-(start|end):(\d+)}/', '', $tmp);   //remove a string {page-start|end:N}
                $tmp = trim($tmp);

                //campo de prestações
                $tmp = explode(chr(10),$tmp);
                $n=[];
                foreach($tmp as $line){
                    $d = explode(chr(9),$line);//0 num prestação, 1 vencimento, 2 desc, 3 fpgto, 4 num DV, 5 valor
                    if(count($d)>=5){//para garantir que seja retornado apenas a tabela de dados e não a outros strings que possam vir depois da tabela
                        $v=null;
                        if(is_numeric($d[5])){
                            $v=$d[5];
                        }else{
                            $v=$d[4];
                        }
                        $n[]= ['data'=>$d[1], 'valor'=>FormatUtility::numberFormat($v)];
                    }
                }
                //dd($n);
                $data['fpgto_n_prestacoes']=count($n);
                $data['fpgto_1_prestacao_valor']=$n[0]['valor'];
                $data['fpgto_1_prestacao_venc']=$n[0]['data'];
                $data['fpgto_dem_prestacao_valor']= count($n)>1 ? $n[1]['valor'] : $n[0]['valor'];
                $data['fpgto_venc_dia_2parcela']= substr( count($n)>1 ? $n[1]['data'] : $n[0]['data'] ,0,2);
                $data['fpgto_venc_dia_1parcela']= $n[0]['data'];
                $data['fpgto_premio_total']='';

            }else if($data['fpgto_tipo']=='boleto'){
                $tmp = $this->getX1(['start'=>'Nº daPrestação','split'=>false,'end'=>'Informações do Sinistro','remove'=>['Nº daPrestação','Informações do Sinistro']]);
                $tmp = TextUtility::getPartOfStr($tmp,['start'=>'DataControle','remove'=>'DataControle']);
                $tmp = explode(chr(10),$tmp);
                $n=[];
                foreach($tmp as $line){
                    $d = explode(chr(9),$line);//0 num prestação, 1 vencimento, 2 desc, 3 fpgto, 4 num DV, 5 valor
                    if(count($d)>=5){//para garantir que seja retornado apenas a tabela de dados e não a outros strings que possam vir depois da tabela
                        $v=null;
                        if(is_numeric($d[5])){
                            $v=$d[5];
                        }else{
                            $v=$d[4];
                        }
                        $n[]= ['data'=>$d[1], 'valor'=>FormatUtility::numberFormat($v)];
                    }
                }
                //dd($n);
                $data['fpgto_n_prestacoes']=count($n);
                $data['fpgto_1_prestacao_valor']=$n[0]['valor'];
                $data['fpgto_1_prestacao_venc']=$n[0]['data'];
                $data['fpgto_dem_prestacao_valor']= count($n)>1 ? $n[1]['valor'] : $n[0]['valor'];
                $data['fpgto_venc_dia_2parcela']= substr( count($n)>1 ? $n[1]['data'] : $n[0]['data'] ,0,2);
                $data['fpgto_venc_dia_1parcela']= $n[0]['data'];
                $data['fpgto_premio_total']='';
            }else{
                //nenhuma ação, deixa os campos vazios...
            }

        $n = explode('/', $data['fpgto_1_prestacao_venc']);
        $data['fpgto_venc_dia_1parcela'] = $n[0];
        $tmp = $this->getX1(['start'=>'Resumo Financeiro','split'=>false]);
        $data['fpgto_premio_total']=TextUtility::getPartOfStr($tmp,['start'=>'Prêmio Bruto','remove'=>'Prêmio Bruto','cb'=>function($v){ return explode(chr(9),$v)[0]; }]);

        $d1=FormatUtility::convertDate($data['fpgto_1_prestacao_venc']);
        $d2=FormatUtility::convertDate($data['inicio_vigencia'] . ' +7 day' );
        $d1 = strtotime($d1);
        $d2 = strtotime($d2);
        if($d1<=$d2){
            $data['fpgto_avista']='avista';
        }else{
            $data['fpgto_avista']='30dias';
        }

        //captura as parcelas
        $blocktext = TextUtility::getPartOfStr($this->text,['start'=>'daprestação']);
        if(!$blocktext)$blocktext = TextUtility::getPartOfStr($this->text,['start'=>'da prestação','satinize'=>false]);
        $blocktext = TextUtility::getPartOfStr($blocktext,['end'=>'Informações do Sinistro']);

        $valores = [];
        $datavenc = [];
        $tmp=str_replace(['.','	'],[',',' '],$blocktext);//troca o '	' que é um caractere não identificado no texto por espaço
        $tmp=str_replace(['0,0 ',',1 ',',2 ',',3 ',',4 ',',5 ',',6 ',',7 ',',8 ',',9 '],['0,00 ',',10 ',',20 ',',30 ',',40 ',',50 ',',60 ',',70 ',',80 ',',90 '],$tmp);
        $tmp=str_replace('Endosso',' ',$tmp);

        TextUtility::getSearchText($tmp,'',function($v,$left,$right) use(&$valores,&$datavenc){
            if(is_numeric($v) && (int)$v>=1 && $v<=12){
                $v=explode(' ',$right)[0];
                if(ValidateUtility::isDate($v)){
                    $datavenc[]=$v;
                    $valores[]=TextUtility::getSearchText($right,'','number_formated');

                }
            }
        },['limit'=>false]);
        $r = PgtoData::makeTable(count($valores), $datavenc, $valores);
        $data['fpgto_n_prestacoes']=count($valores);//atualiza a contagem de parcelas

        //dd($tmp,$valores,$datavenc,$r);
        $data = $data + $r;

        $blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premios emitidos','end'=>'taxas de juros','sanitize'=>true]);
        if(!$blocktext)$blocktext = TextUtility::getPartOfStr($this->text, ['start'=>'premios emitidos','end'=>'total pago ate agora','sanitize'=>true]);

        $r=PgtoData::getFielsPremioAdd($blocktext,$data['fpgto_premio_total'],['get_juros'=>false]);

        $data = $data + $r;

        return ['success'=>true,'data'=>$data,'code'=>'ok'];
    }

}
