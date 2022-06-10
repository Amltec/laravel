<?php /*
Esta pasta armazena as classes responsáveis por retornar ao número da proposta de cada pdf da proposa.
Lógica:
    1) Através do processo de revisão do robô (arquivo robot-review.au3) é acessado o pdf da proposta do respectivo ID em processamento
    2) É extraído o texto deste pdf e enviado uma requisição de extração de dados pela url app\Http\Controllers\WSRobotController -> get_data() -> ProcessCadApoliceController -> wsrobot_getData() -> $field = insurer_data
    3) Esta função chama o respectivo arquivo da seguradora desta pasta extrai que irá extraír o número da proposta e retorna ao json de dados
