<?php

namespace App\ProcessRobot\cad_apolice;
use App\ProcessRobot\cad_apolice\QuiverClass;

use App\Models\Robot;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;


/**
 * Classe de ações gerais do ramos automóvel
 * Deve ser extendida por cada /automovel/{seguradora}Class.php
 */
class ProcessAutomovelClass extends QuiverClass{


    //valida os dados processados pela função ::process()
    public function ValidateData($r){
        if(!$r['success'])return $r;
    	$data=$r['data'];//obs: aqui contém a matriz unica com todos os campos

        if(!isset($data['_ignore_fields_cad']))$data['_ignore_fields_cad']=[];//campos a serem ignorados pelo AutoIt no Quiver

        //validação base com todos os campos
        $n = $this->validateBase($data,'automovel');
        if(!$n['success'])return $n;

        //atualizar a var data
        $data['_ignore_fields_cad'] = join(',',$data['_ignore_fields_cad']);
        $r['data']=$n['data'];
        $r['code']='ok';
        $r['msg']='Extraído com sucesso';

        return $r;
    }


    //########### as funções abaixo são auxiliares para capturar os dados do texto do pdf ###########

    /**
     * Lista de Combustíveis. Sintaxe: text_conforme_pdf => [code_quiver=>..., ignore=>..., title=>...]
     */
    private $combustivel_list=[
        'GASOLINA'              => ['code_quiver'=>'01'],
        'ALCOOL'                => ['code_quiver'=>'02'],
        'DIESEL'                => ['code_quiver'=>'03'],
        'GAS'                   => ['code_quiver'=>'04' ,'ignore'=>['kit gas'] ],
        'FLEX'                  => ['code_quiver'=>'05'],
        'BI-COMBUSTIVEL'        => ['code_quiver'=>'05'],
        'bicombust'             => ['code_quiver'=>'05','title'=>'BI-COMBUSTIVEL'],
        'ETANOL/GASOLINA'       => ['code_quiver'=>'05'],
        'GASOLINA / ALCOOL'     => ['code_quiver'=>'05'],
        'GASOLINA / ALCOOL / GAS'=> ['code_quiver'=>'07'],
        'GASOLINA / GAS'        => ['code_quiver'=>'08'],
        'ELETRICO'              => ['code_quiver'=>'09'],
        'TETRAFUEL'             => ['code_quiver'=>'10'],
        'HIBRIDO'               => ['code_quiver'=>'05'],
        //11 = outros
    ];



    /**
     * Retorna aos dados do veículo
     * @param array $block_text - (string) trecho do texto para pesquisa
     * @param array & $fields - demais campos já processados da apólice. Caso return true, os campos da forma de pagamento (conforme documentação em xls) serão mesclados a esta variável
     * @return array [sucess,msg]
     */
    protected function getData_veiculo($block_text,&$fields=null){
        if(empty($block_text) || gettype($block_text)!='string')return ['success'=>false,'msg'=>'Bloco de texto para extração inválido'];

        $text0 = strtolower(FormatUtility::removeAcents($block_text));
        $data = [];
        //dd($text0);

        //marca
        $data['veiculo_fab_1'] = $this->getData_fab($text0);
        $data['veiculo_fab_code_1'] = $this->quiverVeiCode($data['veiculo_fab_1']);

        //combustível
        $n=$this->getData_combustivel($text0);
        $data['veiculo_combustivel_1'] = $n[0]??'';
        $data['veiculo_combustivel_code_1'] = $n[1]??'';

        //placa
        $data['veiculo_placa_1'] = $this->getData_placa($text0);

        //chassi
        $data['veiculo_chassi_1'] = $this->getData_chassi($text0);

        //ano fab/modelo
        $n=$this->getData_anoModFab($text0);
        $data['veiculo_ano_fab_1'] = $n[0]??'';
        $data['veiculo_ano_modelo_1'] = $n[1]??'';

        //código ci
        $data['veiculo_ci_1'] = $this->getData_ci($text0);

        //mescla os resultados na var $fields
        if($fields)$fields = array_merge($fields,$data);


        return ['success'=>true]+$data;
    }





