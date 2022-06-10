<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;

/**
 * Personalização da model para o ProcessRobo para 'seguradora_files'
 */
class ProcessRobot_SeguradoraData extends Base\ProcessRobot{
    private static $basename='seguradora_data';
    
    
    //**** global scope ****
    protected static function boot(){
        parent::boot();
        static::addGlobalScope('basename', function (Builder $builder){
            $builder->where('process_robot.process_name',self::$basename);
        });
    }
    
    
    //**** Funções *****
    //retorna ao caminho da pasta para envio dos arquivo via ftp
    public function getPaths(){
        $p = $this->baseDir(false);
        $id = $this->attributes['id'];
        $p['dir'].=DIRECTORY_SEPARATOR . $id;
        
        $robot_upload = 'upload_robo' . DIRECTORY_SEPARATOR . $p['relative_dir'] . DIRECTORY_SEPARATOR . $id;
        return [
            //obs: abaixo dir e dir final vão ter sempre o mesmo valor (motivo para facilitar na programação pois em baseDir já existe este campo como dir_final)
            'dir'=>$p['dir'],
            'dir_final'=>$p['dir'],
            //caminho do upload por ftp do app autoit
            'upload_robo'=>storage_path() . DIRECTORY_SEPARATOR . $robot_upload,
            'relative_upload_robo'=>$robot_upload,
        ];
    }
    
     
    //********** relaciomentos ***********
    
    //com a tabela pr_seguradora_data: uma relação de 'processo' tem muitos 'prSeguradoraData' - relacionamento (1-N)
    public function prSeguradoraData(){
        return $this->hasMany(PrSeguradoraData::class,'process_id','id');
    }
    
}
