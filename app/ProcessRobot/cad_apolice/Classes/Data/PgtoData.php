<?php
namespace App\ProcessRobot\cad_apolice\Classes\Data;
use App\Utilities\FormatUtility;
use App\Utilities\TextUtility;
use App\Utilities\ValidateUtility;


/**
 * Classe geral para captura de informações de pagamento no texto do pdf da apolice
 * As classes abaixo são todas estáticas para captura de texto
 */
class PgtoData{
    //% de iof padrão para todos os casos
    public static $iof_perc = 0.0738;

    //margem de diferença de centavos entre o cálculo do iof com o iof capturado em $data para o PgtoData::validateAll(). Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
    protected $validate_iof_margem=0.07;

    /**
     * Retorna aos dados do pagamento, como tipo, valor do prêmio, parcelas e vencimentos
     * @param array|boolean $block_text - (string) trecho do texto para pesquisa, ou ==true para capturar o texto automaticamente
     * @param array & $data - demais campos já processados da apólice. Caso return true, os campos da forma de pagamento (conforme documentação em xls) serão mesclados a esta variável
     * @param float margem - (float) valor limite da margem para comparação da soma das parcelas com o prêmio total. Padrão 0.9
     * @param array $opt - campos adicionais (opcionais):
     *              full_text                   - texto completo para capturar de forma automática caso não encontre em $block_text.
     *              pgto_tipo                   - forma de pagamento. Se informado não irá capturar nesta função.
     *              dt_parcela_1|dt_parcela_2   - data da primeira parcela e segunda parcela. Se informado será usado para montar a tabela de parcelamento. Formato dd/mm/aaaa.
     *                                            Obs: válido somente quando não encontrada a tabela de valores automaticamente
     *              premio                      - valor do prêmio para comparação com as parcelas. Se não informado, irá capturar automaticamente este valor.
     *              thisClass                   - classe do respectivo ramo ou seguradora, ex: automovel\bradescoClass, Classes\Insurers\bradescoInsurer, ...
     * @return array [sucess,msg]
     */
    public static function getPgtoAuto($block_text,&$data,$margem,$opt=null){
        $full_text = $opt['full_text']??'';
        if($block_text===true){
            $block_text = TextUtility::getPartOfStr($full_text,['start'=>'Formas de pagamento']);
            if(empty($block_text))$block_text=TextUtility::getPartOfStr($full_text,['start'=>'Forma de pagamento']);
            if(empty($block_text))$block_text = TextUtility::getPartOfStr($full_text,['start'=>'Dados de Pagamento']);
            if(empty($block_text))$block_text = TextUtility::getPartOfStr($full_text,['start'=>'Demonstração do Prêmio']);
            if(empty($block_text))$block_text = TextUtility::getPartOfStr($full_text,['start'=>'Preço do Seguro']);
            $block_text=substr($block_text,0,1000);//obs: é comum não passar de 1000 caracteres todo este bloco (em geral é bem menos)
        }

        $margem_limit = $margem;

        //dd($block_text);
        //retira as strings que estão conflitando com a lógica deste código (e não farão falta na programação)
        $block_text=str_replace('C/C para Débito','',$block_text);

        if(empty($data['inicio_vigencia']))return ['success'=>false,'msg'=>'Início de vigência requerido'];

        $parcelas=0;
        $datavenc=[];
        $valor=[];
        $text0 = FormatUtility::sanitizeAllText($block_text);
        $is_tbl_valores=false;

        //*** valor do prêmio ***
        $premio = $opt['premio']??null;
        if(empty($premio))$premio = self::getPremioTotal($full_text?$full_text:$block_text);
        if(empty($premio) || !TextUtility::isNumberFormated($premio))return ['success'=>false,'msg'=>'Valor do prêmio inválido'];
        $premio_float = (float)FormatUtility::nDecimal($premio);
        $data['fpgto_premio_total']= $premio;


        //*** forma de pagamento ***
        if($opt['pgto_tipo']??false){

            $data['fpgto_tipo']=$opt['pgto_tipo'];
            $data['fpgto_tipo_code']=self::getPgtoCode( $data['fpgto_tipo'] )[1];
        }else{

            $r = self::getPgtoTipo($block_text,$text0);
            if(!$r && $full_text){
                $r = self::getPgtoTipo( TextUtility::getPartOfStr($full_text,['start'=>'Dados de Cobrança']) );
                if(!$r){
                    $r = self::getPgtoTipo( TextUtility::getPartOfStr($full_text,['start'=>'FORMA DE PAGAMENTO DO PRÊMIO']) );
                }
            }
            if($r){
                $data['fpgto_tipo']=$r;
                $data['fpgto_tipo_code']=self::getPgtoCode( $data['fpgto_tipo'] )[1];
            }
        }

        //dd($data['fpgto_tipo'],$text0);
        //**** número de prestações ****
            if(strpos($text0,'no de prestacoes')!==false || strpos($text0,'no de parcelas')!==false){
                    //XXXXXXXXXX função substituída, pois estava ocupando muito processamento XXXXXXXXXXX
                    /*$parcelas = TextUtility::execFncInStr($text0,2,function($v,$left,$right){
                        $v=trim($v);
                        if(is_numeric($v) && strlen($v)==2){
                            $xl=trim(substr($left,-1));//primeiro caractere a esquerda
                            $xr=trim(substr($right,0,1));//primeiro caractere a direita
                            if($xl=='' && $xr=='')return true;//lógica: a esquerda e direita tem que ser vazios
                        }
                    })[0];*/

                    $parcelas='';
                    TextUtility::exec2FncInStr('/[0-9]{2}/',$text0,function($v,$left,$right) use(&$parcelas){
                        $v=trim($v);
                        if(is_numeric($v) && strlen($v)==2){
                            $xl=trim(substr($left,-1));//primeiro caractere a esquerda
                            $xr=trim(substr($right,0,1));//primeiro caractere a direita
                            //dump([$v,$left,$right]);
                            if($xl=='' && $xr==''){//lógica: a esquerda e direita tem que ser vazios
                                $parcelas = $v;
                                return true;
                            }
                        }
                    });
                    if(!is_numeric($parcelas))return ['success'=>false,'msg'=>'Erro ao capturar parcelas'];
                    $parcelas=(int)$parcelas;
                    if($parcelas<0 || $parcelas>12)return ['success'=>false,'msg'=>'Erro ao capturar parcelas (2)'];
            }else{//não tem um campo com o número de prestaões/parcelas
                  //deixa $parcelas=0 para que seja capturado automaticamente mais abaixo
            }

            //dd($parcelas);
            //verifica se no texto os valores estão organizados entre 1ª Prestação e Demais ª Prestação
            if(strpos($text0,'1a prestacao')!==false){
                //lógica: se deu certo, em $valor retornará a um array de 2 valores na ordem 1ª e 2ª prestação
                //localiza a string e remove do texto
                $nx=explode(' ',$text0);
                foreach($nx as $n){
                    //procura pelo número formato
                    $n=trim($n,',');
                    if(TextUtility::isNumberFormated($n)){//tem que ser um número pelos menos assim: 99,99 ou 9.999,99
                        $n=(float)FormatUtility::nDecimal($n);
                        $valor[]= FormatUtility::numberFormat($n);
                    }
                    if(ValidateUtility::isDate($n)){
                        $datavenc[]=$n;
                    }

                    if($parcelas>0){
                        if(count($datavenc)>$parcelas)break;
                    }
                }
                if(!$datavenc){//quer dizer que não achou a data das parcelas,
                    //neste caso considera da data da vigência

                    //$datavenc[]=$data['inicio_vigencia'];//repete 2x para ter 2 campos (equivalente 1ª e 2ª prestação)
                    $datavenc[]= $opt['thisClass']->getDate1aParc($data['fpgto_tipo'],$data) ;//repete 2x para ter 2 campos (equivalente 1ª e 2ª prestação)

                }

                //dd($datavenc,$valor,$parcelas,$block_text);
                //$r = self::makeTable($parcelas,$datavenc,$valor);

                if($parcelas>1 && $data['fpgto_tipo']=='cartao'){
                    $r = self::makeTable($parcelas,$opt['thisClass']->getDate1aParc($data['fpgto_tipo'],$data) ,$valor);
                }else{
                    $r = self::makeTable($parcelas,$datavenc,$valor);
                }



                if($r){
                    $is_tbl_valores=true;
                    $data = $data + $r;
                }
            }

            if(!$is_tbl_valores){//ainda não montou a tabela de valores
                $r=self::getTableVencParc($block_text);
                //dd('*',$r);

                //dd('*******************',$block_text);
                if(!$r && $full_text){//procura em outro bloco de texto
                    $r=self::getTableVencParc( TextUtility::getPartOfStr($full_text,['start'=>'Parcelamento do Prêmio']) );
                }

                if($r){
                    $is_tbl_valores=true;
                    $data = $data + $r;
                }elseif($full_text){
                    //ainda não montou a tabela de valores
                    $r=self::getTableVencParc($full_text);//procura em todo o texto
                    //dd($r);
                    if($r){
                        $is_tbl_valores=true;
                        $data = $data + $r;
                    }
                }
            }


            if($is_tbl_valores){//achou a tabela de valores
                $parcelas = (int)$data['fpgto_n_prestacoes'];
                if(!$parcelas)return ['success'=>false,'msg'=>'Erro ao capturar parcelas (3)'];

                //dd($parcelas);
            }else{

                //tabela de valores não encontrada
                //provavelmente não tem a tabela e neste caso procura individualmente pelo número de parcelas e pelo total do prêmio em TODO O TEXTO

                $text0 = FormatUtility::sanitizeAllText($full_text);

                //parcela: procura pelo texto 'Forma de Pagamento: {parcela}x'      //padrão das apólices da HDI  ou
                //         procura pelo texto 'Parcelas Mensais: 99 '  //99 = 2 digitos //padrão de apólices Sulamérica
                    //XXXXXXXXXX função substituída, pois estava ocupando muito processamento XXXXXXXXXXX
                    /*TextUtility::execFncInStr($text0,22,function($v) use(&$parcelas){
                        $n='forma de pagamento: ';
                        $n2='parcelas mensais: ';
                        if(substr($v,0,strlen($n))==$n  ||  substr($v,0,strlen($n2))==$n2){
                            $v=trim(str_replace([$n,$n2],'',$v));
                            $v=explode(' ',$v)[0];//pega o primeiro texto separado por ' ', pois as vezes por vir um pouco do texto seguinte
                            if(is_numeric($v)){
                                $v=(int)$v;
                                if($v>0 && $v<=12){$parcelas=$v;return true;}
                            }
                            return false;
                        }
                    });*/
                    //dd($text0);
                    TextUtility::exec2FncInStr('/(forma de pagamento: 1 \+ ([0-9]{1}|[0-9]{2}) )|(forma de pagamento: a vista)|(forma de pagamento: ([0-9]{1}|[0-9]{2})x)|(forma de pagamento: ([0-9]{1}|[0-9]{2}) x)|(parcelas mensais: ([0-9]{1}|[0-9]{2})) /i',$text0,function($v) use(&$parcelas){
                        if($v=='forma de pagamento: a vista'){
                            $parcelas=1;
                            return true;
                        }

                        $v=trim(str_replace(['forma de pagamento:','parcelas mensais:','x'],'',$v));

                        //verifica se está assim (ex):  1 + 9
                        if(strpos($v,'+')!==false){
                            $v=explode('+',trim($v));
                            $v=(int)$v[0] + (int)$v[1];
                            if(is_numeric($v)){$parcelas=$v;return true;}
                            return false;
                        }

                        $v=explode(' ',$v)[0];//pega o primeiro texto separado por ' ', pois as vezes por vir um pouco do texto seguinte
                        if(is_numeric($v)){
                            $v=(int)$v;
                            if($v>0 && $v<=12){$parcelas=$v;return true;}
                        }
                        return false;
                    });
                    if(!$parcelas)return ['success'=>false,'msg'=>'Erro ao extrair total de parcelas'];

                //valor do prêmio / seguro
                    /*TextUtility::execFncInStr($text0,14,function($v,$left,$right) use(&$premio){
                        $v=trim($v);
                        if($v=='premio total :' || $v=='premio total:' || substr($v,0,13)=='premio total '){
                            $v.=$right;
                            $v= str_replace(['premio total',':','-'],'',$v);
                            $n=explode(' ',trim($v))[0];//pega o primeiro valor a direita
                            if(TextUtility::isNumberFormated($n)){
                                $premio=(float)FormatUtility::nDecimal($n);
                                return true;
                            }
                        }
                    });

                    if(!$premio)return ['success'=>false,'msg'=>'Erro ao extrair valor do prêmio (4)'];*/
                  //  dd($text0);
                    //verifica se consegue pegar o valor da primeira parcela
                    //XXXXXXXXXX função substituída, pois estava ocupando muito processamento XXXXXXXXXXX
                    /*
                    $parcela_1=0;
                    TextUtility::execFncInStr($text0,9,function($v,$left,$right)use(&$parcela_1){
                        $v=trim($v);
                        if(substr($v,0,8)=='parcela:' || $v=='parcela u'){
                            $t=true;

                            if($v=='parcela u'){
                                if(substr($v.$right,0,14)!='parcela unica:')$t=false;//erro ao localizar o texto
                            }
                            if($t){
                                $right = trim(str_replace(['r$','nica',':'],' ',$right));
                                $n=explode(' ',trim($right))[0];//pega o primeiro valor a direita
                                if(TextUtility::isNumberFormated($n)){
                                    $parcela_1=$n;
                                    return true;
                                }
                                return false;//interrompe o loop
                            }
                        }
                    });*/

                    $parcela_1=0;
                    TextUtility::exec2FncInStr('/(parcela:)|(parcela u)/i',$text0,function($v,$left,$right) use(&$parcela_1){
                        $v=trim($v);
                        if(substr($v,0,8)=='parcela:' || $v=='parcela u'){
                            $t=true;

                            if($v=='parcela u'){
                                if(substr($v.$right,0,14)!='parcela unica:')$t=false;//erro ao localizar o texto
                            }
                            if($t){
                                $right = trim(str_replace(['r$','nica',':'],' ',$right));
                                $n=explode(' ',trim($right))[0];//pega o primeiro valor a direita
                                if(TextUtility::isNumberFormated($n)){
                                    $parcela_1=$n;
                                    return true;
                                }
                                return false;//interrompe o loop
                            }
                        }
                    });

                    if(!$parcela_1)return ['success'=>false,'msg'=>'Erro ao capturar valor da primeira parcela'];


                //*** monta automaticamente a tabela das parcelas ***
                    //até aqui tem apenas o valor da parcela e do prêmio e neste caso usa a data da vigência como data base para montar a tabela de vencimentos
                    $dt_parcela_1 = ($opt['dt_parcela_1']??false) ? $opt['dt_parcela_1'] : ($opt['thisClass']->getDate1aParc($data['fpgto_tipo'],$data) ?? $data['inicio_vigencia']);
                    $dt_parcela_2 = ($opt['dt_parcela_2']??false) ? $opt['dt_parcela_2'] : ($opt['thisClass']->getDate1aParc($data['fpgto_tipo'],$data) ?? $data['inicio_vigencia']);

                    $datavenc=[$dt_parcela_1];
                    list($d,$m,$y) = explode('/',$dt_parcela_2);//usa a data da 2ª parcela, pois a primeira já está em $datavenc
                    $valor=[$parcela_1];
                    $diff = $premio_float - (float)FormatUtility::nDecimal($parcela_1);
                    $sum = (float)FormatUtility::nDecimal($parcela_1);
                    for($i=2;$i<=$parcelas;$i++){
                        $n=$d;

                        if($i==2 && ($opt['dt_parcela_2']??false)){//foi informado manualmente a data da segunda parcela, portanto pula a verificação da segunda parcela
                            //nenhuma ação
                        }else{
                            $m++;
                            if($m>12){$m=1;$y++;}
                        }
                        $v = str_pad($n, 2 ,'0', STR_PAD_LEFT) .'/'. str_pad($m, 2 ,'0', STR_PAD_LEFT) .'/'. $y;

                        while(true){
                            if(!ValidateUtility::isDate($v)){//data inválida, tira um dia
                                $n--;
                                $v = str_pad($n, 2 ,'0', STR_PAD_LEFT) .'/'. str_pad($m, 2 ,'0', STR_PAD_LEFT) .'/'. $y;
                            }else{
                                break;
                            }
                        }
                        $datavenc[] = $v;

                        //obs: as conversões abaixo ocorrem para garantir a soma sob 2 casas decimal
                        $v=FormatUtility::numberFormat($diff/($parcelas-1));
                        $valor[]=$v;//cria a matriz com todos os índices
                        $sum+=(float)FormatUtility::nDecimal($v);
                    }

                    //desabilitado em 20/10/2021 - motivo: já é verificado dentro do AutomovelClass
                    /*//calcula para ver se a soma das parcelas é igual ao total do prêmio
                    if($sum!=$premio_float){
                        if($sum-$premio_float>1)return ['success'=>false,'msg'=>'Soma das parcelas diferente do prêmio total'];//a diferença da soma é maior que 1,00
                        //ajusta o valor da última parcela para ficar compatível
                        $v=($sum-$premio_float);
                        $valor[count($valor)-1] = FormatUtility::numberFormat( (float)FormatUtility::nDecimal($valor[count($valor)-1]) - $v);

                    }
                    */
                    //ajusta os dados no formato correto
                    $r = self::makeTable($parcelas,$datavenc,$valor);
                    $data = $data + $r;
            }



       //desabilitado - motivo: já é verificado dentro do AutomovelClass
       //*** verifica se a soma das parcelas é igual ao valor do prêmio ***
       /*     //calcula o valor do prêmio a partir das $parcelas e $datavenc retornas
            $n=0;
            for($i=1;$i<=$parcelas;$i++){
                $n+=(float)FormatUtility::nDecimal($data['fpgto_valorparc_'.$i]);
            }
            if($n==0)return ['success'=>false,'msg'=>'Valor do prêmio inválido(2)'];
            //verifica se o valor das parcelas está dentro do prêmio com margem de 0.9
            $n=$n>$premio_float?$n-$premio_float:$premio_float-$n;
            if($n>$margem_limit)return ['success'=>false,'msg'=>'Erro na verificação do valor do prêmio (fora da margem e '. $margem_limit .')'];
        */



        //monta a string final
            $data = self::addFields1($data);


        return ['success'=>true];
    }



