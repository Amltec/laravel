/**
 * Editor Jodit - plugins/jodit/*
 * Informações: https://xdsoft.net/jodit/
 * Funções do arquivo:
 *      joditAWLoad()
 */


//Função de inicialização para cada instância carregada do editor
function joditAWLoad(options){
    var form=options.form;
    var field=options.field;
    var opt=field.attr('data-editor-opt');if(opt)opt=$.parseJSON(field.attr('data-editor-opt'));if(!opt)opt={};
    var jodit_opt={
        globalFullSize:true,
    };
    jodit_opt=$.extend(true,jodit_opt,joditAWTemplate(opt.template));
    
    //console.log(opt)
    /*
    var jodit_opt={height:null,};
    //mescla somente as propriedades existentes em jodit_opt
    Object.keys(jodit_opt).map(function(a){ if(opt[a]) jodit_opt[a]=opt[a]});
    */
   
   if(opt.auto_height){
       if(opt.auto_height!==true)jodit_opt.maxHeight=opt.auto_height;
       jodit_opt.minHeight=opt.height;
   }else{
       if(opt.height)jodit_opt.height=opt.height;
   };
   
   if(opt.toolbar_fixed){//obs: não está funcionando
       jodit_opt.toolbarSticky=true;
       jodit_opt.toolbarStickyOffset=50;
   }
   
   if(opt.filemanager!==false){
        //....
    };
   
   console.log('jodit',jodit_opt)
   var editor = new Jodit(field[0],jodit_opt);
};


//Carrega os templates de configurações. Valores template: short, text, short_text, (undefined) padrão
function joditAWTemplate(template){
    var opt={};
    if(template=='xxxx'){
        
    }else{//template default
        opt.buttons='source,|,undo,redo,bold,italic,underline,strikethrough,eraser,|,superscript,subscript,|,ul,ol,indent,outdent,|,align,|,paragraph,fontsize,brush,|,copyformat,cut,copy,paste,selectall,|,image,hr,table,link,symbol,fullsize';
    };
    return opt;
};