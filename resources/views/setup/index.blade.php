@extends('templates.admin.single-page')
@section('content')
   
<h3 style="margin-top:0;">Instalação do sistema</h3><br>

<form id="form-setup" method="POST" action="{{route('setup-post')}}">
    @csrf
    
    <table width="100%">
    <tr>
        <td><strong>Tipo do ambiente</strong></td>    
        <td>
            <strong style="text-transform:uppercase;">{{ env('APP_ENV') }}</strong>
        </td>
    </tr>
    <tr>
        <td><strong>Debug</strong></td>    
        <td>
            <label><input type="radio" name="debug" {{ env('APP_DEBUG')==true?'checked':'' }} value="s"><span class="checkmark"></span> Sim</label><br>
            <label><input type="radio" name="debug" {{ env('APP_DEBUG')!=true?'checked':'' }} value="n"><span class="checkmark"></span> Não</label><br>
        </td>
    </tr>
    <tr>
        <td><strong>Cache</strong></td>    
        <td>
            <label><input type="checkbox" name="cache-clear" value="s"><span class="checkmark"></span> Limpar todo o cache</label><br>
        </td>
    </tr>
    <tr>
        <td><strong>Ações</strong></td>    
        <td>
            @if(Schema::hasTable('accounts'))
                <label><input type="radio" name="opt1" value="0" checked><span class="checkmark"></span> Nenhuma ação</label><br>
                <label><input type="radio" name="opt1" value="1"><span class="checkmark"></span> <strong>Recriar</strong> todo o banco de dados, deletando todas as tabelas, registros e arquivos</label><br>
                <label><input type="radio" name="opt1" value="2"><span class="checkmark"></span> <strong>Resetar</strong> toda a tabela e deletar todos os registros e arquivos</label><br>
                @if(false)
                <label><input type="radio" name="opt1" value="3"><span class="checkmark"></span> Deletar todos os registros, com exceção da configuração do sistema e usuários <span title="Tabela de seguradoras, corretores, robôs, processos, etc">+</span></label><br>
                <label><input type="radio" name="opt1" value="4"><span class="checkmark"></span> Deletar apenas os dados do processo do robô</label><br>
                @endif
            @else
                <label><input type="radio" name="opt1" value="install" checked><span class="checkmark"></span> Instalar banco de dados</label><br>
            @endif
        </td>
    </tr>
    </table>
    <br>
    <button type="submit" class="btn btn-primary">Configurar</button>
</form>
<script>
    $('#form-setup').on('submit',function(){
        if($('[name=debug]:checked').length==0)return false;
        if($('[name=opt1]:checked').length==0)return false;
        var m=prompt('Digite CONFIRMAR para continuar');
        if(m!=='CONFIRMAR')return false;
    })
</script>
<style>
    table{border-collapse:collapse;}
    td{border:1px solid #e2e2e2;padding:10px;}
</style>
    

@endsection