<?php
namespace App\Http\Controllers;
use App\ProcessRobot\VarsProcessRobot;

/**
 * Classe de status de execução atual do robô para a conta atual logada
 */
class RobotStatusController extends Controller{
    
    /**
     * Página inicial de status do robô válido apenas para a conta logada
     */
    public function index(){
        return view('admin.robot_status');
    }
}
