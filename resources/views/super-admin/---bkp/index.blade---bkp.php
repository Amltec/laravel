@extends('templates.admin.index')

@section('title')
    Painel Principal
@endsection

@section('content-view')
@php
    use App\Http\Controllers\Process\ProcessCadApoliceController as CadApolice;
    
    /*
    $r = \App\Utilities\TextUtility::getSearchTextInColumns(
            '8 77 aurelio sonia alisson hanna 97 987 187 we col1 col2 col3 val1 val2 val3 z1 1,99 z3 12,34 abc 56,78 90,12 hg1 hg2 hg3 qwqw ewq ',
            'col1 col2 col3',
            ['field1','field2'=>'number_formated','field3'],
            [
                //'sanitize'=>true,
                //'side'=>'left',
                'limit'=>false,
                //'only_required'=>true
            ]
        );
    dd($r);
    */

    
    $account_id = Request::input('account_id');

    if(!function_exists('fnc_indexBoxXf01')){
        function fnc_indexBoxXf01($opt){
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
                            <small class="nostrong"><i class="fa fa-circle-o-notch fa-spin"></i></small>
                        </span>
                      </div>
                    </div>
                  </div>';
        }
        
        function fnc_indexBoxXf02($bx_id,$bx_title,$account_id,$sufix_link=''){
            echo '<h4 style="margin-bottom:20px;">'.$bx_title.'</h4>';
            
            echo '<div class="row" id="'.$bx_id.'">';
        
            echo fnc_indexBoxXf01([
                'link'=>route('super-admin.app.get',['process_cad_apolice','list','?process_name=cad_apolice&account_id='.$account_id.$sufix_link]) ,
                'bg'=>'bg-navy-active',
                'text'=>'Todos',
                'attr'=>'data-status="all"',
                'icon_opacity'=>false
            ]);

            foreach(CadApolice::$status as $st_value => $st_text){
                    $color = CadApolice::$statusColor[$st_value];
                    $long_text = CadApolice::$status[$st_value];
                    echo fnc_indexBoxXf01([
                        'link'=>route('super-admin.app.get',['process_cad_apolice','list','?process_name=cad_apolice&account_id='. $account_id .'&status='.$st_value.$sufix_link]), 
                        'bg'=>$color['bg'], 
                        'text'=>$long_text,
                        'attr'=>'data-status="'.$st_value.'"',
                    ]);
            }

            echo fnc_indexBoxXf01([
                'bg'=>'bg-teal',
                'text'=>'Tempo médio de processamento',
                'attr'=>'data-id="avg-time"',
                'icon'=>'fa-clock-o',
            ]);

            echo '</div>';
        }
    }
    
    
    //fnc_indexBoxXf02('box-info-status-all','Todos os registros',$account_id);
    //fnc_indexBoxXf02('box-info-status-today','Registros de <span class="last_date_process">Hoje</span>',$account_id);
    
    
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
    
    
    
    
    
    $last_date_process = (new \App\ProcessRobot\ResumeProcessRobot)->getLastDateProcess($account_id);
    //if(Gate::allows('dev'))dd($last_date_process);
    $last_date_process_label = ($last_date_process==date("Y-m-d") ? 'Hoje' : \App\Utilities\FormatUtility::dateFormat($last_date_process,'date'));
    
    echo '<a href="#" onClick="fncProcessCount(true,this);return false;" class="pull-right" style="margin:10px 10px 0 0;"><i class="fa fa-refresh"></i></a>';
    echo view('templates.ui.tab',[
        'data'=>[
            'st_all'=>['title'=>'Todos', 'attr'=>'data-id="all"', 'content'=>function() use($account_id){
                fnc_indexBoxXf02('box-info-status-all','Todos os registros',$account_id);
            }],
            'st_today'=>['title'=>'<span class="last_date_process">'.$last_date_process_label.'</span>', 'attr'=>'data-id="today"', 'content'=>function() use($last_date_process,$last_date_process_label,$account_id){
                fnc_indexBoxXf02('box-info-status-today','Registros de <span class="last_date_process">'.$last_date_process_label.'</span>', $account_id, '&dt='.$last_date_process);
            }],
        ],
        'id'=>'tab_report_1'
    ])

    
    
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


    
function fncProcessCount(force,objLink){
    var minutes=1;
    var oLabelsLastDate;
    if(objLink)$(objLink).find('.fa').addClass('fa-spin');
    awAjax({
        url: '{{route("super-admin.app.get",["process_cad_apolice","countProcess"])}}',
        data: {
            force:(force?'s':''), 
            last_date_process:'{{$last_date_process}}',
            account_id:'{{_GET("account_id")}}',
        },
        type : 'GET',
        dataType:'json',
        processData:true,
        success: function(r){
            var _fx1=function(d,container){
                if(force)container.css({opacity:0}).animate({opacity:1},'fast');
                var n,os=container.fio-box-numnd('.j-info-box-number');
                for(var i in d.status){
                    n=d.status[i];
                    os.filter('[data-status='+i+']').html(n)
                        .next().html('registro'+ (n>1?'s':'') +'</small>');
                };
                os.filter('[data-status=all]').html(d.count)
                    .next().html('registro'+ (n>1?'s':'') +'</small>');
                os.filter('[data-id=avg-time]').html(d.avg_duration_label).next().html('');
            };
            
            _fx1(r.all,$('#box-info-status-all'));
            _fx1(r.today,$('#box-info-status-today'));
            
            if(!oLabelsLastDate)oLabelsLastDate=$('.last_date_process');
            oLabelsLastDate.text(r.last_date_process_label);
            
            if(!force)setTimeout(fncProcessCount,1000*60*minutes);
            if(objLink)$(objLink).find('.fa').removeClass('fa-spin');
        },
        error: function(xhr){
            console.log('error',xhr.responseText)
            if(!force)setTimeout(fncProcessCount,1000*60*minutes);
            if(objLink)$(objLink).find('.fa').removeClass('fa-spin');
        }
    });
};
fncProcessCount();
</script>

@endsection