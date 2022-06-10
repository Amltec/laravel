<?php

namespace App\ProcessRobot\cad_apolice;
use App\ProcessRobot\cad_apolice\QuiverClass;

use App\Models\Robot;
use App\Utilities\FormatUtility;
use App\Utilities\ValidateUtility;
use App\Utilities\TextUtility;


/**
 * Classe de ações gerais do ramos residencial
 * Deve ser extendida por cada /residencial/{seguradora}Class.php
 */
class ProcessResidencialClass extends QuiverClass{
    protected $seg_name='residencial';


    //valida os dados processados pela função ::process()
    public function ValidateData($r){
        if(!$r['success'])return $r;
    	$data=$r['data'];//obs: aqui contém a matriz unica com todos os campos
        if(!isset($data['_ignore_fields_cad']))$data['_ignore_fields_cad']=[];//campos a serem ignorados pelo AutoIt no Quiver
        
        //validação base com todos os campos
        $n = $this->validateBase($data,$this->seg_name);
        if(!$n['success'])return $n;
        
        //atualizar a var data
        $data['_ignore_fields_cad'] = join(',',$data['_ignore_fields_cad']);
        $r['data']=$n['data'];
        $r['code']='ok';
        $r['msg']='Extraído com sucesso';
        
        return $r;
    }
    
    
    //verifica / captura o tipo de seguro de acordo com as strings abaixo.
    //Return string ao respectivo código encontrado, ou '' se não encontrado
    protected function checkRamo(){
        return $this->checkAllRamo($this->seg_name);
    }
}
