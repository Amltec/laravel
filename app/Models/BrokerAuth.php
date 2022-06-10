<?php

namespace App\Models;
use App\Models\Broker;
use App\Models\Traits\AccountTrait;

/**
 * Classe de corretores somente contas logadas (filtra usuário por conta logada).
 * Lógica: para todas as operações de filtros (where), é filtrado considerando o id da conta logada 
 */
class BrokerAuth extends Broker {
    use AccountTrait;
}
