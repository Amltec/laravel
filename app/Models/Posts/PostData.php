<?php

namespace App\Models\Posts;
use App\Models\Base\ModelMultipleKeys;


class PostData extends ModelMultipleKeys{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $fillable = ['post_id','meta_name','meta_value'];
    protected $primaryKey = ['post_id','meta_name'];
    protected $table = 'post_data';
}
