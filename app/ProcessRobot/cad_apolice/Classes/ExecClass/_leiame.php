<?php /*
Deve conter as classes auxiliares para a ferramenta "Assistente de Leitura de PDFs" em /super-admin/dev/view?pag=tool-test-read-pdf

Arquivo _list.php
    Deve conter a lista das classes já programadas.

Sintaxe nome do arquivo:
    {ClassName}Exec.php     //Ex1Exec.php

Sintaxe classe:
    namespace App\ProcessRobot\cad_apolice\Classes\ExecClass;

    public class Ex1Class extends Exec{
        public function process(){
            $this->processModel...  //repespectivo model process_robot já capturado
            $this->text...          //texto da extração do arquivo já capturado
            return [success=>(bool), msg=>(str), data=>[...] ]
        }
    }