    /**
     * Valores possíveis que descrevem a forma de pagamento.
     * Sintaxes: [pgto_code_name => [strings, ignore]
     *              strings - array de textos de formas de pagamentos conforme consta no pdf
     *              ignore  - textos que se presentes, deve ignorar esta forma de pgto e pular para próxima
     */
    public static $fpgto_list_values=[
        'carne'=>[
            'strings'=>['carne'],
        ],
        'boleto'=>[
            'strings'=>['boleto','a vista','ficha','fcan','fca n'],
            'ignore'=>['cartao de credito','debito em conta','carne','debito','boleto demais debito','boleto demais cartao','boleto debito']
        ],
        'debito'=>[
            'strings'=>['debito'],
            'ignore'=>['boleto','boleto demais debito','boleto debito']
        ],
        'cartao'=>[
            'strings'=>['cartao'],
            'ignore'=>['debito','cartao de segurado','seu cartao', 'carne forma de pagamento','boleto demais cartao']
        ],
        '1boleto_debito'=>[//1ª BOLETO - DEMAIS DEBITO EM CONTA
            'strings'=>['boleto demais debito', 'boleto debito'],
        ],
        '1boleto_cartao'=>[//1ª BOLETO - DEMAIS CARTÃO CREDITO
            'strings'=>['boleto demais cartao'],
        ],
    ];