    //******** Funções que procuram dados no texto *******
    //******** Obs: para maior precisão, informe o texto mais extraído possível com as informaões corretas *******

    //Retorna a placa do veículo
    protected function getData_placa($text0){
        $regexPlate = '/[a-zA-Z]{3}[0-9]{4}/';
        $regexPlateMerc = '/[a-zA-Z]{3}[0-9]{1}[a-zA-Z]{1}[0-9]{2}/';
        preg_match($regexPlate, $text0, $r);
        if(count($r)!==1)preg_match($regexPlateMerc, $text0, $r);
        return count($r)==1 ? strtoupper($r[0]) : '';
    }

    //Retorna ao chassi
    protected function getData_chassi($text0){
        $text0 = FormatUtility::extractAlphaNum($text0);
        $r=null;
        foreach([17,16] as $X){//procura primeiro com 16 e depois com 17 caracteres
            $r = TextUtility::execFncInStr($text0,$X,function($v) use($X){
                $v=trim($v);
                if($this->validate_chassi($v))return true;
            });
            if($r && $r[0])break;
        }
        return strtoupper(trim($r[0]??''));
    }


    protected function validate_chassi($vin){//return boolean
            //https://stackoverflow.com/questions/30314850/vin-validation-regex/53615928#53615928
            if(preg_match('/^(?<wmi>[A-HJ-NPR-Z\d]{3})(?<vds>[A-HJ-NPR-Z\d]{5})(?<check>[\dX])(?<vis>(?<year>[A-HJ-NPR-Z\d])(?<plant>[A-HJ-NPR-Z\d])(?<seq>[A-HJ-NPR-Z\d]{6}))$/',$vin)){
                return true;
            }elseif(preg_match('/[A-HJ-NPR-Z0-9]{17}/',$vin)){
                return true;
            }elseif(preg_match('/(?i)(?<VIN>[A-Z0-9^IOQioq_]{11}\d{6})/',$vin)){
                return true;
            }

            return false;
    }


    //Retorna ao CI do veículo/apólice (considera um número na sintaxe: XXX XXXXXXXXXX-X)
    protected function getData_ci($text0){
        /*$text0=str_replace(['.','-','/'],'',$text0);//coloca X nos caracteres que formatam números
        dd($text0);
        $r = TextUtility::execFncInStr($text0,14,function($v){//regra: tem que ter 14 digitos e ser um número
            if(is_numeric($v))return true;
        });
        return trim($r[0]);*/
        $r = TextUtility::getSearchText($text0,'',function($v){
            $n=str_replace(['.',',','-','/'],'',$v);
            if(is_numeric($n) && strlen($n)==14){
                return $v;
            }
        });
        return $r;
    }


    //Retorna (array) ao combustível com o respectivo código
    protected function getData_combustivel($text0){
        $t=false;
        $data=[];
        foreach($this->combustivel_list as $comb => $opt){
            $c = FormatUtility::removeAcents($comb);
            if(stripos($text0,$c)!==false){

                if($opt['ignore']??false){
                    $t=true;
                    foreach($opt['ignore'] as $ign){
                        if(stripos($text0,$ign)!==false){
                            $t=false;break;
                        }
                    }
                    if(!$t)continue;
                }

                $data['veiculo_combustivel_1']=$opt['title']??$comb;
                $data['veiculo_combustivel_code_1']=$opt['code_quiver'];
            }
        }
        return array_values($data);
    }

