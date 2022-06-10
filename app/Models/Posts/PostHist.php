<?php

namespace App\Models\Posts;
use App\Models\Base\ModelMultipleKeys;


class PostHist extends ModelMultipleKeys{
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
    
    protected $fillable = ['id','post_id','action','log_data','user_id','created_at'];
    protected $table = 'post_hist';
    protected $primaryKey = ['id','post_id'];
}
