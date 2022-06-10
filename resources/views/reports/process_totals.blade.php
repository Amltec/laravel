@php
/****
Relatório de totais de processamento
Variáveis esperadas:
    ...

****/
@endphp

@extends('templates.admin.index')

@section('title')
    Totais de Processamento <small class="strong">Relatório</small>
@endsection

@section('content-view')
@php
    
    
    //lista de anos
    $list_anos = DB::select('select year(created_at) as ano from process_robot where process_name=? group by ano order by ano desc;',['cad_apolice']);
    $list_anos = Collect($list_anos)->pluck('ano','ano')->toArray();
    
    //lista de contas
    $accounts_list = \App\Models\Account::where('account_status','a')->pluck('account_name','id')->toArray();
    
    
    //barra de filtros
    $filter = [
        'year'=>Request::input('year')??date('Y'),
        'account_id'=>Request::input('account_id'),
    ];
    echo view('templates.ui.toolbar',[
        'autodata'=>(object)$filter,
        'autocolumns'=>[
            'year'=>['label'=>'Ano','type'=>'select','list'=>$list_anos],
            'account_id'=>['label'=>'Conta','type'=>'select','list'=>[''=>'']+$accounts_list],
            'bt'=>['title'=>false,'alt'=>'Filtrar','class_group'=>'','type'=>'submit','color'=>'info','icon'=>'fa-filter','attr'=>'style="margin-top:24px;"','class'=>'j-btn-submit'],
        ],
        'metabox'=>false,
    ]);
    echo '<br>';

    
    //dados das colunas
    $col_index   = \App\ProcessRobot\VarsProcessRobot::$configProcessNames['cad_apolice']['products'];
    $col_index   = FormatUtility::pluckKey($col_index  ,'title');
    
    
    
    //relatório: totais de emissões processadas
        $sql=   'select count(1) as total, process_prod, month(created_at) as mes '.
                'from process_robot '.
                'where process_name=? and year(created_at)=? and deleted_at is null and process_status<> ? '.
                    ($filter['account_id']?'and account_id=? ': '').
                'group by process_prod, mes '.
                'order by mes asc';
        $params=[
            'cad_apolice',
            $filter['year'],
            'i',    //status ignorado
        ];
        if($filter['account_id'])$params[]=$filter['account_id'];
        $list_emissoes = DB::select($sql,$params);
        
        echo view('reports._inc.tabela-meses',[
            'title'=>'Totais de Emissões',
            'col_index'=>$col_index,
            'col_label'=>'Ramo',
            'col_name_index'=>'process_prod',
            'data'=>$list_emissoes,
        ]);
        echo '<br>';

        
    
    //relatório: totais de emissões
        $sql='select count(e.id) as total, month(e.process_start) as mes, p.process_prod '.
            'from process_robot_execs e inner join process_robot p on e.process_id=p.id '.
            'where p.process_name=? and year(e.process_start)=? and p.deleted_at is null and p.process_status<> ? '.
                ($filter['account_id']?'and p.account_id=? ': '').
            'group by mes, p.process_prod '.
            'order by mes asc';
        $params=[
            'cad_apolice',
            $filter['year'],
            'i',    //status ignorado
        ];
        if($filter['account_id'])$params[]=$filter['account_id'];
        $list_process_quiver = DB::select($sql,$params);
        //dd($list_process_quiver);
        
        echo view('reports._inc.tabela-meses',[
            'title'=>'Totais de Processamentos no Quiver',
            'col_index'=>$col_index,
            'col_label'=>'Ramo',
            'col_name_index'=>'process_prod',
            'data'=>$list_process_quiver,
        ]);
        echo '<br>';

        
        
   //relatório: totais de boletos
        $params=[
            'seguradora_data',
            'boleto_seg',
            $filter['year'],
        ];
        if($filter['account_id'])$params[]=$filter['account_id'];
        
        $sql='select count(1) as total, month(pr.created_at) as mes, pr.process_prod '.
             'from pr_seguradora_data pr inner join process_robot p on pr.process_id=p.id '.
             'where p.deleted_at is null and p.process_name=? and pr.process_prod=? and year(pr.created_at)=? '.
                ($filter['account_id']?'and p.account_id=? ': '').
             'group by pr.process_prod, mes '.
             'order by mes asc';
        
        $list_emissoes_cancel = DB::select($sql,$params);
        //dd($list_emissoes_cancel,$col_index);
        echo view('reports._inc.tabela-meses',[
            'title'=>'Boletos nas Seguradoras',
            'col_index'=>['boleto_seg'=>'Baixa de Boletos'],
            'col_label'=>'Ramo',
            'col_name_index'=>'process_prod',
            'data'=>$list_emissoes_cancel,
        ]);
        echo '<br>';
    
        
        
    //relatório: totais de emissões ignoradas ou canceladas
        $sql='select count(1) as total, process_prod, month(created_at) as mes '.
            'from process_robot '.
            'where process_name=? and year(created_at)=? and deleted_at is not null and process_status=? '.
                ($filter['account_id']?'and account_id=? ': '').
            'group by process_prod, mes '.
            'order by mes asc';
        $params=[
            'cad_apolice',
            $filter['year'],
            'i',    //status ignorado
        ];
        if($filter['account_id'])$params[]=$filter['account_id'];
        $list_emissoes_cancel = DB::select($sql,$params);
        
        echo view('reports._inc.tabela-meses',[
            'title'=>'Totais de Emissões Ignoradas ou Canceladas no Robô',
            'col_index'=>$col_index,
            'col_label'=>'Ramo',
            'col_name_index'=>'process_prod',
            'data'=>$list_emissoes_cancel,
        ]);
        echo '<br>';
        
    
@endphp

    
@endsection