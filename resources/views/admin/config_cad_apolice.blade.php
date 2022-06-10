@extends('templates.admin.index')

@section('title')
Configuração Cadastro de Apólices
@endsection


@section('content-view')
@php
/* Variáveis esperadas
    $model
*/


//*** Instruções ***
/*function fTab_info($vars){
    
}*/


$products_list = \App\ProcessRobot\VarsProcessRobot::$configProcessNames['cad_apolice']['products'];
$config_cad_apolice = Config::accountService()::getCadApoliceConfig($account);

$account_config = Config::accountConfig('config');
$products_active = array_filter(array_get($account_config,'cad_apolice.products_active')??[]);//array de nomes de produtos ativos, ex: automovel, residencial



//*** Gerais ***
function fTab_gerais($vars){
    extract($vars);
    
    echo '<h4>Leitura da Apólice</h4>';
    
    $data = (object)array_only($config_cad_apolice,['venc_1a_parc_cartao','venc_1a_parc_debito','venc_1a_parc_boleto','venc_1a_parc_1boleto_debito','venc_1a_parc_1boleto_cartao']);
    echo '<p>Quando não houver datas das parcelas do prêmio informadas na apólice, considerar a data da primeira parcela:</p>';
    echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'autocolumns'=>[
                'venc_1a_parc_cartao'=>['type'=>'radio','label'=>'Cartão',
                    'list'=>['vigencia'=>'Data da vigência','emissao'=>'Data da emissão','30d_vigencia'=>'30 dias após a vigência','30d_emissao'=>'30 dias após a emissão'],
                ],
                'venc_1a_parc_debito'=>['type'=>'radio','label'=>'Débito',
                    'list'=>['vigencia'=>'Data da vigência','emissao'=>'Data da emissão','30d_vigencia'=>'30 dias após a vigência','30d_emissao'=>'30 dias após a emissão'],
                ],
                'venc_1a_parc_boleto'=>['type'=>'radio','label'=>'Boleto',
                    'list'=>['vigencia'=>'Data da vigência','emissao'=>'Data da emissão','30d_vigencia'=>'30 dias após a vigência','30d_emissao'=>'30 dias após a emissão'],
                ],
                'venc_1a_parc_1boleto_debito'=>['type'=>'radio','label'=>'1ª Boleto + dédito',
                    'list'=>['vigencia'=>'Data da vigência','emissao'=>'Data da emissão','30d_vigencia'=>'30 dias após a vigência','30d_emissao'=>'30 dias após a emissão'],
                ],
                'venc_1a_parc_1boleto_cartao'=>['type'=>'radio','label'=>'1ª Boleto + cartão',
                    'list'=>['vigencia'=>'Data da vigência','emissao'=>'Data da emissão','30d_vigencia'=>'30 dias após a vigência','30d_emissao'=>'30 dias após a emissão'],
                ],
            ],
            'autodata'=>$data,
        ]);
    
    
    $data = (object)array_only($config_cad_apolice,['venc_ua_parc']);
    echo '<br><p>Quando a última parcela estiver como paga na apólice, considerar:</p>';
    echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'autocolumns'=>[
                'venc_ua_parc'=>['type'=>'radio','label'=>'Todas as F. Pagamento',
                    'list'=>['1parc'=>'Vencimento da primeira', '30d_u'=>'30 dias após a penúltima parcela'],
                ],
            ],
            'autodata'=>$data,
        ]);
    echo '<hr><br>';
    
        
    
    
    echo '<h4>Nomes das Formas de Pagamento</h4>';
    echo '<p>Digite exatamente os nomes conforme consta na configuração do Quiver</p>';
    
    $data = (object)[
        'names_fpgto_carne'         => array_get($config_cad_apolice,'names_fpgto.carne'),
        'names_fpgto_boleto'        => array_get($config_cad_apolice,'names_fpgto.boleto'),
        'names_fpgto_debito'        => array_get($config_cad_apolice,'names_fpgto.debito'),
        'names_fpgto_cartao'        => array_get($config_cad_apolice,'names_fpgto.cartao'),
        'names_fpgto_1boleto_debito'=> array_get($config_cad_apolice,'names_fpgto.1boleto_debito'),
        'names_fpgto_1boleto_cartao'=> array_get($config_cad_apolice,'names_fpgto.1boleto_cartao')
    ];
    
    echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'autocolumns'=>[
                'names_fpgto_carne'         =>['label'=>'Carnê'],
                'names_fpgto_boleto'        =>['label'=>'Boleto'],
                'names_fpgto_debito'        =>['label'=>'Débito'],
                'names_fpgto_cartao'        =>['label'=>'Cartão'],
                'names_fpgto_1boleto_debito'=>['label'=>'1ª Boleto + dédito'],
                'names_fpgto_1boleto_cartao'=>['label'=>'1ª Boleto + cartão'],
            ],
            'autodata'=>$data,
        ]);
    echo '<hr><br>';
    
    
    
    echo '<h4>Nomes das Imagens dos Anexos</h4>';
    echo '<p>Digite exatamente os nomes conforme consta na configuração do Quiver</p>';
    
    $data = (object)[
        'names_anexo_apolice'   => array_get($config_cad_apolice,'names_anexo.apolice'),
        //'names_anexo_historico' => array_get($config_cad_apolice,'names_anexo.historico'),
        'names_anexo_boleto'    => array_get($config_cad_apolice,'names_anexo.boleto'),
    ];
    
    echo view('templates.ui.auto_fields',[
            'layout_type'=>'horizontal',
            'autocolumns'=>[
                'names_anexo_apolice' =>['label'=>'Apólice'],
                //'names_anexo_historico'=>['label'=>'Histórico'],
                'names_anexo_boleto'=>['label'=>'Boleto'],
            ],
            'autodata'=>$data,
        ]);

}




