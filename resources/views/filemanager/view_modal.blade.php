@include('templates.components.metabox',[
     'is_border'=>false,
     'content'=>function() use($file, $controller){
        echo view('templates.ui.files_view',[
            'controller'=>$controller,
            'file'=>$file,
            //'onRemove'=>'@function(r){ if(r.success){window.location.reload();}else{alert(r.msg);} }',
            //'onRemove'=>'@function(r){ console.log(123,r) }',
            //'fields'=>'title,size,link'
            //'bt_remove'=>false
        ]);
    }
])
