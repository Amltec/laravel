@extends('templates.admin.index')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/paragraph@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/embed@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/delimiter@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/list@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/checklist@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/editorjs-table@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/editorjs-text-color-plugin"></script>
@endpush



@section('title')
Editorjs.io - Editor de Conteúdo - <span style='color:red'>em desenvolvimento</span>
@endsection


@section('content-view')
<a href="https://editorjs.io/" target="_blank">https://editorjs.io/</a>
<script>
var dataInit = {
   "time": 1550476186479,
   "blocks": [
      {
         "type": "header",
         "data": {
            "text": "Editor.js",
            "level": 2
         }
      },
      {
         "type": "paragraph",
         "data": {
            "text": "Hey. Meet the new Editor. On this page you can see it in action — try to edit this text. Source code of the page contains the example of connection and configuration."
         }
      },
      {
         "type": "header",
         "data": {
            "text": "Key features",
            "level": 3
         }
      },
      {
         "type": "list",
         "data": {
            "style": "unordered",
            "items": [
               "It is a block-styled editor",
               "It returns clean data output in JSON",
               "Designed to be extendable and pluggable with a simple API"
            ]
         }
      },
      {
         "type": "header",
         "data": {
            "text": "What does it mean «block-styled editor»",
            "level": 3
         }
      },
      {
         "type": "paragraph",
         "data": {
            "text": "Workspace in classic editors is made of a single contenteditable element, used to create different HTML markups. Editor.js <mark class=\"cdx-marker\">workspace consists of separate Blocks: paragraphs, headings, images, lists, quotes, etc</mark>. Each of them is an independent contenteditable element (or more complex structure) provided by Plugin and united by Editor's Core."
         }
      }
   ],
   "version": "2.8.1"
}
</script>
    <div class="container">
        <div id="editorjs"></div>
    </div>
    <!script src="{{asset('js/editorjs.js')}}"></!script>
    
    <button id='btsave' class="btn btn-primary">Salvar</button>





<script>
//### em desenvolvimento ###

//https://www.jsdelivr.com/?query=%40editorjs%20code
const editor = new EditorJS({
    holder: 'editorjs',
    tools:{
        header:{class:Header,inlineToolbar: true},
        delimiter: Delimiter,
        list:{class:List,inlineToolbar: true,},
        checklist: Checklist,
        //quote: Quote,
        code: CodeTool,
        //linkTool: LinkTool,
        inlineCode: InlineCode,
        embed: Embed,
        table: {class:Table,inlineToolbar: true},
        image: {
            class:ImageTool,
            inlineToolbar: true
        },
        paragraph: {class: Paragraph,inlineToolbar: true},
        
        Color:{
            class: ColorPlugin,
            config:{
                colorCollections: ['#FF1300','#EC7878','#9C27B0','#673AB7','#3F51B5','#0070FF','#03A9F4','#00BCD4','#4CAF50','#8BC34A','#CDDC39', '#FFF'],
                defaultColor: '#FF1300',
                type: 'text', 
            }
        },
        Marker: {
            class: ColorPlugin, // if load from CDN, please try: window.ColorPlugin
            config: {
               defaultColor: '#FFBF00',
               type: 'marker', 
            }       
        },
   },
   placeholder: "Digite seu texto aqui",
   //autofocus: true,
   //readOnly: true,
   data:dataInit,
   
});



$().ready(function(){
    $('#btsave').on('click',function(){
        console.log('starting...')
        editor.save().then((output) => {
            console.log('Data: ', output);
        }).catch((error) => {
            console.log('Saving failed: ', error)
        });
    });
});    
</script>
@endsection