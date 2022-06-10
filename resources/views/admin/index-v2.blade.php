@extends('templates.admin.index')

@section('title')
    Processos realizados
@endsection

@section('content-view')
@php
    use App\Http\Controllers\Process\ProcessCadApoliceController as CadApolice;
    use App\ProcessRobot\cad_apolice\Classes\Vars\FilterVar;
        
    $baselink = route('admin.app.get',['process_cad_apolice','list']);
    
    //relação de erros de operador
    $list_err_user=FilterVar::$group_err;
        //retira as opções indesejadas
        unset($list_err_user['quid03'], $list_err_user['quil..']);
    
    
    //relação de finalizados com suas respectivas alterações no quiver
    //precisa ser um dos valores registrado em \App\ProcessRobot\ResumeProcessRobot->calcPrSegCtrl()
    $list_ok_ctrl=[
        'dados_ctrl_vigencia' => 'Vigência',
        'dados_ctrl_premio' => 'Prêmio',
        'automovel_ctrl_classe' => 'Classe Bônus',
    ];
    
    

    if(!function_exists('fnc_caixinhaStatus')){
        function fnc_caixinhaStatus($baselink,$i,$sx,$sopt){
            $text_clr = array_get(CadApolice::$statusColor,$sopt['st_ref'].'.text','text-navy');
            $link = $baselink .'?'. trim(($sx=='all'?'':'&status='.$sx) .'&dt=','&');
            
            echo '<tr onclick="goToUrl(\''. $link .'\');" class="cursor-pointer">
                <td class="col-num text-center '. array_get(CadApolice::$statusColor,$sopt['st_ref'].'.bg','bg-navy') .'">'. (true?$i:'<i class="fa fa-dot-circle-o"></i>') .'</td>
                <td class="col-info">
                    <span class="info-title '. $text_clr .'">'. $sopt['label'] .'</span>
                    <span class="info-descr">'. (CadApolice::$statusLong[$sopt['st_ref']]??'Extraídos ou enviados manualmente') .'</span>
                </td>
                <td class="nav-link col-today col-count" data-sx="'. $sx .'" data-sref="'. $sopt['st_ref'] .'" onclick="goToUrl(\''. $link .'\'+last_date_process);window.event.stopPropagation();return false;">...</td>
                <td class="col-all col-count" data-sx="'. $sx .'" data-sref="'. $sopt['st_ref'] .'">...</td>
            </tr>';
        }
    }
    
    
    echo '<div class="row">';
    
        
    echo '<div class="col-sm-7">';
    echo '<div class="box no-border">
            <div class="box-body no-padding">';
            echo '<table class="table table-view1 table-hover" id="table-view1">
                <thead>
                    <tr>
                        <th colspan="2">Painel de Controle
                            <a href="#" onClick="fncProcessCount(true,this);return false;" title="Atualizar" class="pull-right" style="position:absolute;font-size:12px;margin:5px 0 0 15px;color:#fff;opacity:0.7;"><i class="fa fa-refresh"></i></a>
                        </th>
                        <th class="col-today text-right"><span class="last_date_process">Hoje</span> <i class="fa fa-plus hiddenx" data-icon="fa-plus|fa-minus" style="font-size:10px;cursor:pointer;" onclick="colShow(\'.col-all\',\'auto\',$(this));"></i></th>
                        <th class="col-all text-right">Todos</th></tr>
                </thead>
                <tbody>
                    ';
                
                $i=1;
                echo fnc_caixinhaStatus($baselink,1,'all',['label'=>'Todos','st_ref'=>'all']);
                $i++;
                
                foreach(CadApolice::$status_to_admin as $sx => $sopt){
                    echo fnc_caixinhaStatus($baselink,$i,$sx,$sopt);
                    $i++;
                }

            echo'</tbody>
            </table>';
    echo '</div></div>';//end .box-body, .box
    echo '</div>';//end .col-sm
    
    
    echo '
    <div class="col-sm-5">
            <div class="box box-danger box-danger">
                <div class="box-header">
                    <h3 class="box-title strong text-red">'. CadApolice::$status_to_admin['c']['label'] .'</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-view2 table-hover" id="table-error-user">
                    <tr><th>Pendências</th><th class="text-right">Registros</th></tr>';
                    
                    $codes=[];
                    foreach($list_err_user as $c => $text){
                        $codes[]=$c;
                        echo '<tr class="cursor-pointer" onclick="goToUrl(\''. $baselink .'?status=c&code='. $c .'\');"><td>'.$text.'</td><td class="col-count text-right fields-err" data-code="'.$c.'">0</td></tr>';
                    }
                    $c='_other';$text='Outros';
                    echo '<tr class="cursor-pointer" onclick="goToUrl(\''. $baselink .'?status=c&code=not:'. join(',',$codes) .'\');"><td>'.$text.'</td><td class="col-count text-right fields-err" data-code="'.$c.'">0</td></tr>';
                    
               echo'</table>
                </div>
            </div>
            
            <div class="box box-success" style="margin-top:32px;">
                <div class="box-header">
                    <small class="pull-right text-muted">a partir de 20/01/2021</small>
                    <h3 class="box-title strong text-green">'. CadApolice::$status_to_admin['f']['label'] .'</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-view2 table-hover" id="table-count-ctrl">
                    <tr><th>Atualizações</th><th class="text-right">Registros</th></tr>';
                    foreach($list_ok_ctrl as $c => $text){
                        echo '<tr class="cursor-pointer"><td>'.$text.'</td><td class="col-count text-right fields-ctrl" data-code="'.$c.'">...</td></tr>';
                    }
               echo'</table>
                </div>
            </div>
    </div>';//end .col-sm-5
    
