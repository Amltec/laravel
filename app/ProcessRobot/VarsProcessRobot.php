<?php

namespace App\ProcessRobot;

/**
 * Classe de variáveis / valores gerais
 * Obs: esta classe não é um controller
 */
class VarsProcessRobot{

    //Nome do diretório das apólices em \public\storage\app\
    public static $folder_upload='apolices';
    public static $path_upload='storage'. DIRECTORY_SEPARATOR .'app'. DIRECTORY_SEPARATOR;


    /* Retorna aos nomes de processos autorizados
     * Sintaxe: process_name.process_prod   - {nome do process}.{nome do produto} , ex: cad_apolice.
     * Obs: abaixo para cada produto, é possível ter o campo 'insurers_allow' que contém a relação de base_names do cadastro de seguradoras (tabela insurers.insurer_basename) aceitas. Caso não informado, será considerado todos.
     * Sintaxe:
     *  {process_name}=> [
     *          title       => título do processo
     *          produtcs    => [        //cada produto/subprocesso
     *              {prod_name}=>[
     *                  title           => título curto do produto/subprocesso
     *                  title_long      => título longo
     *                  blocks          => (array) nomes dos blocos processados pelo robô (existente apenas para process_name='cad_apolice'
     *                  insurers_allow  => (array) nomes base (tabela insurers.insurer_basename) das seguradoras permitidas para este produto. Opcional.
     *                                              Obs: alguns {process_name} podem ter este campo separados por ramo, ex: 'automovel'=[insurers.insurer_basename,...]
     *                  login_mode      => tipo do login para o robô (opcional). Se definido sobrepõe o do campo pai.
     *                  can             => quem pode visualizar informações deste processo. Valores: dev, superadmin, admin, user (está em desenvolvimento)
     *              ]
     *          ],
     *          term_id     => (array)ids dos termos das taxonomias (tabela terms.id)
     *          login_mode  => tipo do login para o robô (opcional).
     *          allow       => quem pode visualizar informações deste processo. Valores: dev, superadmin, admin, user (está em desenvolvimento)
     *  ]
     *
     *  Observações:
     *      1) Campo login_mode - usado em \App\Http\Controllers\WSRobotController - Valores:
     *              '', false ou não definido - não considera o login neste processo
     *              'quiver' (quer dizer que o login é da configuração de usuários do quiver),
     *              'insurer' (quer dizer que a configuração é do cadastro de seguradoras para cada corretor).
     *          É obrigatório pelo menos um campo deste por {process_name}
     */
    public static $configProcessNames = [
        'cad_apolice'=>[
            'title'=>'Cadastro de Apólices',
            'products'=>[
                'automovel'=>[
                    'title'=>'Automóvel',
                    'title_long'=>'Automóvel Individual',
                    'title_cli'=>'Automóvel',
                    'blocks'=>'dados,automovel,premio,parcelas,anexo',     //nomes dos blocos processados no cadastro pelo robô
                    //'insurers_allow'=>[],//todas as seguradoras cadastradas
                ],
                'residencial'=>[
                    'title'=>'Residencial',
                    'title_long'=>'Residencial',
                    'title_cli'=>'Residencial',
                    'blocks'=>'dados,residencial,premio,parcelas,anexo',   //nomes dos blocos processados no cadastro pelo robô
                    'insurers_allow'=>['hdi','tokio','bradesco','liberty','porto','sompo','mapfre','allianz','sancor','mitsui','alfa','zurich'],
                ],
                'empresarial'=>[
                    'title'=>'Empresarial',
                    'title_long'=>'Empresarial',
                    'title_cli'=>'Empresarial',
                    'blocks'=>'dados,empresarial,premio,parcelas,anexo',   //nomes dos blocos processados no cadastro pelo robô
                    'insurers_allow'=>['allianz','hdi','liberty','mapfre','porto','sompo','tokio','bradesco','sancor','mitsui','alfa'],
                ],
                /*'condominio'=>[
                    'title'=>'Condominio',
                    'title_long'=>'Condominio',
                    'blocks'=>'dados,condominio,premio,parcelas,anexo',   //nomes dos blocos processados no cadastro pelo robô
                    'insurers_allow'=>['sompo'],
                ],*/
            ],
            'terms'=>[1],//id dos termos das taxonomias (=terms.id)
            'login_mode'=>'quiver',
        ],
        'seguradora_files'=>[
            'panel_superadmin'=>true,//exibe apenas no superadmin
            'title'=>'Arquivos de Seguradoras',
            'products'=>[
                'down_apo'=>[
                    'title'=>'Download de Apólices',
                ]
            ],
            'login_mode'=>'quiver',
            'allow'=>'superadmin',
        ],
        'seguradora_data'=>[
            'panel_superadmin'=>true,//exibe apenas no superadmin
            'title'=>'Dados de Seguradoras',
            'products'=>[
                'apolice_check'=>[
                    'title'=>'Verificação de Apólices',
                    'title_cli'=>'Verificação de Apólices',
                    'insurers_allow'=>[
                        'automovel'=>['hdi','bradesco'],
                        'residencial'=>[],
                    ],
                    'allow'=>'dev',
                ],
                'boleto_seg'=>[
                    'title'=>'Baixa de Boletos Segs',//baixa de boletos nos sites das seguradoras
                    'title_cli'=>'Boletos Seguradoras',
                    'insurers_allow'=>[
                        'automovel'=>['hdi','bradesco','liberty','tokio','allianz','azul','mapfre','sompo','zurich','alfa','porto','sancor'],
                        'residencial'=>['hdi','liberty','tokio','allianz','azul','mapfre','sompo','alfa'],
                        'empresarial'=>['hdi','liberty','tokio','allianz','azul','mapfre','sompo','alfa'],
                    ],
                ],
                'boleto_quiver'=>[
                    'title'=>'Cad. Boletos no Quiver',//inserção de boleto no Quiver
                    'title_cli'=>'Boletos Quiver',
                    'login_mode'=>'quiver',
                ],
            ],
            'login_mode'=>'insurer',
            'allow'=>'dev',
        ],
    ];


