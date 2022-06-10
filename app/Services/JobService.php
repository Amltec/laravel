<?php

namespace App\Services;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Exception;


/**
 * Armazena uma classe para ser executada na fila de processos (queue)
 * Exemplos:
 *       JobService::send( new \App\Mail\TestMail() )
 *          Obs: é disparado na classe TestMail() o método ->JobStart()
 *          Ex: class TestMail{ public function JobStart(){ \Illuminate\Support\Facades\Mail::send($this); }  }
 */
class JobService implements ShouldQueue{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * Envia a classe para ser executada na fila
     * @param array $opt - valores:
     *      tries       - número máximo de tentativas. Default 3
     *      retry_after - tempo em minutos da segunda tentativa em diante. Default 1
     */
    public static function send($class,$opt=[]){
        return self::dispatch($class,$opt);
    }
    
    
    //*********** *********** *********** 
    //os métodos abaixo são para as execuções de filas (queues) e são acionados pelo comando $this->dispatch() acima
    //*********** os métodos abaixo são para as execuções de filas (queues) e são acionados pelo comando $this->dispatch() acima ***********
    
    public $tries = 3;        //limite de tentativas
    public $retryAfter = 1;   //minutos para nova tentativa
    public $class_sel;
    
    public function __construct($class_sel=null,$opt=[]) {
        $this->class_sel = $class_sel;
        if(isset($opt['tries']))$this->tries = $opt['tries'];
        if(isset($opt['retry_after']))$this->retryAfter = $opt['retry_after'];
    }
    
    public function handle(){
        if($this->class_sel){
            try{
                $this->class_sel->JobStart();
            }catch(Exception $e){
                if($this->attempts()>0 && $this->retryAfter>0)$this->release( $this->retryAfter * 10);
            }
        }
    }
    
    /*analisando...
    public function retryAfter(){   
        //tempo limite linear a cada N segundos
        return now()->addSeconds($this->attempts() * 30);

        //aumenta o tempo limite exponencialmente a cada tentativa com falha e até estourar o limite de tentativas
        //return now()->addSeconds((int) round(((2 ** $this->attempts()) - 1 ) / 2);
    }*/
    
    
}