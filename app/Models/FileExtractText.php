<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Classe da tabela de arquivos pdf para extração de texto por orc (pelo robô AutoIt).
 */
class FileExtractText extends Model{
    public $timestamps = false;
    protected $table  = 'files_extract_text';
    protected $fillable = ['file_url','file_path','area_name','area_id','created_at','engine','callback','status','file_text','locked_at','pass'];
}
