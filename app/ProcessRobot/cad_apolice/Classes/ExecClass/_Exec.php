<?php
//Classe a ser extendida

namespace App\ProcessRobot\cad_apolice\Classes\ExecClass;

class _Exec{
    protected $text, $ProcessClass, $ProcessModel;

    public function __construct($ProcessClass, $ProcessModel, $text){
        $this->ProcessClass = $ProcessClass;
        $this->ProcessModel = $ProcessModel;
        $this->text = $text;
        return $this->process();
    }

    //exemplo
    public function process(){
        //...
        //return ['success'=>false,'msg'=>'Não programado','data'=>[]];
    }

}