    /**
     * Retorna a lista de tabelas de seguros já configurados no sistema
     */
    public static $tablesSegs=['automovel','residencial','empresarial']; //,,'condominio'

    /* Retorna aos nomes dos tipos das apólices
     */
    public static $typesApolices=[
        'apolice'       =>'Apólice',
        'historico'     =>'Histórico',
        'endosso'       =>'Endosso',
        //'endosso-hist'  =>'Histórico do Endosso',
    ];


    //Label dos nomes das tabelas
    public static $tableNames = [
        'insurer' => ['name'=>'Seguradoras','singular_name'=>'Seguradora'],
        'broker' =>  ['name'=>'Corretores','singular_name'=>'Corretor']
    ];

    //Relação de extratores
    public static $pdfEngines=[
        'pdfparser'=>'(pdfparser) Php: Smalot\PdfParser\Parser',
        'ws02'=>'(ws02) Java: com pdfbox',
        'ait_ocr01'=>'(ait_ocr01) AutoIt com Google Vision OCR',
        'ait_xpdfr'=>'(ait_xpdfr) AutoIt com XpdfReader',
        'ait_ocr01_xpdfr'=>'(ait_ocr01_xpdfr) AutoIt com Google Vision OCR + XpdfReader',
        'ait_ocr01_aws'=>'(ait_ocr01_aws) AutoIt com Google Vision OCR + AWS Textract',
        'ait_ocr01_tessrct'=>'(ait_ocr01_tessrct) AutoIt com Google Vision OCR + Tesseract EXE',
        'ait_aws'=>'(ait_aws) AutoIt com AWS Textract',
        'ait_tessrct'=>'(ait_tessrct) AutoIt com Tesseract exe',

        //métodos desativados
        //'ws01'=>'(ws01) Python: com pdfminer',
        //'ws02_ocr'=>'(ws02_ocr) PHP com Google Vision OCR',
    ];


    //Relação de erros - sintaxe: [code=>text || [short text, long text]]
    //global para todos os casos
    public static $statusCode = [
        'ok'       =>'Finalizado',
        'ok2'      =>'Não alterado',
        'err'      =>'Erro interno',
        'db01'     =>'Erro ao gravar dados no banco',
        'db02'     =>'Nenhum arquivo encontrado',
        'acc01'    =>'Conta cancelada',
        'acc02'    =>'Conta sem serviço configurado',
        'acc03'    =>'Serviço não ativado',
        'ins01'    =>'Seguradora não encontrada',
        'ins02'    =>'Seguradora cancelada',
        'ins03'    =>'Mais de uma seguradora encontrada',
        'ins04'    =>'Seguradora ou produto não programado',
        'ins05'    =>'Seguradora ignorada',
        'bro01'    =>'Corretor não encontrado',
        'bro02'    =>'Corretor cancelado',
        'bro03'    =>'Mais de um corretor encontrado',
        'bro04'    =>'Corretor sem permissão para o produto da apólice',
        //'quitry'   =>'Erro interno do quiver(T)',
        'quiv00'   =>'Erro interno do quiver',
        'quivnn'   =>'Erro por excesso de tentativas',
        'quil01'   =>'Erro no login',
        'quil02'   =>'Login inválido',
        'quil03'   =>'Login bloqueado',
        'quil04'   =>'Login fora do horário de acesso',
        'quil05'   =>'Sessão expirada',
        'quil06'   =>'Acesso bloqueado',
        'quil07'   =>'Senha expirada',
        'quil08'   =>'Login bloqueado para este dia da semana',
        'quil09'   =>'Primeiro acesso: é necessário regrar a senha',
        'wbot01'   =>'Erro ao gravar dados no banco durante a extração',
        'wbot02'   =>'Dados insuficientes para o robô processar',
        'wbot03'   =>'Dados de login insuficientes',
        'wbot04'   =>'Dados de login não encontrado',
        'wbot05'   =>'Login não encontrado ou não cadastrado',
        'wbot06'   =>'Erro ao gravar dados no banco - ação manual',
        'wbot07'   =>'Dados de login em formato inválido',
        'wbot08'   =>'Login não ativado',
        'wbot09'   =>'Robo da conta pausada',
        'robo02'   =>'Erro ao localizar função em ws_startProcess()',

        'quid01'   =>'Proposta não localizada',
        'quid02'   =>'Proposta cancelada / diferente da emitida',
        'quid03'   =>'Proposta já emitida por outro usuário',
        'quid07'   =>'Proposta já emitida (E)',
        'quid04'   =>'Proposta não cadastrada ou já emitida',
        'quid05'   =>'Mais de uma proposta encontrada',
        'quid06'   =>'Proposta duplicada pelo Quiver ID',
        'quid08'   =>'Proposta não localizada (Cocorretagem)',
    ];

}

