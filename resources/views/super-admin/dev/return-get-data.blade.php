@extends('templates.admin.index')

@section('title')
(DEV) Teste de captura de um dado pelo robô
@endsection

@section('content-view')

@php
if(Auth::user()->user_level!='dev')exit('negado');

    $key_active = '';
    $key_robot = '';
    $robotModel = \App\Models\Robot::where('robot_status','a')->first();
    if($robotModel){
        $key_active = $robotModel->key_active;
        $key_robot = $robotModel->key_robot;
    }

    $post_url = URL::to("/") .'/wsrobot/data';

    echo '<form method="POST" action="'. $post_url .'" id="form1">
        '.csrf_field().'
        <table class="table1">
        <tr><td>POST</td><td>'.$post_url.'</td></tr>
        <tr><td>Chave do de Ativação</td><td><input type="text" name="key_active" value="'. $key_active .'"></td></tr>
        <tr><td>Chave do Robô</td><td><input type="text" name="key_robot" value="'. $key_robot .'"></td></tr>
        <tr><td>Nome da Ação</td><td><input type="text" name="action" value="get_data" readonly></td></tr>
        <tr><td>ID da Conta</td><td><input type="text" name="account_id" value="1"></td></tr>
        <tr><td>Nome do Processo</td><td><input type="text" name="process_name" value="cad_apolice"></td></tr>
        <tr><td>Campos adicionais (querystring)</td><td><input type="text" name="fields" value="field=quiver_id_exists&qid=123&process_id=123"></td></tr>
        <tr><td> </td><td><input type="submit"></td></tr>
        </table>
        <script>
        $("#form1").on("submit",function(e){
            e.preventDefault();
            var f=$(this);
            var o=f.find("[name=fields]");
            var fields=o.val();
            o.remove();
            fields=qsToJSON("?"+fields);
            delete fields._baseurl;
            for(var i in fields){
                f.append(\'<input type="hidden" name="\'+ i +\'" value="\'+ fields[i] +\'">\');
            }
            f[0].submit();
        });
        </script>
    </form>

    <br>
    <p>Exemplo de link para teste (GET):<br>
        <a href="'. route('wsrobot.data') .'?action=get_data&account_id=&process_name" target="_blank">'. route('wsrobot.data') .'?action=get_data&account_id=&process_name=&...</a><br>
    <p>

    <br>
    <h4>Campos Registrados</h4>
    <strong>ProcessName: cad_apolice</strong>
    <table class="table box">
        <tr><th>Campo</th><th>Descrição</th></tr>
        <tr>
            <td>field=<strong>quiver_id_exists</strong>&qid={int}&process_id={int}</td>
            <td>Verifica se um quiver_id já está registrado para algum outro registro</td>
        </tr>
        <tr>
            <td>field=<strong>quiver_id_register</strong>&qid={int}&process_id={int}</td>
            <td>Solicita o registro do quiver_id</td>
        </tr>
        <tr>
            <td>field=<strong>pass_data</strong>&pass_id={int}(opcional)</td>
            <td>Retorna aos dados de login de um usuário. <br>Caso pass_id não informado, será retornado ao usuário de revisão</td>
        </tr>
    </table>




    ';



    //dd((new \App\ProcessRobot\ResumeProcessRobot)->setProcessData('cad_apolice'));
    //dd((new \App\ProcessRobot\ResumeProcessRobot)->getProcessData('cad_apolice',0,true));


    //$model = \App\Models\ProcessRobot_SeguradoraFiles::find('294');
    //dd( \App::call('\\App\\Http\\Controllers\\Process\\ProcessSeguradoraFilesController@doExtracted',[$model]) );


@endphp


<style>
.table1 td{padding:5px;}
input,select{padding:4px 7px;border:1px solid #bbb;}
input[type=text],select{width:300px;}
input[readonly]{background:#f2f2f2;}
</style>
@endsection
