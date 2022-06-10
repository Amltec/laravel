/**
 * Editor CKEditor
 * Funções complementares de AdminLTE-2.4.5/bower_components/ckeditor/ckeditor.js
 * Funções do arquivo:
 *      ckeditorAWLoad()
 *      ckeditorAWTemplate()
 *      
 *  Variáveis globais:
 *      array ckeditor_fnc_load     - adicionar uma função ao ser executada ao carregar o editor. Ex: ckeditor_load_fnc.push(function(editor,options){ ... })
 */
var ckeditor_fnc_load=[];

//Função de inicialização para cada instância carregada do editor
function ckeditorAWLoad(options){//esperado: form, field
    var oForm=options.form;
    var o=options.field;
    var opt=o.attr('data-editor-opt');if(opt)opt=$.parseJSON(o.attr('data-editor-opt'));if(!opt)opt={};
    var extraPlugins=opt.extraPlugins;
    var template=opt.template;
    opt=$.extend(true,opt,ckeditorAWTemplate(template));
    if(extraPlugins)opt.extraPlugins+=','+extraPlugins;
    delete opt.template;
    //ajusta a url do filemanager do editor
    //console.log(opt.filemanager)
    if(opt.filemanager!==false){
          var rfm=admin_vars.route_file_modal.replace('@controller', (opt.filemanager?(opt.filemanager.controller??'files'):'files') );
          var rfm_qs={};
          if(typeof(opt.filemanager)=='object')rfm_qs=$.extend(true,{},opt.filemanager);
          rfm_qs.source='ckeditor';
          rfm_qs=$.param(rfm_qs);
          opt.filebrowserBrowseUrl=addQS(rfm,rfm_qs,'string');
          opt.filebrowserImageBrowseUrl=addQS(rfm,rfm_qs+'&filetype=image','string');
    };
    
    let oCk;
    if(template=='inline'){
        CKEDITOR.disableAutoInline = true;
        oCk = CKEDITOR.inline(o.attr('id'),opt);
    }else{
        oCk = CKEDITOR.replace(o.attr('id'),opt);
    };
    
    //adiciona a var de parâmetros ao editor
    oCk.__opt={
        mention:opt.mention,
        template:template
    };
    
    let oCkGr = o.closest('.form-group');
        oCk.on('change',function(){
            if(oCkGr.hasClass('has-error')){
              oCkGr.removeClass('has-error'); 
              o.nextAll('.help-block').html('');
            };
            o.val(this.getData()); //correção para atualização instantânea do textarea
        });
        oCk.on('contentDom',function(e2){
            oCk.document.on('keydown',function(e){
              e=e.data.$;
              var k=e.keyCode;
              var c=e.ctrlKey;
              if((c && k==82) || k==116 )e.preventDefault();//f5 ctrl r
              if(c & k==83){//ctrl+s
                  e.preventDefault();
                  oForm.trigger('save');
              }
            });
        });
    
    ;
    oCk.on("instanceReady",function(e){
        let oCk=this;
        let opt=oCk.__opt;//opt.mention
        let mentionConfig=$.extend(true,{},opt.mention);
        
        //**** mention ****
        if(opt.mention){
            if(opt.template=='inline'){
                oCk.document.getBody().$.contentEditable = true;
                awMention(oCk.document.getBody().$,mentionConfig);
            }else{
                mentionConfig.iframe=oCk.window.getFrame().$;
                oCk.on('mode', function(e){//ao alterar o modo
                    if(oCk.mode!='source'){
                        awMention(oCk.document.getBody().$,mentionConfig);
                    }else{
                        awMention( $(oCk.container.$).find(".cke_source") );
                    }
                })
                awMention(oCk.document.getBody().$,mentionConfig);
            }
        }
    });
    
    //carrega funções externas
    if(ckeditor_fnc_load){
        for(let j in ckeditor_fnc_load){
            if(typeof(ckeditor_fnc_load[j])=='function')ckeditor_fnc_load[j].call(null,oCk,options);
        };
    }
    
    
    
    /*
    CKEDITOR.on('dialogDefinition',function(ev){//see: https://ckeditor.com/docs/ckeditor4/latest/examples/devtools.html
        var dialogName = ev.data.name;
        var dialogDefinition = ev.data.definition;

        if(dialogName=='link') {
            var infoTab = dialogDefinition.getContents('info');
            infoTab.get('link');
            infoTab.get('browse')['style']='display:none;';
        }
    });*/
};



//Carrega os templates de configurações. Valores template: short, text, short_text, (undefined) padrão
function ckeditorAWTemplate(template){
    var opt={};
    opt.extraPlugins = 'colorbutton';
    if(template){
        if(template=='short'){
            opt.removeButtons='Anchor,Styles';
            opt.toolbarGroups=[
                {name: 'basic1',groups:['basicstyles','links','insert','cleanup','list', 'indent', 'format']},
                {name: 'styles',groups:['Format']},
                {name: 'colors'},
            ];
            
        }else if(template=='text' || template=='text_short'){
            opt.removeButtons='Anchor,Styles';
            opt.toolbarGroups=[
                {name: 'basic1',groups:['basicstyles','links','cleanup','list', 'indent', 'format']},
                {name: 'styles',groups:['Format']},
            ];
        
        }else if(template=='inline'){
            //nenhuma ação
        }
        
    }else{//template default
          opt.toolbarGroups=[
              {name:'clipboard',groups:['clipboard','undo']},
              {name:'editing',groups:['find','selection','spellchecker','editing']},
              {name:'links',groups:['links']},
              {name:'insert',groups:['insert']},
              {name:'tools',groups:['tools']},
              {name:'document',groups:['mode']},
              '/',
              {name:'basicstyles',groups:['basicstyles','cleanup']},
              {name:'paragraph',groups:['list','indent','blocks','align','bidi','paragraph']},
              {name:'styles',groups:['styles']},
              {name:'colors',groups:['colors']},
              {name:'others',groups:['others']},
          ];
          opt.removeButtons='Save,Templates,Find,SelectAll,Scayt,NewPage,Preview,Print,Checkbox,Radio,TextField,Textarea,Form,HiddenField,ImageButton,Button,Select,CreateDiv,BidiRtl,JustifyCenter,CopyFormatting,JustifyLeft,BidiLtr,Iframe,PageBreak,Smiley,Flash,JustifyRight,JustifyBlock,Language,About,ShowBlocks,Font,Replace';
    };
    return  opt;
};
