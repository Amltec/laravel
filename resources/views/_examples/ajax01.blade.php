@extends('templates.admin.index')



@section('title')
Ajax - Acessando recursos por ajax
@endsection


@section('content-view')

<strong>Importante:</strong> Neste exemplo, o form contém dependências externas (como o editor de texto), mas não precisar setar o código de 
carregamento <code>Form::loadScript('ckeditor');</code> pois já está instânciado automaticamente ao carregar via ajax.
<br><br><br>


<textarea class="hiddenx">
Lorem ipsum dolor sit amet, consectetur adipisicing elit. Explicabo pariatur facilis molestiae recusandae vel facere et magnam praesentium veniam numquam eius distinctio dicta nostrum voluptatum doloribus. Optio quidem voluptatum quaerat.<br>
Tempore quas omnis a suscipit doloremque voluptatibus sit non aliquid at accusamus quam vero culpa quisquam aut placeat vel odit. Pariatur architecto placeat nesciunt eveniet dolore eius voluptatibus ab fuga.<br>
Necessitatibus deleniti incidunt rerum quia magni quidem cum sequi veritatis nostrum aliquam sapiente blanditiis laudantium nobis atque ab ipsum temporibus qui! Nihil eligendi fuga aliquam ullam voluptates pariatur ducimus delectus.<br>
Fuga unde illum nisi suscipit esse eveniet quasi eligendi ipsam quia enim voluptates quis architecto omnis cum fugit ab sed culpa pariatur nulla minima voluptate dolorum incidunt accusantium officiis totam.<br>
Nostrum optio sed quam vitae ducimus at aut reprehenderit amet voluptatum dolor doloribus temporibus perferendis veniam magnam illum nulla natus quibusdam repellat. Id aut aperiam qui iste quasi. Sed dolor.<br>
Ratione ad aliquid mollitia ipsam dignissimos aperiam ullam odit earum enim odio adipisci qui non dicta culpa hic facere explicabo. Obcaecati doloribus eligendi voluptatibus placeat voluptas labore quisquam esse sunt.<br>
Mollitia minima nesciunt maiores laboriosam voluptas at quidem necessitatibus repellat vel ullam. Ex ut qui mollitia pariatur illum iste hic rem provident quo ab maiores officiis harum repudiandae doloribus ipsa!<br>
Alias tenetur repudiandae voluptatibus magni eligendi assumenda veritatis suscipit earum ea. Ipsam quibusdam accusantium beatae corrupti quisquam aut quae tempore autem ut dolorem voluptatibus sunt saepe dolor laborum voluptates eum.<br>
Est sapiente earum animi facilis harum voluptatem non nobis ullam aliquam voluptate. Ipsum debitis doloremque harum molestiae non laborum dolorum illo facilis itaque quia excepturi quas sit nulla labore modi.<br>
Eveniet dolorem facilis cumque beatae pariatur explicabo iste enim consequuntur sunt omnis possimus itaque dolor! Ex quas eveniet inventore quisquam sed fugit sequi obcaecati dolore optio libero perferendis natus aliquid.<br>
Mollitia minima nesciunt maiores laboriosam voluptas at quidem necessitatibus repellat vel ullam. Ex ut qui mollitia pariatur illum iste hic rem provident quo ab maiores officiis harum repudiandae doloribus ipsa!<br>
Alias tenetur repudiandae voluptatibus magni eligendi assumenda veritatis suscipit earum ea. Ipsam quibusdam accusantium beatae corrupti quisquam aut quae tempore autem ut dolorem voluptatibus sunt saepe dolor laborum voluptates eum.<br>
Est sapiente earum animi facilis harum voluptatem non nobis ullam aliquam voluptate. Ipsum debitis doloremque harum molestiae non laborum dolorum illo facilis itaque quia excepturi quas sit nulla labore modi.<br>
Eveniet dolorem facilis cumque beatae pariatur explicabo iste enim consequuntur sunt omnis possimus itaque dolor! Ex quas eveniet inventore quisquam sed fugit sequi obcaecati dolore optio libero perferendis natus aliquid.<br>
</textarea>

<p>
    <a href="#" class="btn btn-primary j-open-modal01">Janela Modal com barra de rolagem</a>
    <br><br><br>
    
    <a href="#" class="btn btn-primary j-open-form01">Form com editor de textos</a>
    <br><br><br>
    
    <a href="#" class="btn btn-primary" onclick="awLogin(function(){ alert('logado'); },{iconClose:true});return false;">Ex de tela de login por ajax</a>
    <br><small>Esta função é chamada automaticamente quando é encerrada a sessão, mas a página continua aberta no navegador</small>
</p>


<script>
(function(){
    
    //exemplo de janela modal com barra de rolagem
    $('.j-open-modal01').on('click',function(e){
        e.preventDefault();
        awModal({title:'titulo',html:$('textarea').val(),height:'hmax'});
    });
    
    //exemplo de form com editor de textos dentro de janela modal
    $('.j-open-form01').on('click',function(e){
        e.preventDefault();
        awAjax({
            type:'GET',url:'{{route("super-admin.app.get",["example","ajax_form01"])}}',dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                };
                var oModal=awModal({title:'Formulário 01',html:_fLoad,btClose:false});
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
        
    });
}());
</script>

@endsection