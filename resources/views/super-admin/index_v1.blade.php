@extends('templates.admin.index')

@section('title')
    Painel Principal
@endsection

@section('content-view')
@php
/*
    Versão antiga do painel do superadmin.
    Versão 1.0.
    Foi substituído em 19/02/2021

*/
    use App\Http\Controllers\Process\ProcessCadApoliceController as CadApolice;
    $account_id = Request::input('account_id');
    
    
    if(!function_exists('fnc_caixinhaStatus')){
        function fnc_caixinhaStatus($opt){
            $link       = $opt['link']??'';
            $bgcolor    = $opt['bg']??'';
            $long_text  = $opt['text']??'';
            $attr       = $opt['attr']??'';
            $icon       = $opt['icon']??'fa-dot-circle-o';
            $icon_opacity = $opt['icon_opacity']??true;
            
            $str_link = $link ? 'onclick=\'goToUrl("'.$link.'");\'' : '';
            
            return '<div'. ($link?' style="cursor:pointer;"':'') .' class="col-xs-12 col-sm-4" '. $attr .' '. $str_link .'>
                    <div class="info-box">
                      <span class="info-box-icon '. $bgcolor .'" style="'. ($icon_opacity?'opacity:0.7;':'') .'"><i style="font-size:0.9em;" class="fa '.$icon.'"></i></span>

                      <div class="info-box-content">
                        <span class="info-box-text">'. $long_text .'</span>
                        <span class="info-box-number" >
                            <span style="font-size:1.5em;" class="j-info-box-number" '. $attr .'></span> 
                        </span>
                      </div>
                    </div>
                  </div>';
        }
        
        function fnc_tabPainel($bx_id,$bx_title,$account_id,$sufix_link=''){
            echo '<h4 style="margin-bottom:20px;">'.$bx_title.'</h4>';
            
            echo '<div class="row" id="'.$bx_id.'">';
        
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
                    ]);
            }

            echo fnc_caixinhaStatus([
                'bg'=>'bg-teal',
                'text'=>'Tempo médio de processamento',
                'attr'=>'data-id="avg-time"',
                'icon'=>'fa-clock-o',
            ]);

            echo '</div>';
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
        ],
        'autodata'=>(object)$filter,
        'autocolumns'=>[
            'account_id'=>['label'=>'Conta','class_group'=>'','type'=>'select2','attr'=>'data-allow-clear="true"',
                'list'=>[''=>'']+$accounts_list
            ],
            'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
        ]
    ]);
    
    
    
    echo '<a href="#" id="button-refresh" onClick="fncProcessCount(true);return false;" class="pull-right" style="margin:10px 10px 0 0;"><i class="fa fa-refresh"></i></a>';
    echo view('templates.ui.tab',[
        'data'=>[
            'st_all'=>['title'=>'Todos', 'attr'=>'data-id="all" data-ref="all"', 'content'=>function() use($account_id){
                fnc_tabPainel('box-info-status-all','Todos os registros',$account_id);
            }],
            'st_created'=>['title'=>'Envios <span class="last_date_label" data-ref="created">Hoje</span>', 'attr'=>'data-ref="created"', 'content'=>function() use($account_id){
                fnc_tabPainel('box-info-status-created','Últimos envios de apólices <span class="last_date_label_in" data-ref="created">Hoje</span>', $account_id, '&dt=ZZZ');
            }],
            'st_processed'=>['title'=>'Processados <span class="last_date_label" data-ref="processed">Hoje</span>', 'attr'=>'data-ref="processed"', 'content'=>function() use($account_id){
                fnc_tabPainel('box-info-status-processed','Último processamento <span class="last_date_label_in" data-ref="processed">Hoje</span>', $account_id, '&dt=ZZZ');
            }],
            
        ],
        'id'=>'tab_report_1'
    ]);

    
    
@endphp

<style>
    #form-filter-bar{margin-bottom:40px;margin-right:-20px;}
    #form-filter-bar .form-group{width:200px;float:left;padding-right:10px;}
    @media screen and (min-width: 780px) {
        #form-filter-bar{position:absolute;right:0;top:50px;}
    }
    
    .nav-tabs-custom{background:none !important;}
    .info-box{box-shadow:0px 2px 4px rgba(0,0,0,0.2);}
</style>
<script>
var oForm=$('#form-filter-bar');
//aplica a função de barra de filtos
awFilterBar(oForm);


//funções no Tab
var ajax_filter_tab='all';
var oTab=$('#tab_report_1');
oTab.on('click','li',function(){
    ajax_filter_tab=$(this).attr('data-ref');
    fncProcessCount();//captura os totais
});


//ajax count
var fncProcessCount_cache={};
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
        success: function(r){
            fncProcessCount_cache[ajax_filter_tab]=true;
            //console.log(r)
            if(r.max_created){
                //atualiza as datas nas abas
                oTab.find('.last_date_label[data-ref=created]:eq(0)').text(r.max_created.label);
                oTab.find('.last_date_label[data-ref=processed]:eq(0)').text(r.max_updated.label);
                oTab.find('.last_date_label_in[data-ref=created]:eq(0)').text(r.max_created.label2);
                oTab.find('.last_date_label_in[data-ref=processed]:eq(0)').text(r.max_updated.label2);
            };
            
            //atualiza os totais
            var i,n;
            for(i in r.count_status){
                n=r.count_status[i];
                os.filter('[data-status='+i+']').html(n)
                        .next().html('registro'+ (n>1?'s':'') +'</small>');
            }
            os.filter('[data-status=total]').html(r.count_total)
                .next().html('registro'+ (r.count_total>1?'s':'') +'</small>');
            os.filter('[data-id=avg-time]').html(r.time_avg).next().html('');
                
            objLink.removeClass('fa-spin');
        },
        error: function(xhr){
            console.log('error',xhr.responseText)
            objLink.removeClass('fa-spin');
        }
    });
};
execProcessCount();

</script>

@endsection