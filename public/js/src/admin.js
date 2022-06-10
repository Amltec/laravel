/*
Painel administrativo do sistema
Funções do arquivo:
    DashboardMenu()
    awFilemanager()
    awNotificationsInit()
Requer:
    
*/


function DashboardMenu(cmd){//funções padrões de dashboard
    if(cmd=='active_menu'){
        /*$('#main-sidebar li.active').each(function(){
            $(this).closest('.treeview').addClass('active menu-open');//expande menu pai
        });*/
        $('#main-sidebar li.active').removeClass('active')
            .last().addClass('active')//foca sempre no último
            .closest('.treeview').addClass('active menu-open');//expande menu pai
    }
}



/**
 * Abre a janela de gerenciador de arquivos através de uma janela modal
 * Sem retorno
 */
function awFilemanager(opts){
    var opt = $.extend(true,{
        controller:null,        //nome do controller de gerenciamento de arquivos
        private:false,          //indica se o acesso será a uma pasta privada
        area_name:'',           //filtro por area_name
        area_id:'',             //filtro por area_id
        metadata:null,          //json{meta_name:meta_value,...}
        meta_name:null,         //o mesmo de metadata
        meta_value:null,        //o mesmo de metadata
        thumbnails:null,
        in_trash:null,
        allow_trash:null,
        ref_id:null,            //id de referência do cadastro atual
        tax_id:null,            //string id tax //array ou string (para mais de um valor, separar por virgula)
        folder:'uploads',       //nome da pasta base (+ informações na documentação da tabela 'files')
        filetype:'',            //tipos de arquivos filtrados a serem exibidos. Valores: '' (todos) image, audio, video (para mais de um valor separar por virgula)
        //mimetype:'',          //filtro por mimetype (para mais de um valor separar por virgula)
        accept:'',              //filtro por accept para o upload
        q:'',                   //texto para pesquisa de arquivos
        multiple:false,         //se true permite a seleção de vários arquivos
        onSelectFile:null,      //callback ao selecionar as imagens
        show_folder:true,       //se false - irá ocultar o filtro de patas
        show_trash:true,        //se false - oculta a opção de exibir registros da lixeira
        show_regs:true,         //se false - oculta a opção de registros por página 
        show_view_img:true,     //se false - oculta a opção de registros por página 
        show_remove:true,       //se false - oculta a opção de remove
        show_upload:true,       //se false - oculta a opção de upload
        param_cb:null           //parâmetros a serem retornados pelo evento onSelectFile
        //... demais parâmetros informados pelo arquivo templates.ui.files_list
    },opts);
    if(!opt.controller){alert('awFilemanager() - Controller inválido');return;};
    
    //controle para evitar múltiplos clicks a aberturas de janelas simultãneas
    if(typeof awFilemanager.ctrl == 'undefined')awFilemanager.ctrl=false;
    if(!awFilemanager.ctrl){
        awFilemanager.ctrl=true;
        setTimeout(function(){ awFilemanager.ctrl=false },1000);//
    }else{
        return;
    };

    
    var data=$.extend(true, {}, opt);//duplicate object
    data.load_type='modal';
    delete data.onSelectFile;
    delete data.param_cb;
    //console.log(opt)
    awAjax({
        type:'GET',url:admin_vars.route_file_modal.replace('@controller',opt.controller),dataType:'html',
        data:data,
        processData:true,
        success: function(r){
            var ctrl_id = Math.random().toString(36).substr(2, 4);
            var _fLoad=function(oHtml){
                oHtml.html(r);

                //captura o objeto da lista de dados
                var oList = oHtml.find('[ui-listdata]');

                //resize da janela
                var _fResize=function(){
                    var bs=oList.find('.table-responsive > table:eq(0)');
                    //console.log(bs[0])
                    var contentHeight = $(window).height();
                    var headerHeight  = 136;    //altura do topo (margin + modal + toolbar)
                    var footerHeight  = 98;     //altura da base (margin + modal + footer)
                    var maxHeight     = contentHeight - (headerHeight + footerHeight);
                    bs.css({'height': maxHeight,'overflow-y': 'auto'});
                };
                _fResize();
                $(window).on('resize.awFilemanager_'+ctrl_id,_fResize);
                
                if(opt.onSelectFile)oList.on('onSelectFile',function(e,files){//ao clicar no botão de selecionar
                    //monta as strings de retorno
                    var urls=[],ids=[],count=awCount(files);
                    if(count>0){
                        for(var i in files){
                            urls.push(files[i].file_url);
                            ids.push(i);
                        };
                        urls=urls.join(',');
                        ids=ids.join(',');
                    }else{
                        urls='';ids='';
                    };
                    callfnc(opt.onSelectFile,{
                        count:count,
                        files:files,
                        param_cb: opt.param_cb,//repassa os mesmos parâmetros de entrada
                        urls:urls,
                        ids:ids
                    });
                });
                
            };
            var oModal=awModal({title:false,btClose:false,padding:false,xheight:'h100',html:_fLoad,width:'wmax'});
                oModal.on('shown.bs.modal', function(){
                    $(document.body).addClass('modal-filemanager'); 
                });
                oModal.on('hide.bs.modal', function(){
                    $(window).off('resize.awFilemanager_'+ctrl_id);
                    $(document.body).removeClass('modal-filemanager');
                    awUploadProgress({close:true});
                });

        },
        error:function (xhr, ajaxOptions, thrownError){
            awModal({title:'Erro interno de servidor',html:xhr.responseText,msg_type:'danger',btSave:false});
        }
    });
};


/*
//Em desenvolvimento
//Notificações - initialização
function awNotificationsInit(){
    var li=$('#notifications-menu');
    awAjax({type:'GET',url: admin_vars.url_app + '/notifications/getdata',processData: true,
        success: function(j){
            if(!j)return;
            var x=awCount(j);
            var r='<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bell-o"></i><span class="label label-danger">'+ x +'</span></a>'+
                    '<ul class="dropdown-menu">'+
                        '<li class="header"><small>Você tem '+ x +' '+ (x=='1'?'notificação':'notificações') +'</small></li>'+
                        '<li>'+
                            '<ul class="menu scrollmin" style="max-height:300px">';
                            for(var i in j){
                                //r+='<li><a href="'+ j[i].url +'"><i class="fa fa fa-circle-o text-green"></i> '+ j[i].title +' <span style="position:absolute;left:33px;font-size:11px;"><br>'+ j[i].date +'</span></a></li>';
                                r+='<li><a href="'+ j[i].url +'"><i class="fa fa fa-circle-o text-green"></i> '+ j[i].title +' <span style="font-size:11px;display:block;margin-left:22px;">'+ j[i].date +'</span></a></li>';
                            };
                        r+= '</ul>'+
                        '</li>'+
                        '<li class="footer"><a href="'+ admin_vars.url_app + '/notifications/all' +'">Visualizar todos</a></li>'+
                    '</ul>'+
                    '';
            li.show().html(r);
        }
    });
};

setTimeout(awNotificationsInit,0);
*/