    //Retorna ao tipo da forma de pagamento (complementar de getData_formaPgto())
    public static function getPgtoTipo($blocktext,$text0=null){
        if(!$text0)$text0 = FormatUtility::sanitizeAllText($blocktext);
        //trocar alguns caracteres que estão com outra codificação
        $text0 = str_replace(['В'],['b'],$text0);

        //remove alguns textos que estão dando problema
        $text0 = str_replace(['carneiro'],'',$text0);//por causa do 'carne'

        //as vezes, os textos vem grudados, ex: 'textoboletotexto' e portanto separa (ex texto boleto texto) para garantir a localização correta
        foreach(['boleto','debito','carne','cartao','ficha','fcan'] as $n){
            $text0 = str_replace($n,' '.$n.' ',$text0);
        }
        $text0 = str_replace(['/','  ','  ','  '],' ',$text0);//tira os espaços em brancos e barras

        $fpgto = [];
        $str_in = str_replace([':','.'],' ',$text0);
        //dd($text0);

        //procura os pgtos
        foreach(self::$fpgto_list_values as $f=>$opt){
            foreach($opt['strings'] as $str){
                if(strpos($str_in,' '.$str.' ')!==false){
                    $fpgto[$f]=true;
                }
            }
        }
        //dd($fpgto);
        //procura e descarta por ignorados
        foreach($fpgto as $f=>$t){
            $ign = self::$fpgto_list_values[$f]['ignore']??false;

            if($ign){
                foreach($ign as $str){
                    if(strpos($str_in,' '.$str.' ')!==false){
                        unset($fpgto[$f]);
                        break;
                    }
                }
            }
        }

        if(!$fpgto){//Forma de pagamento não encontrada
            return false;
        }else if(count($fpgto)>1){//achou mais de um e por isto retornou a vazio
            return false; //Encontrado mais de uma forma de pgto
        }else{
            return array_keys($fpgto)[0]??'';
        }
    }

