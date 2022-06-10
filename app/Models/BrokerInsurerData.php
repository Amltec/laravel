<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BrokerInsurerData extends Model{
    protected $fillable = ['broker_id','insurer_id','meta_name','meta_value'];
    protected $table = 'brokers_insurers_data';
    public $timestamps = false;//desativa o uso automático de campos como created_at e updated_at
}

