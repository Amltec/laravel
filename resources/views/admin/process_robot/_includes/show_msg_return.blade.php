@php
/***
    Include padrão de mensagem de retorno para os arquivos show.blade de cada processo
    Variáveis esperadas:
        $robot_data
        $model
***/

if(!function_exists('awProcessRobotShow_formatMsg1')){
    function awProcessRobotShow_formatMsg1($user_logged_level,$error_msg,$isLast=false){//se $isLast=true, indica o último processamento
        $labels=['dado'=>'Dados Básicos','veiculo'=>'Veículo','premio'=>'Prêmio','parcela'=>'Parcela','anexo'=>'Anexos'];
        
        //$error_msg="{blocks:dado,veiculo,premio,anexo}*sep*dado => R - Finalizado - Campos alterados: NumeroApolice => De: '' - Para: '3134173622'|DataEmissao => De: '' - Para: '29/05/2020'*sep*Veículos => R - Finalizado - Campos alterados: VeiculoCodigoCi => De: '' - Para: '51820341812014'*sep*Prêmios => R - Finalizado - Campos alterados: DataVenc1Parcela => De: '10/06/2020' - Para: '01/06/2020'|DiaVencProxParcelas => De: '10' - Para: '20'*sep*Anexos => R - Finalizado - Campos alterados: DescricaoAbexo => De: '' - Para: 'APOLICE'|TipoArquivoAnexo => De: '0' - Para: '1'";
        //dump($error_msg);
        
        $r='';
        if($error_msg){
            $nx=explode('*sep*',$error_msg);
            
            foreach($nx as $i=>$line){
                if(substr(strtolower($nx[$i]),0,8)=='{blocks:'){
                    if($user_logged_level=='dev'){echo '<span class="label margin-r-5 bg-black" style="margin-left:15px;">'. $nx[$i] .'</span>';}
                    unset($nx[$i]);
                    continue;
                }
                
                if(strpos($line,'=>')!==false){
                    $line=array_map('trim',explode('=>',$line,2));
                    $s = substr($line[1],0,1);
                    $r='<tr class="st_'.$s . ($isLast?' is_last':'') .'">'.
                            '<td>'. ($labels[$line[0]]??$line[0]) .'</td>'.
                            '<td>'. str_replace(['|','R - ','E - ',';'],['<br>','','','<br>'],$line[1]) .'</td>'.
                       '</tr>';
                    
                    $nx[$i]=$r;
                }else{
                    if(stripos($line,'erro')!==false){
                        $nx[$i]='<div class="st_E'. ($isLast?' is_last':'') .'">'.$line.'</div>';
                    }elseif(stripos($line,'finalizado')!==false){
                        $nx[$i]='<div class="st_R'. ($isLast?' is_last':'') .'">'.$line.'</div>';
                    }else{
                        $nx[$i]=$line;
                    }
                }
            }
            $r = '<table class="tbl_msg">'.join('',$nx).'</table>';
        }
        return $r;
    }
}



$data=[];
//*** mensagens de retorno ***
    $return_count = (int)($robot_data['return_count']??0);
    
    //retorno msg1
    if($robot_data['error_msg']??false){
        $data[]='<hr style="margin:0;">';
        if($robot_data['process_start']??false){
            $data[]=['title'=>'Início da execução','value'=>$robot_data['process_start'],'type'=>'datetime'];
            if($robot_data['process_end']??false){
                $data[]=['title'=>'Término (horas)','alt'=>$robot_data['process_end']  ,'value'=>FormatUtility::dateDiffFull($robot_data['process_start'],$robot_data['process_end'])];
            }else{
            //    $data[]=['title'=>'Término','value'=>'-'];
            }
        }
        
        $m=$robot_data['error_msg']??''; 
        $data[]=['title'=>'Msg de Retorno','value'=>awProcessRobotShow_formatMsg1($user_logged_level,$m,$return_count==0),'class_value'=>$model->status_color['text']];
    }
    
    //retorno com contagem pela var $return_count
    if($return_count){
        for($i=1;$i<=$return_count;$i++){
            $p_start = $robot_data['process_start_'.$i]??null;
            $p_end = $robot_data['process_end_'.$i]??null;
            $m='';
            if($p_start){
                $m.='Execução - Início: '. FormatUtility::dateFormat($p_start);
                if($p_end??false){
                    $m.=' - Término (horas): '. FormatUtility::dateDiffFull($p_start,$p_end);
                }
            }
            
            if($m)$m.=' - ';
            $m.=awProcessRobotShow_formatMsg1($user_logged_level,$robot_data['error_msg_'.$i]??'',$return_count==$i).
                ($i<$return_count?'<hr style="margin:10px 0 0 0;">':'');
            
            if($m)$data[]=['title'=>'Retorno','value'=>$m,'class_row'=>'j-line-return'];
        }
    }
    
echo view('templates.ui.view',['data'=>$data]);
    

@endphp

<script>
(function(){
    /*var rows=$('.j-line-return');
    var x=rows.length;
    if(x<=1)return;
    
    $('<div class="ui-view-row-group"><div class="ui-view-caption">'+(x-1)+' itens anteriores</div></div>').insertBefore(rows.eq(0))
    .on('click',function(){
        $(this).hide();
        rows.fadeIn();
    });
    rows.each(function(i){
        if(i==x-1)return false;
        var o=$(this);
        o.hide();
    });*/
}());
    
</script>
<style>
    .ui-view-row-group{text-align:center;cursor:pointer;}
    .ui-view-row-group:before{content:'';width:96%;left:2%;height:1px;border-top:1px solid #e2e2e2;margin:11px 0 0 0;position:absolute;z-index:1;}
    .ui-view-row-group .ui-view-caption{background:#fff;padding:1px 10px;z-index:4;position:relative;display:inline-block;font-size:small;}
</style>