    //Retorna (array fab,mod) a placa ano modelo e ano de fabricação
    protected function getData_anoModFab($text0){
        //extrai de todo o texto, somente os números, espaços, barras, textos (ano mod. e ano fab.) para diminuir a qtde de caracteres para comparação
        preg_match_all('/(ano fab.)|(ano mod.)|[0-9 \/]/',$text0,$out);
        $text0 = join('',$out[0]);
        //dd($out,$text0);

        $data=['veiculo_ano_fab_1'=>'','veiculo_ano_modelo_1'=>''];
        //procura assim o ano neste formato: yyyy/yyyy
        TextUtility::execFncInStr($text0,9,function($v,$left,$right) use(&$data){//obs: modifica var $data internamente
           $v=trim($v);
           if(strlen($v)==9 && strpos($v,'/')!==false){
               $n=explode('/',$v);
               if(strlen($n[0])==4 && strlen($n[1])==4 && is_numeric($n[0]) && is_numeric($n[1])){
                   if(substr($left,-1)==' ' && substr($right,0,1)==' '){
                        if((int)$n[0] <= (int)$n[1]){
                            $data['veiculo_ano_fab_1']=$n[0];
                            $data['veiculo_ano_modelo_1']=$n[1];
                            return true;
                        }
                   }
               }
           }
        });

        //procura pelos textos: 'Ano Fab.' e 'Ano Mod.'. Sintaxe esperada: 'Ano Fab.: {99} Ano Mod.: {99}   //{99} - dois digitos esperados
        if(empty($data['veiculo_ano_fab_1']) || empty($data['veiculo_ano_modelo_1']) ){
            $n=TextUtility::getPartOfStr($text0,['start'=>'ano fab.','end'=>'ano mod.','side_len'=>[0,4],'remove'=>':']);

            foreach(['ano fab.','ano mod.'] as $str_ano){
                TextUtility::execFncInStr($n,8,function($v,$left,$right) use(&$data,$str_ano){
                    if($v==$str_ano){
                        $v=explode(' ',trim($right))[0]??'';//captura o primeiro valor que deverá ser um ano

                        //obs: verifica o 'O' por '0' que pode vir trocado no OCR
                        if(strlen($v)==2 && in_array($v,['o1','o2','o3','o4','o5','o6','o7','o8','o9','2o']))$v=str_replace('o','0',$v);
                        if(strlen($v)==2 && is_numeric($v)){
                            //até aqui é considerado um ano válido
                            //verifica se $v tiver 2 digitos e completa
                            if((int)$v>=95){//!IMPORTANTE: considera veículos do ano 1995 para cima, e é válido na lógica até 2095
                                $v = '19'.$v;
                            }else{//maior que 2000
                                $v = '20'.$v;//!IMPORTANTE: esta lógica é válida somente para ano de veículos que estão entre 2000 e 2099, fora deste intervalo está sugeito a erro
                            }
                            if(empty($data['veiculo_ano_fab_1'])){
                                $data['veiculo_ano_fab_1']=$v;
                            }else{
                                $data['veiculo_ano_modelo_1']=$v;
                            }
                            return true;
                        }
                    }
                });
            }

            if((int)($data['veiculo_ano_fab_1']??0) > (int)($data['veiculo_ano_modelo_1']??0)){//erro, limpa os campos
                $data['veiculo_ano_fab_1'] = $data['veiculo_ano_modelo_1'] = '';
            }
            //dd($data);
        }
        return array_values($data);
    }

    //Retorna (array) a marca/fabricante do veículo - lógica: se baseia nos nomes das marcas da função quiverListVei()
    protected function getData_fab($text){
        $list = $this->quiverListVei(true);
        $text = strtolower($text);
        foreach($list as $marca){
            if(!is_array($marca))$marca=[$marca];
            foreach($marca as $m){
                if(stripos(' '.$text.' ', strtolower(' '.$m.' '))!==false){
                    return $m;
                }
            }
        }
        return '';
    }






    //******************* funções auxiliares *****************

    //Retorna a lista a uma lista de veículos do cadastro do quiver
    //Se $onlyList=true, então retorna somente aos valores da lista. Se false retorna ao codigo(quiver)=>valor na lista
    private function quiverListVei($onlyList=false){
        $vei = \App\ProcessRobot\cad_apolice\Classes\Vars\QuiverAutomovelVar::$fabricante_code;

        if($onlyList){
            $r=[];
            foreach($vei as $code=>$str){
                $r[]=$str;
            }
            return $r;
        }

        return $vei;
    }



