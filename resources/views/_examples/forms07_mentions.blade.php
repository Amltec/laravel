@extends('templates.admin.index')


@section('title')
Menções de campos com @{key}
@endsection


@section('content-view')
@php
    //código incluído automaticamente para importação de arquivos
    Form::loadScript('mentionjs');
    Form::loadScript('boxlist');
@endphp

Mais informações, analise o código fonte.<br><br>

<h4>Exemplo de link com @{type}:{id}</h4>
<a href="@post:123">Link de teste para <strong>@post:123</strong></a> <small>(veja no código fonte)</small>
<br><br>
<script>
$(document.body).on('click','a',function(e){
    var url=$(this).attr('href');
    if(url.substring(0,1)=='@'){
        e.preventDefault();
        alert('clicked to '+url)
    }
});
</script>



<h4>Exemplo em campos de texto</h4>
<form>
    Input text <small>(utilize @link)</small><br>
    <input type="text" id="field1" class="fields" value="Meu texto inicial">
    <br><br>
    
    Textarea <small>(utilize @link2)</small> <br>
    <textarea type="text" id="field2" class="fields" rows="4" data-mention='{"key":"@link2"}'>Meu texto inicial</textarea>
    <br><br>
    
    Div Editable <small>(utilize @link)</small>
    <div contentEditable="true" id="field3" class="fields">Meu <strong>te<span style='color:red'>xt</span>o</strong> inicial</div>
    <br><br>
    
    Editor Ckeditor<br>
    <a href="example?name=editor_ckeditor">Ver exemplo</a>
    <br><br>
    
</form>
<br>
<script>
awMention('#field1',{
    box_list:{
        ajax:{ data:{def:'post'} }
    }
});
awMention('#field2');
awMention('#field3');
</script>




<h4>Exemplos com configuração manual <small style="margin-left:10px;">Analise o código fonte para mais informações</small></h4>


Executando qualquer ação a partir da string '@field' 
<input type="text" id="field4" class="fields" placeholder="Digite qualquer texto e a string '@field' para acionar">
<strong style="color:blue;" id="result4"></strong>
<script>
awMention('#field4',{
    key:'@field',
    cb:function(opt){
        $('#result4').html('String @field detected - cursor: '+ opt.cursor.ps ).hide().fadeIn();
        console.log('Key @field detected',opt)
    }
});
</script>
<br><br>


Capturando lista de nomes com @user e exibindo o nome do usuário ao selecionar
<input type="text" id="field5" class="fields" placeholder="Digite qualquer texto e a string '@user' para acionar">
<strong style="color:blue;" id="result5"></strong>
<script>
awMention('#field5',{
    key:'@user',
    onselect:function(json,item,data){
        console.log(json,item,data)
        $('#result5').html('Usuário '+ data.title +' selecionado')
    },
    template_text:function(data){//template no campo input
        return '@'+data.title+' (id='+ data.id +') ';
    },
    box_list:{
        title:'Pesquisa de usuários',
        ajax:{
            url:'{{ route('super-admin.app.post',['example','mention_list']) }}'
        },
        template:function(data){//template na lista
            if(data.head){
                return '<strong>Cabeçalho</strong>';
            }else{
                return data.id +' - '+ data.title;
            }
        }
    }
});
</script>
<br><br>


Capturando lista de nomes com @user e carregando apenas uma vez o ajax, com ação personalizada ao selecionar o item na lista
<input type="text" id="field6" class="fields" placeholder="Digite qualquer texto e a string '@user' para acionar">
<script>
awMention('#field6',{
    key:'@user',
    template_text:'*{title}*',
    box_list:{
        title:false,//'Pesquisa de usuários',
        ajax:{
            url: '{{ route('super-admin.app.post',['example','mention_list']) }}',
            once:true,//para carregar a lista apenas uma única vez
        },
        template:function(data){//template na lista
            if(!data.head)return data.id +' - '+ data.title;
        },
        /*onselect:function(item,data){//ao selecionar diretamente o item da lista
            alert(data.title +' clicked');
            //this.onselect(item,data); //executa o onselect interno da função awMentionUILinks()
        },*/
        //is_title:false,
        //is_search:false,
    }
});
</script>
<br><br><br>


<h4>Exemplos de janela de itens com a função awBoxList()</h4>
<a href="#" id="ex-boxlist01">Link de teste para <strong>@post:123</strong></a> <small>(veja no código fonte)</small>
<br><br>
<script>
$('#ex-boxlist01').on('click',function(e){
    e.preventDefault();
    awBoxListShow(
        {
            title:"Campos do sistema",
            search_onshow:true, 
            ajax:{
                url:"{{ route('super-admin.app.get',['example','mention_list']) }}",
                once:true
            },
            template:function(data){ return data.title; },
            onselect:function(item,data){ alert('clicked: '+ data.title); console.log('clicked',item,data) },
            hide_onselect:true
        },
        {pos:this}
    );
});
    
</script>





<br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br>

<style>
.fields{width:100%;border:1px solid #ccc;padding:10px;}
</style>
@endsection