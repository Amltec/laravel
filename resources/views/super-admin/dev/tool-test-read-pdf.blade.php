@extends('templates.admin.index')

@section('title')
(DEV) Assistente de Leitura de PDFs pelas Classes de Seguradoras
@endsection


@section('content-view')
@php
if(Auth::user()->user_level!='dev')exit('negado');

//Lista de classe já programadas. Sintaxe: [basename=>title]
$exec_class_list = require( app_path() .'/ProcessRobot/cad_apolice/Classes/ExecClass/_list.php');


if(Request::input('pag2')=='diff_fields'){
    //$model=DB::table('dev_process_robot_test_read_pdf')->find(Request::input('id'));
    $model=DB::table('dev_process_robot_test_read_pdf')->where('process_id',Request::input('id'))->first();
    if(!$model)exit('Registro não encontrado');
    $n=$model->diff_fields;
    if($n)$n=unserialize($n);
//  dump($n);

    //expand dd
    echo "<script>
        var compacted = document.querySelectorAll('.sf-dump-compact');
        for (var i = 0; i < compacted.length; i++) {
          compacted[i].className = 'sf-dump-expanded';
        }
        </script>";

    exit;
}




echo '
<p>
    <strong>Esta página simula / salva o processo de "indexação"</strong>, ou seja, testa a classe do respecto ID para confirmar se irá fazer a leitura correta dos campos do texto do PDF.<br>
    <span class="text-orange">Válido apenas para o processo <strong>cad_apolice</strong></span><br>
</p>
';