    //Retorna ao uma matriz de data de parcelas se encontrado no texto (complementar de getData_formaPgto())
    //Espera em $blocktext uma tabela de data e valores colunados (formato tabela). Ex: parcela vencimento valor ... demais campos (e cada valor em uma linha abaixo)
    public static function getTableVencParc($blocktext,$text0=null,$inicia_parcela=1){
        if(!$text0)$text0 = FormatUtility::sanitizeAllText($blocktext);
        if(trim($text0)=='')return false;

        $is_date_valid=false;
            //XXXXXXXXXX função substituída, pois estava ocupando muito processamento XXXXXXXXXXX
            //primeiro verifica se existem datas válidas com textos grudados nas laterais sem espaço, e se houver adiciona espaço
            /*TextUtility::execFncInStr($text0,10,function($dt) use(&$text0,&$is_date_valid){
                $dt=trim($dt);
                if(ValidateUtility::isDate($dt) && strlen($dt)==10){
                    //dump($dt);
                    $text0=str_replace($dt, ' '.$dt.' ', $text0);
                    $is_date_valid=true;
                }
            });*/

        //primeiro verifica se existem datas válidas nos textos (mesmo que estejam grudados nas laterais sem espaço) e adiciona espaço nas alterais das datas
        //caso apólice da Lybert nº 31-72-843.627
        //      Obs: existe a função self::addSpaceDateText() que tem a mesma lógica abaixo, mas não está sendo utilizada aqui, pois abaixo é verificado se deve ou não existir números laterais (e a função addSpaceDateText() não verifica isto)
        preg_match_all('/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/',$text0,$matches);
        if($matches[0]??null){
            foreach($matches[0] as $dt){//loop das datas encontradas no texto
                $i = strpos($text0,$dt);
                $s1 = substr($text0,$i-1,1);//captura o caractere antes do texto
                $i += strlen($dt);
                $s2 = substr($text0,$i,1);//captura o caractere depois do texto

                //não pode conter números nos caracteres ao lado, apenas textos, espaços, etc...
                if(is_numeric($s1))continue;
                if(is_numeric($s2))continue;

                $text0 = str_replace($dt, ' '.$dt.' ' ,$text0);
                $is_date_valid=true;
            }
        }

        //limpa espaços extras laterais
        $text0 = str_replace(['  ','  ','  '], ' ', $text0);

        $datavenc=[];


        if($is_date_valid){//no texto tem datas válidas
                //XXXXXXXXXX função substituída, pois estava ocupando muito processamento XXXXXXXXXXX
                //retira do texto a palavra 'liquidado em dd/mm/aaaa' motivo - está interferindo na extração da tabela de datas
                /*while(true){
                    $text1 = trim(TextUtility::getPartOfStr($text0,['start'=>'liquidado em']));
                    if($text1){
                        $text1= substr($text1,0,30);
                        TextUtility::execFncInStr($text1,10,function($v,$left) use(&$text0){
                            $v=trim($v);
                            if(ValidateUtility::isDate($v) && strlen($v)==10){
                                $text0= str_replace($left.$v,'',$text0);
                                return false;
                            }
                        });
                    }else{
                        break;
                    }
                }*/

                //retira do texto a palavra 'liquidado em dd/mm/aaaa' motivo - está interferindo na extração da tabela de datas
                preg_match_all('/liquidado em ([0-9]{2}\/[0-9]{2}\/[0-9]{4})?/i',$text0,$matches);
                if($matches[0]??null){
                    foreach($matches[0] as $str){//loop das datas encontradas no texto
                        $text0 = str_replace($str,'',$text0);
                    }
                }
                //dd($text0);


                //remove os espaços em branco extras
                while(true){
                    if(strpos($text0,'  ')===false)break;
                    $text0 = str_replace('  ',' ',$text0);
                }
                $nx=explode(' ',$text0);
                //dd($text0);
                $x=-1;
                $left=[];//texto lateral armazenado
                foreach($nx as $n){
                    //procura por uma data
                    if(ValidateUtility::isDate($n) || $n=='quitada'){//algumas apólices vem a palavra 'quitada' no lugar da data
                        //** obs: estas três linhas comentadas abaixo, foram substituídas pela lógica das linhas acima
                        //Para o caso da apólice HDI 01.025.131.048591    //provavelmente em uma coluna da tabela tem um campo assim: Liquidado em dd/mm/aaaa
                        //$strleft = join(' ',array_slice($left, -3));//pega os três últimos caracteres
                        //if(strpos($strleft,'liquidado em')!==false)continue;//como o $n atual é uma data, deve ignorar e pegar o próximo

                        if($x<18){//18 = número máxim de palavras de distância entre as datas
                            $datavenc[]=$n;
                            $x=0;
                        }else{//provavelmente esta data está fora da tabela e não faz parte
                            break;
                        }
                    }
                    if($x>=0)$x++;
                    $left[]=$n;
                }
                //dd($datavenc,$nx);
                if(!$datavenc)return false;
                //procura por cada data da string e verifica quais valores estão na esquerda ou direita da data no loop
                $valor=[];
                $count_left=0;$count_right=0;
                //dd($text0);
                foreach(['left','right'] as $ltr){
                    $str_in = $text0;
                    foreach($datavenc as $i => $dt){
                        //XXXXXXXXXX função substituída (em 19/10/2021), pois estava ocupando muito processamento XXXXXXXXXXX
                        /*
                        TextUtility::execFncInStr($str_in,10,function($v,$left,$right) use($ltr,$dt,&$valor,&$count_left,&$count_right,&$i,&$str_in){
                            if($v==$dt || strpos($v,' quitada ')!==false){//algumas apólices vem a palavra 'quitada' no lugar da data
                                //remove a primeira ocorrência encontrada, para o caso de haver mais ocorrências repetidas (assim a lógica abaixo não dá erro)
                                $str_in = str_replace_first($v, 'dt-'.$ltr.'-'.$i, $str_in);

                                //procura na esquerda
                                if($ltr=='left'){
                                    $str=substr(trim($left),-15);//procura nos últimos 15 caracteres
                                }else{//right
                                    $str=substr(trim($right),0,15);//procura nos últimos 15 caracteres
                                }
                                $nx=explode(' ',$str);
                                foreach($nx as $n){
                                    if(TextUtility::isNumberFormated($n)){
                                        //na captura de parcelas não pode ter um valor zero
                                        if((int)$n===0)continue;

                                        //dump([$i,$ltr,$n,$dt]);
                                        $valor[]=$n;
                                        if($ltr=='left')$count_left++;
                                        if($ltr=='right')$count_right++;
                                        return true;//tem que ser um número pelos menos assim: 99,99 ou 9.999,99
                                    }
                                }
                            }
                        });
                        */
                        //Função atualizada
                        TextUtility::exec2FncInStr('/([0-9]{2}\/[0-9]{2}\/[0-9]{4})|( quitada )/',$str_in,function($v,$left,$right) use($ltr,$dt,&$valor,&$count_left,&$count_right,&$i,&$str_in){
                            if($v==$dt || strpos($v,' quitada ')!==false){//algumas apólices vem a palavra 'quitada' no lugar da data
                                //remove a primeira ocorrência encontrada, para o caso de haver mais ocorrências repetidas (assim a lógica abaixo não dá erro)
                                $str_in = str_replace_first($v, 'dt-'.$ltr.'-'.$i, $str_in);

                                //procura na esquerda
                                if($ltr=='left'){
                                    $str=substr(trim($left),-15);//procura nos últimos 15 caracteres
                                }else{//right
                                    $str=substr(trim($right),0,15);//procura nos últimos 15 caracteres
                                }
                                $nx=explode(' ',$str);
                                foreach($nx as $n){
                                    if(TextUtility::isNumberFormated($n)){
                                        //na captura de parcelas não pode ter um valor zero
                                        if((int)$n===0)continue;

                                        //dump([$i,$ltr,$n,$dt]);
                                        $valor[]=$n;
                                        if($ltr=='left')$count_left++;
                                        if($ltr=='right')$count_right++;
                                        return true;//tem que ser um número pelos menos assim: 99,99 ou 9.999,99
                                    }
                                }
                            }
                        });
                        //dd('a',$valor);


                        if(is_array($valor)==false || (is_array($valor) && count($valor)!=count($datavenc))){
                            //dd($valor,$datavenc,$ltr);
                            //se chegou até aqui, quer dizer que o total valores é diferente do total de vencimentos encontrados
                            //provavelmente é porque a sequencia de valores está na direita ao invés da esquerda
                            //portanto tenta de novo
                            continue;
                        }else{
                            break;
                        }
                    }
                    if($ltr=='left'){
                        if($count_left==count($datavenc)){
                            break;
                        }else{//reseta a var e continua na direita
                            $valor=[];
                            $count_left=0;
                        }
                    }
                }
                //dd([$datavenc,$valor,$str_in]);
        }else{
            return false;
        }

        //dd($valor,$datavenc);
        if(!$valor || !$datavenc){
            return false;
        }elseif(count($valor)!=count($datavenc)){
            return false;//['success'=>false,'msg'=>'Vencimentos e parcelas não batem'];
        }

        //*** lógica: caso na data tenha uma string ex 'quitada', esta deve permanecer no início ou no final da matriz e só pode ter um valor deste tipo nas datas
            //conta quantos registros não são data
            $dt_count_not=0;
            $dt_count_not_pos=0;
            $valor_not=null;
            foreach($datavenc as $i=>$dt){
                if(!ValidateUtility::isDate($dt)){
                    $valor_not=$valor[$i];
                    $dt_count_not++;
                    $dt_count_not_pos=$i;
                    unset($datavenc[$i],$valor[$i]);
                }
            }
            if($dt_count_not>1){
                return false;//+1 de uma 'string' no lugar data (ex 'quitada'), portanto não prossegue
            }

            //ordena a matriz das datas
            list($datavenc,$valor) = self::getTableOrderDate($datavenc, $valor);

            if($dt_count_not>0){
                //insere no início ou no final da array
                if($dt_count_not_pos==0){//início
                    $datavenc=[$datavenc[0]] + $datavenc; //pega a primeira data
                    $valor=[$valor_not] + $valor;
                }else{//final - pega também a primeira data, mas tem que ficar como último resultado
                    $datavenc[]=$datavenc[0];
                    $valor[]=$valor_not;
                }
            }
            //dd($datavenc,$dt_count_not_pos,$valor_not,$dt_count_not);







        //monta  tabela
        $parcelas = count($valor);
        $data = self::makeTable($parcelas,$datavenc,$valor,$inicia_parcela);

        return $data;
    }



