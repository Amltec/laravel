@extends('templates.admin.index')

@section('title')
Painel do Desenvolvedor
@endsection


@section('content-view')
<div class="box">
<div class="box-header"><h3 class="box-title">Links</h3></div>
<div class="box-body">
    <strong>Link para verificação de dados do robo:</strong><br>
    <a href="{{route('wsrobot.data')}}?action=get_process" target="_blank">{{route('wsrobot.data')}}?action=get_process</a>  <span class="text-muted" style="margin-left:10px;">&id={process_id}&robot_id={id}</span><br>
    <br>

    <strong>Para testar o envio do retorno de dados do robô</strong><br>
    <a href="{{route('super-admin.app.get',['dev','view','?pag=return-page-test'])}}" target="_blank">{{route('super-admin.app.get',['dev','view','?pag=return-page-test'])}}</a><br>
    <br>

    <strong>Link para verificação dos dados da revisão pelo robô</strong><br>
    <a href="#" onclick="var id=prompt('Digite o id do processo','');if(id)window.open('{{route('wsrobot.data')}}?action=get_process_review&id='+id);return false;" target="_blank">{{route('wsrobot.data')}}?action=get_process_review&id={process_id}</a><br>
    <br>

    <strong>Link para captura de um dado</strong><br>
    <a href="{{route('super-admin.app.get',['dev','view','?pag=return-get-data'])}}" target="_blank">{{route('super-admin.app.get',['dev','view','?pag=return-get-data'])}}</a><br>
    <br>
    <strong>Cadastro de Apólices - Sobre arquivos de teste</strong><br>
    Para processar um arquivo como teste, renomeio o arquivo iniciando com a palavra 'teste-', ex: 'teste-arquivo01.pdf'<br>
    <br>
</div>
</div>


<div class="box">
<div class="box-header"><h3 class="box-title">Extração de Textos</h3></div>
<div class="box-body">
    <strong>Ferramenta de extração de textos de arquivos pdf</strong><br>
    <a href="{{route('super-admin.app.get',['dev','view','?pag=pdftext-test'])}}" target="_blank">{{route('super-admin.app.get',['dev','view','?pag=pdftext-test'])}}</a><br>
    <br>

    <strong>Link para verificação de dados do robo:</strong><br>
    <a href="{{route('wsfilesextract.data')}}?action=get_process" target="_blank">{{route('wsfilesextract.data')}}?action=get_process</a>  <span class="text-muted" style="margin-left:10px;">&id={id}</span><br>
    <br>

</div>
</div>


<div class="box">
<div class="box-header">
    <div class="pull-right"><small>Mais informações na documentação em XLS</small></div>
    <h3 class="box-title">Processos agendados no servidor (para todas as contas)</h3>
