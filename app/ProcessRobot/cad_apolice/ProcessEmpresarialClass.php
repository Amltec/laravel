<?php
namespace App\ProcessRobot\cad_apolice;

/**
 * Classe de ações gerais do ramos residencial
 * Deve ser extendida por cada /residencial/{seguradora}Class.php
 */
class ProcessEmpresarialClass extends ProcessResidencialClass{
    protected $seg_name='empresarial';
}
