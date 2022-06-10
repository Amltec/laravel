@php

#resources\views\super-admin\view\test-captura-proposta.blade.php
$insurer_name='mapfre';

$texto_pdf = file_get_contents(base_path() . '\\resources\\views\\super-admin\\view\\test-textos\\'. $insurer_name .'.txt');

$strcls = '\\App\\ProcessRobot\\cad_apolice\\ClassesPropostas\\'.$insurer_name.'Class';
$class = new $strcls;
$r = $class->process($texto_pdf);

dd($r);



@endphp
