@extends('templates.admin.index',[
    'dashboard'=>[
        'single_page'=>true,
        'white_page'=>true,
    ]
])

@section('content-view')
@include('templates.ui.files_list',$list_params)
<style>
    body{overflow:hidden;}
    .content-wrapper > .content{padding:10px 10px 0 10px;}
</style>
<script>
(function(){
    //resize da janela
    var oList=$('[ui-listdata]:eq(0)')
        .on('onSelectFile',function(e,files){
            for(var i in files){//retorna somente ao primeiro arquivo
                var funcNum = getFieldQS('CKEditorFuncNum');
                window.opener.CKEDITOR.tools.callFunction( funcNum, files[i].file_url );
                window.close();
                break;
            };
        });
    var _fResize=function(){
        var bs=oList.find('.table-responsive > table:eq(0)');
        //console.log(bs[0])
        var contentHeight = $(window).height();
        var headerHeight  = 35;    //altura do topo (margin + modal + toolbar)
        var footerHeight  = 98;     //altura da base (margin + modal + footer)
        var maxHeight     = contentHeight - (headerHeight + footerHeight);
        bs.css({'height': maxHeight,'overflow-y': 'auto'});
    };
    _fResize();
    $(window).on('resize.awFilemanager_ckeditor',_fResize);
}());
</script>
@endsection