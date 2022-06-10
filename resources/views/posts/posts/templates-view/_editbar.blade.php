@php
if($thisClass->allow($post,'edit')){
    $link = $post->post_folder_id ? route($prefix.'.app.gets',[$post->post_type,$post->post_folder_id,'edit',$post->id]) : route($prefix.'.app.gets',[$post->post_type,'edit',$post->id]);
    echo '<a href="'. $link  .'" class="post-bt-edit btn btn-link"><i class="fa fa-pencil margin-r-5"></i> Editar</a>';
}
@endphp