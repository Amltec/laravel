@extends('templates.admin.index',[
    'dashboard'=>[
        'header'=>false     //desativa o cabeçalho
    ]
])

@section('content-view')

@include('templates.ui.files_list',$list_params)

@if($list_params['click_open_ajax']??false)
<script>
(function(){
    $('.j-filemanager-wrap').on('onOpen',function(e,file){
        e=window.event;
        if(e.ctrlKey || e.shiftKey){
            goToUrl(file.url);
        }else{
            var oItem = file.oTr;//objeto item da lista
            awAjax({
                type:'GET',url: file.url,dataType:'html',
                success: function(r){
                    var oModal=awModal({title:false,btClose:false,padding:false,html:function(oHtml){
                        oHtml.html(r);
                        oHtml.find('.j-btn-remove').on('onRemove',function(e,opt2){
                            if(opt2.success)oItem.fadeOut();//oculta o item da lista
                            oModal.modal('hide');//oculta a janela
                        });
                    },width:'lg'});
                },
                error:function (xhr, ajaxOptions, thrownError){
                    awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
                }
            });
        };
        return false;//return false para anular o click
    })
}());
</script>
<style>
    .content-wrapper{background:#fff;}
</style>
@endif


@endsection