//*** Padrão de número de apólice ***
function fTab_numQuiver($vars){
    extract($vars);
    
    $modelInsurer = \App\Models\Insurer::where('insurer_status','a')->get();
    //campos: label - rótulo do campo, desc - descrição dentro do formulário, desc2 - descrição por extenso (para visualização da regra na tela de informações), field - tipo do campo no formulário
    $fields = [
        'num_origem' =>[
            'label'         =>'Número completo',
            'desc'          =>'Irá capturar o número conforme original',
            'desc2'         =>'captura conforme original',
            'field'         => ['type'=>'select','list'=>[''=>'','s'=>'Sim','n'=>'Não']],
        ],
        'not_dot_traits' =>[
            'label'         =>'Sem formatação',
            'desc'          =>'Remove os pontos e traços do número',
            'desc2'         =>'remove os pontos e traços',
            'field'         => ['type'=>'select','list'=>[''=>'','s'=>'Sim','n'=>'Não']],
        ],
        'len' =>[
            'label'         =>'Ignorar caracteres à esquerda',
            'desc'          =>'Desconsidera os da esquerda para a direita',
            'desc2'         =>'desconsidera os {N} da esquerda para a direita',
            'field'         => ['type'=>'select','list'=>[''=>'','n'=>'Não','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19']],
        ],
        'len_r' =>[
            'label'         =>'Apenas caracteres à direita',
            'desc'          =>'Considera os últimos caracteres à direita',
            'desc2'         =>'considera os {N} últimos caracteres',
            'field'         => ['type'=>'select','list'=>[''=>'','n'=>'Não','1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19']],
        ],
        'last_dot' =>[
            'label'         =>'Últimos caracteres até o ponto',
            'desc'          =>'Capturar os últimos caracteres até o primeiro ponto',
            'desc2'         =>'capturar os últimos caracteres até o primeiro ponto',
            'field'         => ['type'=>'select','list'=>[''=>'','s'=>'Sim','n'=>'Não']],
        ],
        'between_dots' =>[
            'label'         =>'Entre pontos',
            'desc'          =>'Capturar o número entre o penúltimo e o último ponto',
            'desc2'         =>'captura o entre o penúltimo e o último ponto',
            'field'         => ['type'=>'select','list'=>[''=>'','s'=>'Sim','n'=>'Não']],
        ],
        'not_zero_left' =>[
            'label'         =>'Retirar zeros à esquerda',
            'desc'          =>'Remove todos os zeros à esquerda do número',
            'desc2'         =>'remove todos os zeros à esquerda',
            'field'         => ['type'=>'select','list'=>[''=>'','s'=>'Sim','n'=>'Não']],
        ],
    ];
    
    
    //captura os valores padrões (Sintaxe: [insurer_id => ['ex_num'=>, 'num_origem'=>..., ...], ... ])
        $data_def = $config_cad_apolice['num_quiver'];
    
    echo '<a href="#" onclick="$(\'#table-num-quiver\').fadeIn();$(\'#table-num-quiver-info\').hide();$(this).hide();return false;" class="btn btn-sm btn-primary pull-right" style="margin:-10px 0 0 0;" >Editar</a>';
    
    
    
    //*** tabela de visualização da regra ***
    echo '<div class="hiddenx" id="table-num-quiver-info">';
        
                echo '<h4>Padrão de número de apólices para o Quiver</h4>';
                echo '<table class="table table-num-quiver table-hover">
                    <tr class="v-align-m">
                        <th class="col-insurer">Seguradora</th>
                        <th class="col-ex-num">Ex Número de Apólice</th>
                        <th class="col-ex-result">Padrão do Quiver</th>
                        <th class="col-ex-info">Descrição</th>
                    </tr>
                    ';
                    foreach($modelInsurer as $rs){
                        //valor padrão direto de cada classe
                        $class='\\App\\ProcessRobot\\cad_apolice\\Classes\\InsurersClass\\'. $rs->insurer_basename .'Class';
                        try{
                            $class = App::make($class);
                            if(method_exists($class,'numQuiverConfig')){
                                $def_numQuiver = $class->numQuiverConfig();
                            }else{
                                $def_numQuiver = [];
                            }
                        }catch(\Exception $e){
                            $def_numQuiver = [];
                        }

                        //valor da configuração salvo
                        $d = array_merge($def_numQuiver,FormatUtility::array_ignore_null($data_def[$rs->id]??[]));
                        //if($rs->insurer_basename=='hdi')dd($rs->toArray(), $data_def[$rs->id], $def_numQuiver, $d);


                        echo '<tr data-insurer-id="'. $rs->id .'">';
                            echo '<td class="col-insurer">'. $rs->insurer_alias .'</td>';
                            echo '<td class="col-ex-num">'. array_get($d,'ex_num','-') .'</td>';
                            echo '<td class="col-ex-result"><div data-id="field-result_'.$rs->id.'" >-</div></td>';

                            $r=[];
                            foreach($fields as $f_name => $opt){
                                $v = array_get($d,$f_name);
                                if(is_bool($v)){
                                    $v=$v ? 's' : 'n';
                                }
                                if($v=='s'){
                                    $r[] = str_replace('{N}',$v,$opt['desc2']);
                                }
                            }
                            $r = join(', ',$r);
                            echo '<td class="col-ex-info lett">'. ($r ? $r : '-') .'</td>';

                        echo '</tr>';
                    }
                echo '</table>';
                
    echo '</div>'; //end #table-num-quiver-info
    //if(Auth::user()->user_level=='dev')dd('3',$data_def);    
    
    
    
    
    
    
    //*** tabela com formulário para edição ***
    echo '<div class="Xhiddenx" id="table-num-quiver">';
        echo '<h4>Padrão de número de apólices para o Quiver</h4>';
        
        //conteúdo do formulário para o accordtion
        function acc_frm($process_prod,$process_title,$fields,$modelInsurer,$config_cad_apolice,$data_def){
                    $data = $config_cad_apolice['num_quiver_'.$process_prod] ?? $data_def;
                    
                    if($process_prod!='default'){
                        echo '<label><input type="checkbox" class="j-check-default" name="'.$process_prod.'_def" '. (($data['def']??null)?'checked':'') .' value="s"><span class="checkmark"></span> Manter o padrão</label>';
                    }
                    
                    echo '<table class="table table-num-quiver table-hover" id="'.$process_prod.'_table-num-quiver">
                        <tr class="v-align-m">
                            <th class="col-insurer">Seguradora</th>
                            <th class="col-ex-num">Ex Número de Apólice</th>
                            ';
                        foreach($fields as $f => $opt){
                            echo '<th class="col-rule" data-toggle="tooltip" data-placement="top" title="'. $opt['desc'] .'">'. $opt['label'] .'</th>';
                        }
                        echo '<th class="col-ex-result">Ex Padrão do Quiver</th>';
                    echo '</tr>';
                    foreach($modelInsurer as $rs){
                        //valor padrão direto de cada classe
                        $class='\\App\\ProcessRobot\\cad_apolice\\Classes\\InsurersClass\\'. $rs->insurer_basename .'Class';
                        try{
                            $class = App::make($class);
                            if(method_exists($class,'numQuiverConfig')){
                                $def_numQuiver = $class->numQuiverConfig();
                            }else{
                                $def_numQuiver = [];
                            }
                        }catch (\Exception $e){
                            $def_numQuiver = [];
                        }
                        //dd($def_numQuiver);

                        //valor da configuração salvo
                        $d = array_merge($def_numQuiver,FormatUtility::array_ignore_null($data[$rs->id]??[]));
                        //if($rs->insurer_basename=='hdi')dd($rs->toArray(), $data[$rs->id], $def_numQuiver, $d);

                        echo '<tr data-insurer-id="'. $rs->id .'">';
                            echo '<td class="col-insurer">'. $rs->insurer_alias .'</td>';
                            echo '<td class="col-ex-num"><input type="text" maxlength="20" name="'.$process_prod.'_field-ex_num_'.$rs->id.'" value="'. array_get($d,'ex_num') .'" class="form-control" /></td>';

                            foreach($fields as $f_name => $opt){
                                $v = array_get($d,$f_name);
                                if(is_bool($v)){
                                    $v=$v ? 's' : 'n';
                                }

                                echo '<td class="col-rule" title="'. $opt['label'] .'. Valor padrão: '. (($def_numQuiver[$f_name]??false)?'Sim':'Não') .' ">'.
                                        Form::select($process_prod.'_'. $f_name.'_'.$rs->id, $opt['field']['list'], $v, ['class'=>'form-control']).
                                    '</td>';
                            }

                            echo '<td class="col-ex-result v-align-m"><i class="fa fa-spin fa-circle-o-notch hiddenx j-loading" style="position:absolute;font-size:0.8em;margin:5px 0 0 -25px;"></i><div data-id="'.$process_prod.'_field-result_'.$rs->id.'"></div></td>';

                        echo '</tr>';
                    }
                    echo '</table>';
        }
        
        
        $acc_data=[];
        $new_list = ['default'=>'Padrão para todos os ramos'] + FormatUtility::pluckKey($products_list,'title');
        foreach($new_list as $process_prod => $process_title ){
            $acc_data['acc_'.$process_prod] = ['title'=>$process_title,'content'=>function() use($process_prod,$process_title,$fields,$modelInsurer,$config_cad_apolice,$data_def){
                                                    return acc_frm($process_prod,$process_title,$fields,$modelInsurer,$config_cad_apolice,$data_def);
                                                }];
        }//end foreach
        
        echo view('templates.ui.accordion',['data'=>$acc_data,'show_arrow'=>true]);
        
    echo '</div>'; //end #table-num-quiver
    
}





