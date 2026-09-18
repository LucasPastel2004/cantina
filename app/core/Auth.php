<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Camada de Autenticação Segura e Controle de Sessão
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Models\Usuario;
use App\Models\Responsavel;

class Auth
{
    /**
     * Limite de tentativas consecutivas de falha antes do bloqueio temporário
     */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Janela de tempo de bloqueio por força bruta em minutos
     */
    private const BRUTE_FORCE_WINDOW_MINUTES = 15;

    /**
     * Tenta autenticar um usuário da equipe interna (Operador ou Administrador)
     */
    public static function attemptUsuario(string $usuario, string $senha): array
    {
        $usuario = trim($usuario);
        $ip = self::getClientIp();

        // 1. Verifica se o usuário está temporariamente bloqueado por força bruta
        if (self::isBruteForceBlocked($usuario, $ip)) {
            self::logSecurityEvent('bloqueio_brute_force', $usuario, [
                'motivo' => 'Excesso de tentativas incorretas na janela de 15 minutos',
                'ip'     => $ip
            ]);
            return [
                'success' => false,
                'message' => 'Muitas tentativas incorretas. Por segurança, aguarde 15 minutos antes de tentar novamente.'
            ];
        }

        $usuarioModel = new Usuario();
        $user = $usuarioModel->findByUsuario($usuario);

        // 2. Validação de usuário e senha
        if (!$user || !password_verify($senha, $user['senha_hash'])) {
            self::recordFailedLogin($usuario, 'credenciais_invalidas', 'usuario');
            return [
                'success' => false,
                'message' => 'Usuário ou senha incorretos.'
            ];
        }

        // 3. Verifica se a conta está ativa
        if (empty($user['ativo'])) {
            self::logSecurityEvent('login_bloqueado_inativo', $usuario, ['usuario_id' => $user['id']]);
            return [
                'success' => false,
                'message' => 'Esta conta de operador foi inativada. Procure o administrador.'
            ];
        }

        // 4. Autenticação realizada com sucesso
        self::loginUsuario($user);
        self::logSecurityEvent('login_sucesso', $usuario, [
            'tipo'   => 'usuario',
            'perfil' => $user['perfil'],
            'id'     => $user['id']
        ]);

        return [
            'success' => true,
            'user'    => $user
        ];
    }

    /**
     * Tenta autenticar um responsável familiar via CPF e senha
     */
    public static function attemptResponsavel(string $cpf, string $senha): array
    {
        $cleanCpf = Responsavel::cleanCpf($cpf);
        $ip = self::getClientIp();

        // 1. Verifica bloqueio por força bruta
        if (self::isBruteForceBlocked($cleanCpf, $ip)) {
            self::logSecurityEvent('bloqueio_brute_force', $cleanCpf, [
                'motivo' => 'Excesso de tentativas incorretas para este CPF',
                'ip'     => $ip
            ]);
            return [
                'success' => false,
                'message' => 'Muitas tentativas incorretas. Por segurança, aguarde 15 minutos.'
            ];
        }

        $respModel = new Responsavel();
        $responsavel = $respModel->findByCpf($cleanCpf);

        // 2. Verifica se o CPF existe na base escolar
        if (!$responsavel) {
            self::recordFailedLogin($cleanCpf, 'cpf_nao_cadastrado', 'responsavel');
            return [
                'success' => false,
                'message' => 'CPF não localizado na base de alunos do colégio.'
            ];
        }

        // 3. Verifica se é pendente de Primeiro Acesso
        if (Responsavel::isPendentePrimeiroAcesso($responsavel)) {
            return [
                'success'        => false,
                'primeiro_acesso'=> true,
                'message'        => 'Seu cadastro foi localizado, mas você ainda não ativou sua conta. Clique em "Primeiro Acesso".'
            ];
        }

        // 4. Verifica a senha cadastrada
        if (!password_verify($senha, $responsavel['senha_hash'])) {
            self::recordFailedLogin($cleanCpf, 'senha_incorreta', 'responsavel');
            return [
                'success' => false,
                'message' => 'CPF ou senha incorretos.'
            ];
        }

        // 5. Verifica se há bloqueio pelo Mecanismo Especial de Devolução (MED)
        if (!empty($responsavel['bloqueado_med'])) {
            self::logSecurityEvent('login_bloqueio_med', $cleanCpf, ['responsavel_id' => $responsavel['id']]);
            // Responsável pode logar para ver a notificação, mas operações estarão bloqueadas
        }

        // 6. Login realizado com sucesso
        self::loginResponsavel($responsavel);
        self::logSecurityEvent('login_sucesso', $cleanCpf, [
            'tipo'           => 'responsavel',
            'responsavel_id' => $responsavel['id'],
            'bloqueado_med'  => (bool)$responsavel['bloqueado_med']
        ]);

        return [
            'success'     => true,
            'responsavel' => $responsavel
        ];
    }