@endphp


<style>
.content-header{display:none;}
.table-view1, .table-view1 th, .table-view1 td{border-bottom:1px solid #eee;border-right:1px solid #eee;}
.table-view1, .table-view1 .last-col,.table-view1, .table-view1 tr > *:last-child{border-right:0 !important;}
.table-view1 thead{background:#00a7d0;color:#fff;}
.table-view1 th{border-bottom:0 !important;font-size:18px;}
.table-view1 td{vertical-align:middle !important;}
.table-view1 .col-num{width:50px;}
.table-view1 .col-info{}
.table-view1 .col-count{width:120px;font-size:25px;text-align:right;font-weight:600;}
.table-view1 .info-title{font-size:20px;display:block;font-weight:600;}
.table-view1 .info-descr{color:gray}

.col-hidden-all{display:none !important;}
</style>
<script>
var stx_group={!! json_encode(array_fill_keys( array_keys(CadApolice::$status_to_admin),0) ) !!};
var last_date_process='';
var last_date_process_label='';
function fncProcessCount(force,objLink){
    var minutes=3;
    if(objLink)$(objLink).find('.fa').addClass('fa-spin');
    var oTable=$('#table-view1');
    var oTableErrorUser=$('#table-error-user');
    var oTableCountCtrl=$('#table-count-ctrl');
    awAjax({
        url: '{{route("admin.app.get",["resume_data","home_admin"])}}',
        data: {force:(force?'s':'n')},
        type : 'GET',
        dataType:'json',
        processData:true,
        success: function(r){
            _fx1('all',_fGroupStSum(r.count_all));
            _fx1('today',_fGroupStSum(r.count_created));
                        
            if(!force)setTimeout(fncProcessCount,1000*60*minutes);
            if(objLink)$(objLink).find('.fa').removeClass('fa-spin');
            
            last_date_process = r.max_created.date;
            oTable.find('.last_date_process').text(r.max_created.label);
            
            //atualiza a tabela de erros de operador
            _fxSumGroup(oTableErrorUser, r.error_user);
            
            //atualiza a tabela de ctrl de alterações do robô
            var i,a
            for(i in r.pr_seg_ctrl){
                a=r.pr_seg_ctrl[i];
                oTableCountCtrl.find('[data-code='+ i +']').text(a.count)
                    .closest('tr').attr('data-fields',a.fields).on('click',function(){
                        goToUrl('{{$baselink}}?status=f,w&ctrl_robo='+$(this).attr('data-fields'));
                    });
            };
            
        },
        error: function(xhr){
            //console.log('error',xhr.responseText)
            if(!force)setTimeout(fncProcessCount,1000*60*minutes);
            if(objLink)$(objLink).find('.fa').removeClass('fa-spin');
        }
    });
    
    var _fGroupStSum=function(arr){//return new arr stx_group
        var r={},x,k;
        for(var i in stx_group){
            if(!r[i])r[i]=0;
            k=i.split(',');
            for(x in k){
                if(arr.count_status[k[x]]){
                    r[i]+=arr.count_status[k[x]];
                }
            }
        };
        r['all']=arr.count_total;
        return r;
    };
    var _fx1=function(name,arr){
        for(var i in arr){
            oTable.find('.col-'+name+'[data-sx="'+i+'"]').html( formatCurrency(arr[i],0) );
        };
    };
    
    //soma todas as vars de um grupo, ex: td[data-code='code1,code..,code3'] considerando o json: {code1:0,code2:0,...}
    var _fxSumGroup=function(oTable,list){//list = {code1:0,code2:0,...}
        var sum1=0;
        var os=oTable.find('td[data-code]').each(function(){
            var td=$(this);
            var codes=td.attr('data-code').split(',');
            var c,i,x,s=0,n=0;
            for(i in codes){
                c=codes[i];
                if(c.substr(-2)=='..'){//soma todos com o mesmo prefixo (ex 'quiv..')
                    s=0;
                    c=c.replace('..','');
                    for(x in list){
                        if(x.indexOf(c)>-1) s+=list[x];
                    }
                    n+=s;
                }else{
                    n+=list[codes[i]]??0;
                }
            };
            sum1+=n;
            td.text(formatCurrency(n));
        });
        //soma todos
        var sum2=0;
        for(var i in list){sum2+=list[i];};
        var n=sum2-sum1;if(n<0)n=n*-1;
        os.filter('[data-code=_other]').text(n);
    };
};
fncProcessCount();

function colShow(colSel,sh,bt){//sh=auto|(boolen)
    var tb=$('#table-view1')
    var os=tb.find(colSel);
    if(bt){
        var ics=bt.attr('data-icon').split('|');
        bt.removeClass(ics[0]+' '+ics[1]);
    }
    if(sh=='auto')sh=os.eq(0).hasClass('col-hidden-all');
    if(sh){
        os.removeClass('col-hidden-all');
        if(bt)bt.addClass(ics[1]);
    }else{
        os.addClass('col-hidden-all');
        if(bt)bt.addClass(ics[0]);
    };
    tb.find('td,th').removeClass('last-col');
    tb.find('tr').each(function(){
        $(this).find('>*:visible:last').addClass('last-col');
    });
};
    //ao carregar a página
    //colShow('.col-all',false);
</script>
@endsection