    /**
     * Retorna ao uma matriz de data de parcelas se encontrado no texto, procurando apenas pelos valores pois não tem datas de vencimentos para comparar
     * Esta função faz o mesmo da função self::getTableVencParc(), com o mesmo retorno, mas sem usar datas
     *     [nParcela   a vista|Fatura do Cartão    999,99]    .... repete coluna
     *     [nParcela   a vista|Fatura do Cartão    999,99]    .... repete coluna
     *     ...
     * @param $blocktext - bloco de texto onde deve procurar a informação
     * @param $dt_parcela_1 - data da primeira parcela (requerido)
     * @param $dt_parcela_2 - data da segunda parcela (opcional)
     * @param $premio - (float|string) se informado será usado como base para verificar se a soma das parcelas corresponde ao prêmio
     *                  =='auto' soma as parcelas e verifica automaticamente dentro do texto e adiciona no campo de retorno
     * @param $ignore_diff_1 - se true irá ignora a diferença de 1 real entre as parcelas. Default false.
     * @param $margem - (float) valor limite da margem para comparação da soma das parcelas com o prêmio total. Padrão 0.9.
     * @obs Tentativa: contas quantos valores com diferença de 1 real cada existem e neste caso considera este total como o número de parcelas.
     * Obs: Esta função NÃO é executada dentro da função getData_formaPgto() e existe apenas para ser chamada manualmente
     * @return: o mesmo de self::getTableVencParc() ou false
     */
    public static function getTableVencParc_noDate($blocktext,$dt_parcela_1,$dt_parcela_2=null,$premio=null,$full_text=null,$ignore_diff_1=false,$margem=0.9){
        $text0 = FormatUtility::sanitizeAllText($blocktext);
        //dd($dt_parcela_1,$dt_parcela_2,$text0);
        if(!$dt_parcela_2)$dt_parcela_2= FormatUtility::addDate(FormatUtility::convertDate($dt_parcela_1),'m','1','datebr');//adiciona 1 mês
        //dd($dt_parcela_2);
        $valor=[];
        $sum=0;
        $nx=array_map('trim',explode(' ',trim($text0)));
        //dd($nx);
        foreach($nx as $v){

            //armazena todos os valores encontrados
            if(TextUtility::isNumberFormated($v)){//é um número formatado
                //verifica se $v tem diferença menor que 1 real em $valor
                $v=(float)FormatUtility::nDecimal($v);
                if($ignore_diff_1){
                    $valor[]=$v;
                    $sum+=$v;
                }else{
                    if($valor){
                        foreach($valor as $n){
                            $n= $n>$v ? $n-$v : $v-$n;
                            if($n<1){
                                $valor[]=$v;
                                $sum+=$v;
                                break;
                            }
                        }
                    }else{
                        $valor[]=$v;
                        $sum+=$v;
                    }
                }
            }
        }
        if(!$valor)return false;
        //dd($valor,$sum,$text0);
        $data=[];

        //verifica se a soma das parcelas é igual ao valor do prêmio
        if($premio){
            if($premio=='auto'){
                $premio = self::getPremioTotal($full_text?$full_text:$block_text);
                $data['fpgto_premio_total'] = $premio;
            }

            if(empty($premio) || !TextUtility::isNumberFormated($premio))return ['success'=>false,'msg'=>'Valor do prêmio para verificação inválido'];

            //verifica se o valor das parcelas está dentro do prêmio com margem de 0.9
            $premio_float=(float)FormatUtility::nDecimal($premio);
            $n=$sum>$premio_float?$sum-$premio_float:$premio_float-$sum;
            //dd($n,$margem,$premio);
            if($n>$margem)return false;
            //dd(123);
        }

        //parcelas
        $parcelas=count($valor);

        if($parcelas<1 || $parcelas>12)return false;

        //monta as datas de vencimentos
        $datavenc=[];
        foreach($valor as $i=>$v){
            if($i==0){
                $n=$dt_parcela_1;
            }else if($i==1){
                $n=$dt_parcela_2;
            }else{
                $n=FormatUtility::addDate(FormatUtility::convertDate($dt_parcela_2),'m',$i-1, 'datebr');//adiciona 1 mês
            }
            $datavenc[]=$n;
            $valor[$i]= FormatUtility::numberFormat($v);
        }
        //dd($valor,$datavenc,$v);
        $data = $data + self::makeTable($parcelas,$datavenc,$valor);

        return $data;
    }


    /**
     * Retorna ao uma matriz de data de parcelas e valores procurando apenas pela quantidade de valores e datas, sem considerar sua ordem (se o valor está na ordem associado a data)
     * @param $blocktext - bloco de texto onde deve procurar a informação
     * @param $premio - (string) valor do prêmio para comparar o total de parcelas. Opcional
     * @param $margem - (float) valor limite da margem para comparação da soma das parcelas com o prêmio total. Padrão 0.9.
     * @return: o mesmo de self::getTableVencParc() ou false
     */
    public static function getTableVencParc_mixed($blocktext,$premio=null,$margem=0.9){
        $datavenc = TextUtility::getSearchText($blocktext,'','datebr',['limit'=>false]);
        $valores = TextUtility::getSearchText($blocktext,'','number_formated',['limit'=>false]);
        if(count($datavenc) != count($valores))return false;//os totais precisam ser iguais

        //verifica se a soma das parcelas corresponde ao prêmio
        $total=0;
        foreach($valores as $v){
            $total+=FormatUtility::nDecimal($v);
        }

        if($premio && !self::checkPremioTotal($premio,FormatUtility::numberFormat($total),$margem))return false;

        $parcelas = count($valores);
        $data = self::makeTable($parcelas,$datavenc,$valores);
        return $data;
    }

    /**
     * Retorna aos campos de parcelas de vencimentos e valores a partir dos dados já prontos em $data
     * @param $data - dados já formatados, utiliza os campos:
     *                      fpgto_n_prestacoes,fpgto_1_prestacao_valor, fpgto_dem_prestacao_valor, fpgto_1_prestacao_venc, fpgto_venc_dia_2parcela
     * @return: retorna a array $data com os campos fpgto_datavenc_{n} e fpgto_valorparc_{n}
     * @obs: esta função não é executada em getData_formaPgto() e deve ser chamada manualmente se necessároi
     */
    public static function makeTable2($data){
        $parcelas = (int)$data['fpgto_n_prestacoes'];
        $date2=null;
        for($i=1;$i<=$parcelas;$i++){
            if($i==1){
                $d=$data['fpgto_1_prestacao_venc'];
            }elseif($i==2){
                $d=str_replace($data['fpgto_venc_dia_1parcela'],$data['fpgto_venc_dia_2parcela'],$data['fpgto_1_prestacao_venc']);
                $d=FormatUtility::convertDate($d);
                $d=FormatUtility::addDate($d,'m',$i-1,'datebr');
                $date2=$d;
            }else{
                $d=FormatUtility::convertDate($date2);
                $d=FormatUtility::addDate($d,'m',$i-1,'datebr');
            }
            if($i==1){
                $v=$data['fpgto_1_prestacao_valor'];
            }else{
                $v=$data['fpgto_dem_prestacao_valor'];
            }
            $data['fpgto_datavenc_'.$i] = $d;
            $data['fpgto_valorparc_'.$i] = $v;
        }
        return $data;
    }



    /**
     * Ordena as matrizes de $datavenc e $valor por ordem de data
     * Padrão das variáveis: $datavenc - dd/mm/aaaa, $valor 999,99
     * Return array [$datavenc, $valor]
     */
    public static function getTableOrderDate($datavenc,$valor){
        //unifica as varáveis para ordená-las
        $datavenc_valor=[];
        foreach($datavenc as $i => $dt){
            $datavenc_valor[]=['d'=> FormatUtility::convertDate($dt), 'v'=>$valor[$i] ];
        }
        //reordena a matriz
        usort($datavenc_valor, function($a, $b){
            return ($a['d'] < $b['d']) ? -1 : 1;
        });
        //ajusta a var $datavenc_valor no padrão das variáveis $datavenc e $valor
        $datavenc=[];
        $valor=[];
        foreach($datavenc_valor as $n){
            $datavenc[]=FormatUtility::dateFormat($n['d'],'date');
            $valor[]=$n['v'];
        }
        return [$datavenc,$valor];
    }

    /**
     * Monta os campos finais da forma de pagamento a partir dos dados já formatados
     * @param array $data - os dados já formatados de $data pela função self::makeTable()
     * @return array $data com os campos adicionais
     */
    public static function addFields1($data){
        //monta a string final
            $data = [
                    'fpgto_1_prestacao_valor'=>($data['fpgto_valorparc_1']??''),
                    'fpgto_1_prestacao_venc'=>($data['fpgto_datavenc_1']??''),
                    'fpgto_dem_prestacao_valor'=>($data['fpgto_valorparc_2'] ?? $data['fpgto_valorparc_1'] ?? ''),
                    'fpgto_venc_dia_1parcela'=>substr(($data['fpgto_datavenc_1']??''),0,2),//Dia de vencimento da segunda prestações (gravar com 2 dígitos)
                    'fpgto_venc_dia_2parcela'=>substr(($data['fpgto_datavenc_2'] ?? $data['fpgto_datavenc_1'] ?? ''),0,2),//Dia de vencimento da segunda prestações (gravar com 2 dígitos)
                    'fpgto_avista'=>null,
            ] + $data;


        //verifica se o pagamento é a vista ou 30 dias
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

        return $data;
    }


