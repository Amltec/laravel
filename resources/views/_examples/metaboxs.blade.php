@extends('templates.admin.index')


@section('title')
Metaboxs
@endsection


@section('content-view')
    
    @include('templates.components.metabox',[
        'title'=>'Box 01',
        'content'=>'Meu conteúdo <strong>html</strong> de exemplo (string).',
    ])
    
    @include('templates.components.metabox',[
        'title'=>'Box 02',
        
        //obs: aqui posso usar a função get_defined_vars() que captura as variáveis locais no formato de array
        'content'=>function(){
            echo 'Meu conteúdo <strong>html</strong> de exemplo (callback function).';
        },
        'color'=>'danger',
        'is_close'=>true,
        'is_collapse'=>true,
        'is_bg'=>true,
    ])
    
    @include('templates.components.metabox',[
        'title'=>'Box 03',
        'content'=>'Meu conteúdo <strong>html</strong> de exemplo (string).',
        'color'=>'success',
        'is_collapse'=>true,
        'footer'=>'Meu rodapé'
    ])
    
    @include('templates.components.metabox',[
        'title'=>'Box 04',
        'content'=>'Meu conteúdo <strong>html</strong> de exemplo (string).',
        'color'=>'info',
        'is_collapse'=>true,
        'header'=>'Informações adicionais <span class="badge bg-red">123</span> ',
        'footer'=>'Meu rodapé'
    ])
    
    @include('templates.components.metabox',[
        'title'=>'Box 05',
        'content'=>'Box sem cabeçaçalho - apenas conteúdo',
        'header'=>false,
    ])
    
    @include('templates.components.metabox',[
        'title'=>'bla bla bla',
        'content'=>'Box sem borda ',
        'is_border'=>false
    ])
    
    @include('templates.components.metabox',[
        'title'=>'Importando componentes',
        'content'=>function(){
            echo view('templates.components.button',['title'=> 'Meu Botão','color'=>'primary', 'icon'=>'fa-user']);
        },
        'is_border'=>false
    ])
    
    <div class="row">
        <div class="col-sm-6">
        @include('templates.components.metabox',[
            'title'=>'Tamanho reduzido',
            'content'=>'Meu conteúdo <strong>html</strong> de exemplo (string).',
            'is_collapse'=>true,
            'class'=>''
        ])
        </div>
    </div>
    
    <br>
    @include('templates.components.metabox',[
        'title'=>'Botões padrões - by function',
        'content'=>'Meu conteúdo <strong>html</strong> de exemplo (string).',
        'is_collapse'=>true,
        'class'=>'',
        'footer'=>function(){
            echo '<div class="pull-left">';
                echo view('templates.components.button',['title'=> 'Confirmar','color'=>'primary']);
            echo '</div>';
            echo '<div class="pull-right">';
            echo view('templates.components.button',['title'=> 'Cancelar']);
            echo '</div>';
        }
    ])
    
    <br>
    @include('templates.components.metabox',[
        'title'=>'Botões padrões - by param footer',
        'content'=>'Meu conteúdo <strong>html</strong> de exemplo (string).',
        'is_collapse'=>true,
        'class'=>'',
        'footer'=>[
            'bt'=>true,
            'bt2'=>['title'=>'Fechar', 'icon'=>'fa-close'],
        ]
        
    ])
    
@endsection