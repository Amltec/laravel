@extends('templates.admin.index')

@section('title')
    Painel Principal
@endsection

@section('content-view')
@php
    use App\Http\Controllers\Process\ProcessCadApoliceController as CadApolice;
    use App\ProcessRobot\VarsProcessRobot;
    
    
    
    $configProcessNames = VarsProcessRobot::$configProcessNames;
    $account_id = Request::input('account_id');
    
    
    if(!function_exists('fnc_caixinhaStatus')){
        function fnc_caixinhaStatus($opt){
            $link       = $opt['link']??'';
            $bgcolor    = $opt['bg']??'';
            $long_text  = $opt['text']??'';
            $attr       = $opt['attr']??'';
            $icon       = $opt['icon']??'fa-dot-circle-o';
            $icon_opacity = $opt['icon_opacity']??true;
            $text_color = $opt['text_color']??'';
            
            $str_link = $link ? 'onclick=\'goToUrl(_fGetLink("'. $link .'"));\'' : '';
            
            return '<tr'. ($link?' style="cursor:pointer;"':'') .' '. $attr .' '. $str_link .'>
                    <td style="width:50px;vertical-align:middle;" class="'. $bgcolor .' text-center"><i class="fa fa-dot-circle-o"></i></td>
                    <td class="info-title '. $text_color .'">'. $long_text .'</td>
                    <td class="info-number j-info-box-number" '. $attr .'>...</td>
                  </tr>';
        }
        
        function fnc_tabPainel($bx_id,$bx_title,$account_id,$sufix_link=''){
            echo '<h3 style="margin:8px 0 20px 0;">'.$bx_title.'</h3>';
            
            
            echo '<table class="table table-view1 table-hover no-margin" id="'.$bx_id.'">';
                    echo fnc_caixinhaStatus([
                        'link'=>route('super-admin.app.get',['process_cad_apolice','list','?process_name=cad_apolice&account_id='.$account_id.$sufix_link]) ,
                        'bg'=>'bg-navy-active',
                        'text'=>'Todos',
                        'attr'=>'data-status="total"',
                        'icon_opacity'=>false
                    ]);
                    
                    foreach(CadApolice::$status as $st_value => $st_text){
                            $color = CadApolice::$statusColor[$st_value];
                            $long_text = CadApolice::$status[$st_value];
                            echo fnc_caixinhaStatus([
                                'link'=>route('super-admin.app.get',['process_cad_apolice','list','?process_name=cad_apolice&account_id='. $account_id .'&status='.$st_value.$sufix_link]), 
                                'bg'=>$color['bg'], 
                                'text'=>$long_text,
                                'attr'=>'data-status="'.$st_value.'"',
                                'text_color'=>$color['text'], 
                            ]);
                    }
                    
                     echo fnc_caixinhaStatus([
                        'bg'=>'bg-teal',
                        'text'=>'Tempo médio de processamento',
                        'attr'=>'data-id="avg-time"',
                        'icon'=>'fa-clock-o',
                    ]);
            echo '</table>';
            
        }
    }
    
    
    $accounts_list = \App\Models\Account::where('account_status','a')->pluck('account_name','id')->toArray();
    
    
    //*** filtros ***
    $filter=[
        'account_id'=>_GET('account_id'),
    ];
    //dd($filter);
    echo view('templates.ui.auto_fields',[
        'form'=>[
            'id'=>'form-filter-bar',
            'url_action'=>'#',
            'alert'=>false,
            'class'=>'form-filter-bar-line',
            'data_opt'=>[
                'fields_log'=>false,
            ]
        ],
        'autodata'=>(object)$filter,
        'autocolumns'=>[
            'account_id'=>['label'=>'Conta','class_group'=>'','type'=>'select2','attr'=>'data-allow-clear="true"',
                'list'=>[''=>'']+$accounts_list
            ],
            'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
        ]
    ]);
    
    
    
    
    //********** monta os quadros gerais do painel **************
    //echo '<br>';
    echo '<div class="row">';
        
    //*** cadastro de apólice ***
    echo '
    <div class="col-sm-7">
        <div class="box box-primary">
            <div class="box-header">
                <div class="pull-left"><h3 class="box-title strong">Cadastro de Apólices</h3></div>
                <div class="pull-right" style="padding-right:40px;margin-top:-10px;">
                    <a href="#" id="button-refresh" onClick="fncProcessCount(true);return false;" style="position:absolute;right:0;margin:8px 15px 0 0;"><i class="fa fa-refresh"></i></a>
                    ';
                    echo view('templates.components.radio',[
                        'id'=>'filter_cad_apolice',
                        'name'=>'filter_cad_apolice',
                        'list'=>['all'=>'Todos', 'created'=>'Envios <span class="last_date_label" data-ref="created">Hoje</span>', 'processed'=>'Processados <span class="last_date_label" data-ref="processed">Hoje</span>'],
                        'value'=>'all',
                    ]);
                    echo '
                </div>
            </div>
            <div class="box-body cad_apolice_tab_sty no-pad-top">';
                    echo view('templates.ui.tab',[
                        'data'=>[
                            'st_all'=>['title'=>'Todos', 'attr'=>'data-id="all" data-ref="all"', 'content'=>function() use($account_id){
                                fnc_tabPainel('box-info-status-all','Todos os registros',$account_id);
                            }],
                            'st_created'=>['title'=>'Envios <span class="last_date_label" data-ref="created">Hoje</span>', 'attr'=>'data-ref="created"', 'content'=>function() use($account_id){
                                fnc_tabPainel('box-info-status-created','Últimos envios de <span class="last_date_label_in" data-ref="created">Hoje</span>', $account_id, '&dt=:date');
                            }],
                            'st_processed'=>['title'=>'Processados <span class="last_date_label" data-ref="processed">Hoje</span>', 'attr'=>'data-ref="processed"', 'content'=>function() use($account_id){
                                fnc_tabPainel('box-info-status-processed','Último processamento <span class="last_date_label_in" data-ref="processed">Hoje</span>', $account_id, '&dt=:date');
                            }],

                        ],
                        'id'=>'tab_report_1'
                    ]);
    echo '</div>
        </div>
    </div>';
        

    
    //*** Outros processos ***
            function _fBoxOtherProcess($process_name, $process_prod, $title, $opt=[]){
                $opt = array_merge([
                    'link'=>'',
                    'second_column' =>  null,       //se definido, informar o título da coluna
                    'second_link' =>  '',           //se definido, informar o segundo link da coluna
                ],$opt);
                
                $str_first_link     = $opt['link'] ? 'goToUrl(\''. $opt['link'] .'=:status\');' : '';
                $str_second_link    = $opt['second_link'] ? 'goToUrl(\''. $opt['second_link'] .'=:status\')' : '';
                $str_row_link       = $str_second_link ? '' : $str_first_link;
                
                $r= '<div class="col-sm-5">
                    <div class="box box-primary">
                            <div class="box-header">
                                <h3 class="box-title strong">'. 
                                    $title .
                                    ($opt['second_column']? ' <span style="font-size:0.8em;margin-left:10px;">'. $opt['second_column'] .'</span>' :'') .
                                '</h3>
                                
                            </div>
                            <div class="box-body no-padding">
                                <table class="table table-view2 table-hover" id="table-'.$process_name.'.'.$process_prod.'">
                                <tr class="'. ($str_row_link?'cursor-pointer':'') .'" onclick="'. str_replace(':status','p',$str_row_link) .'">
                                    <td>Pronto para o robô</td>
                                    <td class="col-count text-right" onclick="'. str_replace(':status','p',$str_first_link) .'"><span class="btn btn-default btn-xs" data-name="col1" data-st="p">0</span></td>
                                    '. ($opt['second_column']? '<td class="col-count text-right" onclick="'. str_replace(':status','p',$str_second_link) .'"><span class="btn btn-default btn-xs" data-name="col2" data-st="p">0</span></td>' :'') .'
                                </tr>
                                <tr class="'. ($str_row_link?'cursor-pointer':'') .'" onclick="'. str_replace(':status','a',$str_row_link) .'">
                                    <td>Em andamento</td>
                                    <td class="col-count text-right" onclick="'. str_replace(':status','a',$str_first_link) .'"><span class="btn btn-default btn-xs" data-name="col1" data-st="a">0</span></td>
                                    '. ($opt['second_column']? '<td class="col-count text-right" onclick="'. str_replace(':status','a',$str_second_link) .'"><span class="btn btn-default btn-xs" data-name="col2" data-st="a">0</span></td>' :'') .'
                                </tr>
                                <tr class="'. ($str_row_link?'cursor-pointer':'') .' text-danger" onclick="'. str_replace(':status','e',$str_row_link) .'">
                                    <td>Erros</td>
                                    <td class="col-count text-right strong" onclick="'. str_replace(':status','e',$str_first_link) .'"><span class="btn btn-default btn-xs" data-name="col1" data-st="e">0</span></td>
                                    '. ($opt['second_column']? '<td class="col-count text-right" onclick="'. str_replace(':status','e,c,1',$str_second_link) .'"><span class="btn btn-default btn-xs" data-name="col2" data-st="e">0</span></td>' :'') .'
                                </tr>
                                </table>
                            </div>
                        </div>
                    </div>';
                return $r;
            }
            
            echo _fBoxOtherProcess('seguradora_files', 'down_apo',  'Área de Seguradoras',[
                'second_column'=>'Download / Marcar Concluído',
                'link'=>route('super-admin.app.index',['process_seguradora_files']) .'?status',
                'second_link'=>route('super-admin.app.index',['process_seguradora_files']) .'?status_pr_group'
            ]);
            
            foreach($configProcessNames['seguradora_data']['products'] as $prod_prod => $prod_opt){
                echo _fBoxOtherProcess('seguradora_data', $prod_prod,  $prod_opt['title'],[
                    'link'=> route('super-admin.app.get',['process_seguradora_data','list']).'?process_prod='.$prod_prod.'&status_pr_group'
                ]);
            }
    
    
            
    echo '</div>'; //end .row
    
    
    
    