//*** Configuração dos produtos para busca de apólice ***
function fTab_searchProducts($vars){
    extract($vars);
    
    echo '<h4>Nomes dos produtos para a busca de apólices</h4>';
    echo '<p> Digite um valor por linha</p>';
    
    
    foreach($products_list as $prod_name => $prod_opt){
        echo view('templates.ui.auto_fields',[
                'class'=>'prod-'. $prod_name .'-block',
                'autocolumns'=>[
                    'search_products_' . $prod_name => ['type'=>'textarea','rows'=>5,'auto_height'=>true,'label'=>$prod_opt['title']]
                ],
                'autodata'=> (object)[
                    'search_products_' . $prod_name => str_replace('|',chr(10),array_get($config_cad_apolice,'search_products.'.$prod_name))
                ]
            ]);
    }
}





//*** Configuração dos ramos para a área de seguradoras ***
function fTab_downApoRamo($vars){
    extract($vars);
    
    echo '<h4>Nomes dos ramos para o filtro de download de apólices pela área de seguradoras</h4>';
    echo '<p>Digite um valor por linha</p>';
    
    
    foreach($products_list as $prod_name => $prod_opt){
        echo view('templates.ui.auto_fields',[
                'class'=>'prod-'. $prod_name .'-block',
                'autocolumns'=>[
                    'down_apo_ramo_' . $prod_name =>['type'=>'textarea','rows'=>5,'auto_height'=>true,'label'=>$prod_opt['title']]
                ],
                'autodata'=>(object)[
                    'down_apo_ramo_' . $prod_name => str_replace('|',chr(10),array_get($config_cad_apolice,'down_apo_ramo.'.$prod_name))
                ]
            ]);
    }
}