</div>
<div class="box-body no-padding">
    <table class="table">
        <tbody>
        <tr>
            <th style="min-width:150px;">Processo</th>
            <th>Url </th>
            <th>Intervalo</th>
        </tr>
        @php
        $ListProcesssServer=[
            [
                'title'=>'Fila de Processos (CONFIG PENDENTE)',
                'url'=>'/queue/start',
                'description'=>'Executa a fila de classes de processos de pendentes no servidor',
                'time'=>'5 min',
            ],
            [
                'title'=>'Cadastro de Apólice <br><span class="nostrong text-blue">Aplicação AutoIt "robo-process-files-all"</span>',
                'url'=>'/process_cad_apolice/processFilesAll',
                'description'=>'Faz o processo de indexação/leitura do pdf e gravação dos dados no DB de todos que estiverem com process_status=0. Parâmetros adicionais querystring:<br>'.
                                '<strong>id=...</strong> (para processar o respectivo id)<br>'.
                                '<strong>lock=n</strong> (ignora trava de registro)<br>'.
                                '<strong>regs=...</strong> (ignora trava de registro número de registros por vez. Default 50.)<br>',
                'time'=>'1 seg para sequências de processos<br>'.
                        '60 segs para verificações em repouso',
            ],
            [
                'title'=>'Área de Seguradoras - Adiciona processo Download de Apólices <br><span class="nostrong text-teal">Configurado Cronjob</span>',
                'url'=>'/process_seguradora_files/add_process_auto',
                'description'=>'Adiciona o processo que fará o robô capturar os pdfs das apólices (na área de seguradas) no cadastro do Quiver'.
                                '<br><small>Obs: pode ser executado várias vezes por dia, gerando apenas novas requisições de busca para os registros já criados no mesmo dia.</small>',
                'time'=>'2x por dia: 07:30, 19:00',
            ],
            [
                'title'=>'Área de Seguradoras - Adiciona processo Marcar como Concluído <br><span class="nostrong text-teal">Configurado Cronjob</span>',
                'url'=>'/process_seguradora_files/add_process_markdone',
                'description'=>'Adiciona o processo que fará o robô verificar se existem registros para marcar como concluído no cadastro do Quiver'.
                                '<br><small>Obs: pode ser executado várias vezes por dia, gerando apenas novas requisições de busca para os registros já criados no mesmo dia.</small>',
                'time'=>'2x por dia: 18:30, 23:00',
            ],
            [
                'title'=>'Cadastro de Apólice - Remove os ignorados (CONFIG PENDENTE)',
                'url'=>'/process_cad_apolice/send_process_auto_trash',
                'description'=>'Faz o envio automático de processos com status="i" (ignorados) para a lixeira (válido somente se o campo process_auto=true)</small>',
                'time'=>'30 min',
            ],
            [
                'title'=>'Remove todos os processos da lixeira (CONFIG PENDENTE)',
                'url'=>'/process/remove_auto_trash',
                'description'=>'Remove todos os processos que estão na lixeira há mais de 7 dias de qualquer valor do campo process_name<br><small>Obs: os horários são próximos para o caso de erro no sistema e tenta de novo</small>',
                'time'=>'3x ao dia: 01:00, 02:00, 03:00',
            ],
            [
                'title'=>'Verificação dos históricos com apólices',
                'url'=>'/process_cad_apolice/process_fix_historico',
                'description'=>'Verifica se tem algum histórico com status="w" (ag. apólice) com apólice finalizado, a altera o status="f" (finalizado).<br>'.
                                'Este processo existe para ser executado manualmente e realizar os possíveis ajustes.<br>'.
                                'Por enquanto não é necessário agendar este processo.',
                'time'=>'-',
            ],
            [
                'title'=>'Limpa os logins do Quiver ocupados <br><span class="nostrong text-teal">Configurado Cronjob</span>',
                'url'=>'/process/clear_busy_pass',
                'description'=>'Limpa os logins do Quiver que estão ocupados com processos do robô que não esteja com status "a" Em Andamento.<br>'.
                                'Ou seja, ocorreu algum erro no fluxo do processo em que estes logins não foram desocupados.',
                'time'=>'5 min',
            ],
        ];

        foreach($ListProcesssServer as $process){
            echo '<tr>
                    <td><strong>'. $process['title'] .'</strong></td>
                    <td>
                        <a href="'. URL::to('/') . $process['url'] .'" target="_blank" class="strong">'. $process['url'] .'</a><br>
                        <small>'. $process['description'] .'</small>
                    </td>
                    <td>'. $process['time'] .'</td>
                </tr>';
        }
        @endphp
        </tbody>
    </table>
</div>
</div>


<div class="box">
<div class="box-header"><h3 class="box-title">Nomes dos Processos já Programados</h3></div>
<div class="box-body">
    @php
    echo '<ul>';
    foreach(\App\ProcessRobot\VarsProcessRobot::$configProcessNames as $process_name => $opt){
        echo '<li><strong>'. $opt['title'] .' <code>'. $process_name .'</code>';
            echo '<ul>';
            foreach($opt['products'] as $prod_name => $prod_opt){
                echo '<li>'. $prod_opt['title'] .' <code>'. $prod_name .'</code></li>';
            }
            echo '</ul>';
        echo '</li>';
    }
    echo '</ul>';
    @endphp
</div>
</div>
<br>


<div class="box">
<div class="box-header"><h3 class="box-title">Ferramentas</h3></div>
<div class="box-body">
    <a class="btn btn-default" href="#" onclick="awBtnPostData({url:'{{route('super-admin.app.post',['setup_tools','clear_cache'])}}',confirm:true,cb:function(r,oBt){if(r.success){r.oBt.text('Concluído');awModal({title:false,html:r.msg,btSave:false});}  }} ,this);return false;">Limpar cache de sistema</a><br>
    <br>
    <a class="btn btn-default" href="{{route('super-admin.terms.index')}}" target="_blank">Grupo de Marcadores</a><br>
    <br>
    <a class="btn btn-default" href="{{route('super-admin.app.get',['dev','view','?pag=tool-test-read-pdf'])}}" target="_blank">Assistente de Leitura de PDFs</a><br>
    <br>
    <a class="btn btn-default" href="{{route('super-admin.app.get',['setup-maintenance','index'])}}" target="_blank">Ações de Desenvolvedor</a><br>
    <br>
</div>
</div>



</div>



<style>
   .btn-wmin{min-width:180px;}
</style>
@endsection
