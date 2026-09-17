<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador de Autenticação, Primeiro Acesso e Recuperação de Credenciais
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Usuario;
use App\Models\Responsavel;
use App\Services\WhatsAppService;

class AuthController extends Controller
{
    /**
     * Exibe a tela de login unificada (Família e Equipe)
     */
    public function login(): void
    {
        // Se o usuário já estiver logado, redireciona ao painel respectivo
        if (Auth::check()) {
            $destino = Auth::isResponsavel() ? '/portal' : '/admin';
            $this->redirect($destino);
            return;
        }

        $tab = $this->input('tab', 'familia');
        if (!in_array($tab, ['familia', 'equipe'], true)) {
            $tab = 'familia';
        }

        $this->render('auth/login', [
            'pageTitle' => 'Acesso ao Sistema',
            'tab'       => $tab
        ], 'auth');
    }

    /**
     * Processa a submissão de credenciais de login
     */
    public function loginPost(): void
    {
        $dados = $this->all();

        // 1. Validação do token CSRF
        if (!Session::validateCsrfToken($dados['csrf_token'] ?? '')) {
            Session::setFlash('danger', 'A validação de segurança CSRF falhou. Recarregue a página.');
            $this->redirect('/login');
            return;
        }

        $tipo = $dados['tipo_acesso'] ?? 'familia';

        // 2. Fluxo de login do Responsável Familiar (por CPF)
        if ($tipo === 'familia') {
            $cpf = $dados['cpf'] ?? '';
            $senha = $dados['senha'] ?? '';

            if (empty($cpf) || empty($senha)) {
                Session::setFlash('warning', 'Por favor, informe seu CPF e sua senha.');
                $this->redirect('/login?tab=familia');
                return;
            }

            $resultado = Auth::attemptResponsavel($cpf, $senha);

            if (!empty($resultado['primeiro_acesso'])) {
                Session::setFlash('info', $resultado['message']);
                $cleanCpf = Responsavel::cleanCpf($cpf);
                $this->redirect('/primeiro-acesso?cpf=' . urlencode($cleanCpf));
                return;
            }

            if (!$resultado['success']) {
                Session::setFlash('danger', $resultado['message']);
                $this->redirect('/login?tab=familia');
                return;
            }

            // Sucesso no login de Responsável
            Session::setFlash('success', 'Bem-vindo(a) ao Portal da Família!');
            $intendedUrl = Session::get('_intended_url');
            Session::remove('_intended_url');

            $this->redirect($intendedUrl ?: '/portal');
            return;
        }

        // 3. Fluxo de login da Equipe Interna da Cantina (por Usuário)
        if ($tipo === 'equipe') {
            $usuario = $dados['usuario'] ?? '';
            $senha = $dados['senha'] ?? '';

            if (empty($usuario) || empty($senha)) {
                Session::setFlash('warning', 'Por favor, informe seu usuário e sua senha.');
                $this->redirect('/login?tab=equipe');
                return;
            }

            $resultado = Auth::attemptUsuario($usuario, $senha);

            if (!$resultado['success']) {
                Session::setFlash('danger', $resultado['message']);
                $this->redirect('/login?tab=equipe');
                return;
            }

            // Sucesso no login de Operador/Administrador
            Session::setFlash('success', 'Sessão iniciada com sucesso. Bom trabalho!');
            $intendedUrl = Session::get('_intended_url');
            Session::remove('_intended_url');

            $this->redirect($intendedUrl ?: '/admin');
            return;
        }

        $this->redirect('/login');
    }

    /**
     * Encerra a sessão autenticada com segurança (Logout)
     */
    public function logout(): void
    {
        $dados = $this->all();

        // Validação CSRF quando submetido com token
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($dados['csrf_token'])) {
            if (!Session::validateCsrfToken($dados['csrf_token'])) {
                Session::setFlash('danger', 'Falha na validação de segurança. Tente novamente.');
                $this->redirect('/');
                return;
            }
        }