    /**
     * Monta o retorno final dos campos a partir dos parâmetros (complementar de getData_formaPgto())
     * @param int $parcelas - número de parcelas. Se não informado irá capturar pelo total de $datavenc
     * @param array|string $datavenc - (array) matriz de vencimentos
     *                               - (string) neste caso será adicionado 30 dias a cada após a data da parcela informada
     * @param array $valores - matriz de vencimentos
     * @param int $inicia_parcela - numero inicial da parcela para nomear os campos: datavenc_{N} e valorparc_{n}.... Default 1.
     * @param datebr $data_add - data a ser considerada caso alguma índice de $datavenc tenha um valor tenha uma string inválida. Ex: '01/01/9999' (data inválida), 'quitada',...
     * @return array - conforme documentação xls de campo de 'fpgto_...'
     * Obs1: a var $valores pode ter menos elementos que $datavenc, e neste caso o último índice se repetirá até o final do índice de $datavenc
     */
    public static function makeTable($parcelas,$datavenc,$valores,$inicia_parcela=1,$data_add=null){
        //*** formata os valores para retorno final dos campos ***
        //dd($datavenc);
        $data=[];
        if(!$parcelas)$parcelas=count($datavenc);

        if(is_string($datavenc)){//foi informado apenas uma data no formado string
            $dts=$datavenc;
            $datavenc=[];
            for($i=0;$i<$parcelas;$i++){
                $datavenc[]=FormatUtility::addDate(FormatUtility::convertDate($dts),'m',$i, 'datebr');//adiciona 1 mês
            }
        }

        for($i=0;$i<$parcelas;$i++){
            //valor
            $v = $valores[$i]??false;
            if(!$v)$v=$valores[count($valores)-1];//fica sempre com o último índice

            //data
            $dt = $datavenc[$i]??false;
            if($data_add && in_array(strtolower($dt),['01/01/9999','quitada']))$dt=$data_add;

            if(!$dt)$dt=$datavenc[count($datavenc)-1];//fica sempre com o último índice
            $data['fpgto_datavenc_'.($i+$inicia_parcela)] = trim($dt);
            $data['fpgto_valorparc_'.($i+$inicia_parcela)] = trim($v);
        }
        $parcelas=str_pad($parcelas, 2 ,'0', STR_PAD_LEFT);
        return ['fpgto_n_prestacoes'=>$parcelas] + $data;
    }


    /**
     * Retorna a matriz de datas e parelas a partir do da matriz preparada em $data
     * Ex: de $data['fpgto_datavenc_{n}', fpgto_valorparc_{n}]  - para: ['datavenc'=>[dt1,dt2...],'valor'=>[val1,val2,...]]
     */
    public static function getArrayFromData($data){
        $datavenc=[];
        $valor=[];
        foreach($data as $f=>$v){
            if(substr($f,0,15)=='fpgto_datavenc_')$datavenc[]=$v;
            if(substr($f,0,16)=='fpgto_valorparc_')$valor[]=$v;
        }
        return ['datavenc'=>$datavenc,'valor'=>$valor];
    }



    //********************* funções auxiliares ***************************

    /**
     * Captura e retorna ao valor do prêmio total do seguro
     */
    public static function getPremioTotal($text){
        $n = TextUtility::getSearchText($text,'premio total','number_formated',['sanitize'=>true]);
        if(!$n)$n = TextUtility::getSearchText($text,'total a pagar','number_formated',['sanitize'=>true]);
        if(!$n)$n = TextUtility::getSearchText($text,'valor Total do Seguro','number_formated',['sanitize'=>true]);
        return $n;
    }

    /**
     * Captura e retorna ao valor do prêmio total a partir da soma das parcelas.
     * Verifica também se o valor total das parcelas existe no texto, caso não exista retorna a vazio.
     * @param array $parcelas - valor de cada parcela
     * @param $margem   - (float) valor limite da margem para comparação da soma das parcelas com o prêmio total. Padrão 0.9.
     * @param $desc     - (number_formated) valor de desconto (opcional - válido somente para alguns casos (como a Porto)).
     *                          Obs: Deve ser aplicado somente caso a soma das parcelas tenha o valor já descontado em relação ao prêmio total.
     * @return valor final do prêmio total encontrado no texto.
     */
    public static function getPremioTotalParcela($text,$parcelas,$margem,$desc=''){
        if($desc)$desc=FormatUtility::nDecimal($desc,true);

        $total=0;
        foreach($parcelas as $p){
            $total += FormatUtility::nDecimal($p);
        }
        if($desc)$total+=$desc;

        //procura se o valor existe no texto dentro da margem
        $margem=$margem*100;//ex de 0,90 para 90
        $v=$total-($margem/100);
        $total=0;
        for($i=0;$i<($margem*2);$i++){//para acima
            if(strpos($text,FormatUtility::numberFormat($v))!==false){
                $total=$v;
                break;
            }
            $v+=0.01;
        }

        if($total>0){
            return FormatUtility::numberFormat($total);
        }else{
            return '';
        }
    }

    /**
     * Compara o valor do prêmio com a soma das parcelas
     * @param string $premio - número formatado do prêmio
     * @param array $data - demais campos já processados da apólice com os campos das parcelas existentes
     * @return boolean
     */
    /*public static function checkPremioParcela($premio,$data){
        $parcelas=[];
        for($p=1; (int)$data['fpgto_n_prestacoes']; $p++){
            $parcelas[]=$data['fpgto_valorparc_'.$p];
        }
        return !empty(self::getPremioTotalParcela($premio, $parcelas));
    }*/



    /**
     * Verifica se o valor do prêmio existe dentro de todo o texto extraído
     * @param string|float $premio
     * @param float $margem
     * @return boolean
     */
    public static function checkPremioTotal($premio,$full_text,$margem){
        //o valor do prêmio precisa existir dentro de todo o texto extraído
        if(!is_string($premio))$premio = FormatUtility::numberFormat($premio);
        $text0 = str_replace(' ','',FormatUtility::sanitizeAllText($full_text));
        if(strpos($text0,$premio)===false){//não bateu e neste caso procura pela com a margem de 1 real
            $margem=$margem*100;
            $t=false;
            $v=(float)FormatUtility::nDecimal($premio);
            for($i=0;$i<$margem;$i++){//para acima
                $v+=0.01;
                if(strpos(' '.$text0.' ',' '.FormatUtility::numberFormat($v).' ')!==false){$t=true;break;}
            }
            if(!$t){
                $v=(float)FormatUtility::nDecimal($premio);
                for($i=0;$i<$margem;$i++){//para baixo
                    $v-=0.01;
                    if(strpos(' '.$text0.' ',' '.FormatUtility::numberFormat($v).' ')!==false){$t=true;break;}
                }
            }
            return $t;
        }else{
            return true;
        }
    }


    //Lista de códigos de forma de pagamento no cadastro do quiver
    //Return array: [tipo, code]        //retorna a ['',''] caso não encontrado     //ex: [carne, 2]
    public static function getPgtoCode($fpgto_tipo){
        $fpgto_tipo=FormatUtility::sanitizeAllText($fpgto_tipo);
        //dd($fpgto_tipo);
        if($fpgto_tipo=='carne' || $fpgto_tipo=='a vista'){
            $fpgto_tipo='carne';
            $code='2';
        }elseif($fpgto_tipo=='debito c/c' || $fpgto_tipo=='debito cc' || $fpgto_tipo=='debito c c' || $fpgto_tipo=='debito'){
            $fpgto_tipo='debito';
            $code='4';
        }elseif($fpgto_tipo=='cartao de credito' || $fpgto_tipo=='cartao'){
            $fpgto_tipo='cartao';
            $code='3';
        }elseif($fpgto_tipo=='boleto' || $fpgto_tipo=='boleto com registro' || $fpgto_tipo=='ficha'){
            $fpgto_tipo='boleto';
            $code='10';
        }elseif($fpgto_tipo=='1boleto_debito'){
            $fpgto_tipo='1boleto_debito';
            $code='9';
        }elseif($fpgto_tipo=='1boleto_cartao'){
            $fpgto_tipo='1boleto_cartao';
            $code='62884';
        }else{
            $fpgto_tipo='';
            $code='';
        }
        return [$fpgto_tipo,$code];
    }


