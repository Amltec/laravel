@php
/****
Variáveis esperadas:
    $title              - Título
    $data               - (array) lista de dados contendo (stdclass), total, dia, mes
    $col_index          - (array) lista da primeira coluna
    $col_name_index     - (string) nome do campo da primeira coluna em $data correspondente a $col_index
    $col_label          - (string) nome da primeira coluna
***/
    
    //dump($data);
    
    
    $meses = [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
    
    
    $data_count=[];
    //*** soma os totais ***
    foreach($data as $reg){
        $n=$reg->mes;
        if(!isset($data_count[$reg->$col_name_index]))$data_count[$reg->$col_name_index]=[$n=>0];
        if(!isset($data_count[$reg->$col_name_index][$n]))$data_count[$reg->$col_name_index][$n]=0;
        $data_count[$reg->$col_name_index][$n]+=$reg->total;
    }
    //dd($data_count);
    
    //*** monta a tabela ***
    $r='';
    $r.='<tr class="head">'.
        '<th class="col-first">'.$col_label.'</th>';
    foreach($meses as $mes_n => $mes_text){
        $r.='<th>'. $mes_text .'</th>';
    }
    $r.='<th class="col-label-totals">Total</th>'.
        '</tr>';
    
    $total_col=[];
    foreach($col_index as $col_name => $col_label){
        $total_line=0;
        $r.='<tr>'.
            '<td class="col-first">'. $col_label .'</td>';
        foreach($meses as $mes_n => $mes_text){
            $n = array_get($data_count,$col_name.'.'.$mes_n,0);
            $r.='<td class="col-number">'. FormatUtility::numberFormat($n,0) .'</td>';
            
            if(!isset($total_col[$mes_n]))$total_col[$mes_n]=0;
            $total_col[$mes_n]+=$n;
            $total_line+=$n;
        }
        $r.='<td class="col-totals col-number">'. FormatUtility::numberFormat($total_line,0) .'</td>'.
            '</tr>';
    }
    
    $n=0;
    $r.='<tr class="bottom">'.
        '<td class="col-label-totals">Total</td>';
    foreach($meses as $mes_n => $mes_text){
        $n+=$total_col[$mes_n];
        $r.='<td class="col-totals col-number">'. FormatUtility::numberFormat($total_col[$mes_n],0) .'</td>';
    }
    $r.='<td class="col-totals col-number">'. FormatUtility::numberFormat($n,0) .'</td>'.
        '</tr>';
    
    echo '
    <h4>'. $title .'</h4>
    <div class="box box-widget">
        <table class="table table-bordered table-report-numbers">'.$r.'</table>
    </div>';
    

@endphp

@pushonce('bottom_once')
<style>
    .table-report-numbers th{text-align:right;}
    .table-report-numbers .col-first{text-align:left;}
    .table-report-numbers .col-number{text-align:right;}
</style>
@endpushonce