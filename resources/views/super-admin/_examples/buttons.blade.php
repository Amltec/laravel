@extends('templates.admin.index')


@section('title')
Botões
@endsection


@section('content-view')
    
<div class="box box-primary">
<div class="box-body">
    
    <p>Botões - Cores Padrões</p>
    @include('templates.components.button',['title'=> 'Default'])
    @include('templates.components.button',['title'=> 'Primary','color'=>'primary'])
    @include('templates.components.button',['title'=> 'Success','color'=>'success'])
    @include('templates.components.button',['title'=> 'Info','color'=>'info'])
    @include('templates.components.button',['title'=> 'Danger','color'=>'danger'])
    @include('templates.components.button',['title'=> 'Warning','color'=>'warning'])
    @include('templates.components.button',['title'=> 'Link','color'=>'link'])
    
    <p>Botões - Cores Adicionais por classes</p>
    @include('templates.components.button',['title'=> 'Maroon', 'class'=>'bg-maroon'])
    @include('templates.components.button',['title'=> 'Purple', 'class'=>'bg-purple'])
    @include('templates.components.button',['title'=> 'Navy', 'class'=>'bg-navy'])
    @include('templates.components.button',['title'=> 'Orange', 'class'=>'bg-orange'])
    @include('templates.components.button',['title'=> 'Olive', 'class'=>'bg-olive'])
    @include('templates.components.button',['title'=> 'Red', 'class'=>'bg-red'])
    @include('templates.components.button',['title'=> 'Green', 'class'=>'bg-green'])
    @include('templates.components.button',['title'=> 'Blue', 'class'=>'bg-blue'])
    
    <br><br><p>Botões - Ícones</p>
    @include('templates.components.button',['title'=> 'Meu Botão', 'icon'=>'fa-user'])
    @include('templates.components.button',['title'=> '', 'alt'=> 'Meu Botão', 'icon'=>'fa-user'])
    @include('templates.components.button',['title'=> 'Meu Botão', 'icon'=>'fa-user', 'icon_pos'=>'right'])
    @include('templates.components.button',['title'=> 'Meu Botão', 'icon'=>'fa-user', 'icon_pos'=>'top'])
    
    <br><br><p>Botões - Tamanhos</p>
    @include('templates.components.button',['title'=> 'Normal', 'icon'=>'fa-user'])
    @include('templates.components.button',['title'=> 'Large', 'icon'=>'fa-user', 'size'=>'lg'])
    @include('templates.components.button',['title'=> 'Small', 'icon'=>'fa-user', 'size'=>'sm'])
    @include('templates.components.button',['title'=> 'X-Small', 'icon'=>'fa-user', 'size'=>'xs'])
    
    <br><br><p>Botões - Classes</p>
    @include('templates.components.button',['title'=> 'Desabilitado', 'icon'=>'fa-user', 'class'=>'disabled'])
    @include('templates.components.button',['title'=> 'Loading 1', 'icon'=>'fa-spinner fa-spin'])
    @include('templates.components.button',['title'=> 'Loading 2', 'icon'=>'fa-circle-o-notch fa-spin'])
    @include('templates.components.button',['title'=> 'Loading 3', 'icon'=>'fa-refresh fa-spin'])
    <div style="width:300px;border:1px solid #ccc;padding:20px;margin-top: 10px;">
        @include('templates.components.button',['title'=> 'Bloco', 'icon'=>'fa-user', 'class'=>'btn-block'])
    </div>
    
    <br><br><p>Botões - Badge</p>
    @include('templates.components.button',['title'=> 'Botão', 'badge'=>'123'])
    @include('templates.components.button',['title'=> 'Botão', 'badge'=>'123', 'badge_color'=>'green'])
    @include('templates.components.button',['title'=> 'Botão', 'badge'=>'Meu texto', 'icon_pos'=>'top'])
    
    <br><br><p>Botões - Menu</p>
    @include('templates.components.button',['title'=> 'Botão com menu', 'sub'=>
        [
            '1'=>'Menu 1',
            '2'=>'Menu 2',
            '3'=>'Menu 3',
        ]
    ])
    @include('templates.components.button',['title'=> 'Botão com opções em menu', 'sub_opt'=>
        [
            '1'=>'Menu 1',
            '2'=>'Menu 2',
            '3'=>'Menu 3',
        ]
    ])
    
    @include('templates.components.button',['title'=> 'Botão completo 2', 'color'=>'info', 'icon'=>'fa-user',
        'size'=>'lg','badge'=>'ok',
        'sub_opt'=>[
            '1'=>'Menu 1',
            '2'=>'Menu 2',
            '3'=>'Menu 3',
        ]
    ])
    @include('templates.components.button',['title'=> 'Botão completo / menu', 'color'=>'info', 'icon'=>'fa-user',
        'badge'=>'ok','icon_pos'=>'top',
        'sub'=>[
            '1'=>'Menu 1',
            '2'=>'Menu 2',
            '3'=>'Menu 3',
        ]
    ])
    
    <br><br><p>Botões - Grupo (manual html)</p>
    <div class="btn-group">
        @include('templates.components.button',['title'=> 'Opção A'])
        @include('templates.components.button',['title'=> 'Opção B'])
        @include('templates.components.button',['title'=> 'Opção C'])
    </div>
    
    
    <br><br><p>Botões - Grupo (auto include)</p>
    @include('templates.components.button_group',[
        'buttons'=>[
            ['title'=> 'Botão 1','icon'=>'fa-edit'],
            ['title'=> 'Botão 2','icon'=>'fa-close'],
            ['title'=> 'Botão 3','icon'=>'fa-user'],
        ]
    ])
    @include('templates.components.button_group',[
        'buttons'=>[
            ['title'=> 'Botão','color'=>'primary'],
            ['title'=> 'Botão com menu','color'=>false,'class'=>'bg-navy',
                'sub'=>[
                    '1'=>'Menu 1',
                    '2'=>'Menu 2',
                    '3'=>'Menu 3',
                ]
            ],
            ['title'=> 'Link','href'=>'https://www.google.com/','target'=>'blank'],
            ['title'=> false,'icon'=>'fa-user','color'=>'danger'],
        ]
    ])
    
    
    <hr style="margin:50px 0;">
    <h3>Botões de Ações</h3>
    
    <br><p>Botões - Upload</p>
    @include('templates.components.button',['title'=> 'Upload', 'type'=>'upload', 'color'=>'primary'])
    
    
    <br><br><p>Botões - Links</p>
    @include('templates.components.button',['title'=> 'Link', 'href'=>'https://www.google.com.br', 'target'=>'_blank'])
    
    
    <br><br><p>Botão - Abre o Filemanager</p>
    @include('templates.components.button',['title'=> 'Abrir Arquivos','color'=>'primary', 'icon'=>'fa-image','onclick'=>'awFilemanager({multiple:true,onSelectFile:function(opt){ alert(awCount(opt.files)+" arquivos selecionados") }  });'])
    
    
    <br><br><br><br><br><br><br><br><br><br><br>
    
</div>
</div>
@endsection