$get_defined_vars = get_defined_vars();
echo view('templates.ui.form',[
    'url'=>route('admin.app.get',['config_cad_apolice','save']),
    'bt_back'=>false,
    'data_opt'=>[
        'focus'=>true,
    ],
    'content'=>function() use($get_defined_vars){
        echo view('templates.ui.tab',[
            'id'=>'tab_main',
            'tab_active_js'=>true,
            'data'=>[
                //'info'=>['title'=>'Instruções','content'=>['fTab_info',$get_defined_vars] ],
                'general'=>['title'=>'Gerais','content'=>['fTab_gerais',$get_defined_vars] ],
                'num_quiver'=>['title'=>'Número de Apólices','content'=>['fTab_numQuiver',$get_defined_vars] ],
                'products'=>['title'=>'Busca de Apólices','content'=>['fTab_searchProducts',$get_defined_vars] ],
                'ramos'=>['title'=>'Área de Seguradoras','content'=>['fTab_downApoRamo',$get_defined_vars] ],
            ],
        ]);
    }
]);


@endphp

<style>
.col-insurer{width:130px;}
.col-ex-num{width:200px;}
.col-ex-result{padding-left:30px !important;}
.col-rule{width:100px;text-align:center;}
.col-ex-info::first-letter{text-transform:uppercase;}