    /**
     * Verifica todos campos de 'fpgto...' da variável $dados para validar se os valores são compatíveis (total, líquido, juros, etc)
     * @param array $dados - array dos dados (campos da tabela pr_seg_data)
     * @param array $parcelas - array das parcelas, sintaxe: [1=>[field1=>...], 2=>... ](campos da tabela pr_seg_parcelas)
     * @param float|boolean $marg_parc - (float) valor limite da margem para comparação da soma das parcelas com o prêmio total. Padrão 0.9. Se ==false desativa esta verificação
     * @param float|boolean $marg_iof - (float) valor limite da diferença de centavos entre o cálculo do iof com o iof capturado em $dados. Obs: por padrão deveria ser igual, mas na prática existe diferença de centávos entre as apólices.
     * @param boolean $change_data - (boolean) se true, irá ajustar os valores de $dados conforme necessário no cálculo de verificação de pagamento, ex: As vezes o adicional já está somado no prêmio liquido, e se true, irá ajustar os respectivos valores em $dados.
     * return [success,msg,code]
     */
    public static function validateAll(&$dados,$parcelas,$marg_parc=0.9,$marg_iof=0.07,$change_data=false){

        //valida os campos de premio total, liquido, adiconal, juros, etc para que a soma de todos batam corretamente
        $total      = (float)FormatUtility::nDecimal($dados['fpgto_premio_total'] ?? null);
        $liquido    = (float)FormatUtility::nDecimal($dados['fpgto_premio_liquido'] ?? null);
        $custo      = (float)FormatUtility::nDecimal($dados['fpgto_custo'] ?? null);
        $adicional  = (float)FormatUtility::nDecimal($dados['fpgto_adicional'] ?? null);
        $servico    = (float)FormatUtility::nDecimal($dados['fpgto_premio_liq_serv'] ?? null);
        $iof        = (float)FormatUtility::nDecimal($dados['fpgto_iof'] ?? null);
        $juros      = (float)FormatUtility::nDecimal($dados['fpgto_juros'] ?? null);
        $juros_md   = (float)FormatUtility::nDecimal($dados['fpgto_juros_md'] ?? null);
        $desc       = (float)FormatUtility::nDecimal($dados['fpgto_desc'] ?? null);

        if(!$total)return ['success'=>false,'msg'=>'Prêmio total inválido','code'=>'read11'];

        //valida para que a soma das parcelas não seja diferente do prêmio total
        if($marg_parc){
            if(count($parcelas)!=(int)$dados['fpgto_n_prestacoes'])return ['success'=>false,'msg'=>'Número de parcelas não compatível','code'=>'read11'];

            $sum=0;
            foreach($parcelas as $p => $d){
                $sum+= FormatUtility::nDecimal($d['fpgto_valorparc'] ?? 0);
            }

            if($dados['fpgto_desc']){//existe desconto para comparar
                //1 - verifica se o desconto está nas parcelas
                $sum2 = $sum + $desc;//soma o desconto a soma das parcelas
                $sum2 = FormatUtility::numberFormat($sum2);
                if(!self::checkPremioTotal($dados['fpgto_premio_total'],$sum2,$marg_parc)){//divergência do prêmio total em relação a soma das parcelas
                    //2 - verifica se o desconto já está substraído do prêmio total, e neste caso a soma das parcelas já deve estar igual ao prêmio total
                    $sum2 = FormatUtility::numberFormat($sum);
                    if(self::checkPremioTotal($dados['fpgto_premio_total'],$sum2,$marg_parc)){
                        //até aqui quer dizer que a soma das parcelas e o prêmio total batem, mas como existe um desconto, calcula os valores do em relação ao prêmio total para verificar se bate
                        $tmp_total = ($liquido + $custo + $adicional + $servico + $juros + $juros_md) - $desc;
                        $tmp_iof = $tmp_total * self::$iof_perc;
                        $tmp_total += $tmp_iof;
                        $tmp_total = FormatUtility::numberFormat($tmp_total);
                        if(!self::checkPremioTotal($tmp_total,$sum2,$marg_parc)){//divergência do prêmio total em relação a soma das parcelas
                            return ['success'=>false,'msg'=>'Divergência no valor - Prêmio Total com desconto: '.$dados['fpgto_premio_total'].' - Soma das parcelas: '.$sum,'code'=>'read05'];
                        }

                        //*** Atualização de 01/06/2021 ***
                        //Se chegou até aqui, quer dizer que o desconto subtraindo do prêmio total passou por todas as validações e portanto precisa ser ajustado para:
                        //  1 - tirar o valor do desconto do prêmio líquido
                        //  2 - zerar o valor do desconto
                        //Motivo: o Quiver não tem o campo desconto, e portanto para ficar compatível precisa fazer este ajuste aqui na extração para igual os campos do arquivoer
                            //Lógica do cálculo: retira do líquido o prêmio total até bater o cálculo do iof
                            //Obs: abaixo modifica a var $dados para que seja gravado o novo valor
                            $liquido-=$desc;
                            $desc=0;
                            $dados['fpgto_premio_liquido']=FormatUtility::numberFormat($liquido);
                            $dados['fpgto_desc']='0,00';
                    }else{
                        //até aqui o prêmio total e a soma das parcelas não bateram
                        if($juros){//verifica se existe o campo de juros
                            //verifica se a soma total bate somando os juros
                            $sum2 = FormatUtility::numberFormat($sum + $juros);
                            if(self::checkPremioTotal($dados['fpgto_premio_total'],$sum2,$marg_parc)){//bateu
                                //quer dizer que os juros não estão inclusos nas parcelas
                                return ['success'=>false,'msg'=>'Juros não estão inclusos nas parcelas','code'=>'read23'];
                            }

                        }
                        return ['success'=>false,'msg'=>'Divergência no valor - Prêmio Total: '.$dados['fpgto_premio_total'].' - Soma das parcelas com desconto: '.$sum,'code'=>'read05'];
                    }
                }

            }else{//não tem desconto
                $sum=FormatUtility::numberFormat($sum);
                if(!self::checkPremioTotal($dados['fpgto_premio_total'],$sum,$marg_parc))return ['success'=>false,'msg'=>'Divergência no valor - Prêmio Total: '.$dados['fpgto_premio_total'].' - Soma das parcelas: '.$sum,'code'=>'read05'];
            }
        }


        if($marg_iof!==false){
            //verifica o iof (tem que ser 7,38%) dos valores
            $sum = ($liquido + $custo + $adicional + $servico + $juros + $juros_md) * self::$iof_perc;
            $sum = round($sum,2);
            //dd($sum,$iof);

            if(!ValidateUtility::isValueMargin($sum,$iof,$marg_iof,$marg_iof)){
                //não passou no validate, portanto verifica se o iof é calculado descontado os serviços (ex: na seguradora alfa é assim)
                $sum = ($liquido + $custo + $adicional + $juros + $juros_md) * self::$iof_perc;
                $sum = round($sum,2);

                if(!ValidateUtility::isValueMargin($sum,$iof,$marg_iof,$marg_iof)){
                    //não passou no validate, portanto verifica se o iof é calculado descontado o adicional (ex: na seguradora bradesco, algumas apólices são assim)
                    $sum = ($liquido + $custo + $servico + $juros + $juros_md) * self::$iof_perc;
                    $sum = round($sum,2);

                    //dd($liquido , $custo , $adicional , $servico , $juros, $juros_md);
                    if(!ValidateUtility::isValueMargin($sum,$iof,$marg_iof,$marg_iof)){
                        //verifica primeiro se o iof pode estar zerado, para personalizar a mensagem de erro
                        if(($liquido + $custo + $servico + $juros + $juros_md) == $total ){//os valores são iguais mesmo sem
                            return ['success'=>false,'msg'=>'IOF com valor zero','code'=>'read17'];
                        }else{
                            return ['success'=>false,'msg'=>'Divergência no valor: Iof','code'=>'read13'];
                        }
                    }
                }
            }
        }

        //verifica a soma dos valores bate com o valor do prêmio
        $sum = $liquido + $custo + $adicional + $servico + $iof + $juros + $juros_md;
        $sum = FormatUtility::numberFormat($sum);
        if(!self::checkPremioTotal($dados['fpgto_premio_total'],$sum,$marg_parc)){
            //não passou no validate, portanto verifica a soma sem considerar o adicional (que já deve estar somando ao prêmio liquido)
            //neste caso verifica o valor exato (sem considerar a margem aceita)
            $sum = $liquido + $custo + $servico + $iof + $juros + $juros_md;
            $sum = FormatUtility::numberFormat($sum);
            if($sum!=$dados['fpgto_premio_total']){
                return ['success'=>false,'msg'=>'Divergência nos valores: Prêmio Total + Líquido + Custo + Adicional + Serviços + Juros + Iof','code'=>'read12'];
            }
            //até aqui passou no validade
            if($change_data===true){
                //altera o valor do liquido para não incluir o adicional
                $liquido=$liquido - $adicional;
                $dados['fpgto_premio_liquido'] = FormatUtility::numberFormat($liquido);
            }
        }

        if($liquido==0)return ['success'=>false,'msg'=>'Valor do prêmio líquido inválido','code'=>'read12'];


        //verifica a ordem das parcelas
        $n1=null;
        $n2=null;
        for($i=1;$i<=count($parcelas);$i++){
            $n1 = $parcelas[$i]['fpgto_datavenc'] ?? false;
            $n2 = $parcelas[($i+1)]['fpgto_datavenc'] ?? false;
            if($n1===false || $n2===false)continue;
            if(ValidateUtility::ifDate($n1,'>',$n2)){
                return ['success'=>false,'msg'=>'Vencimento das parcelas fora de ordem','code'=>'read21'];
            }
        }
        //dd('passou');


         //verifica a ordem das parcelas e parcelas iguais
        $n1=null;
        $n2=null;
        $last_n=null;
        for($i=1;$i<=count($parcelas);$i++){
            $n1 = $parcelas[$i]['fpgto_datavenc'] ?? false;
            $n2 = $parcelas[($i+1)]['fpgto_datavenc'] ?? false;

            //verifica se as parcelas estão fora de ordem
            if($n1===false || $n2===false)continue;
            if(ValidateUtility::ifDate($n1,'>',$n2)){
                return ['success'=>false,'msg'=>'Vencimento das parcelas fora de ordem','code'=>'read21'];
            }

            //verifica se as parcelas são iguais - lógica: a primeira parcela e a segunda as vezes podem ser iguais, mas da terceira em diante não pode ser
            if($i>2){
                if($last_n==$n1)return ['success'=>false,'msg'=>'Vencimento das parcelas não podem ser iguais','code'=>'read22'];
            }

            $last_n=$n1;
        }
        //dd('passou');


        return ['success'=>true,'msg'=>'','code'=>'ok'];
    }



