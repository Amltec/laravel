<?php
namespace App\Utilities;
use Illuminate\Support\Arr;

/*
 * Classe de formatações de dados gerais
 */
Class FormatUtility{


    /**
     * Converte a string no format slug
     */
    public static function sanitizeSlug($filename,$removeExtension=false){
        $info = pathinfo($filename);
        $ext  = empty($info['extension']) ? '' : '.' . $info['extension'];
        $name = basename($filename, $ext);
        if(is_string($name)){
            $name = str_slug($name);//str_slug laravel helper
        };
        return $name . ($removeExtension==false ? $ext : '');
    }

    /**
     * Limpa a string removendo espaços acentos, deixando tudo minúsculo, etc.
     */
    public static function sanitizeText($text){
        return is_string($text) ? strtolower(self::removeAcents(self::trimAll($text))) : $text;
    }

    /**
     * Limpa a string removendo apenas as quebras de linhas
     */
    public static function sanitizeBreakText($text){
        return is_string($text) ? trim(str_replace([chr(10),chr(13),chr(9),'  '],' ',$text)) : $text;
    }

    /**
     * Formata o texto deixando-o em uma única linha, minúsculo, sem acentos, sem quebras de linhas, tabs, etc...
     */
    public static function sanitizeAllText($text){
        return trim(str_replace([chr(9), chr(10), chr(13),'  '], ' ', strtolower(FormatUtility::removeAcents($text))));
    }




    /**
     * Converte a data em formato automático para hoje ou dd/mm/aaaa considerando a data atual.
     * @param string $dt = yyyy-mm-dd hh:mm:ss
     * @param string $format = valores: <br>
     *      '' retorna a data e hora em formato date br <br>
     *      'datetime2' retorna a data e hora no formato dd/mm/aaaa hh:mm <br>
     *      'auto' retorna a data ou hora dependendo da data atual <br>
     *      'date' retorna somente a data formato date br <br>
     *      'time' retorna somente a hora <br>
     *      'param php' qualquer parâmetro de data e hora php (https://www.php.net/manual/pt_BR/function.date.php) <br>
     */
    public static function dateFormat($dt,$format=''){
        if(empty($dt))return '';
        $ds=strtotime($dt);
        if($format=='auto'){
            if(date('d/m/Y', $ds) == date('d/m/Y')){
                return date('H:i', $ds);
            }else{
                return date('d/m/Y', $ds);
            }
        }else if($format=='datetime2'){
            return date('d/m/Y H:i', $ds);
        }else if($format=='date'){
            return date('d/m/Y', $ds);
        }else if($format=='time'){
            return date('H:i:s', $ds);
        }else if(!empty($format)){
            return \App\Utilities\TranslateUtility::date( date($format, $ds) );//traduz alguns textos que podem vir personalizados
        }else{//data e hora
            return date('d/m/Y H:i:s', $ds);
        }
    }

    /**
     * Converte a data do formato dd/mm/aaaa para aaaa-mm-dd. Também aceita hora.
     */
    public static function convertDate($datebr,$isTime=false){
        if(empty($datebr)){
            return $datebr;
        }else{
            return date('Y-m-d' . ($isTime?' H:i:s':''), strtotime(str_replace('/', '-', $datebr)));
        }
    }

    /**
     * Ajusta o ano da data no formato dd/mm/aa para dd/mm/aaaa (de 2 para 4 digitos no ano)
     * @param string $datebr - data no formato dd/mm/aaaa
     * @param string $dateref - data de referência para saber qual ano deve adicionar.
     *                          Ex: fixYearDateBr('01/02/20','15/10/2025') //irá capturar o ano de 2025 para considerar o intervalo do ano de 2000 a 2099
     *                          Valor padrão 'now' para considera ao ano atual
     * @return string datebr - data corrigida no formato dd/mm/aaaa
     */
    public static function fixYearDateBr($datebr,$dateref='now'){
        $n=str_replace('/','',$datebr);
        if(strpos($datebr,'/')!==false && strlen($datebr)==8 && strlen($n)==6 && is_numeric($n) ){//está no formato dd/mm/aa, sendo o ano com apenas 2 digitos
            $datebr=explode('/',$datebr);
            $n=$datebr[2]??null;
            if(!$n)return $datebr[0] .'/'. $datebr[1] .'/'. $datebr[2];;
            $n=(int)$n;

            if(strpos($dateref,'/')!==false){
                $dateref = self::convertDate($dateref);
            }else if($dateref=='now'){
                $dateref = date('Y-m-d');
            }
            $ref = explode('-',$dateref)[0];//captura apenas o ano
            $ref = (int)substr($ref,0,2);//captura apenas os dois primeiros digitos
            //ex: se $ref = 20 então o ano da data pode estar no intervalo de 2000 a 2099

            $datebr = $datebr[0] .'/'. $datebr[1] .'/'. $ref . $datebr[2];
        }
        return $datebr;
    }


    /**
     * !!!! obs: por enquanto programado apenas para meses... !!!!
     * Adiciona um período a uma data
     * @param $date - yyyy-mm-dd ou dd/mm/yyyy
     * @param $type - d,m,y (adiciona um dia, mês, ano...)      /
     * @param $value - valor a adicionar
     * @param $format - valores date, datebr
     * @return new date yyyy-mm-dd
     */
    public static function addDate($date,$type,$value,$format='date'){
        if(!$date)return '';
        if(strpos($date,'/')!==false)$date = FormatUtility::convertDate($date);
        //if(strpos($date,'-')===false)return '';//erro data no formato errado
        $d1 = \DateTime::createFromFormat('Y-m-d',$date);
        $year = $d1->format('Y');
        $month = $d1->format('n');
        $day = $d1->format('d');

        $year += floor($value/12);
        $value = $value%12;
        $month += $value;
        if($month > 12) {
            $year ++;
            $month = $month % 12;
            if($month === 0)
                $month = 12;
        }
        if(!checkdate($month, $day, $year)) {
            $d2 = \DateTime::createFromFormat('Y-n-j', $year.'-'.$month.'-1');
            $d2->modify('last day of');
        }else {
            $d2 = \DateTime::createFromFormat('Y-n-d', $year.'-'.$month.'-'.$day);
        }
        $d2->setTime($d1->format('H'), $d1->format('i'), $d1->format('s'));
        $d2 = $d2->format('Y-m-d');
        if($format=='datebr')$d2 = self::dateFormat($d2,'date');
        return $d2;
    }


    /**
     * Converte o valor do tipo decimal amerciano para inserção correto no SQL. Ex: de 1.000,12 para 1000.12
     * @param boolean $ret_float - se true irá retornar ao número do tipo float. Default false - retorna tipo string
     * @return float
     */
    public static function nDecimal($number,$ret_float=false){//$number=string ex 1.123,45
        if(gettype($number)=='string'){
            $n=str_replace(['.', ','], ['', '.'], $number);
            if($ret_float)$n=(float)$n;
            return $n;
        }else{
            return $number;
        }
    }

    /**
     * Retorna a diferença entre duas datas
     * @param string $dateMin,$dateMax - yyyy-mm-dd ou dd/mm/yyyy (converte automaticamente). Aceita a string 'now'.
     * @param format - formato de retorno, valores:
     *                  f1  - totalmente por extenso, ex: 1 ano, 2 meses, 3 dias, 2 horas, 1 minuto e 10 segundos
     *                  f2  - ex: 1 ano, 2 meses, 3 dias, 2h 1m 10s
     *                  f3  - ex: 1A 2M 3D 02:01:10     //3 dias,  meses 1 ano
     *                  f4  - ex: 03/02/01 02:01:10     //3 dias,  meses 1 ano
     *                  //retorna ao inteino da diferença das datas em:
     *                  d1  - dias
     *                  h1  - horas
     *                  m1  - minutos
     *                  s1  - segundos
     *                  //outros formatos
     *                  float  - ex: 2:30:00            //150,00
     *                  ''  - (default) 123:01:10       //123 horas, 1 minuto e 10 segundos
     * @return string
     */
    public static function dateDiffFull($dateMin,$dateMax,$format=''){
        if($dateMin=='now')$dateMin=date('Y-m-d H:i:s');
        if($dateMax=='now')$dateMax=date('Y-m-d H:i:s');
        if(strpos($dateMin,'/')!==false)$dateMin = FormatUtility::convertDate($dateMin,true);
        if(strpos($dateMax,'/')!==false)$dateMax = FormatUtility::convertDate($dateMax,true);

        $diff=date_diff(date_create($dateMin),date_create($dateMax));
        if($format=='f1' ||  $format=='f2'){
            $y = $diff->y;//anos
            $m = $diff->m;//meses
            $d = $diff->d;//dias
            $h = $diff->h;//horas
            $i = $diff->i;//minutos
            $s = $diff->s;//segundos

            $r=[];
            if($y)$r[]=$y . ($y>1?' anos':' ano');
            if($m)$r[]=$m . ($m>1?' meses':' mês');
            if($d)$r[]=$d . ($d>1?' dias':' dia');
            if($format=='f1'){
                if($h)$r[]=$h . ($h>1?' horas':' hora');
                if($i)$r[]=$i . ($i>1?' minutos':' minuto');
                if($s)$r[]=$s . ($s>1?' segundos':' segundo');
                $r = join(', ',$r);
                $x=strrpos($r,',');
                $r = substr_replace($r,' e',$x,$x+1-strlen($r));
            }else{
                $r = join($r).' ';
                if($h)$r.=$h.'h ';
                if($i)$r.=$i.'m ';
                if($s)$r.=$s.'s ';
                $r=trim($r);
            }
        }else if($format=='f3'){
            $r=$diff->format('%yA %mM %dD %H:%M:%S');
        }else if($format=='f4'){
            $r=$diff->format('%D/%M/%Y %H:%M:%S');
        /*}else if($format=='d1'){//retorna ao inteiro da diferença de dias
            $r=(int)$diff->format('%a');
        }else if($format=='h1'){//retorna ao inteiro da diferença de horas
            $r=(int)$diff->format('%h');
        }else if($format=='m1'){//retorna ao inteiro da diferença de minutos
            $r=(int)$diff->format('%i');
        }else if($format=='s1'){//retorna ao inteiro da diferença de segundos
            $r=(int)$diff->format('%s');*/
        }else if(in_array($format,['d1','h1','m1','s1'])){//retorna ao inteiro da diferença
            $d=(int)$diff->format('%a');//dias
            $h=(int)$diff->format('%h');//horas
            $m=(int)$diff->format('%i');//minutos
            $s=(int)$diff->format('%s');//segundos

            if($format=='d1'){
                $r=$d;

            }else if($format=='h1'){
                $r = $d * 24;
                $r += $h;

            }else if($format=='m1'){
                $r = $d * 24 * 60;
                $r += $h * 60;
                $r += $m;

            }else if($format=='s1'){
                $r = $d * 24 * 60 * 60;
                $r += $h * 60 * 60;
                $r += $m * 60;
                $r += $s;
            }

        }else{//default
            $timeIn        = new \DateTime($dateMin);
            $timeOut       = new \DateTime($dateMax);
            $r= date_diff($timeIn, $timeOut)->format("%H:%I:%S");

            if($format=='float')$r=self::convertTimeStrFloat($r, 'float');

        }
        return $r;
    }


    /**
     * Converte a hora de string para float ou vice versa.
     * @param string,float $time - informar string ex '12:35:01' ou float '147.15'
     * @param string $format - valores para converter: 'str' ou 'float'
     * @param string $type - valores: seconds, minutes
     */
    public static function convertTimeStrFloat($time,$format,$type='minutes'){
        if($type=='seconds'){
            if($format=='float'){//$time = str - converte para float
                //obs: este trecho de código não foi testado!!! precisa revisar
                $str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $time);
                sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
                return $hours * 3600 + $minutes * 60 + $seconds;
            }else{//$time = float - converte para string
                return sprintf('%02d:%02d:%02d', ($time/ 3600),($time/ 60 % 60), $time% 60);//retorna em horas minutos e segundos
            }
        }else{//minutes
            if($format=='float'){//$time = str - converte para float
                $timeArr = explode(':', $time);
                return ($timeArr[0]*60) + ($timeArr[1]) + ($timeArr[2]/60);
            }else if($format=='str'){//$time = float - converte para string
                $hours = floor($time / 60);
                $minutes = floor($time % 60);
                $seconds = $time - (int)$time;
                $seconds = round($seconds * 60);
                return str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" . str_pad($minutes, 2, "0", STR_PAD_LEFT) . ":" . str_pad($seconds, 2, "0", STR_PAD_LEFT);
            }else{
                return null;
            }
        }
    }


    /**
     * Converte números para o formato decimal br
     */
    public static function numberFormat($num,$dec=2){
        return number_format($num,$dec,',','.');
    }


    /**
     * Adiciona um prefixo em um array (ex de uso: nas mensagens retornadas pelo validator).
     * Ex: $arr=['a'=>1], $prefix='test-', então retorna a ['test-a'=>1]
     */
    public static function addPrefixArray($arr,$prefix,$in_value=false,$sufix=false){
        $d=[];
        foreach($arr as $a=>$v){
            $n = $sufix ? $a.($in_value?'':$prefix) : (($in_value?'':$prefix).$a);
            $d[$n]=is_array($v) ? $v : ($in_value?$prefix:'').$v;   //obs: se is_array $v, então não tem como adicionar prefix|sufix no valor
        }
        return $d;
    }


    /**
     * Filtra a array a partir de um prefixo.
     * Ex: $arr=['test_a'=>1,'test_b'=>2], então retorna a ['a'=>1,'b'=>2]
     */
    public static function filterPrefixArray($arr,$prefix){
        $data=[];
        foreach($arr as $field=>$value){
            if(substr($field,0,strlen($prefix))==$prefix)$data[substr($field,strlen($prefix),strlen($field))]=$value;
        }
        return $data;
    }

    /**
     * Filtra a array considerando uma coleção de prefixos
     * @param $prefix - informar sempre com '{N}' para ser substituído pelo respectivo índice, ex: 'campo{N}|'
     * Ex: $arr - ex: ['prefix{1}|field'=>..., 'prefix{2}|field'=>....,], então retorna a [1=>[field=>...], 2=>[field=>...], ]
     * Obs: caso exista o campo em $arr: "prefix'._autofield_count'" o mesmo não é considerado nesta função
     */
    public static function filterPrefixArrayList($arr,$prefix){
        $data=[];
        $count=$arr[$prefix.'_autofield_count']??false;
        if($count && false){//existe o campo $prefix.'_autofield_count'
            for($n=1;$n<=(int)$count;$n++){
                if(!isset($data[$n]))$data[$n]=[];
                $p=str_replace('{N}','{'.$n.'}',$prefix);
                $data[$n]=self::filterPrefixArray($arr,$p);
            }
        }else{//não existe o campo $prefix.'_autofield_count' - captura automático
            $keys = preg_grep('/'. str_replace(['{N}','|'],['\{[0-9]\d{0,2}\}','\|'],$prefix) .'/', array_keys($arr));
            foreach($keys as $key){
                preg_match('/[0-9]\d{0,2}/',$key,$n);//captura o número '{N}' na string
                $n=$n[0]??'';
                if($n){
                    if(!isset($data[$n]))$data[$n]=[];
                    $x=substr($key,strlen($prefix),strlen($key));
                    $x=trim($x,'|');
                    $data[$n][$x] = $arr[$key];
                }
            }
        }
        return $data;
    }

    /**
     * Filtra a array considerando uma relação de nomes de campos e sufixos numéricos.
     * Ex: De filterNamesArrayList([fieldA_1=>, fieldB_1=>, inputA_1=>],'field');       //retorna a [1=>[fieldA=>,fieldB=>...], ... ]
     * Obs: é obrigatório no nome do campo ter no final a sintaxe '..._{N}' para separação dos campos
     * @param array $arr - lista completa
     * @param string|array $fields - matriz de campos (ex: [fieldA,fieldB,...]). Caso não informado será agrupado todos os campos por sufixo numérico apenas. Caso não informado, filtra somente pelo sufixo numérico.
     */
    public static function filterNamesArrayList($arr,$fields=null){
        if(is_string($fields))$fields=explode(',',$fields);
        $data=[];
        $data2=[];
        foreach($arr as $k=>$v){
            if($fields){
                $t=true;
                foreach($fields as $f){
                    if(substr($k,0,strlen($f))==$f){$t=false;break;}
                }
                if($t)continue;
            }
            $n=explode('_',$k);$n=$n[count($n)-1]??null;
            if(!is_null($n) && is_numeric($n)){
                if(!isset($data[$n]))$data[$n]=[];
                $data[$n][substr($k,0,strlen($k)-strlen($n)-1) ]=$v;
            }else{
                $data[$k]=$v;
            }
        }
        return $data;
    }

    /**
     * Extrais nos números de uma string.
     * @param $clearOnly - null|all, left, right, side (limpa tudo, limpa somente a esquerda, direita ou esquerda e direita do até encontrar o primeiro número)
     * @param $allow - string de caracteres permitidos além dos números. Ex: extractNumbers('R$ 1.234,56',null,'.,'])    //return 1.234,56
     * Return string com os números extraídos.
     */
    public static function extractNumbers($str,$clearOnly=null,$allow=null){
        if(!is_array($allow))$allow=str_split($allow);
        if(!$allow)$allow=[];
        if($clearOnly){//limpa os não número somente da esquerda ou direita
            if($clearOnly=='right')$str=strrev($str);

            $r='';
            foreach(str_split($str) as $v){
                if(is_numeric($v) || in_array($v,$allow)!==false)$r.=$v;
            }

            if($clearOnly=='right')$r=strrev($r);
            if($clearOnly=='side')$r=self::extractNumbers($r,'right', $allow);//obs: o default aqui seria o left, e por isto repete com o right
            return $r;
        }else{
            //return (int) filter_var($str, FILTER_SANITIZE_NUMBER_INT);
            //return preg_replace('/[^0-9]'.$allow.'/', '', $str);
            $r='';
            foreach(str_split($str) as $v){
                if(is_numeric($v) || in_array($v,$allow)!==false)$r.=$v;
            }
            return $r;
        }
    }


    /**
     * Extrai somente os caracteres alpha numéricos da string
     * @param string $filters - valores em string separados por virgula:
     *                     num - filtra com números
     *                     spec - filtra com caracteres especiais
     *                     all,'' ou não informado - todos
     * @ex extractAlphaNum('Meu Texto 123','num')   //irá filtrar apenas com números, mas sem caracteres especiais
     */
    public static function extractAlphaNum($str,$filters=null){
        $not='';
        $nums='0123456789';
        $spec1='!@#$%¨&*()[]{}<>-_+=/\\|.:,;';

        if($filters){
            if(stripos($filters,'num')===false)$not.=$nums;
            if(stripos($filters,'spec')===false)$not.=$spec1;
        }//else: todos - nenhuma ação

        $str = trim(\Illuminate\Support\Str::ascii($str));

        if($not){
            //remove os caracteres que não deve conter
            $str = str_replace(str_split($not),'',$str);
            $str = str_replace('  ',' ',$str);
        }
        return $str;
    }




    /**
     * Converte o valor de bytes para kb, mb, etc.. automaticamente
     */
    public static function bytesFormat($bytes, $unit = "", $decimals = 2){
        $units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4,
			'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);

	$value = 0;
	if ($bytes > 0) {
		// Generate automatic prefix by bytes
		// If wrong prefix given
		if (!array_key_exists($unit, $units)) {
			$pow = floor(log($bytes)/log(1024));
			$unit = array_search($pow, $units);
		}

		// Calculate byte value by prefix
		$value = ($bytes/pow(1024,floor($units[$unit])));
	}

	// If decimals is not numeric or decimals is less than 0
	// then set default value
	if (!is_numeric($decimals) || $decimals < 0) {
		$decimals = 2;
	}

	//return sprintf('%.' . $decimals . 'f '.$unit, $value);
        return self::numberFormat($value).' '.$unit;
    }

    /**
     * Retorna ao valor em bytes de uma string com a unidade de medida.
     * @param string $val - ex: 2K, 2M, 2G
     */
    public static function bytesVal($val) {
        $u = substr((string)$val,-1);
        $val = (int)$val;
        switch($u){
        case 'G':
            $val *= 1024;
        case 'M':
            $val *= 1024;
        case 'K':
            $val *= 1024;
        }//caso contrário já é considerado o valor em bytes mesmo
        //apenas retorna ao valor
        return $val;
    }


    /**
     * Remove todos os espaços, tabs e linhas deixando apenas o espaço entre os textos
     */
    public static function trimAll($text){
        $r = trim(str_replace([chr(10),chr(13),chr(9),'  '],[' ',' ',' ',' '],$text));
        return trim(str_replace('  ',' ',$r));//limpa novamente
    }

    /**
     * O mesmo da função trim, mas considera a palavra inteira para retirar das laterais
     * @param $side - left, right, all (default)
     * @param string|array $words
     */
    public static function trim($str,$words='',$side='all'){
        if($words){
            if(!is_array($words))$words=[$words];
            foreach($words as $w){
                if($side=='left' || $side=='all'){
                    while(1){
                        if(substr($str,0,strlen($w))==$w){
                            $str=substr($str,strlen($w));
                        }else{break;}
                    }
                }
                if($side=='right' || $side=='all'){
                    while(1){
                        if(substr($str,strlen($str)-strlen($w),strlen($w))==$w){
                            $str=substr($str,0,strlen($str)-strlen($w));
                        }else{break;}
                    }
                }
            }
            return $str;
        }else{
            if($side=='right'){return rtrim($str);}
            elseif($side=='left'){return ltrim($str);}
            else{return trim($str);}
        }
    }


    /**
     * Remove os acentos da uma string
     * @param boolean $satinize = se true irá limpar toda a string, deixando tudo minúsculo e sem espaços intermediários, default false
     */
    public static function removeAcents($str,$satinize=false){
        /*if($satinize){
            $str = str_slug($str,chr(9));
            $str = str_replace(chr(9),' ',$str);
        }else{
            $str = trim(\Illuminate\Support\Str::ascii($str));
        }
        return $str;*/

        $str = preg_replace(
            array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/º/"),
            explode(" ","a A e E i I o O u U n N c C a o"),
            $str
        );
        $str = str_replace([chr(10),chr(13)],' ', $str);

        if($satinize){
            $str = preg_replace('~[^\pL\d\.]+~u', '-', $str);
            $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
            $str = preg_replace('~[^-\w]+~', '', $str);
            $str = trim($str, '-');
            $str = preg_replace('~-+~', '-', $str);
            $str = strtolower($str);
            $str = str_replace('-', ' ', $str);
        }
        return $str;
    }

    /**
     * Aplica a máscara na string
     * Echo mask($cnpj,"##.###.###/####-##").'<BR>';
     */
    public static function mask($str,$mask){
        $str = str_replace(" ","",$str);
        for($i=0;$i<strlen($str);$i++){
            $mask[strpos($mask,"#")] = $str[$i];
        }
        return $mask;
    }


    /**
     * Ajusta os tipos de campos para outros formatos, ex de 01/10/2000 para 2000-10-01 e vice versa
     * Obs: esta função apenas ajusta os valores informados na matriz $data se existirem (não serão validados, e para isto utilize a classe ValidateUtility::validateData())
     * @param array $data - sintaxe: [field=>valor,...] //obs: aceita também na sintaxe [field=>[valor1, valor2, ...], ...]
     * @param array $rules - sintaxe 1 (string|array): [field1=>'param1:value1, ...', field2=>...] - valores separados por virgula ou array:
     *                       Ex: 'type:datebr,max:10'
     *                       Parâmetros:
     *                                  type        - tipo da validação, valores:
     *                                                 dateauto, date|datebr, datetime, number, price|decimalbr, int, instr
     *                                                 slug, bytes
     *                                                  ... demais tipos serão considerados strings
     *                                  max|size    - (int) máximo de caracteres (irá cortar o texto)
     *                                  values|values_to - deve ser usado para informar o tipo de conversão de valores (separador '|')
     *                                                   'values:SIM|NAO, values_to:1|0'    //quer dizer que todo valor SIM=1, e NAO=0
     *
     *                       sintaxe 2: (array) [field=>['opt1','opt2',...])        //ex: [cpf=>['type:cpf',(function),...]]            //obs: se function - ex: ,function($v){ return (boolen|string)}
     *                       sintaxe 3: (function) [field=>function($v,$data){return (boolen|string);})
     *                      Obs:é possível em $rules ter todos os parâmetros da classe ValidateUtility::validateData(), mas somente os citados acima serão considerados para a conversão de dados.
     * @param $mode - indica se a conversão de dados é para inserção de dados no db ou para a visualização na tela. Valores: 'db', 'view'
     * @return $data - com os valores alterados
     */
    public static function formatData($data,$rules,$mode){
        $rules_new=[];
        //dd($rules);
        foreach($data as $field=>$value){
            $rl = $rules[$field]??null;
            if($rl=='')continue;

            if(is_callable($rl)){
                if(!isset($rules_new[$field]))$rules_new[$field]=[];
                $rules_new[$field]['fnc_n']=$rl;

            }else{
                $rl=is_array($rl) ? $rl : explode(',',$rl);
                foreach($rl as $i=>$rl_item){//esperado field:value
                    if(is_callable($rl_item)){
                        $rl_name='fnc_'.$i;
                        $rl_value=$rl_item;
                    }else{
                        if(strpos($rl_item,':')!==false){
                            list($rl_name,$rl_value) = explode(':',$rl_item);
                        }else{
                            $rl_name=$rl_item;$rl_value='';
                        }
                    }
                    if(!isset($rules_new[$field]))$rules_new[$field]=[];
                    $rules_new[$field][$rl_name]=$rl_value;
                }
            }
        }

        $rules=$rules_new;
        foreach($data as $field=>$value){
            if(is_array($value)){
                $r=[];
                foreach($value as $i=>$v){
                    $r[$i] = self::formatDataSingle($v, ($rules[$field]??null), $mode, $data);
                }
                $data[$field]=$r;
            }else{
                $data[$field]=self::formatDataSingle($value, ($rules[$field]??null), $mode, $data);
            }
        }
        //dd($data,$rules);

        return $data;
    }
    //complemento de self::formatData //formata apenas o valor
    public static function formatDataSingle($value,$rl,$mode,$data=[]){//string|int $value, array $rl, array all $data (opcional)
        if(!$rl || is_null($value) || !is_array($rl))return $value;
        foreach($rl as $rl_name => $rl_value){//esperado field:value
            if($rl_name=='type'){
                switch($rl_value){
                case 'price': case 'decimalbr': case 'number':
                    if($mode=='db'){
                        $value = self::nDecimal($value);
                    }else{//view
                        if(!ValidateUtility::isNumberStr($value))$value=self::extractNumbers($value,null,'.,');//existem outras strings no número, ex 'R$ 123,45'
                        if(strpos($value,',')!==false)$value=self::nDecimal($value);//o número tem virgula
                        try{
                            //dump(['a',$value,$rl_value]);
                            $value = self::numberFormat($value, ($rl_value=='number'?0:2) );
                        } catch (\Exception $e){
                            //dump(['b',$value,$rl_value]);
                        }
                    }
                    break;
                case 'date': case 'datebr': case 'datetime': case 'dateauto':
                    if($mode=='db'){
                        $value = self::convertDate($value,$rl_value=='datetime');
                    }else{//view
                        if(strpos($value,'/')===false){//lógica: se tiver uma barra, entende que já está formatado
                            $n='';
                            if($rl_value=='date' || $rl_value=='datebr')$n='date';
                            if($rl_value=='dateauto')$n='auto';
                            $value = self::dateFormat($value,$n);
                        }
                    }
                    break;
                case 'slug':
                    if($mode=='db'){
                        $value = self::sanitizeSlug($value);
                    }//else{//view - nenhuma ação
                    break;
                case 'bytes':
                    if($mode=='db'){
                        //nenhuma ação, considera que o valor já esteja em bytes
                    }else{//view - nenhuma ação
                        $value = self::bytesVal($value);
                    }
                    break;
                //...demais types são ignorados
                }
            }

            if($rl_name=='max' || $rl_name=='size'){
                $value = substr($value,0,$rl_value);
            }

            if($rl_name=='values_to'){
                if(empty($rl['values']))continue;//precisa estar definido 'values' também
                $v0 = explode('|',$rl['values']);
                $vT = explode('|',$rl['values_to']);
                if(count($v0)!=count($vT))continue;//o número de índices precisa ser igual
                $i = array_search((string)mb_strtolower($value), array_map('mb_strtolower',$v0) );//retorna ao índice correspondente a value
                //if($value=='juridica')dd($value,$v0,$i);
                if($i!==false)$value = $vT[$i];
                //dd($data,$rl,$v0,$vT,$i,$value);

            }else if(substr($rl_name,0,4)=='fnc_'){
                $value=callstr($rl_value,[$value,$data],true);
            }

            if($mode=='db' && $value==='')$value=null;//ajusta para null, para a correta inserção de dados no banco

        }
        return $value;
    }

    /**
     * O mesmo de self::formatData(), mas verifica cada valor de um array $data.
     * Sintaxe $data esperada: [1=>[field1=>[valor1, valor2,...], ...], 2=>...]
     * @param type $param
     */
    public static function formatDataArr($data,$rules,$mode){
        foreach($data as $f => $v){
            if(is_array($v)){
                $data[$f] = self::formatData($v,$rules,$mode);
            }else{
                $data[$f] = self::formatDataSingle($v, ($rules[$f]??null), $mode, $data);
            }
        }
        return $data;
    }


    /**
     * Explode a string a partir de um delimitador considerando aspas intermediárias
     * @return array
     */
    public static function explodeQuotes($subject, $delimiter, $quote=null){//ex $quote='\'';
        if($quote){
            $regex = "(?:[^$delimiter$quote]|[$quote][^$quote]*[$quote])+";
            preg_match_all('/'.str_replace('/', '\\/', $regex).'/', $subject, $matches);
            return $matches[0];
        }else{
            return explode($delimiter,$subject);
        }
    }

    /**
     * Mesclarem de matrizes (como na função array_merge_recursive(), mas não modifica o tipo da variável)
     * @param $rpl_array - se true indica que os array que não forem associativos terão todo o valor substituído.
     *              Exemplos:
     *                  ...([a=>1,b=>[2,3]], [b=>[4]]);         //return [a=>1,b=>[4,3]]
     *                  ...([a=>1,b=>[2,3]], [b=>[4]], true);   //return [a=>1,b=>[4]]
     */
    public static function array_merge_recursive_distinct(array &$array1, array &$array2, $rpl_array=false){
        $merged = $array1;
        foreach ( $array2 as $key => &$value ){
                if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ){
                        if($rpl_array==false || ValidateUtility::isArrayAssoc($value)){
                            $merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value, $rpl_array);
                        }else{
                            $merged [$key] = $value;
                        }
                }else{
                        $merged [$key] = $value;
                }
        }
        return $merged;
    }

    /**
     * Filtra o array ignorando os valores nulos
     */
    public static function array_ignore_null($arr){
        return array_filter($arr,function($a){return !is_null($a);});
    }


    //###### futuramente, remover daqui estas funções, para que sejam sempre chamadas diretamente por TextUtility::.... #####
    public static function getPartOfStr($text,$args){
        return TextUtility::getPartOfStr($text, $args);
    }
    public static function execFncInStr($str,$len,$fnc,$side_len=0,$start_init=0){//futuramente, remover daqui esta função
        return TextUtility::execFncInStr($str,$len,$fnc,$side_len,$start_init);
    }


    //Converte string in boolean
    public static function isBool($v,$preserve=false){
        if(in_array($v,[true,'true','TRUE',1,'s'],true)){
            return true;
        }elseif(in_array($v,[false,'false','FALSE',0,'s'],true)){
            return false;
        }else{
            return $preserve ? $v : false;
        }
    }
    public static function cBool($v){
        if(in_array($v,[true,'true','TRUE','s'],true)){
            return true;
        }elseif(in_array($v,[false,'false','FALSE','n'],true)){
            return false;
        }else{
            return $v;
        }
    }


    /**
     * Corrige a rota para o caso de estar com barras vazias (e: dir1//dir2)
     */
    public static function route($route_name,$params){
        return str_replace(['://','//','{$HTTP}'],['{$HTTP}','/','://'],route($route_name,$params));
    }


    /**
     * Como a função pluck, mas incluir no id a chave da matriz.
     * Ex de fnc([a=>[x=>1,y=>2], ...],'x')     //= [a=>1]...
     */
     public static function pluckKey($arr,$name){
         $r=[];
         foreach($arr as $k=>$v){
             $r[$k]=Arr::get($v,$name);
         }
         return $r;
     }


     /**
      * Ajusta o array para o padrão select2
      * De [field=>value,...] para [id=>field,text=>value]
      */
     public static function toSelect($arr){
        $r=[];
        foreach($arr as $f=>$v){
            $r[]=['id'=>$f,'text'=>$v];
        }
        return $r;
     }
}