$status_list = [
    '0' => ['title'=>'Não iniciado', 'count'=>0],
    'o' => ['title'=>'Em extração', 'count'=>0],
    'a' => ['title'=>'Em andamento', 'count'=>0],
    'e' => ['title'=>'Erro', 'count'=>0],
    'f' => ['title'=>'Finalizado ok', 'count'=>0],
    'x' => ['title'=>'Id inválido', 'count'=>0],

    //obs: 'diff' não é um valor de status, mas está nesta matriz para ficar compatível na lista de filtros mais abaixo
    'diff'=>['title'=>'<span title="Finalizado com diferenças na extração em relação ao gravado no registro">F. Diferenças</span>', 'count'=>0],
];
$count_st_a0=0;


    echo view('templates.ui.auto_fields',[
        'form'=>[
            'url_action'=> route('super-admin.app.post',['dev','toolTestReadPdfActions']),
            'data_opt'=>[
                'onSuccess'=>"@function(){ window.location.reload(); }"
            ],
            'bt_save'=>'Verificar IDs',
        ],
        'metabox'=>true,
        'autocolumns'=>[
            'action'=>['value'=>'add','type'=>'hidden'],
            'ids'=>['label'=>'IDs do Cadastro de Apólices <small style="margin-left:5px;color:#999;font-weight:400;">Valores separados por virgula</small>','require'=>true,'type'=>'textarea'],
            'opt[]'=>['type'=>'checkbox','break_line'=>true,'list'=>[
                    'clear'=>'A - Remover todos os registros atuais',
                    'extract'=>'B - Executar extração (irá salvar o texto)',
                    'extract_compare'=>'C - <span title="Compara se o teste da extração atual é diferente da extração anterior">Comparar com a extração salva</span> <a href="#" onclick="$(\'#form-group-compare_fields\').fadeToggle();return false;" class="margin-r-10 strong">Editar</a>',
                    'save_index'=>'D - Salvar resultado da indexação',
                    'exec_class'=>'E - Executar a classe <a href="#" onclick="$(\'#form-group-exec_class\').fadeToggle();return false;" class="margin-r-10 strong">Editar</a>',
                ],
                'info_html'=>'<span class="text-muted" style="font-size:0.9em;">
                                - B, D: Apenas salva os dados, mas não irá alterar os campos dos registros: seguradora, corretor e status <br>
                                - C, D: se marcar ação C (Comparar com a extração salva), não será processado a opção D (Salvar resultado da indexação) <br>
                                - E: nesta caso não executa a indexação, e apenas a classe informada (item ignora os demais: B, C e D).
                            </span>'
            ],
            'compare_fields'=>['type'=>'textarea','class_group'=>'hiddenx','rows'=>3,'auto_height'=>true,'label'=>'Digite os nomes dos campos separados por virgula ou por linha'.
                                    '<br><small class="nostrong">Deixe vazio para todos</small>'.
                                    '<br><small class="nostrong">Obs: para campos multinhas deverá escrever apenas o nome do campo, ex: "cep_1" para "cep"</small>'.
                                    ''
                                ],
            'exec_class'=>['type'=>'select','list'=>[''=>''] + $exec_class_list,'label'=>'Selecione o arquivo da Classe','class_group'=>'hiddenx col-sm-4','attr'=>'onchange=\'$("[type=checkbox][value=exec_class]").prop("checked",this.value!="");\''],

        ],
    ]);

    //captura os dados da lista
    $list = DB::table('dev_process_robot_test_read_pdf')->orderBy('dt_start','asc');

    //filtros
    $f_st = Request::input('f_st');
    if($f_st){
        if($f_st=='diff'){
            $list->whereNotNull('diff_fields');
        }else{
            $list->where('status',$f_st);
        }
    }

    //captura os registros
    $list = $list->paginate(_GETNumber('regs')??15);


    echo view('templates.components.metabox',[
            'id'=>'div_resume_data',
            'content'=>function() use($status_list,$f_st){
                echo '<div class="pull-right" style="margin-top:5px;">'.
                        '<a href="?pag=tool-test-read-pdf&action=stop" class="btn btn-primary hiddenx j-stop" id="btn-process-stop"><span class="fa fa-circle-o-notch fa-spin margin-r-5"></span> Parar processamento</a>'.
                        '<a href="?pag=tool-test-read-pdf" class="btn btn-primary '. (Request::input('action')=='stop'?'':'hiddenx') .' j-play" id="btn-process-play"><span class="fa fa-play margin-r-5"></span> Iniciar processamento</a>'.
                    '</div>';

                echo 'Resumo do processamento <span class="div-perc"></span><br>'.
                    '<a href="'. Request::fullUrlWithQuery(['f_st'=>null,'page'=>null]) .'" class="margin-r-5'. ($f_st=='' ?' strong text-aqua':'') .'">Todos <span class="div-count div-count-all"></span></a> ';
                foreach($status_list as $st => $opt){
                    //dump([$f_st,$st]);
                    echo '<a href="'. Request::fullUrlWithQuery(['f_st'=>$st,'page'=>null]) .'" class="margin-r-5'. ((String)$f_st===(String)$st ?' strong text-aqua':'') .'">'. $opt['title'] . ' <span class="div-count div-count-'.$st.'"></span></a> ';
                }
            }
        ]);

    echo view('templates.ui.auto_list',[
            'list_id'=>'process_robot_list',
            'data'=>$list,
            'columns'=>[
                'process_id'=>['Process ID','value'=>function($v){return '<a href="'. route('super-admin.app.show',['process_cad_apolice',$v]) .'" target="_blank">'.$v.'</a>';}],
                'dt_start'=>'Data',
                'status'=>['Status','value'=>function($v) use($status_list){return $status_list[$v]['title']??'-';}],
                'msg'=>['Mensagem','value'=>function($v){return str_limit($v,100);}],
                'bts1'=>['Ações','value'=>function($v,$reg){
                    if($reg->exec_class_name){
                        return '<a href="'. route('super-admin.app.get',['dev','pageRetData']) .'?id='.$reg->process_id.'" target="_blank" class="margin-r-10">Ver retorno</a>';
                    }else{
                        return '<a href="'. route('super-admin.app.get',['process_cad_apolice','pageFileExtracted',$reg->process_id,'type=xml&force=ok']) .'" target="_blank" class="margin-r-10">Ver extração</a>'.
                                    ($reg->diff_fields ? '<a href="?pag=tool-test-read-pdf&id='. $reg->process_id .'&pag2=diff_fields" target="_blank">Diferenças</a>' : '');
                    }
                }],
            ],
            'options'=>[
                'checkbox'=>true,
                'select_type'=>2,
                'pagin'=>true,
                'confirm_remove'=>true,
                'toolbar'=>true,
                'regs'=>true,
                'search'=>false,
                'is_trash'=>false,
                'list_remove'=>false,
            ],
            'routes'=>[
                'remove'=>route('super-admin.app.post',['dev','toolTestReadPdfActions']),
            ],
            'row_opt'=>[
                'class'=>function($reg){return in_array($reg->status,['e','x'])?'text-red':'';}
            ],
            'field_id'=>'process_id',
            'metabox'=>true,
            'list_class'=>'table-condensed',
            'toolbar_buttons'=>[
                ['title'=>'Capturar IDs','onclick'=>'dev_getIds()','class'=>'j-show-on-select']
            ],
            'toolbar_buttons_right'=>[
                ['title'=>'Todos','icon'=>'fa-trash','alt'=>'Remover todos','class'=>'margin-r-5','onclick'=>'awBtnPostData({url:"'. route('super-admin.app.post',['dev','toolTestReadPdfActions']) .'",data:{action:"remove_all"},cb:function(){window.location.reload();},confirm:true},this);'],
            ]
        ]);


