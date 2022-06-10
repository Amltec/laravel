@php
$list = $list??['s'=>'Sim','n'=>'Não'];
$type='radio';

$name2=rtrim($name,'[]');//tira o final os caracteres[] que indicam que os valores deste campo estão no formato array
$this_value = data_get($autodata??null,$name) ?? Form::getValueAttribute($name) ?? $value ?? null;


if(in_array((string)($this_value??''),['s','1']))$this_value='s';
if(in_array((string)($this_value??''),['n','0']))$this_value='n';
if(in_array((string)($list[0]??''),['s','1']))$list[0]='s';
if(in_array((string)($list[1]??''),['n','0']))$list[1]='n';

@endphp

@include('templates.components.radio')
