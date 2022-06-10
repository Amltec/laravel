@extends('templates.admin.index')

@php
$value = '
<h1>Qua igitur re ab deo vincitur, si aeternitate non vincitur?</h1>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Occultum facinus esse potuerit, gaudebit; Tria genera bonorum; At certe gravius. Ea possunt paria non esse. Fortasse id optimum, sed ubi illud: Plus semper voluptatis?</p>
</p>Hoc Hieronymus summum <strong>bonum</strong> esse dixit. De vacuitate doloris eadem sententia erit. Duo Reges: constructio interrete. Hunc vos beatum;</p>
';

//carrega os dados do editor (não necessário, pois será carregado automaticamente)
//Form::loadScript('jodit');


@endphp



@section('title')
Editores de Conteúdo  - Jodit (em desenvolvimento)
@endsection


@section('content-view')
<a href="https://xdsoft.net/jodit/" target="_blank">https://xdsoft.net/jodit/</a><br><br>

<div style="color:red;font-weight:bold;">em desenvolvimento</div><br>

@include('templates.ui.auto_fields',[
    'form'=>[
        'url_action' => route('super-admin.app.post',['example','testSaveEditor']),
        'bt_save' => true,
    ],
    'layout_type'=>'Vertical',
    'autocolumns'=>[
        'editor01'=>['type'=>'editor','plugin'=>'jodit','label'=>'Jodit <span class="nostrong">(configuração padrão)</span>','auto_height'=>true,'toolbar_fixed'=>true,
            'filemanager'=>['private'=>true,'show_trash'=>false,'show_remove'=>false,'show_regs'=>false,'show_view_img'=>false]
        ],
    ]
])


@endsection