@endphp



<script>
//processa os resultados da lista
var is_running=false;
function process_result(){
    var is_stop = {{Request::input('action')=='stop'?'true':'false'}};
    awAjax({
        url: "{{route('super-admin.app.get',['dev','toolTestReadPdfActions'])}}",
        data:{action:'process_result',stop:(is_stop?'ok':'')},
        processData:true,
        success: function(r){
            //console.log(r)
            if(r.success){
                var base=$('#div_resume_data');
                var s=r.status_list;
                for(var i in s){
                    base.find('.div-count-'+i).text('('+s[i]+')');
                };
                //percentual
                var n=0,p=0;
                if(s['all']>0){
                    n= 1- ((s['0'] + s['a'] + s['o']) / s['all']);
                    p = (n*100).toFixed(2).replace('.',',') +'%';
                    base.find('.div-perc').html(p + (n<1 && is_stop==false?' <span class="fa fa-circle-o-notch fa-spin" style="margin-left:5px;"></span>':'') );

                    if(is_stop)return;
                    var btns=$('#btn-process-stop,#btn-process-play');
                    if(n>=1){//nada para processar
                        if(is_running)window.location.reload();
                    }else{//exibe o botão parar
                        is_running=true;
                        btns.filter('.j-stop').show();
                    };
                }else{

                }
            };
            if(r.auto_continue)setTimeout(process_result, 10);
        },
        error:function (xhr, ajaxOptions, thrownError){
            console.log('Erro interno de servidor',xhr.responseText.substring(0,200) + '...');
            setTimeout(process_result, 1000*30);
        }
    });

    //autoresfresh a cada 10min
    setTimeout('window.location.reload();',1000*60*10);
};


function dev_getIds(){
    var ids=$('#process_robot_list').triggerHandler('get_select');
    if(ids.length==0){
        alert('Nenhum registro selecionado');
        return;
    };
    var oModal = awModal({
        title:'Captura de IDs',
        html:function(oHtml){
            var r=''+
                '<p><span class="j-count-ids">'+ ids.length +'</span> registro(s) selecionado(s)</p> '+
                '<div class="form-group">'+
                    '<div class="control-div">'+
                        '<textarea class="form-control" rows="7" readonly="readonly">'+ ids.join(',') +'</textarea>'+
                    '</div>'+
                '</div>'+
                '<p><button class="btn btn-primary" id="bt-import-ids-all">Importar todos os IDs</button>';

            oHtml.html(r);
            var field=oHtml.find('textarea').on('click',function(){$(this).select();});

            oHtml.find('#bt-import-ids-all').on('click',function(){
                if(confirm('Importar todos os ids desta lista?\nLimite de 10.000 registros.'))
                    awBtnPostData({
                        url:'{{route('super-admin.app.get',['dev','toolTestReadPdfActions'])}}',
                        data:{f_st:'{{$f_st}}',action:'get_ids_by_qs'},
                        cb:function(r){
                            if(r.success){field.val(r.ids.join(','));oHtml.find('.j-count-ids').text(r.ids.length);}
                        }
                    },this);
            });
        },
    });
};

//initializa
process_result();
</script>
<style>
.col-msg{max-width:300px;}
.div-count{min-width:25px;display:inline-block;}
</style>

@endsection
