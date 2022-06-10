@php

############
#    <span style='color:red;'>Em análise / desenvolvimento...</span> #
############

$editor_type = $editor_type??'editor';


$editor_id = 'editor-view-wrap';
$prefix = Config::adminPrefix();


echo '<div id="'.$editor_id.'" class="editor-view-wrap">';
    
//*** barra de ferramentas ***


$buttons=[
        'bt_save'=>['type'=>'button','title'=>'Salvar','color'=>'info','icon'=>'fa-save'],
        'file_manager'=>['type'=>'button','title'=>'Arquivos','color'=>'primary','icon'=>'fa-files-o','onclick'=>'editorViewFiles();'],
        'html_split'=>['type'=>'button','title'=>false,'alt'=>'Modo de Visualização','color'=>'primary','icon'=>'fa-columns','onclick'=>'setViewMode("auto");'],
        'edit'=>['type'=>'button','title'=>false,'alt'=>'Modo de Edição','color'=>'primary','icon'=>'fa-edit'],
        'html_pre'=>['type'=>'checkbox','list'=>['on'=>'&lt;BR&gt;'],'id'=>'tool-html_pre','width'=>71],
        'fields'=>['type'=>'select2','list'=>[
            ''=>'',
            'id'=>'ID',
            'nome'=>'Nome',
            'cpf'=>'CPF',
            'ctrl_id'=>'Nº da Apólice',
            'fields'=>'Outros',
        ]],
        'html_view'=>['type'=>'button','title'=>false,'alt'=>'Visualizar','color'=>'primary','class'=>'pull-right margin-right-none','icon'=>'fa-eye','onclick'=>'setViewMode("view");'],
    ];
    
if($editor_type!='editorcode')unset($buttons['html_pre']);

echo view('templates.ui.toolbar',[
    'metabox'=>false,
    'is_filter'=>false,
    'class'=>'ui-toolbar-line form-no-padd',
    'autocolumns'=>$buttons
]);
echo '<br>';



//*** editor ***
echo '<div class="editor-view-table row table-view-split" width="100%">
    <div class="editor-view-td col-sm-6">';
        
        if($editor_type=='editorcode'){
            $edit_params=['type'=>'editorcode','value'=>''];
        }else{//editor
            $edit_params=['type'=>'editor','value'=>'','template'=>'short'];
        }
        
        echo view('templates.ui.auto_fields',[
            'form'=>[
                'url_action' => route('super-admin.app.post',['example','testSaveEditor']),
                'bt_save' => false,
                'class'=> 'editor-view-form',
            ],
            'autocolumns'=>[
                'editor_view'=>$edit_params,
            ]
        ]);

echo '</div>
    <div class="editor-view-td col-sm-6 no-padd-left">
        <div class="editor-view-html"><div class="editor-view-html-in editor-view-page"></div></div>
    </div>
</div>'; //.row
echo '</div>';//.editor-view-wrap



@endphp
<script>
var editor_type='{{$editor_type}}';
var editor=null;
var base=$('#{{$editor_id}}');
(function(){
    var view=base.find('.editor-view-html:eq(0)');
    var viewIn=view.find('>.editor-view-html-in:eq(0)')
        .off('click.editor_view').on('click.editor_view',function(e){
            e.stopPropagation();
            e.preventDefault();
        });
    var fRes=function(){
        var w=window.innerHeight;
        var t=base.offset().top;
        var h=w-t-15 - 54;
        //base.height(h);
        @if($editor_type=='editorcode')
            if(editor)editor.setSize(null, h);
        @else
            if(editor)editor.resize('100%',h);
        @endif
        view.height(h);
    };
    fRes();
    $(window).on('resize',fRes);
    
@if($editor_type=='editorcode')
    $().ready(function(){
        editor=base.find('[name=editor_view]').data('CodeMirror');
        fRes();
        var fch1=function(){
            var v = editor.getValue();
            if(oHtmlPre.prop('checked'))v=v.replace(/\n/g,'<br>');
            setView(v);
        };
        var oHtmlPre=base.find('#form-group-html_pre [name=html_pre]').on('click',fch1);
        editor.on('change',fch1);
    });    
    
@else
    CKEDITOR.on("instanceReady", function(e,a){
        editor=CKEDITOR.instances["editor_view"];
        fRes();
        editor.on('change',function(){
            var v = this.getData();
            setView(v);
        });
    });
@endif
    
    //seta html no visualizador
    function setView(v){viewIn.html(v)};
}());


//seta dados no editor
function editorSetData(v){
    if(editor_type=='editorcode'){setValCodeMirror(v);}else{editor.insertHtml(v);}
}

//abre o gerenciador de arquivos
function editorViewFiles(){
    awFilemanager({
        multiple:true,
        onSelectFile:function(opt){
            var o,r,i;
            for(i in opt.files){
                o=opt.files[i];
                u=o.file_url;
                if(o.is_image){
                    r='<img src="'+o.file_url+'" title="'+o.file_title+'"> \n';
                }else{
                    r='<a href="'+o.file_url+'">'+o.file_title+'</a> \n';
                };
                editorSetData(r);
            }
       }
    });
};

@if($editor_type=='editorcode')
function setValCodeMirror(data){
    var doc = editor.getDoc();
    var cursor = doc.getCursor();
    var line = doc.getLine(cursor.line);
    var pos = {
        line: cursor.line,
        ch: line.length// - 1
    };
    //doc.replaceRange('\n'+data+'\n', pos);
    doc.replaceRange(data, pos);
}
@else
@endif

//modo de visualização - cmd: split, one, view, auto
function setViewMode(cmd){
    var table=base.find('.editor-view-table:eq(0)');
    var tds=base.find('.editor-view-td');
    if(cmd=='auto')cmd=table.hasClass('table-view-split')?'one':'split';
    if(cmd=='one'){
        table.removeClass('table-view-split row');
        tds.removeClass('col-sm-6');
        tds.eq(1).hide();
    }else{//split
        table.addClass('table-view-split row');
        tds.addClass('col-sm-6');
        tds.eq(1).show();
    }
}


</script>
<style>
.editor-view-form .form-block-wrap{padding-right:0;}
.editor-view-form .form-group{margin-bottom:0;}
.editor-view-wrap{}
.editor-view-table{height:100%;}
.table-view-split .editor-view-td{width:50%;}
.editor-view-html-in{height:100%;border:1px solid rgb(209, 209, 209);background:#fff;padding:30px 40px;overflow:auto;position:relative;}

.editor-view-page img{max-width:100%;height:auto !important;}

</style>


