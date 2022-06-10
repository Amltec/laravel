<?php

namespace App\ProcessRobot\cad_apolice\Classes\Insurers;


/**
 * Classe trait de funções funções gerais para leitura de apólices pdf de qualquer ramo da Seguradora Sompo
 * Deve ser incorporada a partir da uma classe de um ramo específico, como a classe ex: App\ProcessRobot\cad_apolice\automovel\sulamericaClass.php
 */
trait sulamericaInsurer{



    /**
     * Configuração para o padrão do número do quiver
     */
    function numQuiverConfig(){
        return [
            'ex_num'=>'397407-0',
            'not_dot_traits'=>true
        ];
    }
}