@endphp

<style>
    #form-filter-bar{margin-bottom:40px;margin-right:-20px;}
    #form-filter-bar .form-group{width:200px;float:left;padding-right:10px;}
    @media screen and (min-width: 780px) {
        #form-filter-bar{position:absolute;right:0;top:50px;}
    }
    
    .cad_apolice_tab_sty .nav-tabs-custom{margin-top:-10px;}
    .cad_apolice_tab_sty .nav-tabs{display:none;}
    .cad_apolice_tab_sty .tab-content{padding:0;}
    
    .table-view1 .info-title{font-size:20px;font-weight:600;}
    .table-view1 .info-number{font-size:23px;font-weight:600;text-align:right;}
    .table-view1 .info-number span{border-radius:3px;display:inline-block;padding:0px 10px;;}
    .table-view1 tr[data-status="e"] .info-number span.is_val, .table-view1 tr[data-status="1"] .info-number span.is_val{background:#dd4b39;color:#fff;}
    .col-count{font-size:1.2em;}
</style>
<script>
var oForm=$('#form-filter-bar');
//aplica a função de barra de filtos
awFilterBar(oForm);


//funções no Tab
var ajax_filter_tab='all';
var oTab=$('#tab_report_1');
$('#form-group-filter_cad_apolice input').on('click',function(){
    var v=this.value;
    ajax_filter_tab=v;
    fncProcessCount();//captura os totais
    oTab.find('.tab-pane').hide().filter('#st_'+v).show();
});


//ajax count
var fncProcessCount_cache={};
var last_date_process='';
//depois de N minuto(s), reseta o cacha na variável para capturar os dados novamente
var countInterval=0;
var minutesInterval=1;
var forceFnc=false;
var interval = setInterval(function(){
    if(countInterval==-1 || countInterval > (100*60*minutesInterval)){
        fncProcessCount_cache['all']=false;
        fncProcessCount_cache['created']=false;
        fncProcessCount_cache['processed']=false;
        countInterval=0;
        execProcessCount(forceFnc);
        forceFnc=false;
    };
    countInterval++;
},10);
function fncProcessCount(force){
    forceFnc=force?true:false;
    if(forceFnc){
        countInterval=-1;
    }else if(!fncProcessCount_cache[ajax_filter_tab]){
        execProcessCount(forceFnc);
        countInterval=0;
    };
};

function execProcessCount(force){
    if(!force && fncProcessCount_cache[ajax_filter_tab])return false;//já está no cache, portanto não prossegue
    if(force)countInterval=0;
    
    var objLink = $('#button-refresh').addClass('fa-spin');
    var container=$('#box-info-status-'+ajax_filter_tab);
    var os=container.find('.j-info-box-number');
    os.html('<small class="nostrong"><i class="fa fa-circle-o-notch fa-spin"></i></small>');//carregando nos números
    
    awAjax({
        url: '{{route("super-admin.app.get",["resume_data","home_super_admin"])}}',
        data: {force:(force?'s':'n'),account_id:'{{$account_id}}',filter_date:ajax_filter_tab},
        type : 'GET',
        dataType:'json',
        processData:true,
        success: function(j){
            var r=j.cad_apolice;
            fncProcessCount_cache[ajax_filter_tab]=true;
            
            if(ajax_filter_tab=='created'){
                last_date_process = r.max_created.date;
            }else if(ajax_filter_tab=='processed'){
                last_date_process = r.max_updated.date;
            }else{
                last_date_process = '';
            };
            
            //console.log(r)
            if(r.max_created){
                //atualiza as datas nas abas
                oTab.find('.last_date_label[data-ref=created]:eq(0)').text(r.max_created.label);
                oTab.find('.last_date_label[data-ref=processed]:eq(0)').text(r.max_updated.label);
                oTab.find('.last_date_label_in[data-ref=created]:eq(0)').text(r.max_created.label2);
                oTab.find('.last_date_label_in[data-ref=processed]:eq(0)').text(r.max_updated.label2);
            };
            
            //atualiza os totais
            var i,x,n,o;
            for(i in r.count_status){
                n=r.count_status[i];
                os.filter('[data-status='+i+']').html('<span class="'+ (n>0?'is_val':'') +'">'+n+'</span>')
                        .next().html('registro'+ (n>1?'s':'') +'</small>');
            }
            os.filter('[data-status=total]').html('<span>'+r.count_total+'</span>')
                .next().html('registro'+ (r.count_total>1?'s':'') +'</small>');
            os.filter('[data-id=avg-time]').html('<span>'+r.time_avg+'</span>').next().html('');
                
            objLink.removeClass('fa-spin');
            
            
            //atualiza os demais blocos de serviços
            var _fUpdTable1=function(process_name,table,st,arr,col_name){
                if(table.length==0)return;
                table.find('[data-name='+col_name+'][data-st=a]').text(_fUpdGrStatus(st,arr.gr_status_a,'a'));
                table.find('[data-name='+col_name+'][data-st=p]').text(_fUpdGrStatus(st,arr.gr_status_p,'p'));
                table.find('[data-name='+col_name+'][data-st=f]').text(_fUpdGrStatus(st,arr.gr_status_f,'f'));
                
                var o=table.find('[data-name='+col_name+'][data-st=e]').removeClass('btn-danger btn-default');
                var v=_fUpdGrStatus(st,arr.gr_status_e,'e');
                o.text(v);if(v>0){o.addClass('btn-danger');}else{o.addClass('btn-default');}
            };
                var _fUpdGrStatus=function(list_status,gr_status,def_status){//soma os valores de grupos de status - gr_status sintaxe:[gr_status_a|p|e|f]
                    var x,i,n=0;
                    if(gr_status){
                        for(i in gr_status){ n+=list_status[ gr_status[i] ]??0; };
                    }else{
                        n=list_status[def_status]??0;
                    };
                    return n;
                };
            
            //seta os dados nas tabelas
                if(j['seguradora_files.down_apo']){
                    i='seguradora_files.down_apo';
                    _fUpdTable1(i,$('[id="table-'+i+'"]'),j[i].count_status, j[i],'col1');
                    x='seguradora_files.mark_done';
                    _fUpdTable1(i,$('[id="table-'+i+'"]'),j[x].count_status, j[x],'col2');

                    i='seguradora_data.apolice_check';
                    _fUpdTable1(i,$('[id="table-'+i+'"]'),j[i].count_status, j[i],'col1');

                    i='seguradora_data.boleto_seg';
                    _fUpdTable1(i,$('[id="table-'+i+'"]'),j[i].count_status, j[i],'col1');

                    i='seguradora_data.boleto_quiver';
                    _fUpdTable1(i,$('[id="table-'+i+'"]'),j[i].count_status, j[i],'col1');
                }
        },
        error: function(xhr){
            console.log('error',xhr.responseText)
            objLink.removeClass('fa-spin');
        }
    });
};
execProcessCount();


function _fGetLink(link){
    return link.replace(':date',last_date_process);
}

</script>

@endsection