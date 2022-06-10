@php
use App\Utilities\HtmlUtility;

$content_type = $post->post_content_type;


echo '<div class="post-content ctype-'.$content_type.'" data-ctype="'.$content_type.'">';
        
        if($content_type=='t'){//texto puro
            $r = htmlentities($post->post_content_format);
            $r = str_replace(chr(13),chr(10),$r);
            $r = str_replace(chr(10).chr(10),chr(10),$r);
            $r = str_replace(chr(10),'<br>',$r);
            echo $r;
            
        }else if($content_type=='h'){//html
            echo $post->post_content_format;
            
        }else if($content_type=='m'){//markdown
            echo HtmlUtility::markdown($post->post_content_format);
            
        }else if($content_type=='b'){//pagebuilder (desenvolvimento)
            echo $post->post_content_format;
        }
        
            
echo'</div>';
    
@endphp