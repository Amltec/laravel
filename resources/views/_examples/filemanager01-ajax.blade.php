@extends('templates.admin.index')


@section('title')
Gerenciador de Arquivos - Abrindo em Janela Modal
@endsection


@section('content-view')
Abre a janela de gerenciador de arquivos dentro de uma janela modal.


<br><br><br><br>
<p>
    <a href="#" class="btn btn-primary j-open-filemanager">Ex de Gerenciador de Arquivos - Código Manual</a> 
    
    <br><br>******************************<br><br>
    
    
    <a href="#" class="btn btn-primary j-open-filemanager-system">Ex de Gerenciador de Arquivos - Padrão do Sistema</a><br><small>Analise o código para testar os parâmetros</small><br><br>
</p>




<script>
(function(){
    
    //exemplo de filenamager customizado - código manual com exemplo de como criar
    $('.j-open-filemanager').on('click',function(e){
        e.preventDefault();
        awAjax({
            type:'GET',url:'{{route("super-admin.app.get",["example","filemangerModal","?load_type=modal"])}}',dataType:'html',
            success: function(r){
                var _fLoad=function(oHtml){
                    oHtml.html(r);
                    
                    //captura o objeto da lista de dados
                    var oList = oHtml.find('[ui-listdata]');
                    
                    //resize da janela
                    var _fResize=function(){
                        var bs=oList.find('.table-responsive > table:eq(0)');
                        var contentHeight = $(window).height();
                        var headerHeight  = 136;    //altura do topo (margin + modal + toolbar)
                        var footerHeight  = 88;     //altura da base (margin + modal + footer)
                        var maxHeight     = contentHeight - (headerHeight + footerHeight);
                        bs.css({'max-height': maxHeight,'overflow-y': 'auto'});
                    };
                    _fResize();
                    $(window).on('resize',_fResize);
                    /*
                    //evento click
                    oList.on('onOpen',function(e,opt){
                        console.log('custom click row',opt);
                        return false;//return false para anular o click
                    });
                    */
                   
                    oList.on('onSelectFile',function(e,file){
                        alert("Arquivos selecionados veja no console!");console.log("file selected",file);
                    });
                    
                    /*** //obs: neste exemplo, este comando está setado por parâmetro 'file_view'=>'modal' no arquivo ExampleController@get_filemangerModal
                    //exemplo de código de visualização de arquivo por janela modal
                    oList.on('onOpen',function(e,file){
                        awAjax({
                            type:'GET',url: file.url,dataType:'html',
                            success: function(r){
                                awModal({title:false,btClose:false,padding:false,html:r,width:'lg'});
                            },
                            error:function (xhr, ajaxOptions, thrownError){
                                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
                            }
                        });
                        return false;//return false para anular o click
                    });
                    */
                    
                };
                var oModal=awModal({title:false,btClose:false,padding:false,heigxht:'hmax',html:_fLoad,width:'wmax'});
                
            },
            error:function (xhr, ajaxOptions, thrownError){
                awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
            }
        });
        return false;//return false para anular o click
    });
    
    
    
    //exemplo de filenamager customizado - código manual com exemplo de como criar
    $('.j-open-filemanager-system').on('click',function(e){
        awFilemanager({
            controller:'files',
            //multiple:false,
            //private:true,
            //folder:'_system',
            //filetype:'pdf',
            //mimetype:'image/gif',
            //q:'nature',
            //show_folder:false,
            //show_trash:false,
            //show_regs:false,
            //show_view_img:false,
            //show_remove:false,
            //show_upload:false,
            //area_name:'test',
            //area_id:'111',
            //area_status:'',
            //metadata:{field01:'val01'},
            //meta_name:'field01',meta_value:'val01',
            //taxs:[27,15], //cinema
            onSelectFile:function(opt){
                console.log('Arquivos selecionados: ',opt)
            }
        });
    }).click();
    
}());
</script>


@endsection