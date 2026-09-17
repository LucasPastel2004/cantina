<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador Inicial e de Status Operacional
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;

class HomeController extends Controller
{
    /**
     * Tela inicial de status do sistema e validação da arquitetura MVC
     */
    public function index(): void
    {
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];

        $data = [
            'appName'     => $config['app']['name'] ?? 'Cantina Web',
            'environment' => $config['app']['env'] ?? 'production',
            'phpVersion'  => PHP_VERSION,
            'faseAtual'   => 'Fase 6 Concluída (Autenticação, Sessão e RBAC)',
            'timestamp'   => date('d/m/Y H:i:s'),
            'sessionActive' => session_status() === PHP_SESSION_ACTIVE,
            'csrfToken'   => Session::getCsrfToken()
        ];

        $this->render('home/index', $data);
    }

    /**
     * Endpoint da API de status do sistema em JSON
     */
    public function status(): void
    {
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];

        $this->json([
            'status'      => 'online',
            'app'         => $config['app']['name'] ?? 'Cantina Web',
            'environment' => $config['app']['env'] ?? 'production',
            'php_version' => PHP_VERSION,
            'fase_atual'  => 'Fase 6 Concluída (Autenticação, Sessão e RBAC)',
            'mvc_core'    => [
                'router'       => 'App\Core\Router',
                'controller'   => 'App\Core\Controller',
                'model'        => 'App\Core\Model',
                'session'      => 'App\Core\Session',
                'errorHandler' => 'App\Core\ErrorHandler'
            ],
            'timestamp'   => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Rota de teste para verificação de parâmetros dinâmicos na URL
     */
    public function testeParametro(array $params): void
    {
        $id = $params['id'] ?? 'indefinido';
        $this->json([
            'sucesso'   => true,
            'mensagem'  => "Parâmetro dinâmico capturado com sucesso pelo Router.",
            'parametro' => $id,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Rota de teste para submissão POST e validação de token CSRF
     */
    public function testeCsrf(): void
    {
        $dados = $this->all();

        $valido = $this->validate($dados, [
            'csrf_token' => 'required|csrf',
            'nome'       => 'required|min:3'
        ]);

        if (!$valido) {
            $this->json([
                'sucesso' => false,
                'erros'   => $this->getValidationErrors()
            ], 422);
        }

        $this->json([
            'sucesso'  => true,
            'mensagem' => 'Validação CSRF executada com sucesso absoluto!',
            'dados_recebidos' => [
                'nome' => $dados['nome']
            ]
        ]);
    }

    /**
     * Rota de teste para simulação de erro não tratado e validação de contingência
     */
    public function testeErro(): void
    {
        throw new \RuntimeException("Erro de simulação intencional para validação da contingência do ErrorHandler.");
    }
}