    /**
     * Retorna aos campos adicionais relacionais ao prêmio
     * @return array
     * @obs chamar esta função após $data já estar com todos os campos preenchidos
     */
    public static function getFielsPremioAdd($blocktext,$data,$opt=[]){
        $opt = array_merge([
            'tem_juros' => false,
            //abaixo informar valor numérico (opcional)
            'premio_liquido'=>false,
            'premio_liq_serv'=>false,
            'custo'=>false,
            'adicional'=>false,
            'iof'=>false,       //false - captura automática, 'auto' - calcula automaticamente sob outros valores, (number) valor do iof informado
            'juros'=>false,
            'juros_md'=>false,
            'desc'=>false,
        ],$opt);

        $blocktext = str_replace(' 0,0 ', ' 0,00 ', $blocktext);
        //dd($blocktext);

        $premio_liquido=0;
        $premio_liq_serv=0;
        $custo=0;
        $adicional=0;
        $iof=0;
        $juros=0;
        $juros_md=0;
        $desc=0;

        if($opt['premio_liquido']===false){//automático
            $premio_liquido= TextUtility::getSearchText($blocktext,'sub-total:','number_formated',['side'=>'right']);
                if(!$premio_liquido)$premio_liquido= TextUtility::getSearchText($blocktext,'premio liquido','number_formated',['side'=>'right']);
                if(!$premio_liquido)$premio_liquido= TextUtility::getSearchText($blocktext,'liquido das coberturas','number_formated',['side'=>'right']);
        }else{
            $premio_liquido = $opt['premio_liquido'];
        }


        if($opt['premio_liq_serv']===false){//automático
            //em desenvolvimento
        }


        if($opt['custo']===false){//automático
            $custo=TextUtility::getSearchText($blocktext,'custo de apolice','number_formated',['side'=>'right']);
            if(!$custo ){
                $custo  = '0,00';
            }
        }else{
            $custo = $opt['custo'];
        }

        if($opt['adicional']===false){//automático
            $adicional=TextUtility::getSearchText($blocktext,'Adicional','number_formated',['side'=>'right']);
            if(!$adicional){
                $adicional= TextUtility::getSearchText($blocktext,'adic.','number_formated',['side'=>'right']);
            }
            if(!$adicional){
                $adicional= TextUtility::getSearchText($blocktext,'adicionais','number_formated',['side'=>'right']);
            }
            //dd($adicional);
            if(!$adicional ){
                $adicional  = '0,00';
            }
        }else{
            $adicional = $opt['adicional'];
        }

        if($opt['iof']===false){//automático
            $iof=TextUtility::getSearchText($blocktext,'iof','number_formated',['side'=>'right']);
            if(!$iof){
                $iof= TextUtility::getSearchText($blocktext,'I.O.F','number_formated',['side'=>'right']);
            }
        }elseif($opt['iof']==='auto'){//calcula
            $iof =  (float)FormatUtility::nDecimal($premio_liquido) +
                    (float)FormatUtility::nDecimal($premio_liq_serv) +
                    (float)FormatUtility::nDecimal($custo) +
                    (float)FormatUtility::nDecimal($adicional) +
                    (float)FormatUtility::nDecimal($juros);
            $iof *= self::$iof_perc;
            $iof = FormatUtility::numberFormat($iof);

        }else{
            $iof = $opt['iof'];
        }

        if($opt['juros']===false){//automático
            if($opt['tem_juros']==true){
                $juros=TextUtility::getSearchText($blocktext,'valor juros','number_formated',['side'=>'right']);
                if(!$juros){
                    $juros=TextUtility::getSearchText($blocktext,'juros','number_formated',['side'=>'right']);
                }
               // dd($juros);
            }else{
                $juros='0,00';
            }
        }else{
            $juros = $opt['juros'];
        }

        //juros melhor data
        if($opt['juros_md']!==false){
            $juros_md = $opt['juros_md'];
        }

        //desconto
        if($opt['desc']!==false){
            $desc = $opt['desc'];
        }


        $r = [
            'fpgto_premio_liquido'=>$premio_liquido,
            'fpgto_premio_liq_serv'=>$premio_liq_serv,
            'fpgto_custo'=>$custo,
            'fpgto_adicional'=>$adicional,
            'fpgto_iof'=>$iof,
            'fpgto_juros'=>$juros,
            'fpgto_juros_md'=>$juros_md,
            'fpgto_desc'=>$desc,
        ];

        //converte de (int)0 para (str) 0,00
        foreach($r as &$n){if($n===0)$n='0,00';}

        return $r;
    }



    /**
     * Gera as parcelas e vencimentos a partir do valor total e quantidade de parcelas
     * @param string $premiototal - valor total do premio - formato 9.999,99
     * @param int $nparcela - número total de parcelas
     * @param string $venc_inicial - data inicial para o vencimento das parcelas - formato dd/mm/aaaa
     */
    public static function makeTable3($premiototal,$nparcela,$venc_inicial){
        $nparcela = (int)$nparcela;
        if($nparcela==1){//existe apenas 1 parcela
            $list=[1=>$premiototal];
        }else{//existem mais 2 parcelas ou mais
            $premiototal = FormatUtility::nDecimal($premiototal,true);
            $valor_parcela = $premiototal / FormatUtility::nDecimal($nparcela,true);
            //dd(FormatUtility::numberFormat($valor_parcela));
            $parcela_total = $valor_parcela * $nparcela; //vakl
            $diff_total = $parcela_total<$premiototal ? $premiototal-$parcela_total : 0;

            //monta a lista de parcelas
            $list=[1=>$valor_parcela+$diff_total];
            for($i=2;$i<=$nparcela;$i++){
                $list[$i] = FormatUtility::numberFormat($valor_parcela);
            }
        }
        return self::makeTable($nparcela,$venc_inicial,$list);
    }


    /**
     * Verifica se existem datas válidas nos textos (mesmo que estejam grudados nas laterais sem espaço) e adiciona espaço nas alterais das datas
     */
    public static function addSpaceDateText($text){
        $count=0;
        preg_match_all('/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/',$text,$matches);
        if($matches[0]??null){
            foreach($matches[0] as $dt){//loop das datas encontradas no texto
                $i = strpos($text,$dt);
                $s1 = substr($text,$i-1,1);//captura o caractere antes do texto
                $i += strlen($dt);
                $s2 = substr($text,$i,1);//captura o caractere depois do texto
                $text = str_replace($dt, ' '.$dt.' ' ,$text);
                $count++;
            }
        }
        return ['count'=>$count, 'text'=>$text];
    }
}
