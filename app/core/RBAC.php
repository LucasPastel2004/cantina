<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controle de Acesso Baseado em Papéis (RBAC - Role-Based Access Control)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class RBAC
{
    /**
     * Perfis oficiais definidos no FSD
     */
    public const ROLE_RESPONSAVEL   = 'responsavel';
    public const ROLE_OPERADOR      = 'operador';
    public const ROLE_ADMINISTRADOR = 'administrador';
    public const ROLE_COLABORADOR   = 'colaborador'; // Perfil passivo (sem login web)

    /**
     * Matriz de Permissões Funcionais (FSD Seção 8)
     */
    private const PERMISSIONS_MATRIX = [
        'login_portal_familia'        => [self::ROLE_RESPONSAVEL, self::ROLE_ADMINISTRADOR],
        'login_painel_cantina'        => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'recarga_pix_online'          => [self::ROLE_RESPONSAVEL],
        'recarga_balcao_dinheiro'     => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'agendamento_semanal'         => [self::ROLE_RESPONSAVEL],
        'edicao_cancelamento_corte'   => [self::ROLE_RESPONSAVEL],
        'gestao_restricoes'           => [self::ROLE_RESPONSAVEL, self::ROLE_ADMINISTRADOR],
        'extrato_propria_familia'     => [self::ROLE_RESPONSAVEL, self::ROLE_ADMINISTRADOR],
        'extrato_aluno_balcao'        => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'painel_corte_fornecedor'     => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'conferencia_recebimento'     => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'impressao_lote_tickets'      => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'reimpressao_segunda_via'     => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'operacao_pdv_balcao'         => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'venda_desconto_folha'        => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'estorno_balcao'              => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'aproveitamento_sobras'       => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'abertura_fechamento_caixa'   => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'lancamento_perdas'           => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'pausa_rapida_produtos'       => [self::ROLE_OPERADOR, self::ROLE_ADMINISTRADOR],
        'cadastro_produtos_precos'    => [self::ROLE_ADMINISTRADOR],
        'entrada_xml_nfe'             => [self::ROLE_ADMINISTRADOR],
        'importacao_planilha_escolar' => [self::ROLE_ADMINISTRADOR],
        'gestao_calendario_letivo'    => [self::ROLE_ADMINISTRADOR],
        'ajuste_manual_saldo'         => [self::ROLE_ADMINISTRADOR],
        'relatorios_gerenciais'       => [self::ROLE_ADMINISTRADOR],
        'gestao_usuarios_equipe'      => [self::ROLE_ADMINISTRADOR],
        'configuracoes_empresa'       => [self::ROLE_ADMINISTRADOR],
        'consulta_logs_seguranca'     => [self::ROLE_ADMINISTRADOR],
    ];

    /**
     * Verifica se o usuário autenticado possui uma permissão específica
     */
    public static function can(string $permission): bool
    {
        $role = Auth::role();
        if (!$role) {
            return false;
        }

        $allowedRoles = self::PERMISSIONS_MATRIX[$permission] ?? [];
        return in_array($role, $allowedRoles, true);
    }

    /**
     * Middleware: Exige que a requisição seja feita por um usuário autenticado
     */
    public static function requireAuth(array $params = []): bool
    {
        if (Auth::guest()) {
            Session::setFlash('warning', 'Acesso restrito. Faça login para continuar.');

            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $base = str_replace('\\', '/', $scriptDir);
            $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');

            // Salva URL solicitada para redirecionamento após o login
            if (!empty($_SERVER['REQUEST_URI'])) {
                Session::set('_intended_url', $_SERVER['REQUEST_URI']);
            }

            header('Location: ' . $baseUrl . '/login');
            exit;
        }

        return true;
    }

    /**
     * Middleware: Exige que a requisição seja feita por um visitante (não autenticado)
     */
    public static function requireGuest(array $params = []): bool
    {
        if (Auth::check()) {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $base = str_replace('\\', '/', $scriptDir);
            $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');

            // Redireciona o usuário para seu painel de acordo com seu perfil
            $destino = Auth::isResponsavel() ? '/portal' : '/admin';
            header('Location: ' . $baseUrl . $destino);
            exit;
        }

        return true;
    }

    /**
     * Middleware: Exige que o usuário logado possua um determinado papel (role)
     */
    public static function requireRole(array|string $roles): \Closure
    {
        return function (array $params = []) use ($roles): bool {
            if (!self::requireAuth($params)) {
                return false;
            }

            $allowed = is_array($roles) ? $roles : [$roles];
            $currentRole = Auth::role();

            if (!in_array($currentRole, $allowed, true)) {
                // Registra infração no log de segurança
                Auth::logSecurityEvent('acesso_negado', Auth::username(), [
                    'rota'             => $_SERVER['REQUEST_URI'] ?? '',
                    'metodo'           => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                    'papel_atual'      => $currentRole,
                    'papeis_exigidos'  => $allowed
                ]);

                // Trata resposta JSON para chamadas de API
                $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

                if ($isAjax) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'error'   => 'Acesso proibido',
                        'message' => 'Você não tem permissão para executar esta ação.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Redirecionamento amigável com alerta visual corporativo
                Session::setFlash('danger', 'Acesso negado: seu perfil não possui autorização para acessar este recurso.');

                $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
                $base = str_replace('\\', '/', $scriptDir);
                $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');

                $destino = Auth::isResponsavel() ? '/portal' : '/admin';
                header('Location: ' . $baseUrl . $destino);
                exit;
            }

            return true;
        };
    }
}