    /**
     * Efetua o login do usuário da equipe na sessão
     */
    public static function loginUsuario(array $user): void
    {
        Session::regenerate(true);

        Session::set('_auth_logged', true);
        Session::set('_auth_type', 'usuario');
        Session::set('_auth_id', (int)$user['id']);
        Session::set('_auth_username', $user['usuario']);
        Session::set('_auth_nome', $user['nome']);
        Session::set('_auth_email', $user['email']);
        Session::set('_auth_role', $user['perfil']); // 'operador' | 'administrador'
    }

    /**
     * Efetua o login do responsável familiar na sessão
     */
    public static function loginResponsavel(array $responsavel): void
    {
        Session::regenerate(true);

        Session::set('_auth_logged', true);
        Session::set('_auth_type', 'responsavel');
        Session::set('_auth_id', (int)$responsavel['id']);
        Session::set('_auth_username', $responsavel['cpf']);
        Session::set('_auth_nome', $responsavel['nome']);
        Session::set('_auth_email', $responsavel['email']);
        Session::set('_auth_whatsapp', $responsavel['whatsapp']);
        Session::set('_auth_role', 'responsavel');
        Session::set('_auth_saldo', (float)$responsavel['saldo_atual']);
        Session::set('_auth_bloqueado_med', (bool)$responsavel['bloqueado_med']);
    }

    /**
     * Encerra a sessão autenticada com segurança
     */
    public static function logout(): void
    {
        $identificador = self::username();
        if ($identificador) {
            self::logSecurityEvent('logout', $identificador, ['ip' => self::getClientIp()]);
        }

        Session::remove('_auth_logged');
        Session::remove('_auth_type');
        Session::remove('_auth_id');
        Session::remove('_auth_username');
        Session::remove('_auth_nome');
        Session::remove('_auth_email');
        Session::remove('_auth_whatsapp');
        Session::remove('_auth_role');
        Session::remove('_auth_saldo');
        Session::remove('_auth_bloqueado_med');

        Session::regenerate(true);
    }

    /**
     * Verifica se há um usuário autenticado na sessão atual
     */
    public static function check(): bool
    {
        return (bool)Session::get('_auth_logged', false);
    }

    /**
     * Verifica se a sessão é de um visitante não autenticado
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Retorna os dados do usuário autenticado
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id'            => Session::get('_auth_id'),
            'type'          => Session::get('_auth_type'),
            'username'      => Session::get('_auth_username'),
            'nome'          => Session::get('_auth_nome'),
            'email'         => Session::get('_auth_email'),
            'whatsapp'      => Session::get('_auth_whatsapp'),
            'role'          => Session::get('_auth_role'),
            'perfil'        => Session::get('_auth_role'),
            'saldo'         => Session::get('_auth_saldo', 0.00),
            'bloqueado_med' => Session::get('_auth_bloqueado_med', false)
        ];
    }

    /**
     * Retorna o ID do usuário autenticado
     */
    public static function id(): ?int
    {
        return Session::get('_auth_id');
    }