    //Lista de códigos de veículos no cadastro do quiver
    //Parâmetro $fab_name - nome do fabricante, ex: FIAT
    //Return: código do fabrincante ou '' caso não encontrado
    protected function quiverVeiCode($fab_name){
        $vei = $this->quiverListVei();

        $fab_name=strtolower(FormatUtility::sanitizeAllText($fab_name));           //$fab_name=strtolower($this->sanitizeText($fab_name));
        $fab_name=str_replace('-',' ',$fab_name);//retira os traços

        //verifica com o nome completo
        if(in_array($fab_name,['GM','gm'])){
            $code = '17';
            return (string)$code;

        }elseif(in_array($fab_name,['kia','kia motors'])){
            $code = '27';
            return (string)$code;

        }else{
            foreach($vei as $code => $fab){
                if(!is_array($fab))$fab=[$fab];
                foreach($fab as $f){
                    $f=str_replace('-',' ',$f);//retira os traços
                    if(strtolower($f) == $fab_name){
                        return (string)$code;
                    }
                }
            }

            //até aqui não achou com o nome completo e procura somente com a primeira parte, ex: 'gm = chevrolet', procura por apenas 'gm'
            $fab_name=trim(explode('-',$fab_name)[0]);
            foreach($vei as $code => $fab){
                if(!is_array($fab))$fab=[$fab];
                foreach($fab as $f){
                    if(strtolower($f) == $fab_name){
                        return (string)$code;
                    }
                }
            }
        }
        return '';
    }



    /**
     * Retorna a marca e modelo separado em campos
     * @param $strMarcaMod - deve conter a string exatada da marca e modelo para separar os dados, ex: = 'G M Montana 1.0' - ex retorno marca GM , modelo montana
     * @return array
     */
    protected function getMarcaModelo($strMarcaMod){
            if(empty($strMarcaMod))return ['marca'=>'','modelo'=>''];

            $vei = $this->quiverListVei(true);
            $n = explode(" ", strtoupper(str_replace([chr(13),chr(10),chr(9),'  '],' ',$strMarcaMod) ));
            if(count($n)<=1)return ['marca'=>'','modelo'=>''];
            $marca='';
            $modelo='';

            foreach($vei as $fab){
                if(!is_array($fab))$fab=[$fab];
                foreach($fab as $f){
                    if($n[0].' '.$n[1] == $f || $n[0].'-'.$n[1] == $f){
                        $marca=$f;
                        unset($n[0],$n[1]);
                        $modelo = join(" ",$n);
                        break;
                    }
                }
                if($marca)break;
            }

            if(!$marca){
                foreach($vei as $fab){
                    if(!is_array($fab))$fab=[$fab];
                    foreach($fab as $f){
                        if($n[0] == $f){
                            $marca=$f;
                            unset($n[0]);
                            $modelo = join(" ",$n);
                            break;
                        }
                    }
                    if($marca)break;
                }
            }

            if(!$marca){
                //neste caso, procura pelo nome da marca tirando considerando apenas a primeira palavra antes do traço, ex: de 'IVECO-FIAT'=='IVECO'   compara para 'IVECO'=='IVECO'
                foreach($vei as $fab){
                    //dump([$n[0],$fab]);
                    if(!is_array($fab))$fab=[$fab];
                    foreach($fab as $f){
                        $tmp = explode('-',$n[0]);
                        if($tmp[0] == explode('-',$f)[0]){
                            $marca=$f;
                            unset($n[0]);
                            $modelo = (isset($tmp[1])?$tmp[1].' ':'') . join(" ",$n);
                            break;
                        }
                    }
                    if($marca)break;
                }
            }
            return ['marca'=>$marca,'modelo'=>$modelo];
    }



    //Lista de códigos de combustível no cadastro do quiver
    //Return: código do combustível ou '' caso não encontrado
    protected function getCombustivelCode($combustivel){
        foreach($this->combustivel_list as $comb => $opt) {
            if(strtoupper($combustivel)==strtoupper($comb)){
                return $opt['code_quiver'];
            }
        }
        return '';
    }




    //verifica / captura o tipo de seguro de acordo com as strings abaixo.
    //Return string ao respectivo código encontrado, ou '' se não encontrado
    protected function checkRamo(){
        return $this->checkAllRamo('automovel');
    }
}
