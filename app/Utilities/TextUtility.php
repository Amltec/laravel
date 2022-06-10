<?php
namespace App\Utilities;
use App\Utilities\ValidateUtility;
use App\Utilities\FormatUtility;

/*
 * Classe com funções de extrações de parte do texto em geral.
 * Usados identificar e capturar partes de texto, ex: datas, números moeda, etc...
 */
Class TextUtility{
    
    /**
     * Verifica se é um um número pelos menos assim: 99,99 ou 9.999,99 (precisa ter virgula)
     */
    public static function isNumberFormated($str){
        if(trim($str)!=''){
            return strpos($str,',')!==false && ValidateUtility::isNumberStr($str) && strlen(explode(',',substr($str,-3))[1]??'')==2;
        }else{
            return false;
        }
    }
    
    /**
     * Verifica se é um número contendo 2 digitos, ex: 02, 10, 99. Fora deste intervalo retorna a false
     */
    public static function isNumber02($str){
        $n=trim($str);
        return strlen($n)==2 && is_numeric($n);
    }
    
    /**
     * Verifica se é um cep valido
     */
    public static function isCep($str){
        $v=str_replace(['.','-'],'',trim($str));
        return is_numeric($v) && strlen($v)==8;
    }
    
    /**
     * Verifica se é um cep formatado valido
     * Sintaxe: 99999-999
     */
    public static function isCepFormated($str){
        $v=str_replace('.','',trim($str));
        if(strpos($v,'-')===false)return false;
        $n=explode('-',$v);
        if(count($n)!=2)return false;
        return is_numeric($n[0]) && strlen($n[0])==5 && is_numeric($n[1]) && strlen($n[1])==3;
    }
    
    /**
     * Verifica se é um dado do tipo físico ou jurídico.
     * Considera dos textos: física, jurídica
     */
    public static function isTipoFJ($str){
        $v=FormatUtility::sanitizeAllText($str);
        return $v=='fisica' || $v=='juridica';
    }
    
    /**
     * Verifica se é um dado do tipo masculino, feminino
     * Considera dos textos: física, jurídica
     */
    public static function isSexo($str){
        $v=FormatUtility::sanitizeAllText($str);
        return $v=='masculino' || $v=='feminino';
    }
    
    /**
     * Verifica se é um dado do tipo Sim/Não
     */
    public static function isSimNao($str){
        $v=FormatUtility::sanitizeAllText($str);
        return $v=='sim' || $v=='nao';
    }
    
    /**
     * Verifica se é um ano válido.
     * Lógica: 4 digitos numéricos e > 1900 , 
     * @param $range_now - se >0 considera a quantidade de anos de intervalo com o ano atual que é aceita. Default 0. Ex: 20: aceita 2 anos para + ou -, caso contrário retorna a false.
     */
    public static function isAno($str,$range_now=0){
        $v=(string)trim(str_replace(['.',',','-'],'x',$str));//acrescenta x nos caracteres que invalidam um tipo 'ano'
        if(strlen($v)==4 && is_numeric($v)){
            $v=(int)$v;
            if($v<1900)return false;
            if($range_now>0){
                $a = (int)$year=date('Y');
                $v = $v>$a ? $v-$a : $a-$v;
                if($v>$range_now)return false;
            }
            return true;
        }
        return false;
    }
    
    
    
    /**
     * Procura na string uma data por extenso no formato '{dd} de {mmmm} de {yyyy}'
     * @param $text - texto
     * @param $format - formato de saída. Valores: date, datebr, extenso (default)
     */
    public static function getDateExtenso($text,$format='extenso'){
        //$str = self::getSearchText($text,'', $fnc)
        $text0 = FormatUtility::sanitizeAllText($text);
        $mes1=['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
        $mes2=['janeiro','fevereiro','marco','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
        
        //primeiro procura se existe o mês por extenso
        $m='';
        $nx = explode(' ',$text0);
        foreach([$mes2,$mes1] as $mes_arr){
            foreach($mes_arr as $a){
                foreach($nx as $b){
                    if($a==$b){
                        $m=$a;break;
                    }
                }
                if($m)break;
            }
            if($m)break;
        }
        if(!$m)return '';
        
        //captura o valor numérico do mês
        $m_int=0;
        foreach($mes1 as $i=>$a){
            if(substr($m,0,3)==$a)$m_int=$i+1;
        }
        if(!$m_int)return '';
        $m_int = str_pad($m_int, 2, '0', STR_PAD_LEFT);
        
        //procura pelo dia na esquerda (formato 99)
        $d = self::getSearchText($text0,$m,'number',['side'=>'left']);
        if(!$d)return '';
        $d = str_pad($d, 2, '0', STR_PAD_LEFT);
        
        //procura pelo ano na direita (formato 9999)
        $a = self::getSearchText($text0,$m,'ano');
        if(!$a)return '';
        
        if($format=='datebr'){
            return $d .'/'. $m_int .'/'. $a;
        }elseif($format=='date'){
            return $a .'-'. $m_int .'-'. $a;
        }else{
            $meslabel=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
            return $d .' de '. $meslabel[(int)$m_int-1] .' de '. $a;
        }
    }
    
    
   /**
    Retorna a parte da string.
    @param array $args:
            string|int start|end	- texto ou posição inicial|final(a partir do inicial) a ser localizado (valores iniciam em 0 (zero) )
                                                                    Será retornado aos textos entre a localização destes paramêtros.
                                                                    Ex1: getPartOfStr('Casa verde rosa ok', ['start'=>0, 'end'=>-2 ])				//Retornará a 'casa verde rosa'
                                                                    Ex2: getPartOfStr('Casa verde rosa ok', ['start'=>'casa', 'end'=>'ok' ])		//Retornará a 'verde rosa'

            string not				- texto negado. Se tiver este texto na string, ignora a segue com a busca.
            int|array page			- caso o texto seja separado em páginas. Irá procurar pelo 'start|end' somente dentro da página especificada. Ex: 'page'=>2, ou 'page'=>[2,3]	//considera as páginas 2 e 3
                                                                    Obs: as strings separados de páginas são: {page-start:{page}}. Ex: {page-start:1}... {page-end:1}

            boolean|string|array trim	- executa a função trim() no resultado. Default true. Ex array: ['*',',']		//remove para o ex: *meu texto,* 	retorna 'menu texto'
                                          aceita também o valor ===true para indicar o uso do comando trim() padrão
            string|array remove		- caracteres a serem removidos.
            boolean sanitize            - se true irá limpar toda a string, removendo espaços acentos, deixando tudo minúsculo, etc. Default false. 
                                            Obs: se true, será aplicado antes de executar os demais processos desta função sobre o texto.

            string split			- caractere divisor que irá quebrar a string para procurar entre estas partes. Defautl false.
            //abaixo válido somente se definido o 'split' acima
            int	count				- conta qual ocorrência deve considerar (somente se definido o 'split' acima). Valores: 1 (default) primeiro, 2 segunda... -1 última ocorrência
                                                                                            Ex: de getPartOfStr('Nome: João', [ 'remove'=>['nome:'] ])		//Retornará a 'João'
            string return_type		- tipo do retorno. Valores: 
                                                                            ''			- corresponde a item atual na string
                                                                            prev|next	- corresponde ao todo item anterior|seguinte (sem filtro) em relação ao item encontrado do loop.
                                                                                                            Neste caso somente os parâmetros 'split' e 'remove' são aplicados
                                                                                                            Obs: se for user uma destas opções, evite usar o parâmetro 'count' acima (pois pode gerar confusão, mas funciona perfeitamente).
                                                                            prev2|next2|prev3|next3	- idem acima, mas correspondente ao item x2,x3
            function cb				- função a ser executado sob o resultado. No primeiro parâmetro, recebe o valor retornado. Ex: function($v){return is_numeric($v);}
            boolean|string required	- se definido irá retornar a um erro caso o valor seja vazio. Valores:
                                                                    false			- default - não valida.
                                                                    true || '*'		- requerido (valida se não vazio)
                                                                    'number'		- valida se é um número
                                                                    'date'			- valida se é uma data
                                                                    'sn'			- valida se a responsa é 'Sim' ou 'Não'
                                                                    ... obs: em desenvolvimento esta parte....
            int|array side_len           - (int) comprimento da margem de texto retornado no resultado. Default 0. Ex getPartOfStr('texto de exemplo',['start'=>'de','side_len'=>2]), retornará a 'o de e'
                                           (array[left,right]) comprimento na esquerda e direita. Ex: side_len=[0,10) //somente 10 caracteres a mais na direita
    @return string
    */
    public static function getPartOfStr($text,$args){
        $args = array_merge([
                'start'=>0,
                'end'=>strlen($text),
                'not'=>'',
                'page'=>false,
                'split'=>false,
                'sanitize'=>false,
                'return_type'=>'',
                'count'=>1,
                'trim'=>true,
                'remove'=>false,
                'cb'=>false,
                'required'=>false,
                'side_len'=>false,
        ],$args);
       // dd($args);
       // if($args['sanitize'])$text=self::sanitizeText($text);
        if($args['sanitize'])$text= FormatUtility::sanitizeText($text);

        if($args['page']){
                //captura somente o texto da página especificada
                if(!is_array($args['page']))$args['page']=[$args['page']];
                $r='';
                foreach($args['page'] as $pg){
                        $r.= self::getPartOfStr($text,[
                                'start'=>'{page-start:'.$pg.'}',
                                'end'=>'{page-end:'.$pg.'}',
                                'remove'=>['{page-start:'.$pg.'}','{page-end:'.$pg.'}']
                        ]);
                };
                $text=$r;
        };
        
        if($args['split']){$text=explode($args['split'],$text);}else{$text=[$text];}

        $count=1;
        $r='';
        $line2='';
        foreach($text as $index => $text_part){
                $pi=$args['start'];
                $pf=$args['end'];
                if($pi<0)$pi=0;
                if(is_string($args['start'])){
                        $pi=stripos($text_part,$args['start']);
                        if($pi===false)continue;
                        //$pi=$pi + strlen($args['start']);
                }
                if(is_string($args['end'])){
                        $pf=stripos($text_part,$args['end']);
                                //$tmp=str_repeat('*',$pi+strlen($args['start'])) .substr($text_part,$pi+strlen($args['start']));
                                //$pf=stripos($tmp,$args['end']);
                        if($pf===false){$pf=0;}else{$pf+=strlen($args['end']);}
                        if($pf>-1)$pf=$pf-$pi;
                        //dd([$pf],1);
                }
                
                if($args['side_len']){
                    if(is_array($args['side_len'])){
                        list($x1,$x2)=$args['side_len'];
                    }else{//int
                        $x1=$x2=$args['side_len'];
                    }
                    $pi-=$x1;
                    if($pi<0)$pi=0;
                    $pf+=($x2*2);
                }
                
                $r=substr($text_part, $pi, $pf);
                $r=self::getPartOfStr_fx01($r,$args);
                //dd([$pi,$pf],1);
                //dd([$index,$text_part,$r],1);

                if(stripos($text_part,$args['not'])!==false){//achou um texto negado
                        $r='';//limpa a string para continuar
                };

                if(empty($r)){//item vazio, continua para o próximo loop
                        continue;
                }else{
                        if($args['return_type']=='prev'){//retorna a toda linha anterior
                                $r=array_get($text,$index-1);
                        }elseif($args['return_type']=='next'){//retorna a toda linha seguinte
                                $r=array_get($text,$index+1);
                        }elseif($args['return_type']=='prev2'){//retorna a toda linha anterior x2
                                $r=array_get($text,$index-2);
                        }elseif($args['return_type']=='next2'){//retorna a toda linha seguinte x2
                                $r=array_get($text,$index+2);
                        }elseif($args['return_type']=='prev3'){//retorna a toda linha anterior x3
                                $r=array_get($text,$index-3);
                        }elseif($args['return_type']=='next3'){//retorna a toda linha seguinte x3
                                $r=array_get($text,$index+3);
                        }

                        $r=self::getPartOfStr_fx01($r,$args);
                }


                if($count==$args['count'])break;//para retornar ao número de ocorrência encontrada
                $count++;
        }

        if($args['cb'] && is_callable($args['cb']))$r=callstr($args['cb'],['r'=>$r],true);
        
        
        if(isset($args['breaks']))exit('comando TextUtility::getPartOfStr(["breaks"] desabilitado');
        /*if($args['breaks']===false || $args['breaks']=='all'){
            $r= self::trimAll($r);
            if($args['breaks']=='all')$r= strtolower(self::removeAcents($r));
        }*/
        
        return $r;
    }
    
    
    //Função auxiliar de getPartOfStr()
    private static function getPartOfStr_fx01($r,$args){
        if($args['remove']){
            $rem=is_array($args['remove'])?$args['remove']:[$args['remove']];
            foreach($rem as $rem_i){
                $r=str_ireplace($rem_i,'',$r);
            }
        }
        if($args['trim']){
            if(is_array($args['trim'])){
                foreach($args['trim'] as $trx){
                    if($trx===true){$r=trim($r);}else{$r=trim($r,$trx);}
                }
            }else{
                if($args['trim']===true){$r=trim($r);}else{$r=trim($r,$args['trim']);}
            }
        }
        return $r;
    }
    
    
    
    /**
     * Executa a função $fnc percorrendo toda a string para extrair uma parte do texto.
     * @param string $str - toda a string a ser pesquisada
     * @param int $len - comprimento do texto a ser percorrido
     * @param function $fnc - função a ser executada no comprimento do texto. Deve retornar a true ou false (neste caso retorna a find=null) para encerrar o loop. Recebe como primeiro parâmetro o texto atual
     * @param int $side_len - comprimento do texto dos parâmetros informados na função $fnc $left e $right. Valores: =0 (default) nenhum ou >0. Usado para poder pesquisar nas vars $left|$right como valores aproximados.
     * @return array[0 find,1 left,2 right]
     *      find - respectivo texto encontrado
     *      left - texto à esquerda
     *      right - texto à direita
     *      Obs: caso um dos valores seja inválido, retornará a null
     * Ex: execFncInStr('Peso 80 Kg e tenho 20 anos',2,function($v,$left,$right){
     *          if(is_numeric($v) && strlen((int)$v)==2 && trim(substr($right,0,5))=='anos')return true;
     *     },10) //return [20,'...tenho ',' anos ...']      //,10 é usado para pesquisar apenas nos 10 carac da esquerda ou direita
     */
    public static function execFncInStr($str,$len,$fnc,$side_len=0,$start_init=0){//$start_init - uso interno da função
        $start = $start_init;
        $end = $len;
        $count = strlen($str);
        
        $find = substr($str, $start, $end);
        $left = substr($str, 0, $start);
        if(($start+$end)>strlen($str)){//terminou a string
            $right = substr($str, $start+$end);
        }else{
            $right = substr($str, $start+$end, $count);
        }
        
        if($side_len>0){
            $left=substr($left,-$side_len);
            $right=substr($right,0,$side_len);
        }
        
        if(($start+$end)>strlen($str)){//terminou a string
            return [null,$left,$right];;
        }else{
            $r=callstr($fnc,[$find,$left,$right],true);
            if(is_bool($r)){
                return [$r?$find:null, $left, $right];
            }else{//loop
                return self::execFncInStr($str,$len,$fnc,$side_len,$start+1);
            };
        }
    }
    
    /**
     * O mesmo da função execFncInStr(), mas utilizando expressão regular (tem mais desempenho)
     * Obs: é semelhante a função execFncInStr(), mas com melhor desempenho
     * @param string $regx - expressão regular da pesquisa
     * @param string $str - toda a string a ser pesquisada
     * @param function $fnc - função a ser executada para cada item encontrado. Se o retorno da função for diferente null, encerra o loop com o respectivo retorno. Recebe como primeiro parâmetro o texto atual
     * @return sem retorno
     * @example Ex: execFncInStr('/[0-9]{2}/','Peso 80 Kg e tenho 20 anos',function($v,$left,$right){
     *          if(is_numeric($v)=='80')return true;
     *     },10) //return [80,'Peso ',' Kg...']
     */
    public static function exec2FncInStr($regx,$str,$fnc) {
        preg_match_all($regx,$str,$matches,PREG_OFFSET_CAPTURE);
        if(empty($matches[0]))return [null,'',''];
        foreach($matches[0] as $arr){//loop das datas encontradas no texto
            if(!$arr[0])continue;
            $find = $arr[0];
            $pos = $arr[1];
            
            $left = substr($str,0,$pos);
            $right = substr($str,$pos + strlen($find));
            
            $r=callstr($fnc,[$find,$left,$right],true);
            if(!is_null($r))return $r;
        }
    }
    
    
    /** Funções automáticas para as funções: getSearchText() e getSearchTextInColumns()
     * @param $fnc  - (string) filtro de conteúdo, valores: 
     *                          number_formated, number02, numberstr, number, datebr, cpf, cnpj, document (cpf ou cnpj), mail, phone, cep, cep_formated
     *                          tipofj, sexo, simnao, ano
     *                          value (primeiro valor encontrado)
     *                (function) função a ser executada para cada palavra encontrada. Deve retornar ao texto desejado para encerrar o loop. 
     *                           Recebe os parâmetros: ($find,$params...)
     * @param $val      - parâmetro informado em $fnc
     * @param $params   - (array) parâmetros adicionais informados em $fnc ($val = function). 
     * @return resultado de $fnc
     *                  Ex: self::autoStrFnc(function($v,$a,$b),'meu valor',[$param1,$param2]);
     */
    private static function autoStrFnc($fnc,$val,$params=[]){
        $r='';
        $v=$val;
        switch($fnc){
        case 'number_formated': 
            if(TextUtility::isNumberFormated($v))$r=$v;
            break;
        case 'number': 
            if(is_numeric($v))$r=$v;
            break;
        case 'cep_formated': 
            if(TextUtility::isCepFormated($v))$r=$v;
            break;
        case 'datebr':
            if(ValidateUtility::isDate($v))$r=$v;
            break;
        case 'cpf': case 'cnpj': case 'document': case 'mail': case 'phone': case 'numberstr':
            $f='is'.$fnc;
            if(ValidateUtility::$f($v))$r=$v;
            break;
        case 'cep': case 'tipofj': case 'sexo': case 'simnao': case 'ano': case 'number02':
            $f='is'.$fnc;
            if(TextUtility::$f($v))$r=$v;
            break;
        case 'value':
            $r=$v;
            break;
        default:
            if(is_callable($fnc)){
                $new_param = array_merge([$v],$params);
                $v=callstr($fnc,$new_param,true);
                if($v!='')$r=$v;
            }else{//retorna a vazio, pois o parâmetro $fnc não é uma função
                $v='';
            }
        }
        return $r;
    }
    
    /**
     * Retorna ao texto ao redor do texto em $find encontrado.
     * A pesquisa é feita por caracteres separados por espaço
     * @param $text - texto completo
     * @param $find - texto de referência para localizar o conteúdo. Se '', então irá procurar em todo o texto;
     * @param $fnc  - (string|fnc) filtro de conteúdo. Detalhes na função: self::autoStrFnc().
     * @param $opt  - (array) opções:
     *                  side        - (string) direção da pesquisa. Valores: left, right (default), all (primeiro procura na esquerda e depois na direita)
     *                  max_words   - (int) número máximo de palavras ao redor do texto $find para referência para localizar o conteúdo. Default '0 (nenhum). Obs: se $side='all', este valor é para cada lado
     *                  limit       - (int) limite de resultados. Se =1 (default) return string. Se >1 return array de valores. 0 ou false, sem limite.
     *                  sanitize    - (booelan) se true irá limpar todo o texto com a função FormatUtility::sanitizeAllText(). Default false.
     * @return string - texto encontrado ou '' caso não encontre o conteúdo.
     * 
     * Ex: return TextUtility::getSearchText($this->text,'E-mail','mail',['sanitize'=>true]);
     */
    public static function getSearchText($text,$find,$fnc,$opt=[]){
        $opt=array_merge(['side'=>'right','max_words'=>0,'limit'=>1,'sanitize'=>false],$opt);
        if($opt['sanitize']){
            //deixa todo o texto minusculo e sem acento
            $text0 = FormatUtility::sanitizeAllText($text);
            $find = FormatUtility::sanitizeAllText($find);
        }else{
            //retira somente as quebras de linha
            $text0 = FormatUtility::sanitizeBreakText($text);
            $find = FormatUtility::sanitizeBreakText($find);
        }
        
        $limit = $opt['limit'];if($limit===false)$limit=0;
        
        $n='';
        $side = $opt['side'];
        if($find){//procura em parte do texto
            if($side=='all' || $side=='left'){
                $n.=TextUtility::getPartOfStr($text0,['end'=>$find,'remove'=>$find]);
            }elseif($side=='right'){//se $side==all, então apenas no próximo loop desta função é que será executado este código
                $n.=TextUtility::getPartOfStr($text0,['start'=>$find,'remove'=>$find]);
            }else{
                return '';
            }
        }else{//procura em todo o texto
            $n=$text0;
        }
        $list = explode(' ',$n);
        if($side=='all' || $side=='left')$list = array_reverse($list);//inverte a ordem pois tem que procurar no final para ser mais próximo do resultado encontrado
        
        $r=[];
        $count=0;
        $left='';
        $right='';
        foreach($list as $i=>$v){
            //Lógica: como $v já tem as palavras separados por espaço, então o comando trim retira alguns caracteres separadores que podem existir no texto
            $v=trim($v,', ; / \\ .');
            
            $n=$list[$i-1]??'';if($n)$left.=$n.' ';
            $n=array_slice($list,$i+1,count($list))??[];$right=join(' ',$n);
            
            /*switch($fnc){
            case 'number_formated': 
                if(TextUtility::isNumberFormated($v))$r[]=$v;
                break;
            case 'number': 
                if(is_numeric($v))$r[]=$v;
                break;
            case 'cep_formated': 
                if(TextUtility::isCepFormated($v))$r[]=$v;
                break;
            case 'datebr':
                if(ValidateUtility::isDate($v))$r[]=$v;
                break;
            case 'cpf': case 'cnpj': case 'document': case 'mail': case 'phone': case 'numberstr':
                $f='is'.$fnc;
                if(ValidateUtility::$f($v))$r[]=$v;
                break;
            case 'cep': case 'tipofj': case 'sexo': case 'simnao': case 'ano': case 'number02':
                $f='is'.$fnc;
                if(TextUtility::$f($v))$r[]=$v;
                break;
            case 'value':
                $r[]=$v;
                break;
            default:
                if(is_callable($fnc)){
                    $v=callstr($fnc,[$v,trim($left),trim($right)],true);
                    if($v)$r[]=$v;
                }else{//retorna a vazio, pois o parâmetro $fnc não é uma função
                    $v='';
                }
            }*/
            $v0=$v;
            $v=self::autoStrFnc($fnc,$v,[trim($left),trim($right)]);
            if($v!='')$r[]=$v;
         
            if($opt['max_words']>0 && $count>=$opt['max_words'])break;
            if($limit>0 && is_array($r) && count($r)>=$limit)break;
            if($v0)$count++;//conta somente se for diferente de vazio
        }
        if($r){
            if($limit==1 && count($r)==1)$r=$r[0];//só tem um resultado e portanto retorna a uma string
        }else{
            $r=$limit==1?'':[];
        }
        
        if($side=='all'){//executa do lado direito
            $opt['side']='right';
            $n=self::getSearchText($text,$find,$fnc,$opt);
            if($r && $n){
                if(is_array($n) || is_array($r)){//pelo menos um dos retornos é matriz, portanto este retorno deve ser uma matriz
                    if(!is_array($n))$n=[$n];
                    if(!is_array($r))$r=[$r];
                    $r=$r+$n;//cancatena os resultados
                }
            }elseif($n && !$r){
                $r=$n;
            }else{//!$n && $r
                //o resultado já está em $r
            }
            
        }
        
        return $r;
    }
    
    
    
    /**
     * Retorna a dados extraídos a partir textos colunados.
     * Ex de texto: 'col1 col2 col3 val1 val2 val3'... neste caso 'col1' contém o valor de 'val1' e assim por diante..
     * Utiliza espaços para separar os caracteres.
     * @param $text - (string) texto completo.
     * @param $find - (string) informar o texto de referência para localizar o conteúdo e os respectivos campos a serem retornados.
     *                  Obs: informar apenas: 'col1 col2 col3', e espera que o texto seguinte seja 'val1 val2 val3'.
     * @param $fields - (array) respectivos campos a serem retornados
     *                  Sintaxe: [find => [field1, field2, field3,...]  //obs: aceita também um segundo find  para pesquisa dentro do array
     *                  Ex entrada: ['col1 col2 col3'=>['field1', 'field2', 'field3']
     *                  Ex retorno: ['field1'=>val1, 'field1'=>val2, 'field1'=>val3]
     *                  Obs: também aceita 'field'=>$fnc(string|fnc) (filtro de conteúdo). Detalhes na função: self::autoStrFnc()
     * 
     * @param $opt  - (array) opções:
     *                  side        - (string) direção da pesquisa. Valores: left, right (default)
     *                  limit       - (int) limite de resultados. Se >1 or false retorna a uma matriz de resultados. Ex: [ ['field1'=>val1, ... ], ...]
     *                                Obs: se >0|false - irá funcionar apenas para side=right
     *                  sanitize    - (booelan) se true irá limpar todo o texto com a função FormatUtility::sanitizeAllText(). Default false.
     * @return array (encontrado) ou false se não encontrado
     */
    public static function getSearchTextInColumns($text,$find,$fields,$opt=[]){
        $opt=array_merge(['side'=>'right','limit'=>1,'sanitize'=>false],$opt);
        if($opt['sanitize']){
            //deixa todo o texto minusculo e sem acento
            $text = FormatUtility::sanitizeAllText($text);
            $find = FormatUtility::sanitizeAllText($find);
        }else{
            //retira somente as quebras de linha
            $text = FormatUtility::sanitizeBreakText($text);
            $find = FormatUtility::sanitizeBreakText($find);
        }
        //retira os espaços
        $text = FormatUtility::trimAll($text);
        $find = FormatUtility::trimAll($find);
        
        if($opt['side']=='left'){
            if($find)$text = self::getPartOfStr($text,['end'=>$find,'remove'=>$find]);
            if(empty($text))return false;
            //conta a quantidade de palavra esperadas em $fields e corta a string da direita para esquerda (para ficar compatível com a programação abaixo)
            $tmp = explode(' ',$text);
            $tmp = array_slice($tmp, count($tmp)-count($fields), count($tmp));
            $text = join(' ',$tmp);
        }else{//right
            if($find)$text = self::getPartOfStr($text,['start'=>$find,'remove'=>$find]);
            if(empty($text))return false;
        }
        $limit = $opt['limit'];if($limit===false)$limit=0;
        //dd($find,$text);
        $ret=[];
        $find_text = explode(' ',$text);
        $i=$x=0;
        $left=[];
        $right=$find_text;
        
        for($count=0;$count<=100;$count++){//limite de 100 loops
            //if(!$find_text)break;
            //dump(count($ret) .'|'. $count);
            $count_empty=0;
            foreach($fields as $f=>$fnc){
                unset($right[0]);//tira o primeiro índice que corresponde ao valor $v já encontrado
                
                if(is_numeric($f)){$f=$fnc;$fnc=null;}//ajusta para ficar sempre [field=>fnc|null]
                $v = $find_text[$i]??'';
                
                //dump([$left,$right]);
                if(!trim($v))$count_empty++;
                if($fnc){
                    $v=self::autoStrFnc($fnc,$v,[$left,$right]);
                }
                
                if(!isset($ret[$count]))$ret[$count]=[];
                $ret[$count][$f] = $v;
                
                array_push($left,$find_text[$i]??'');
                $right=array_slice($find_text,$i+1);
                $i++;
            }
            if($count_empty>0 && $count_empty=count($fields)){//todos os campos estão vazio, encerra o loop (acabou o trecho válido de texto)
                unset($ret[$count]);
                break;
            }
            
            $t=true;
            foreach($ret[$count] as $f=>$v){
                if(!$v){$t=false;break;}
            }
            
            if($t){//toda a linha tem resultados
                $x++;
                if($limit>0 && $x>=$limit)break;
            }else{
                unset($ret[$count]);
            }
        }
        
        if($ret){
           if($limit==1)$ret=$ret[array_keys($ret)[0]];
           return $ret;
        }else{
            return false;
        }
    }
    
    
    /**
     * Verifica se as strings existem na respectiva ordem (mas pode conter textos intermediários).
     * Ex: existsWordsOrder('gato no chão da sala',['gato','chão']) //return true
     * @param string $blocktext - texto onde deve ser procurado as strings
     * @param array $arr - matriz de valores na ordem que deve existir
     * @return boolean
     */
    public static function existsWordsOrder($blocktext,$arr){
        //verifica se existe as palavras na ordem especificada
        $t=true;
        foreach($arr as $palavra){
            $p=stripos($blocktext, $palavra);
            //dump([$palavra,$p,$blocktext]);
            if($p===false){$t=false;break;}
            $blocktext=substr($blocktext, $p+strlen($palavra), strlen($blocktext));
        }
        return $t;
    }
    
    /**
     * Corrige um texto de CPF/CNPJ verificando se os dois últimos digitos podem ser simplificados com um zero a esquerda ex: 123.456.789-1 é igual a 123.456.789-01
     * @return new CPF/CNPF. Obs: se CPF/CNPJ for inválido, nada é alterado
     */
    public static function fixCPFCNPJ($doc){
        if(ValidateUtility::isCNPJ($doc) || ValidateUtility::isCPF($doc)) return $doc;
        $a=explode('-',$doc);
        $b=$a[count($a)-1];
        unset($a[count($a)-1]);
        $b=str_pad($b,2,'0',STR_PAD_LEFT);
        $n=join('-',$a) .'-'. $b;
        return ValidateUtility::isCNPJ($n) || ValidateUtility::isCPF($n) ? $n : $doc;
    }
}