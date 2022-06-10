@extends('templates.admin.index')

@section('title')
@php
    if(!$model){
        echo 'Novo Corretor';
    }else{
        echo 'Corretor <small>#'.$model->id.' - '. $model->created_at .'</small>';
    }
@endphp
@endsection


@section('content-view')
@php
/* Variáveis esperadas
    $model
*/


echo view('templates.ui.tab',[
    'id'=>'tab_main',
    'tab_active'=>  _GET('pag')??'edit',
    'data'=>[
        'edit'=>['title'=>'Dados','href'=>'#'],
        'images'=>['title'=>'Desconto de Comissão','href'=>'#comissao'],
        'insurers'=>['title'=>'Seguradoras','href'=>'#insurers'],
    ],
    'content'=>false,
    'class'=>'no-margin',
]);



//monta o campo que será exibido no form
        
        $account_config = \Config::accountData()->data['config'];
        $is_broker_login = $account_config['cad_apolice']['login_mode']=='separate';
        $forms=[];
        
        //*** dados do corretor ***
            $param_fields = [
                'broker_alias'=>['label'=>'Corretor','maxlength'=>100,'require'=>true],
                'broker_name'=>['label'=>'Nome ou Razão Social','maxlength'=>100,'require'=>true],
                'broker_doc'=>['label'=>'Nº SUSEP','maxlength'=>100,'require'=>true, 'info_html'=>'<span class="text-muted">Para mais de um valor, utilize virgula</span>'],
                'broker_cpf_cnpj'=>['label'=>'CNPJ / CPF','maxlength'=>100,'require'=>true],
                'broker_status'=>['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true],

                'line01'=>['type'=>'info','text'=>'<hr><span class="text-muted">Informações para Login no Quiver</span>'],
                'broker_col_user'=>['label'=>'Usuário','maxlength'=>20,'require'=>true],
                'broker_col_login'=>['label'=>'Login','maxlength'=>20,'require'=>true],
                'broker_col_senha'=>['type'=>'password','label'=>'Senha','maxlength'=>20,'require'=>true],
                'broker_col_senha2'=>['type'=>'password','label'=>'Confirmar Senha','require'=>true,'maxlength'=>20,
                    'info_html'=>($model ? 
                            '<span class="text-muted">Preencher somente se for alterar</span>'.
                            (Gate::allows('superadmin')?'<br><em class="text-muted">Senha atual: <b onclick="$(this).text(\''.$model->broker_col_senha.'\')">[clique para ver]</b></em>':'')
                        : '')],
            ];

            if(!$is_broker_login){//as informações de login do corretor não devem aparecer
                unset($param_fields['line01']);
                $param_fields['broker_col_user']['type']='hidden';
                $param_fields['broker_col_login']['type']='hidden';
                $param_fields['broker_col_senha']['type']='hidden';
                $param_fields['broker_col_senha2']['type']='hidden';
            }
            
            if(empty($model)){//add
                $param_fields['broker_status']=['type'=>'select','label'=>'Status','list'=>[''=>'','a'=>'Normal','c'=>'Cancelado'],'require'=>true];
                unset($param_fields['broker_status']);
            }else{//edit
                //limpa o campo senha 
                $model->broker_col_senha='';
            }
            
            $forms[]=[
                'layout_type'=>'horizontal',
                'autocolumns'=>$param_fields,
                'autodata'=>$model??false,
            ];
            
        //desconto de comissão de 1% no cadastro
            $field_insurer = [];
            $checks_ids_insurer = [];
            $modelInsurer = \App\Models\Insurer::where('insurer_status','a')->get();
            if($modelInsurer){
                $configProcessNames = \App\ProcessRobot\VarsProcessRobot::$configProcessNames;
                $r='';
                foreach($configProcessNames['cad_apolice']['products'] as $process_prod => $process_opt){
                    $r.='<div class="block_comissao_desc">'.
                        '<strong>'.$process_opt['title'].'</strong>';
                    foreach($modelInsurer as $rs){
                        $t=false;
                        if($model){//somente se for atualização
                            $data = $rs->getBrokerData($model->id);//captura os dados gerais relacionados entre corretor com a seguradora do loop
                            $comissao_desc = $data['cad_apolice_comissao_desc']??[];
                            if($comissao_desc[$process_prod]??false === true)$t=true;
                            //dump($data);
                        }
                        $r.='<br><label class="nostrong"><input type="checkbox" name="cad_apolice_comissao_desc[]" '. ($t?'checked':'') .' value="'. $process_prod .','. $rs->id .'"><span class="checkmark"></span> '.$rs->insurer_alias.'</label>';
                    }
                    $r.='</div>';
                }
                if($r){
                    $r='<hr><span class="text-muted">Descontar Comissão de 1% no cadastro</span><br>'.
                        $r.
                        '<div class="clearfix"></div>';
                    $forms[]=$r;
                }
            }
        
            
        //retorna a view com todos os forms
        echo view('templates.ui.auto_groups',[
            'autogroups'=>$forms,
            'form'=>[
                'url'=>($model?route('admin.app.update',['brokers',$model->id]):route('admin.app.store','brokers')),
                'method'=>'post',
                'url_back'=>route('admin.app.index','brokers'),
                'data_opt'=>[
                    'focus'=>true,
                    'onSuccess'=>"@function(r){if(r.action=='add'){window.location=String('". route('admin.app.edit',['brokers',':id']) ."').replace(':id',r.data.id);} }"
                ],
                'method'=> $model?'put':'post'
            ],
            'metabox'=>true
        ]);

@endphp


<style>
.block_comissao_desc{width:20%;float:left;}
@media (max-width:780px){ 
    .block_comissao_desc{width:50%;}
}
</style>
@endsection