    /**
     * Retorna o identificador de login (usuário ou CPF)
     */
    public static function username(): ?string
    {
        return Session::get('_auth_username');
    }

    /**
     * Retorna o tipo de usuário ('usuario' ou 'responsavel')
     */
    public static function type(): ?string
    {
        return Session::get('_auth_type');
    }

    /**
     * Retorna o papel (role) do usuário: 'responsavel', 'operador', 'administrador'
     */
    public static function role(): ?string
    {
        return Session::get('_auth_role');
    }

    /**
     * Verifica se o usuário é Responsável familiar
     */
    public static function isResponsavel(): bool
    {
        return self::role() === 'responsavel';
    }

    /**
     * Verifica se o usuário é Operador da cantina
     */
    public static function isOperador(): bool
    {
        return self::role() === 'operador';
    }

    /**
     * Verifica se o usuário é Administrador
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'administrador';
    }

    /**
     * Verifica se o identificador excedeu o limite de tentativas de login incorretas
     */
    public static function isBruteForceBlocked(string $identificador, string $ip): bool
    {
        try {
            $pdo = Model::getConnection();
            $sql = "SELECT COUNT(*) FROM logs_seguranca 
                    WHERE evento = 'login_falha' 
                    AND cpf_ou_usuario = :identificador
                    AND created_at >= NOW() - INTERVAL " . self::BRUTE_FORCE_WINDOW_MINUTES . " MINUTE
                    AND created_at > COALESCE((SELECT MAX(created_at) FROM logs_seguranca WHERE evento = 'login_sucesso' AND cpf_ou_usuario = :identificador_sucesso), '2000-01-01')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'identificador'         => $identificador,
                'identificador_sucesso' => $identificador
            ]);

            return (int)$stmt->fetchColumn() >= self::MAX_LOGIN_ATTEMPTS;
        } catch (\Throwable $e) {
            // Em caso de falha de conexão, não trava a aplicação
            return false;
        }
    }

    /**
     * Registra uma tentativa de login com falha
     */
    private static function recordFailedLogin(string $identificador, string $motivo, string $tipo): void
    {
        self::logSecurityEvent('login_falha', $identificador, [
            'tipo'   => $tipo,
            'motivo' => $motivo,
            'ip'     => self::getClientIp()
        ]);
    }

    /**
     * Registra um evento estruturado na tabela logs_seguranca e contingência
     */
    public static function logSecurityEvent(string $evento, ?string $identificador = null, array $detalhes = []): void
    {
        $ip = self::getClientIp();
        $json = json_encode($detalhes, JSON_UNESCAPED_UNICODE);

        try {
            $pdo = Model::getConnection();
            $sql = "INSERT INTO logs_seguranca (evento, cpf_ou_usuario, detalhes_json, ip_origem, created_at) 
                    VALUES (:evento, :identificador, :detalhes_json, :ip_origem, :created_at)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'evento'        => substr($evento, 0, 50),
                'identificador' => $identificador ? substr($identificador, 0, 50) : null,
                'detalhes_json' => $json,
                'ip_origem'     => substr($ip, 0, 45),
                'created_at'    => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            // Fallback obrigatório no arquivo storage/logs/security.log
            $logPath = __DIR__ . '/../../storage/logs/security.log';
            if (is_dir(dirname($logPath))) {
                $entry = sprintf(
                    "[%s] [EVENTO: %s] [ID: %s] [IP: %s] [DADOS: %s]\n",
                    date('Y-m-d H:i:s'),
                    $evento,
                    $identificador ?? 'ANÔNIMO',
                    $ip,
                    $json
                );
                @file_put_contents($logPath, $entry, FILE_APPEND);
            }
        }
    }

    /**
     * Retorna o endereço IP remoto da requisição
     */
    public static function getClientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }
}
