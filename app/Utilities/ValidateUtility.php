<?php
namespace App\Utilities;
use App\Utilities\FormatUtility;

/*
 * Classe de validações Gerais / verificação de dados
 */
Class ValidateUtility{

    /** Valida se o e-mail é verdadeiro. Return Boolean. */
    public static function isMail($email) {
        return preg_match('/[a-z0-9_\.\-]+@[a-z0-9_\.\-]*[a-z0-9_\.\-]+\.[a-z]{2,4}$/', $email);
    }


    /** Valida se o número de CEP é verdadeiro. Return Boolean.
     * Considera o formato: 999.999.999-99
     * Se $mask=true valida se está formatado com a máscara do número
     */
    public static function isCPF($cpf,$mask=false){
        if($mask && !preg_match('/[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}/', $cpf))return false;//verifica se está formatado

        if(preg_replace('/[^A-Z]+/', '', $cpf))return false;//tem letras entre os números
        $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);

        if (in_array($cpf,['00000000000','11111111111','22222222222','33333333333','44444444444','55555555555','66666666666','77777777777','88888888888','99999999999']))return false;

	if (strlen($cpf) != 11)return false;
	// Calcula e confere primeiro dígito verificador

	for ($i = 0, $j = 10, $soma = 0; $i < 9; $i++, $j--)
		$soma += $cpf[$i] * $j;
	$resto = $soma % 11;
	if ($cpf[9] != ($resto < 2 ? 0 : 11 - $resto))
		return false;
	// Calcula e confere segundo dígito verificador
	for ($i = 0, $j = 11, $soma = 0; $i < 10; $i++, $j--)
		$soma += $cpf[$i] * $j;
	$resto = $soma % 11;
	return $cpf[10] == ($resto < 2 ? 0 : 11 - $resto);
    }


    /** Valida se o número de CNPJ é verdadeiro.  Return Boolean.
     * Considera o formato: 99.999.999/9999-99
     * Se $mask=true valida se está formatado com a máscara do número
     */
    public static function isCNPJ($cnpj,$mask=false){
        if($mask && !preg_match('/[0-9]{2}\.[0-9]{3}\.[0-9]{3}\/[0-9]{4}-[0-9]{2}/', $cnpj))return false;//verifica se está formatado

        if(preg_replace('/[^A-Z]+/', '', $cnpj))return false;//tem letras entre os números
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
	// Valida tamanho
	if (strlen($cnpj) != 14)
		return false;
	// Valida primeiro dígito verificador
	for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++){
		$soma += $cnpj[$i] * $j;
		$j = ($j == 2) ? 9 : $j - 1;
	}
	$resto = $soma % 11;
	if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
		return false;
	// Valida segundo dígito verificador
	for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++){
		$soma += $cnpj[$i] * $j;
		$j = ($j == 2) ? 9 : $j - 1;
	}
	$resto = $soma % 11;
	return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }

    /**
     * Valida se o número é um CPF ou CNPJ verdadeiro. Return Boolean.
     */
    public static function isDocument($doc){
        return self::isCPF($doc) || self::isCNPJ($doc);
    }

    /**
     * Valida se o número é um número válido dentro de uma string formatada: ex: 1.234,56 ou 12/001-44
     */
    public static function isNumberStr($v){
        $v=str_replace(['.',',','-','/'],'',$v);
        return is_numeric($v);
    }


    /**
     * Verifica se a data em string é válida. Obs: aceita data e hora.
     * @param string $strdate = parâmetro data.
     * @param array $format = 'datebr' (default) formato do parâmetro $str como como dd/mm/aaaa, = '' ou 'date' Padrão como aaaa-mm-dd.
     * @Return Booelan.
     */
    public static function isDate($strdate,$format='datebr') {
        if(!empty($strdate)){
                if($format=='datebr'){// dd/mm/aaaa
                    if(substr_count($strdate,'/')!=2 || is_numeric(str_replace('/','',str_replace(' ','',str_replace(':','',$strdate))))==false){
                        return false;
                    }else{
                        if(count(explode('/',$strdate))>=3){
                            $n=explode('/',explode(' ',trim($strdate))[0]);
                            if(count($n)>=3){
                                //list($dd,$mm,$yyyy) = ;//para pegar somente a data
                                $dd=$n[0]; $mm=$n[1]; $yyyy=$n[2];
                            }else{
                                return false;
                            }
                        }else{
                            return false;
                        }
                    }
                }else{//$format=='date' // aaaa-mm-dd
                    if(substr_count($strdate,'-')!=2 || is_numeric(str_replace('-','',str_replace(' ','',str_replace(':','',$strdate))))==false){
                        return false;
                    }else{
                        list($yyyy,$mm,$dd) = explode('-',explode(' ',trim($strdate))[0]);//para pegar somente a data
                    }
                }
                $y=strlen((string)$yyyy);
                if(in_array($y, [2,4])){//o ano precisa ter 2 ou 4 caracteres
                    if(checkdate((int)$mm,(int)$dd,(int)$yyyy)){//a data passou, verifica se tem hora
                        $strdate=explode(' ',$strdate)[1]??'';
                        if($strdate){
                            $strdate=explode(':',$strdate);
                            $n=$strdate[0]??null;if($n && ((int)$n<0 || (int)$n>=24))return false;
                            $n=$strdate[1]??null;if($n && ((int)$n<0 || (int)$n>=60))return false;
                            $n=$strdate[2]??null;if($n && ((int)$n<0 || (int)$n>=60))return false;
                        }
                        return true;
                    }else{
                        return false;
                    };
                }else{
                    return false;
                }
        }else{
                return false;
        }
    }




     /**
     * Compara duas datas.
     * @param strdate $dt1,$dt2 - data incial e final. Formato yyyy-mm-dd ou dd/mm/yyyy (converte automaticamente). Aceita a string 'now'.
     * @param string $op - operador: >, <, >=, <=, !=, =
     * @return booelan
     */
    public static function ifDate($dt1,$op,$dt2){
        if(strpos($dt1,'/')!==false){
            $dt1= new \DateTime(FormatUtility::convertDate($dt1,true));
        }elseif(gettype($dt1)=='string'){
            $dt1= new \DateTime($dt1);
        }elseif ($dt1=='now') {
            $dt1=new \DateTime();
        }
        if(strpos($dt2,'/')!==false){
            $dt2= new \DateTime(FormatUtility::convertDate($dt2,true));
        }elseif(gettype($dt2)=='string'){
            $dt2= new \DateTime($dt2);
        }elseif($dt2=='now'){
            $dt2=new \DateTime();
        }
        //dd($dt1,$op,$dt2);
        switch($op){
        case '=':case '==': return $dt1==$dt2;break;
        case '>':   return $dt1>$dt2;break;
        case '<':   return $dt1<$dt2;break;
        case '>=':  return $dt1>=$dt2;break;
        case '<=':  return $dt1<=$dt2;break;
        case '!=':  return $dt1!=$dt2;break;
        default:    return null;
        }
    }


    /**
     * Verifica se o número de telefone é válido.
     * Nenhum DDD iniciado por 0 é aceito, e nenhum número de telefone pode iniciar com 0 ou 1.
     * Exemplos válidos: +55 (11) 98888-8888 / 9999-9999 / 21 98888-8888 / 5511988888888
     * Return boolean
     */
    public static function isPhone($phone){
        if (preg_match('/^(?:(?:\+|00)?(55)\s?)?(?:\(?([1-9][0-9])\)?\s?)?(?:((?:9\d|[2-9])\d{3})\-?(\d{4}))$/', $phone) == false){
            return false;
        }else{
            return true;
        }
    }


    /**
     * Verifica se $data é um valor serial
     * @param boolean $strict Whether to be strict about the end of the string.
     * @return boolean
     */
    public static function isSerialized($data, $strict=true){
        if ( ! is_string( $data ) )return false;
        $data = trim( $data );
        if ( 'N;' == $data )return true;
        if ( strlen( $data ) < 4 )return false;
        if ( ':' !== $data[1] )return false;
        if ( $strict ) {
            $lastc = substr( $data, -1 );
            if ( ';' !== $lastc && '}' !== $lastc )return false;
        } else {
            $semicolon = strpos( $data, ';' );
            $brace     = strpos( $data, '}' );
            // Either ; or } must exist.
            if ( false === $semicolon && false === $brace )return false;
            // But neither must be in the first X characters.
            if ( false !== $semicolon && $semicolon < 3 )return false;
            if ( false !== $brace && $brace < 4 )return false;
        }
        $token = $data[0];
        switch ( $token ) {
            case 's':
                if ( $strict ) {
                    if ( '"' !== substr( $data, -2, 1 ) )return false;
                } elseif ( false === strpos( $data, '"' ) ) {
                    return false;
                }
                // or else fall through
            case 'a':
            case 'O':
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
        }
        return false;
    }


     /**
     * Verifica se string $data é um json válido
     * @return boolean
     */
    public static function isJson($data){
        json_decode($data);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    /**
     * Verifica se um valor está dentro da margem
     * @return boolean
     * Ex: isValMarg(100,105,1,2) //==false, pois 105 está fora da margem de de 99 a 102
     */
    public static function isValueMargin($val,$compare,$margDown,$margUp){
        return $compare >= ($val-$margDown) && $compare <= ($val+$margUp);
    }


    /**
     * Validações gerais em toda a matriz de dados
     * Obs: combina as demais validações desta classe.
     * @param array $data - sintaxe: [field=>valor,...]
     * @param array $rules - sintaxe 1 (string|array): [field1=>'param1:value1, ...', field2=>...] - valores separados por virgula ou array:
     *                       Ex: '*,type:cpf'   ou  '*,max:20,values:a|b|c,...',    //obs: aceita "aspas" nos valores, ex:  '*,values:"a b|c"'
     *                       Parâmetros:
     *                                  *           - indica que é obrigatório
     *                                  exists      - indica que o campo precisa pelo menos existir em $data (mesmo que vazio ou nulo)
     *                                  type        - tipo da validação, valores:
     *                                                  date, datebr, int, decimalbr (9.999,99), email, cpf, cnpj, sn (s ou n), document (cpf ou cnpj), phone,
     *                                                  intstr - inteiro em string desconsiderando formatações, ex: '18.406-070' é um número inteiro (obs: neste caso o min|max é considerado sem a máscara do valor),
     *                                  min|max     - (int) mínimo e máximo de caracteres
     *                                  vmin|vmax   - (int|float) valor mínimo e máxmo (para type=int|decimalbr|intstr). Sintaxe: para float 99.99
     *                                  marg(down|up)|margtotal     - (int|float) valor de margem (geral | para baixo | para acima) e seu total (valores type=int|decimalbr|intstr). Sintaxe: para float 99.99.
     *                                                                      Ex: marg=>3, margtotal=>10      //então data['value']=> tem que ser entre 7 e 13.
     *                                                                      Todos os nomes: marg|margup|margdown|margtotal
     *                                                                      Obs: é obrigatório informar alguma marg(...) e margtotal
     *                                  size        - (int) tamanho extato de caracteres
     *                                  values      - deve conter um dos valores mencionados, valores separados por | ex: ,values a|b|c...
     *                                  ignore      - valores que devem ser ignorados (return true) se estiverem presentes, sintaxe: val1|val2..., ex: ignore:a|b|c...
     *                                  left|right  - (int) caracteres que deve cortar na esquerda ou direita antes da comparação, ex: left:2
     *                                  empty       - relação de valores considerados vazios, sintaxe: val1|val2|..., etc: 0|no|...
     *                       sintaxe 2: (array) [field=>['opt1','opt2',...])        //ex: [cpf=>['*','type:cpf',(function),...]]            //obs: se function - ex: ,function($v){ return (boolen|string)}
     *                       sintaxe 3: (function) [field=>function($v,$data){return (boolen|string);})
     *
     *                       Observações: é provável que existam alguns parâmetros informados para a função FormatUtility::formatData(), que usa o mesmo padrão (contendo apenas alguns campos) acitados acima.
     *
     * @param array $required - matriz de campos obrigatórios (opcional). Se definido irá sobrescrever a string '*' em $rules que indica que o campo é obrigatório.
     *                      Sintaxes: [field=>required (booelan),...]  ou  [field_required1,field_required1,...]
     *
     * @param array $opt - (ver nesta função)
     * @return boolean  - se ===true então todos os parâmetros passaram na validação
     * @return array    - se array, contém os campos que negados, sintaxe: [field=>'msg',...]
     */
    public static function validateData($data,$rules,$required=null,$opt=[]){
        $opt=array_merge([
            'sufix'=>null,          //se informado é utilizado paneas para completar o retorno do nome do campo. Ex: se $num=2 então retorna 'meucampo_2'
            'required_all'=>true,   //se true, indica que se existem um campo em $required que não exista em $data, retornará como validado obrigatrório. Se false, indica que retornará validado como 'ok' pois não existe o campo de $required em $data.
        ],$opt);

        $r=[];
        foreach($data as $field=>$value){
            $rl = $rules[$field]??null;
            if(is_callable($rl)){
                $n=callstr($rl,[$value,$data]);
            }else{
                $q=null;
                if($required && is_array($required)){
                    $ks=isset($required[$field])?array_keys($required):[];
                    if(isset($required[$field]) || in_array($field,$ks)){
                        $q=isset($required[$field]) ? $required[$field] : in_array($field,$ks); //informado na sintaxe: [field=>bool,...] ou [field,..]
                    }
                }
                $n=self::validateField($value,$rl,$q,$field);
            }
            if($n!==true)$r[$field]=$n;
        }
        //dd('***',$r);
        //verifica se tem algum campo informado em $rules que não existe em $data
        foreach($rules as $field=>$param){
            if(!array_key_exists($field,$data)){//não informado o campo            //if(!isset($data[$field])){//não informado o campo
                $rl = is_array($param) ? $param : explode(',',$param);
                if(in_array('exists',$rl))$r[$field]='Campo não existe';
                //dump($field);
                /*if(isset($required[$field])){//foi definido pela var $required
                    if($required[$field])$r[$field]='Campo requerido';
                }else{
                    if(in_array('*',$rl))$r[$field]='Campo requerido';
                }*/
            }
        }

        //verifica os campos obrigatório
        if($required){
            if($required[0]??null){//sintaxe:[field,...]
                foreach($required as $field){
                    if($opt['required_all']===true){//todos são considerados requeridos
                        $v=$data[$field]??'';
                        if(!$v && $v!='0')$r[$field]='Campo requerido';
                    }else{//somente se o campo existir é considerado requerido
                        if(array_key_exists($field,$data)){
                            $v=$data[$field];
                            if(!$v && $v!='0')$r[$field]='Campo requerido';
                        }
                    }
                }
            }else{//sintaxe: [field=>bool,...]
                foreach($required as $field=>$param){
                    if($opt['required_all']===true){//todos são considerados requeridos
                        if($required[$field] && empty($data[$field]??''))$r[$field]='Campo requerido';
                    }else{//somente se o campo existir é considerado requerido
                        if(array_key_exists($field,$data)){
                            if($required[$field] && empty($data[$field]??''))$r[$field]='Campo requerido';
                        }
                    }
                }
            }
        }

        if($opt['sufix']){
            $sufix = !is_null($opt['sufix']) ? (is_numeric($opt['sufix'])?'_'.$opt['sufix']:$opt['sufix']) : '';
            $n=[];
            foreach($r as $field=>$value){
                $n[$field.$sufix]=$value;
            }
            $r=$n;
        }
        return empty($r)?true:$r;
    }
    public static function validateField($value,$rule,$required_p=null,$field_name=null){//return (boolean) true|false  or   (string) msg       //obs: se (boolean) $required_p for definido, então irá sobrepor a regra $rule['*']
        if(!$rule)return true;//sem regra definida, portanto retorna a true pois o campo não precisa ser validado
        $value=trim($value);

        $required=false;
        $type='';
        $min=$max=$vmin=$vmax=$size=0;
        $rule_values=[];
        $ignore=[];
        $left=$right=0;
        $marg=$margUp=$margDown=$margCompare=0;
        $empty=[];

        if(is_string($rule))$rule = FormatUtility::explodeQuotes($rule,',','"');

        foreach($rule as $fparam=>$param){
            if(!is_callable($param)){
                if(is_string($fparam))$param=$fparam.':'.$param;
                $param=trim(strtolower($param));

                if(!is_null($required_p)){
                    $required=$required_p;
                }else if($param=='*'){
                    $required=true;
                }
                //if($field_name=='corretor_susep')dump([$required,$required_p,$fparam,$param]);

                if(substr($param,0,5)=='type:')$type=substr($param,5);
                if(substr($param,0,4)=='max:' || substr($param,0,4)=='min:'){
                    $n=substr($param,4);
                    if(!is_numeric($n))return 'Erro na regra de validação '. strtoupper($param);
                    $n=(int)$n;
                    if(substr($param,0,4)=='max:'){$max=$n;}else{$min=$n;}
                }
                if(substr($param,0,5)=='vmax:' || substr($param,0,5)=='vmin:'){
                    $n=substr($param,5);
                    if($type=='intstr'){
                        $n=str_replace(['.',',','-','/'],'',$n);
                    }elseif($type=='decimalbr'){
                        $n=(float)($n);
                    }
                    if(!is_numeric($n))return 'Erro na regra de validação '.strtoupper($param);
                    $n=(float)$n;
                    if(substr($param,0,5)=='vmax:'){$vmax=$n;}else{$vmin=$n;}
                }
                if(substr($param,0,5)=='marg:' || substr($param,0,7)=='margup:' || substr($param,0,9)=='margdown:' || substr($param,0,12)=='margcompare:'){
                    $f=substr($param,strpos($param,':')+1);
                    $n='';
                    if($type=='intstr'){
                        $n=str_replace(['.',',','-','/'],'',$f);
                    }elseif($type=='decimalbr'){
                        $n=(float)($f);
                    }
                    if(!is_numeric($n))return 'Erro na regra de validação '.strtoupper($param);
                    $n=(float)$n;
                    if(substr($param,0,5)=='marg:')$marg=$n;
                    if(substr($param,0,7)=='margup:')$margUp=$n;
                    if(substr($param,0,9)=='margdown:')$margDown=$n;
                    if(substr($param,0,12)=='margcompare:')$margCompare=$n;
                }

                if(substr($param,0,5)=='size:'){
                    $size=substr($param,5);
                    if(!is_numeric($size))return 'Erro na regra de validação SIZE';
                    $size=(int)$size;
                }
                if(substr($param,0,7)=='values:'){
                    $rule_values=explode('|',substr($param,7));
                    $rule_values=array_map(function($n){return trim($n,'"');}, $rule_values);//tira as aspas laterais
                }
                if(substr($param,0,7)=='ignore:'){
                    $ignore=explode('|',substr($param,7));
                    $ignore=array_map(function($n){return trim($n,'"');}, $ignore);//tira as aspas laterais
                }
                if(in_array(substr($param,0,5),['left:','right:'])){
                    $f=substr($param,0,4);
                    $n=substr($param,5);
                    if(!is_numeric($n))return 'Erro na regra de validação '. strtoupper($f);
                    eval("\$\$f = (int)\"$n\";");
                }
                if(substr($param,0,6)=='empty:'){
                    $empty=explode('|',substr($param,6));
                    $empty=array_map(function($n){return trim($n,'"');}, $empty);//tira as aspas laterais
                }
            }
        }

        //ignore
        if($ignore && in_array(strtolower($value),array_map('strtolower',$ignore)))return true;

        //required
        if(!$required && !$value && $value!='0')return true;//não é requerido e o valor é vazio

        if($empty && in_array($value,$empty))$value='';//seta vazio, pois $value é um dos valores considerados vazios
        if(!$value && $value!='0')return 'Campo requerido';//(string)'0' está vindo como false, mas neste caso não deve ser

        //corta o texto para comparar
        if($left)$value=substr($value,0,$left);
        if($right)$value=substr($value,$right);

        //types
        $value_number=0;
        switch($type){
        case 'date':
            if(!self::isDate($value,'date'))return 'Data inválida';
            break;
        case 'datebr':
            if(!self::isDate($value,'datebr'))return 'Data inválida';
            break;
        case 'int':
            if(!is_numeric($value))return 'Número inválido';
            $value_number=(float)$value;
            break;
        case 'decimalbr'://ex: 9.999,99
            $v=str_replace(['.',','],['','.'],$value);
            if(!is_numeric($v))return 'Número inválido';
            $value_number=(float)$v;
            break;
        case 'email':
            if(!self::isMail($value))return 'E-mail inválido';
            break;
        case 'cpf':
            if(!self::isCPF($value))return 'CPF inválido';
            break;
        case 'cnpj':
            if(!self::isCNPJ($value))return 'CNPJ inválido';
            break;
        case 'document':
            if(!self::isDocument($value))return 'CNPJ / CNPJ inválido';
            break;
        case 'phone':
            if(!self::isPhone($value))return 'Telefone inválido';
            break;
        case 'sn':
            if(!str_contains($value,['s','n']))return 'Valor inválido';
            break;
        case 'intstr'://verifica se é número considerando a máscara dos números
            $v=str_replace(['.',',','-','/'],'',$value);
            if(!is_numeric($v))return 'Número inválido';
            $value=$v;//deixa o value sem a mascara para a comparação abaixo de $min e $max
            $value_number=(int)$v;
            break;
        }

        //max, min, size, vmin, vmax
        if($min && strlen((string)$value)<$min)return 'Mínimo de '.$min.' caracteres';
        if($max && strlen((string)$value)>$max)return 'Máximo de '.$max.' caracteres';
        if($size && strlen((string)$value)!=$size)return 'Precisa ter '.$size.' caracteres';
        if(in_array($type,['int','decimalbr','intstr']) && ($vmin || $vmax)){
            if($vmin && $value_number<$vmin)return 'Valor menor que o mínimo '.$vmin;
            if($vmax && $value_number>$vmax)return 'Valor maior que o máximo '.$vmax;
        }
        if($empty && in_array($type,['int','decimalbr','intstr'])){
            if($required_p && in_array($value_number,$empty))return 'Campo requerido';
        }

        //values
        if($rule_values && !in_array(mb_strtolower($value),array_map('mb_strtolower',$rule_values)))return 'Precisa ser um dos valores '. strtoupper(join(', ',$rule_values)) .'';

        //margem
        //dd($value_number,$marg,$margUp,$margDown,$margCompare);
        if(($marg || $margUp || $margDown) && $margCompare){
            if($marg){
                $n=$value_number>$margCompare ? $value_number-$margCompare : $margCompare-$value_number;
                if($n>$marg)return 'Valor fora da margem de '.$marg;
            }
            if($margDown){
                $n=$value_number-$margCompare;
                if($n>$margDown)return 'Valor abaixo da margem de '.$margDown;
            }
            if($margUp){
                $n=$margCompare-$value_number;
                if($n>$margUp)return 'Valor acima da margem de '.$margUp;
            }
        }

        //caso alguma regra seja função
        foreach($rule as $param){
            if(is_callable($param)){
                $n=callstr($param,$value);
                if($n!==true)return $n;
            }
        }

        return true;
    }


    /**
     * O mesmo de self::validateData(), mas apenas compara se $data1 e $data2 são iguais
     * Em $rules, considera apenas o parâmetro 'type'. Sintaxe: 'type:...'. Valores: int,float,decimalbr,cpf,cmpj,phone,inststr,date,datebr,sn... demais são comparados como strings
     * @return array[field=>boolean]
     */
    public static function equalsData($data1,$data2,$rules){
        $r=[];
        foreach($data1 as $f=>$v){
            $r[$f]=false;
            $rl = $rules[$f]??null;
            $v1=$data1[$f]??null;
            $v2=$data2[$f]??null;
            $r[$f] = self::equalsDataField($v1,$v2,$rl);
        }
        return $r;
    }
    public static function equalsDataField($v1,$v2,$type){//return boolean, $type = datebr,inststr... ou 'type:datebr,... demais parâmetros não considerados aqui...'
        if(is_null($v1))$v1='';
        if(is_null($v2))$v2='';

        $r=false;

        if(is_callable($type))$type='';
        if(is_array($type) || strpos($type,':')!==false){//está assim: 'type:..., min:..., max:..., etc...' //captura apenas o type
            $rl=$type;
            if(is_string($rl))$rl = FormatUtility::explodeQuotes($rl,',','"');
            foreach($rl as $rx){
                if(!is_callable($rx) && substr($rx,0,5)=='type:'){
                    $type=substr($rx,5);
                    break;
                }
            }
        }

        switch($type){
        case 'int':
            $v1=(int)$v1;
            $v2=(int)$v2;
            break;
        case 'float':
            $v1=FormatUtility::numberFormat($v1);
            $v2=FormatUtility::numberFormat($v2);
            break;
        case 'decimalbr':
            $v1=(string)FormatUtility::nDecimal($v1);
            $v2=(string)FormatUtility::nDecimal($v2);
            break;
        case 'cpf': case 'cnpj': case 'phone': case 'intstr':
            $x=['.',',','-','/','(',')'];
            $v1=(string)str_replace($x,'',$v1);
            $v2=(string)str_replace($x,'',$v2);
            break;
        default://demais são comparados como strings
            $v1=(string)$v1;
            $v2=(string)$v2;
        }
        if($v1===$v2)$r=true;
        return $r;
    }

    /**
     * Retorna se a matriz é associativa ou sequencial
     */
    public static function isArrayAssoc($arr){
        if(!is_array($arr))return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}
