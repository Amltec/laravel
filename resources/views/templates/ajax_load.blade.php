@php
/***
    Template para carregamento de componentes e ui via ajax.
    Esta view deve ser chamada sempre que carregado uma view com componentes/ui que tem funções js pré carregadas automaticamente (com a função Form::execFnc(...))
    Parâmetros esperados:
        view        - nome da view
        data        - parâmetros da view
        html_before - (string|function) html adicionado antes da view
        html_after  - (string|function) html adicionado depois da view
*/
if(!isset($view) || !isset($data)){
    echo 'Parâmetro $view e $data inválido';
    return;
}

$auto_id = 'scripts-upload-'.uniqid();

echo callstr($html_before??null);
echo view($view,$data);
echo '<div id="'. $auto_id .'">';
echo Form::writeScripts(true);
echo '</div>';
echo callstr($html_after??null);


/*
  Obs: abaixo move todo o bloco de scripts para fora a raiz do documento, pois todo este arquivo foi carregado a partir do ajax 
       e pode ocorrer que ao remover dinamicamente este container/div do qual é carregado este arquivo, seja removido também
       todos os scripts escritos dentro dele (neste caso poderá ocorrer de arquivos css não serem carregados em uma segunda execução
*/
@endphp
<script>
$('#{{$auto_id}}').appendTo(document.body);
</script>