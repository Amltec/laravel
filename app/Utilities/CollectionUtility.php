<?php

namespace App\Utilities;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as PaginationPaginator;
use Illuminate\Support\Collection;

/**
 * Extensão da classe Collection (from helper collect)
 * Exemplo:
 *      $a = new CollectionUtility([$arr]);  //$arr - [[a=>,b=>,...],....]       ou  [current_page=>,data=>[[a=>,b=>,...]],....]
 *      $list = $a->paginate(15);
 */
class CollectionUtility extends Collection{
    private $list;

    public function __construct(Array $list){
        if(isset($list['data']) && isset($list['current_page'])){
            $this->list=$list;
            $list=$list['data'];

        }else{//$list - contem todo o array
            $this->list=['data'=>null,'total'=>count($list)];
        }

        //verifica se os itens da lista são objectos
        foreach($list as &$reg){
            if(is_array($reg)){
                $reg=(object)$reg;
            }
        };
        $this->list['data'] = $list;

        return parent::__construct($list);
    }

    public function paginate($perPage, $total = null, $page = null, $pageName = 'page'){
        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?? $this->list['per_page'];
        $total = $total ?? $this->list['total'] ?? $this->count();

        //dd($this->count(),$total,$perPage,$page);

        $page_tmp=$page;
        if($this->count()<=$perPage){//quer dizer que não tem mais registros além do exposto na página atual, e portanto não deve ser feito a paginação nesta matriz
            $page_tmp=1;
        }

        return new LengthAwarePaginator(
            $this->forPage($page_tmp, $perPage),
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

}