        Auth::logout();
        Session::setFlash('info', 'Você saiu da sua conta com segurança.');
        $this->redirect('/login');
    }

    /**
     * Primeiro Acesso - Etapa 1: Inserção do CPF
     */
    public function primeiroAcesso(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isResponsavel() ? '/portal' : '/admin');
            return;
        }

        $cpfPreenchido = $this->input('cpf', '');

        $this->render('auth/primeiro_acesso_cpf', [
            'pageTitle'     => 'Primeiro Acesso — Portal da Família',
            'cpfPreenchido' => htmlspecialchars($cpfPreenchido, ENT_QUOTES, 'UTF-8')
        ], 'auth');
    }

    /**
     * Primeiro Acesso - Processa a verificação do CPF e dispara o código WhatsApp
     */
    public function primeiroAcessoSolicitar(): void
    {
        $dados = $this->all();

        if (!Session::validateCsrfToken($dados['csrf_token'] ?? '')) {
            Session::setFlash('danger', 'Validação CSRF expirada. Recarregue a página.');
            $this->redirect('/primeiro-acesso');
            return;
        }

        $cpf = $dados['cpf'] ?? '';
        $cleanCpf = Responsavel::cleanCpf($cpf);

        if (strlen($cleanCpf) !== 11) {
            Session::setFlash('warning', 'Por favor, informe um CPF válido com 11 dígitos.');
            $this->redirect('/primeiro-acesso?cpf=' . urlencode($cpf));
            return;
        }

        $respModel = new Responsavel();
        $resp = $respModel->findByCpf($cleanCpf);

        if (!$resp) {
            Session::setFlash('danger', 'CPF não localizado no cadastro escolar. Verifique os números ou procure a secretaria.');
            $this->redirect('/primeiro-acesso?cpf=' . urlencode($cpf));
            return;
        }

        // Se o responsável já tiver ativado a conta com senha e termos
        if (!Responsavel::isPendentePrimeiroAcesso($resp)) {
            Session::setFlash('info', 'Sua conta já está ativada. Informe sua senha cadastrada para entrar.');
            $this->redirect('/login?tab=familia');
            return;
        }

        // Gera código de 6 dígitos e envia via WhatsApp
        $codigo = WhatsAppService::generateCode();
        $whatsAppService = new WhatsAppService();
        $whatsAppService->sendFirstAccessCode($resp['whatsapp'], $codigo, $resp['nome']);

        // Guarda estado seguro na sessão
        Session::set('_pa_responsavel_id', (int)$resp['id']);
        Session::set('_pa_cpf', $cleanCpf);
        Session::set('_pa_nome', $resp['nome']);
        Session::set('_pa_whatsapp', $resp['whatsapp']);
        Session::set('_pa_codigo', $codigo);
        Session::set('_pa_expira_em', time() + 900); // 15 minutos

        Session::setFlash('success', 'Código de verificação enviado para o seu WhatsApp cadastrado!');
        $this->redirect('/primeiro-acesso/codigo');
    }

    /**
     * Primeiro Acesso - Etapa 2: Digitação do código de 6 dígitos
     */
    public function primeiroAcessoCodigo(): void
    {
        if (!Session::has('_pa_responsavel_id')) {
            Session::setFlash('warning', 'Sessão de ativação expirada. Informe seu CPF novamente.');
            $this->redirect('/primeiro-acesso');
            return;
        }

        $whatsapp = Session::get('_pa_whatsapp', '');
        // Mascara o número do WhatsApp para privacidade (ex: (11) 9****-1234)
        $maskedPhone = self::maskPhone($whatsapp);

        $this->render('auth/primeiro_acesso_codigo', [
            'pageTitle'   => 'Confirmação de Código — Cantina Web',
            'maskedPhone' => $maskedPhone,
            'mockCode'    => WhatsAppService::getLastCode($whatsapp) // Útil para depuração em desenvolvimento
        ], 'auth');
    }

    /**
     * Primeiro Acesso - Valida o código digitado
     */
    public function primeiroAcessoValidarCodigo(): void
    {
        $dados = $this->all();

        if (!Session::validateCsrfToken($dados['csrf_token'] ?? '')) {
            Session::setFlash('danger', 'Validação CSRF expirada. Tente novamente.');
            $this->redirect('/primeiro-acesso/codigo');
            return;
        }

        $codigoDigitado = trim($dados['codigo'] ?? '');
        $codigoEsperado = Session::get('_pa_codigo');
        $expiraEm = (int)Session::get('_pa_expira_em', 0);

        if (time() > $expiraEm) {
            Session::setFlash('danger', 'O código de verificação expirou (validade: 15 minutos). Solicite um novo reenvio.');
            $this->redirect('/primeiro-acesso/codigo');
            return;
        }

        if ($codigoDigitado !== $codigoEsperado) {
            Session::setFlash('danger', 'Código de verificação incorreto. Confira a mensagem recebida no WhatsApp.');
            $this->redirect('/primeiro-acesso/codigo');
            return;
        }

        // Código aprovado com sucesso
        Session::set('_pa_verificado', true);
        $this->redirect('/primeiro-acesso/senha');
    }

    /**
     * Primeiro Acesso - Reenvia código WhatsApp
     */
    public function primeiroAcessoReenviar(): void
    {
        if (!Session::has('_pa_responsavel_id')) {
            $this->redirect('/primeiro-acesso');
            return;
        }

        $whatsapp = Session::get('_pa_whatsapp', '');
        $nome = Session::get('_pa_nome', '');

        $novoCodigo = WhatsAppService::generateCode();
        (new WhatsAppService())->sendFirstAccessCode($whatsapp, $novoCodigo, $nome);

        Session::set('_pa_codigo', $novoCodigo);
        Session::set('_pa_expira_em', time() + 900);

        Session::setFlash('success', 'Novo código de segurança enviado para o seu WhatsApp!');
        $this->redirect('/primeiro-acesso/codigo');
    }

    /**
     * Primeiro Acesso - Etapa 3: Definição de senha e aceite dos termos
     */
    public function primeiroAcessoSenha(): void
    {
        if (!Session::get('_pa_verificado', false)) {
            Session::setFlash('warning', 'Por favor, valide o código de segurança primeiro.');
            $this->redirect('/primeiro-acesso');
            return;
        }

        $this->render('auth/primeiro_acesso_senha', [
            'pageTitle' => 'Cadastrar Senha e Aceitar Termos',
            'nome'      => Session::get('_pa_nome', '')
        ], 'auth');
    }

    /**
     * Primeiro Acesso - Conclusão do cadastro de senha e formalização de termos
     */
    public function primeiroAcessoConcluir(): void
    {
        $dados = $this->all();

        if (!Session::validateCsrfToken($dados['csrf_token'] ?? '')) {
            Session::setFlash('danger', 'Validação CSRF expirada. Tente novamente.');
            $this->redirect('/primeiro-acesso/senha');
            return;
        }

        if (!Session::get('_pa_verificado', false)) {
            $this->redirect('/primeiro-acesso');
            return;
        }

        $senha = $dados['senha'] ?? '';
        $senhaConfirm = $dados['senha_confirmacao'] ?? '';
        $aceitouTermos = !empty($dados['aceite_termos']);

        if (strlen($senha) < 6) {
            Session::setFlash('warning', 'A senha deve ter no mínimo 6 caracteres.');
            $this->redirect('/primeiro-acesso/senha');
            return;
        }

        if ($senha !== $senhaConfirm) {
            Session::setFlash('warning', 'As senhas digitadas não coincidem.');
            $this->redirect('/primeiro-acesso/senha');
            return;
        }

        if (!$aceitouTermos) {
            Session::setFlash('warning', 'Você precisa ler e aceitar os Termos e Políticas da Cantina para prosseguir.');
            $this->redirect('/primeiro-acesso/senha');
            return;
        }

        $respId = (int)Session::get('_pa_responsavel_id');
        $respModel = new Responsavel();

        // Grava senha e registro de aceite dos termos
        $respModel->updatePassword($respId, $senha);
        $respModel->aceitarTermos($respId);

        // Registra evento de auditoria técnica
        Auth::logSecurityEvent('primeiro_acesso_concluido', Session::get('_pa_cpf'), [
            'responsavel_id' => $respId,
            'ip'             => Auth::getClientIp()
        ]);

        // Carrega dados atualizados e efetua login imediato
        $respAtualizado = $respModel->find($respId);

        // Limpa variáveis de primeiro acesso da sessão
        Session::remove('_pa_responsavel_id');
        Session::remove('_pa_cpf');
        Session::remove('_pa_nome');
        Session::remove('_pa_whatsapp');
        Session::remove('_pa_codigo');
        Session::remove('_pa_expira_em');
        Session::remove('_pa_verificado');

        if ($respAtualizado) {
            Auth::loginResponsavel($respAtualizado);
        }

        Session::setFlash('success', 'Conta ativada com sucesso! Seja bem-vindo à Cantina Web.');
        $this->redirect('/portal');
    }

    /**
     * Recuperação de Senha - Formulário inicial
     */
    public function recuperarSenha(): void
    {
        $this->render('auth/recuperar_senha', [
            'pageTitle' => 'Recuperação de Senha — Cantina Web'
        ], 'auth');
    }

    /**
     * Recuperação de Senha - Solicita código via WhatsApp
     */
    public function recuperarSenhaSolicitar(): void
    {
        $dados = $this->all();

        if (!Session::validateCsrfToken($dados['csrf_token'] ?? '')) {
            Session::setFlash('danger', 'Validação de segurança expirada. Tente novamente.');
            $this->redirect('/recuperar-senha');
            return;
        }

        $cpf = $dados['cpf'] ?? '';
        $cleanCpf = Responsavel::cleanCpf($cpf);

        $respModel = new Responsavel();
        $resp = $respModel->findByCpf($cleanCpf);

        // Mensagem genérica para não permitir enumeração de dados
        if (!$resp) {
            Session::setFlash('info', 'Se o CPF estiver cadastrado, um código de verificação será enviado para o WhatsApp associado.');
            $this->redirect('/login?tab=familia');
            return;
        }

        $codigo = WhatsAppService::generateCode();
        (new WhatsAppService())->sendPasswordResetCode($resp['whatsapp'], $codigo, $resp['nome']);

        Session::set('_rec_responsavel_id', (int)$resp['id']);
        Session::set('_rec_cpf', $cleanCpf);
        Session::set('_rec_codigo', $codigo);
        Session::set('_rec_expira_em', time() + 900);

        Session::setFlash('success', 'Código de recuperação enviado para o WhatsApp cadastrado.');
        $this->redirect('/recuperar-senha/codigo');
    }

    /**
     * Recuperação de Senha - Digitação do código e nova senha
     */
    public function recuperarSenhaCodigo(): void
    {
        if (!Session::has('_rec_responsavel_id')) {
            $this->redirect('/recuperar-senha');
            return;
        }

        $this->render('auth/recuperar_senha_codigo', [
            'pageTitle' => 'Redefinir Senha — Cantina Web'
        ], 'auth');
    }

    /**
     * Recuperação de Senha - Grava nova senha
     */
    public function recuperarSenhaRedefinir(): void
    {
        $dados = $this->all();

        if (!Session::validateCsrfToken($dados['csrf_token'] ?? '')) {
            Session::setFlash('danger', 'Validação CSRF expirada. Tente novamente.');
            $this->redirect('/recuperar-senha/codigo');
            return;
        }

        $codigo = trim($dados['codigo'] ?? '');
        $senha = $dados['senha'] ?? '';
        $senhaConfirm = $dados['senha_confirmacao'] ?? '';

        $codigoEsperado = Session::get('_rec_codigo');
        $expiraEm = (int)Session::get('_rec_expira_em', 0);

        if (time() > $expiraEm || $codigo !== $codigoEsperado) {
            Session::setFlash('danger', 'Código de segurança inválido ou expirado.');
            $this->redirect('/recuperar-senha/codigo');
            return;
        }

        if (strlen($senha) < 6) {
            Session::setFlash('warning', 'A nova senha deve ter no mínimo 6 caracteres.');
            $this->redirect('/recuperar-senha/codigo');
            return;
        }

        if ($senha !== $senhaConfirm) {
            Session::setFlash('warning', 'As senhas digitadas não conferem.');
            $this->redirect('/recuperar-senha/codigo');
            return;
        }

        $respId = (int)Session::get('_rec_responsavel_id');
        (new Responsavel())->updatePassword($respId, $senha);

        Auth::logSecurityEvent('senha_redefinida', Session::get('_rec_cpf'), [
            'responsavel_id' => $respId,
            'ip'             => Auth::getClientIp()
        ]);

        Session::remove('_rec_responsavel_id');
        Session::remove('_rec_cpf');
        Session::remove('_rec_codigo');
        Session::remove('_rec_expira_em');

        Session::setFlash('success', 'Senha redefinida com sucesso! Você já pode entrar com a nova senha.');
        $this->redirect('/login?tab=familia');
    }

    /**
     * Exibe o documento oficial dos Termos e Políticas da Cantina
     */
    public function termos(): void
    {
        $this->render('auth/termos', [
            'pageTitle' => 'Termos e Políticas da Cantina — Colégio Monteiro Lobato'
        ], 'auth');
    }

    /**
     * Auxiliar para mascarar número de WhatsApp
     */
    private static function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 11) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 1) . '****-' . substr($digits, 7);
        }
        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 2) . ') ****-' . substr($digits, 6);
        }
        return '***-****';
    }
}
