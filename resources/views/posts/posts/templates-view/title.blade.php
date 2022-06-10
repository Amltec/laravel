@php

echo '<h1 class="post-title" style="margin-top:0;">'. $post->post_title . '</h1>';
if($is_resume??false)echo '<p class="post-resume strong">'. $post->post_resume_format .'</p>';

@endphp