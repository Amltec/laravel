<?php
namespace App\ProcessRobot\cad_apolice\Classes\Data;


/**
 * Classe que irá retornar ao padrão do número da apólice para o Quiver.
 * Este classe é processado no controller App\Http\Controllers\Process\ProcessCadApoliceControll()->processFilePDF()
 */
class NumQuiverData{

    /**
     * Método principal
     */
    public static function process($num_original, Array $opt){
        $opt = array_merge([
            'ex_num'=>'',           //número de exemplo
            'num_origem'=>false,    //se true irá pegar o número conforme original
            'not_dot_traits'=>false,//se true irá pegar o número e retirar os pontos e traços
            'len'=>false,           //(int) limite de caracteres da esquerda para a direita. Se =0 ou false, ignora
            'len_r'=>false,         //(int) limite de caracteres da direita para a esquerda. Se =0 ou false, ignora
            'last_dot'=>false,      //se true irá pegar o número depois do último ponto
            'between_dots'=>false,  //se true irá pegar o número entre o penultimo e o último ponto
            'not_zero_left'=>false, //se true irá retirar os zeros a esquerda
            
        ],$opt);
        //dd('xx',$opt);
        $num = $num_original;

        extract($opt);

        //se true irá pegar o número conforme original
        if($num_origem){
            $num = $num;
        }

        //se true irá pegar o número e retirar os pontos e traços
        if($not_dot_traits){
            $num = str_replace(['.','-'],[''],$num);
        }

        //limite de caracteres da esquerda para a direita
        if(is_numeric($len))$len=(int)$len;
        if($len>0){
            $num = substr($num,$len);
        }
        
        //limite de caracteres da direita para a esquerda
        if(is_numeric($len_r))$len_r=(int)$len_r;
        if($len_r>0){
            $num = substr($num,strlen($num)-$len_r,strlen($num));
        }
        
        

        ///se true irá pegar o último caractere até o ponto
        if($last_dot){
            $n = explode('.',$num);
            $num = $n[ count($n)-1 ];
        }

        ///se true irá pegar o número entre o penultimo e o último ponto
        if($between_dots){
            if(strpos($num,'.')!==false){
                $n = explode('.',$num);
                $num = $n[ count($n)-2 ] ?? '';
            }
        }


        ///se true irá retirar os zeros a esquerda
        if($not_zero_left){
            $num = ltrim($num,'0');
        }

        //dd('*',$num);
        return $num;
    }
}