@php
    //Obs: aqui são listados as classe de todos os produtos não visíveis, pois todos precisam constar sempre no DOM
    foreach($products_list as $prod_name => $prod_opt){
        if(!empty($products_active) && !in_array($prod_name,$products_active)){
            echo '.prod-'. $prod_name .'-block,#headingacc_'. $prod_name .'{display:none;}';
        }
    }
@endphp

</style>
<script>
(function(){
    $('[data-toggle="tooltip"]').tooltip({container:'body'});
    
    //padrão de número de apólices
    (function(){
        var base=$('#table-num-quiver');
        
        //ao clicar no checkbox: Manter o Padrão
        var oChecks=base.find('.j-check-default').on('click click-init',function(){
            var o=$(this).parent().nextAll('.table-num-quiver:eq(0)');
            var t=$(this).prop('checked');
            if(!t){o.show();}else{o.hide();}
        });
        
        var f=function(){
            //inicializa o click no checkbox
            oChecks.trigger('click-init');
            
            //exibe o loading
            var oL = $(this).closest('tr').find('.j-loading').show();
            
            //processamento dos exemplos
            awAjax({
                url: "{{ route('admin.app.get',['config_cad_apolice','process-example']) }}",
                data: os.serialize(),
                processData: true,
                success: function(r){
                    oL.fadeOut();
                    for(let prod in r){
                        for(let insurer_id in r[prod]){
                            $('[data-id='+prod+'_field-result_'+insurer_id+']').text(r[prod][insurer_id] ?? '-');
                        }
                    }
                },
                error:function (xhr, ajaxOptions, thrownError){
                    console.log('err','Erro interno de servidor');
                }
            });
        };
        var os=base.find(':input').on('change',f);
        f();
    }());
    
    
    //tab auto height textarea
    $('#tab_main').on('click','.nav-tabs li',function(){
        var li=$(this)
        setTimeout(function(){ 
            $('#'+li.attr('data-id')).find('textarea').trigger('input');
        },10);
    });
    
}());
</script>
@endsection