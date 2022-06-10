@extends('templates.admin.index')

@section('title')
(DEV) Teste de Extração de Arquivos PDF
@endsection

@section('content-view')


@include('templates.ui.auto_fields',[
    'metabox'=>true,
    'form'=>[
        'id'=>'form1',
        'url_action'=>route('super-admin.app.post',['dev','pdftextTest']),
        'files'=>true,
        'data_opt'=>[
            'btAutoUpload'=>'[name=file]',
            'onStart'=>'@uploadStart',
            'onSuccess'=>'@uploadSuccess',
        ],
    ],
    'layout_type'=>'horizontal',
    'autocolumns'=>[
        'pdf_engine'=>['type'=>'select','label'=>'Recurso de extração','list'=>['auto'=>'Automático'] + \App\ProcessRobot\VarsProcessRobot::$pdfEngines,'class_group'=>'require'],
        'file'=>['type'=>'upload','label'=>'Arquivo PDF','class_button'=>'primary','value'=>'Upload','accept'=>'application/pdf','info_html'=>'<small>Sintaxe para arquivos com senha <code>{cpf-cnpj}--arquivo.pdf</code>. <br>Ex: <code>123.456.789-01--arquivo.pdf</code></small>'],
        'action'=>['type'=>'hidden','value'=>'upload'],

        'data-opt'=>['type'=>'hidden','value'=>
            json_encode([
                //'filename'=>'pdftext-test-tmp.pdf',
                'folder'=>'tmp/dev',
                'account_off'=>true,
            ])
        ],

    ]
])

<div id="div-result" class="div-result">
    Arquivo: <span id="label_filename" class="strong"></span> <span class="space"></span> Extração PDF: <span id="label-pdf-engine" class="strong"></span>
    <textarea class="div-result-in" onclick="this.select();" readonly="readonly"></textarea>
</div>

<style>
.space{display:inline-block;width:30px;}
.div-result{display:none;}
.div-result-in{background:#222;color:#00d95a;padding:10px 15px;white-space:pre;margin-top:5px;font-family:monospace;font-size:13px;display:block;overflow:hidden;width:100% !important;resize:none;}
</style>
<script>
function uploadStart(){
    $('#div-result').hide();
}

function fLoad_fileExtractText(id,oText){//para pdf_engine=='ait_...'
    oText.css({opacity:0}).animate({opacity:1},'fast');
    awAjax({
        url: '{{route('super-admin.app.post',['dev','pdftextTestLoad'])}}',
        data: {id:id},
        processData:true,
        success: function(r){
            if(r.processing){//continua aguardando
                oText.val(r.msg);
                setTimeout(function(){
                    fLoad_fileExtractText(id,oText);
                },5*1000);
            }else if(r.success){
                oText.val(r.text);
                console.log(r)
            }else{
                oText.val(r.msg);
                alert(r.msg);
                console.log(r)
            }
            oText[0].style.height = "1px";
            oText[0].style.height = (25+oText[0].scrollHeight)+"px";
        },
        error:function (xhr, ajaxOptions, thrownError){
            alert('Erro interno de servidor - tentando novamente em 30 segundos');
            setTimeout(function(){
                fLoad_fileExtractText(id,oText);
            },30000);
        }
    });
};

function uploadSuccess(r){
    if(!r.success)return;

    var oText = $('#div-result').hide().fadeIn().find('.div-result-in').val(r.html);
    oText[0].style.height = "1px";
    oText[0].style.height = (25+oText[0].scrollHeight)+"px";
    $('.alert').hide();
    $('#label_filename').html(r.filename);
    $('#label-pdf-engine').html(r.pdf_engine);

    if(r.pdf_engine.substring(0,4)=='ait_'){
        var id=r.file_extract_text_id;
        console.log('*** Process ID: '+ id +' ***');
        fLoad_fileExtractText(id,oText);
    }
    setTimeout(function(){awObjUploadProgress.base.hide();},10);
}

function selectText(element){
	//Seleciona o texto em uma div
    var doc = document;
    if (doc.body.createTextRange) {
        var range = document.body.createTextRange();
        range.moveToElementText(element);
        range.select();
    } else if (window.getSelection) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(element);
        selection.removeAllRanges();
        selection.addRange(range);
    }
};
</script>

@endsection
