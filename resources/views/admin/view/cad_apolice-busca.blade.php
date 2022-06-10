@extends('templates.admin.index')

@section('title')
Critérios utilizados para busca da proposta no Quiver
@endsection

@section('content-view')

@php
    $config_cad_apolice = Config::account() ? Config::accountService()::getCadApoliceConfig(Config::account()) : null;
@endphp


<div class="box">
    <div class="box-body">
<div style="white-space:pre-line;">Atualização em 01/10/2021
Consulta no Quiver pelo menu Operacional -> Proposta/Apólice.
O robô utiliza três modos de pesquisa

<h4>Modo 1 – Pesquisa por Chassi:</h4>
(apenas para automóvel)
1.	Informa na busca o campo: 
    -	Chassi
2.	Após o resultado da busca, continua verificando:
A.	Seguradora: deve ser igual ao nome da seguradora que consta no PDF da Apólice;
B.	Produto: verifica se o produto é do ramo de automóvel
C.	Início de Vigência: deve ser igual a data de início de vigência que consta no PDF da apólice;
D.	Status: deve ser diferente de CANCELADA;

Caso nenhum resultado da pesquisa atenda os critérios é executada uma segunda pesquisa:

<h4>Modo 2 - Pesquisa por: C.P.F./C.N.P.J:</h4>
1.	Informa na busca os campos:
    -	 Tipo Pessoa: 
    -	Número do CNPJ ou CNPJ
    -	Início e final da vigência conforme consta no PDF da apólice
2.	Após o resultado da busca, continua verificando:
A.	Seguradora: deve ser igual ao nome da seguradora que consta no PDF da Apólice;
B.	Produto: verifica se o produto é do ramo de automóvel
C.	Início de Vigência: deve ser igual a data de início de vigência que consta no PDF da apólice;
D.	Status: deve ser diferente de CANCELADA;

Caso nenhum resultado da pesquisa atenda os critérios é executada uma segunda pesquisa:

<h4>Modo 3 - Pesquisa por: C.P.F./C.N.P.J:</h4>
1.	Informa na busca os campos:
    -	 Tipo Pessoa: 
    -	Número do CNPJ ou CNPJ
    -	Início da vigência e final da vigência com intervalo de 5 dias. 
Ex vigência=10/10/20 – pesquisa de 05/10/20 a 15/10/2020
2.	Após o resultado da busca, continua verificando:
A.	Seguradora: deve ser igual ao nome da seguradora que consta no PDF da Apólice;
B.	Produto: verifica se o produto é do ramo de automóvel
C.	Início de Vigência: deve existir dentro do intervalo de 5 dias conforme consta no PDF da Apólice .
D.	Status: deve ser diferente de CANCELADA;
3.	Caso nenhum registro seja retornado, será feito a busca novamente, repetindo todo o Modo 3, alterando o prazo de vigência dentro de um intervalo de 10 dias.
    -	Caso não encontre seja repetido novamente dentro de um intervalo de 20 dias.

<h4>Modo 3 – Continuação por Nº da Proposta da Cia:</h4>
A partir do item  2.D
1.	Caso retorne a mais de um resultado válido (todas as demais informações comparadas são idênticas), verifica se existe o Documento Quiver armazenado, e neste caso procura se algum item da lista tem é compatível com este campo.
2.	Caso não exista o Documento Quiver, procura se algum item da lista na coluna do Nº da Proposta da Cia é igual ao mesmo campo extraído do PDF da apólice.

Observações:
1.	Primeiro procura no Modo 1, e caso não encontre, segue para o Modo 2, e caso não encontre, segue para o Modo 3, e caso não encontre, retorna ao erro 
“Proposta não encontrada” ou “Proposta não localizada”.

2.	Para cada modo de busca, considera como proposta encontrada apenas um registro exclusivo, ou seja, caso seja compatível mais de um resultado, no último modo de busca, retorna a mensagem: 
“Proposta localizada mas sem parâmetro programado para selecionar mais de um resultado"

3.	O produto é precisa ser igual a um dos valores previstos:
<strong>Automóvel: </strong>
@php
    $n=array_get($config_cad_apolice,'search_products.automovel');
    echo $n ? str_replace('|',', ',$n) : '<em class="text-muted">Conforme produtos configurados em cada conta</em>';
    echo '<br>';
@endphp
<strong>Residencial: </strong>
@php
    $n=array_get($config_cad_apolice,'search_products.residencial');
    echo $n ? str_replace('|',', ',$n) : '<em class="text-muted">Conforme produtos configurados em cada conta</em>';
    echo '<br>';
@endphp
<strong>Empresarial: </strong>
@php
    $n=array_get($config_cad_apolice,'search_products.empresarial');
    echo $n ? str_replace('|',', ',$n) : '<em class="text-muted">Conforme produtos configurados em cada conta</em>';
    echo '<br>';
@endphp

4.	Após conclusão da execução da primeira emissão, é armazenado o número do Documento do Quiver no banco de dados do Robô, para associar de forma exclusiva o registro atualizado.
Obs: este é um ID interno do Quiver para identificação de cada proposta emitida.

 
<h4>Verificação adicional 1 </h4>
Quando encontrar a proposta para emitir, dentro nas condições acima descritas, é verificado:
1.	Se a proposta for a primeira atualização do robô (não existe o Documento do Quiver), e neste verifica se o status é diferente de EMITIDA, prossegue corrigindo os dados, caso contrário, compara com o número da apólice da coluna da busca com o extraído do pdf. 
Lógica: número da apólice é igual ou um dos números existem dentro da coluna do número da apólice no Quiver. Ex: “1234” = “1234” ou “1234” existe em “11234” e vice versa.
    -	Se verdadeiro: retorna a “Proposta já emitida”
    -	Se falso: retorna a: “Proposta não cadastrada”

2.	Se a proposta já foi emitida anteriormente pelo robô (existe um Documento do Quiver registrado garantindo a associação correta da proposta), apenas prossegue corrigindo os dados.

<h4>Verificação adicional 2</h4>
1.	Após localizar a proposta com todas as condições acima, é verificado se o Documento do Quiver existe na base de dados em outro registro processado anteriormente, e:
    -	Se existir, retorna a um erro de “Proposta duplicada pelo Quiver ID”
    -	Se não existir, prossegue com a emissão. 


</div>
</div></div>
@endsection