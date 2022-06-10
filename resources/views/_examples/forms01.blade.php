@extends('templates.admin.index')


@section('title')
Formulários 
@endsection


@section('content-view')
    
    
    <strong>Campos automáticos por parâmetro - tipos de campos</strong>
    @include('templates.ui.auto_fields',[
            'metabox'=>true,
            'form'=>[
                'url_action' => route('admin.app.post',['example','testSaveAuto']),
                'bt_save' => true,
            ],
            'layout_type'=>'horizontal',
            'autocolumns'=>[
                'fieldname1'=>['label'=>'Campo Input','maxlength'=>40,'class_group'=>'require','info_html'=>'Mensagem html adicional'],
                'fieldname1_bt'=>['label'=>'Campo Input com botão 1','class_group'=>'require','button'=>['title'=>'Action']],
                'fieldname1_bt1'=>['label'=>'Campo Input com botão 1','class_group'=>'require','button'=>['icon'=>'fa-user','title'=>'']],
                'fieldname1_bt2'=>['label'=>'Campo Input com botão 2','class_group'=>'require','button'=>[
                    'align'=>'left','icon'=>'fa-user','title'=>'Botão', 'alt'=>'texto completo do botão','color'=>'primary',
                    'sub'=>[
                        'a'=>'Opção 1',
                        'b'=>'Opção 2',
                        'c'=>['title'=>'Opção 3','sub'=>[
                            'a'=>'Opção 1',
                            'b'=>'Opção 2',
                            'c'=>'Opção 3',
                        ]]
                    ]
                 ]],
                'fieldname2'=>['type'=>'select','label'=>'Campo Seleção','list'=>[
                    'a'=>'Opção 1',
                    'b'=>'Opção 2',
                    'c'=>'Opção 3',
                ],'class_group'=>'require'],
                
                'fieldname2_group'=>['type'=>'select','label'=>'Campo Seleção Grupo','list'=>[
                    ''=>'',
                    'Grupo 1'=>[
                        'a'=>'Opção 1',
                        'b'=>'Opção 2',
                        'c'=>'Opção 3',
                    ],
                    'Grupo 2'=>[
                        'd'=>'Opção 4',
                        'e'=>'Opção 5',
                        'f'=>'Opção 6',
                    ],
                    'g'=>'Opção 7',
                ],'class_group'=>'require','value'=>'e'],
                
                'fieldname3'=>['type'=>'textarea','label'=>'Campo textarea','maxlength'=>20],
                'fieldname3_a'=>['type'=>'textarea','label'=>'Campo textarea (altura automática)','maxlength'=>100,'rows'=>'3','max_rows'=>'3','auto_height'=>true],
                'fieldsearch'=>['type'=>'search','label'=>'Busca','class_group'=>'require'],
                'fieldnamecid|fieldnameuf'=>['type'=>'cidade_uf','label'=>'Cidade / UF','class_group'=>'require','placeholder'=>true],
                'fieldnameend|fieldnamenum'=>['type'=>'endereco_num','label'=>'Endereço, nº','class_group'=>'require'],
                'fieldnameuf2'=>['type'=>'uf','label'=>'Estados UF'],
                'fieldnamepais'=>['type'=>'pais','label'=>'Países'],
                'fieldname5'=>['type'=>'select2','label'=>'Campo Seleção 2','attr'=>'data-allow-clear="true"','list'=>[
                    ''=>'',
                    'a'=>'Opção 1',
                    'b'=>'Opção 2',
                    'c'=>'Opção 3',
                ]],
                'fieldname5ajax'=>['type'=>'select2','label'=>'Campo Seleção 2 com Ajax','ajax_url'=>route('admin.app.index','example').'?name=data-select-ex'],
                
                'fieldname5_multiple'=>['type'=>'select2','label'=>'Campo Seleção 2 Múltiplo','attr'=>'multiple="multiple"','list'=>[
                    'a'=>'Opção 1',
                    'b'=>'Opção 2',
                    'c'=>'Opção 3',
                ]],
                
                'fieldname9'=>['type'=>'password','label'=>'Senha','maxlength'=>5],

                'fieldname10a'=>['type'=>'email','label'=>'E-mail'],
                'fieldname10b'=>['type'=>'email','label'=>'E-mail Mark','is_mark'=>true,'value'=>'a@b.c','value_mark'=>'Opção e-mail'],

                'fieldname12'=>['type'=>'number','label'=>'Número','maxlength'=>5],
                'fieldname13'=>['type'=>'info','label'=>'Label','text'=>'Minha informação de exemplo'],
                'fieldname14'=>['type'=>'date','label'=>'Data','picker'=>true],
                'fieldname15'=>['type'=>'time','label'=>'Hora','picker'=>true],
                'fieldname16'=>['type'=>'datetime','label'=>'Data e Hora','picker'=>true],
                'fieldname17'=>['type'=>'daterange','label'=>'Data Intervalo','picker'=>true],
                'fieldname18'=>['type'=>'cpf','label'=>'CPF'],
                'fieldname19'=>['type'=>'cnpj','label'=>'CNPJ'],
                'fieldname20'=>['type'=>'cep','label'=>'Cep'],

                'fieldname21a'=>['type'=>'phone','label'=>'Fone somente', 'value'=>'1535242357'],
                'fieldname21b'=>['type'=>'phone','label'=>'Fone + marcador', 'is_mark'=>true, 'value'=>'1535242357', 'value_mark'=>'Opção customizado'],
                'fieldname21c'=>['type'=>'phone','label'=>'Fone + ddi', 'is_ddi'=>true, 'value'=>'1535242357', 'value_ddi'=>'88'],
                'fieldname21d'=>['type'=>'phone','label'=>'Fone + ddi + marc', 'is_ddi'=>true, 'is_mark'=>true, 'value'=>'1535242357', 'value_ddi'=>'88', 'value_mark'=>'Opçao Customizado'],

                'fieldname23'=>['type'=>'decimal','label'=>'Valor Decimal'],
                'fieldname24'=>['type'=>'currency','label'=>'Valor Moeda/Preço'],
                'fieldname25_radio'=>['type'=>'radio','label'=>'Radio','break_line'=>true,'list'=>[
                    'a'=>'Opção 1',
                    'b'=>'Opção 2',
                    'c'=>'Opção 3',
                ]],
                'fieldname26_checkbox[]'=>['type'=>'checkbox','label'=>'Checkbox','break_line'=>true,'value'=>['b'],'list'=>[
                    'a'=>'Opção 1',
                    'b'=>'Opção 2',
                    'c'=>'Opção 3',
                ]],
                'fieldname26_sim_nao'=>['type'=>'sim_nao','label'=>'Sim ou Não','default'=>'n'],
                'fieldname27'=>['type'=>'button_field','label'=>'Botão como campo','color'=>'primary','title'=>'Meu Botão'],
                'fieldname27a'=>['type'=>'upload','label'=>'Upload','class_button'=>'btn btn-primary'],
                'fieldname27-uplzone'=>['type'=>'uploadzone','label'=>'Zona de Upload'],
                
                'fieldname28'=>['type'=>'color','label'=>'Cor','picker'=>true],
                'fieldname28a'=>['type'=>'colorbox','label'=>'Cor Box'],
                'fieldname29'=>['type'=>'editor','label'=>'CkEditor - exemplo em 1 linha','height'=>200,'class_group'=>'form-group-line'],//deixa em uma linha só
                'fieldname30'=>['type'=>'editor','label'=>'CkEditor - Curto','template'=>'short'],
                'fieldname31'=>['type'=>'editorcode','label'=>'Editor de Código'],
                'fieldname32'=>['type'=>'editorcode','label'=>'Editor de Código Curto','template'=>'short'],
                'fieldname34'=>['type'=>'text_filemanager','label'=>'Campo Input padrão com botão filemanager','class_group'=>'require',
                    'filemanager_param'=>['multiple'=>true,'show_upload'=>false,'controller'=>'files'],
                    //'filemanager_return'=>'ids',    //or 'urls'
                    'filemanager_return'=>'@fncCustomGetFiles',
                ],
                'fieldname35'=>['type'=>'text_filemanager','label'=>'Campo Input Customizado com botão filemanager',
                    'placeholder'=>'Clique no botão ao lado',
                    'attr'=>'readonly="readonly"',
                    'button'=>['title'=>'Escolher apenas um arquivo','icon'=>'fa-refresh'],
                    'filemanager_param'=>['filetype'=>'pdf','show_folder'=>false,'controller'=>'files'],
                ],
            ],
    ])
    
    
<script>
    function fncCustomGetFiles(opt){ console.log(opt);return opt.count + " arquivos retornados"; }
</script> 

@endsection


    