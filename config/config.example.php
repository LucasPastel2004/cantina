<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Arquivo de Configuração de Exemplo (Modelo)
 * 
 * Copie este arquivo para config/config.php e configure os parâmetros locais.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return [
    // Informações da Aplicação
    'app' => [
        'name'     => 'Cantina Web — Colégio Monteiro Lobato',
        'env'      => 'development', // 'development' ou 'production'
        'debug'    => true,
        'url'      => 'http://localhost:8080',
        'timezone' => 'America/Sao_Paulo',
        'key'      => 'SUA_CHAVE_SECRETA_ALEATORIA_DE_32_CARACTERES',
    ],

    // Banco de Dados MySQL / MariaDB
    'db' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'cantina',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    // Parâmetros Operacionais da Cantina
    'cantina' => [
        'recarga_minima_padrao'      => 10.00,
        'alerta_saldo_baixo_padrao'  => 15.00,
        'timeout_sessao_minutos'     => 60,
        'limite_tentativas_login'    => 5,
        'itens_por_pagina'           => 25,
        'horario_corte_matutino'     => '08:00',
        'horario_corte_vespertino'   => '13:30',
        'horario_fechamento_matutino'=> '11:00',
        'horario_fechamento_vespertino' => '16:00',
    ],

    // Integrações Externas (Gateway Pix e WhatsApp)
    'integracoes' => [
        'pix' => [
            'provedor'         => 'mock', // 'efi', 'mercado_pago', 'mock'
            'client_id'        => 'SEU_CLIENT_ID_PIX',
            'client_secret'    => 'SEU_CLIENT_SECRET_PIX',
            'chave_pix'        => 'financeiro@colegiomonteirolobato.com.br',
            'certificado_path' => '',
            'webhook_secret'   => 'SEU_WEBHOOK_SECRET',
        ],
        'whatsapp' => [
            'provedor'         => 'mock', // 'z-api', 'evolution', 'mock'
            'api_url'          => 'https://api.whatsapp.exemplo.com',
            'api_token'        => 'SEU_TOKEN_WHATSAPP',
            'instance_id'      => 'INSTANCIA_ID',
        ],
    ],

    // Configurações de Geração de PDF (Gotenberg / Fallback HTML Print)
    'pdf' => [
        'driver'        => 'gotenberg', // 'gotenberg' ou 'native_html'
        'gotenberg_url' => 'http://gotenberg:3000', // Em Docker: 'http://gotenberg:3000', Local: 'http://localhost:3000'
        'timeout'       => 15,
    ],

    // Segurança das Migrações
    'security' => [
        'migration_token' => 'TOKEN_SECRETO_PARA_EXECUTAR_MIGRATIONS',
    ],
];
