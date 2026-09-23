<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 14: Gestão de Estoque, XML de NF-e e Perdas
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/NotaFiscal.php';
require_once __DIR__ . '/../app/models/ItemNotaFiscal.php';
require_once __DIR__ . '/../app/models/Estoque.php';
require_once __DIR__ . '/../app/models/Auditoria.php';
require_once __DIR__ . '/../app/services/XmlParserService.php';

use App\Models\Produto;
use App\Models\Categoria;
use App\Models\NotaFiscal;
use App\Models\ItemNotaFiscal;
use App\Models\Estoque;
use App\Models\Auditoria;
use App\Services\XmlParserService;

class Fase14Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  [OK] {$descricao}\n";
        } else {
            echo "  [FAIL] {$descricao}\n";
        }
    }

    public function executar(): void
    {
        echo "\n=======================================================\n";
        echo "INICIANDO TESTES DA FASE 14 — ESTOQUE, XML NF-E E PERDAS\n";
        echo "=======================================================\n\n";

        $this->testarXmlParserService();
        $this->testarModelosNotaFiscalEItens();
        $this->testarEntradaEstoqueXmlEFatorConversao();
        $this->testarLancamentoPerdasERegrasNegocio();
        $this->testarSaldosEAlertaEstoqueMinimo();
        $this->testarExtratoMovimentacoes();

        echo "\n-------------------------------------------------------\n";
        echo "RESULTADO FINAL: {$this->assercoesSucesso} de {$this->totalAssercoes} asserções aprovadas.\n";
        if ($this->assercoesSucesso === $this->totalAssercoes) {
            echo "STATUS: SUCESSO TOTAL (100% DE APROVAÇÃO)\n";
        } else {
            echo "STATUS: FALHA DETECTADA\n";
            exit(1);
        }
        echo "=======================================================\n\n";
    }

    /**
     * 1. Testes do serviço XmlParserService
     */
    private function testarXmlParserService(): void
    {
        echo "1. Testando XmlParserService...\n";

        // Cria XML sintético válido de NF-e modelo 55 com namespace
        $chaveTeste = '35240912345678000199550010000008881000008884';
        $xmlValido = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">
  <NFe>
    <infNFe Id="NFe{$chaveTeste}" versao="4.00">
      <ide>
        <nNF>888</nNF>
        <serie>1</serie>
        <dhEmi>2026-09-22T14:30:00-03:00</dhEmi>
      </ide>
      <emit>
        <CNPJ>12345678000199</CNPJ>
        <xNome>Distribuidora de Alimentos Imperial Ltda</xNome>
        <xFant>Alimentos Imperial</xFant>
      </emit>
      <det nItem="1">
        <prod>
          <cProd>BEB-SUCO-UVA</cProd>
          <xProd>Suco de Uva Integral 200ml Caixa com 12 un</xProd>
          <NCM>20098990</NCM>
          <uCom>CX</uCom>
          <qCom>5.0000</qCom>
          <vUnCom>36.0000</vUnCom>
          <vProd>180.00</vProd>
        </prod>
      </det>
      <det nItem="2">
        <prod>
          <cProd>BIS-POLVILHO</cProd>
          <xProd>Biscoito de Polvilho 100g Fardo com 20 un</xProd>
          <NCM>19059090</NCM>
          <uCom>FD</uCom>
          <qCom>2.0000</qCom>
          <vUnCom>40.0000</vUnCom>
          <vProd>80.00</vProd>
        </prod>
      </det>
      <total>
        <ICMSTot>
          <vNF>260.00</vNF>
        </ICMSTot>
      </total>
    </infNFe>
  </NFe>
</nfeProc>
XML;

        $dados = XmlParserService::parseXml($xmlValido);

        $this->assert($dados['chave_nfe'] === $chaveTeste, "Chave de acesso de 44 dígitos extraída com sucesso");
        $this->assert($dados['numero_nota'] === '888', "Número da nota fiscal correto (888)");
        $this->assert($dados['serie'] === '1', "Série da nota fiscal correta (1)");
        $this->assert($dados['cnpj_emitente'] === '12.345.678/0001-99', "CNPJ do emitente formatado corretamente");
        $this->assert($dados['razao_social_emitente'] === 'Distribuidora de Alimentos Imperial Ltda', "Razão social do emitente extraída");
        $this->assert($dados['valor_total'] === 260.0, "Valor total da nota fiscal coincide (R$ 260,00)");
        $this->assert(count($dados['itens']) === 2, "Dois itens faturados identificados no XML");
        $this->assert($dados['itens'][0]['codigo_produto_fornecedor'] === 'BEB-SUCO-UVA', "Código do primeiro item extraído");
        $this->assert($dados['itens'][0]['quantidade_fornecedor'] === 5.0, "Quantidade faturada do primeiro item coincide (5.0)");
        $this->assert($dados['itens'][1]['codigo_produto_fornecedor'] === 'BIS-POLVILHO', "Código do segundo item extraído");

        // Teste de rejeição de XML vazio
        $rejeitouVazio = false;
        try {
            XmlParserService::parseXml('');
        } catch (\InvalidArgumentException $e) {
            $rejeitouVazio = true;
        }
        $this->assert($rejeitouVazio, "XmlParserService rejeita conteúdo vazio com exceção");

        // Teste de proteção contra XXE (DTD / ENTITY)
        $rejeitouXxe = false;
        try {
            $xmlMalicioso = '<!DOCTYPE foo [ <!ENTITY xxe SYSTEM "file:///etc/passwd"> ]><root>&xxe;</root>';
            XmlParserService::parseXml($xmlMalicioso);
        } catch (\InvalidArgumentException $e) {
            $rejeitouXxe = true;
        }
        $this->assert($rejeitouXxe, "XmlParserService bloqueia tentativa de ataque XXE com DTD");

        // Teste de rejeição de XML com chave inválida (menos de 44 dígitos)
        $rejeitouChaveCurta = false;
        try {
            $xmlChaveCurta = '<infNFe Id="NFe123"><ide><nNF>1</nNF></ide></infNFe>';
            XmlParserService::parseXml($xmlChaveCurta);
        } catch (\InvalidArgumentException $e) {
            $rejeitouChaveCurta = true;
        }
        $this->assert($rejeitouChaveCurta, "XmlParserService valida estritamente a chave de 44 dígitos");
    }

    /**
     * 2. Testes de persistência em NotaFiscal e ItemNotaFiscal
     */
    private function testarModelosNotaFiscalEItens(): void
    {
        echo "\n2. Testando modelos NotaFiscal e ItemNotaFiscal...\n";

        // Garante usuário operador/admin para testes
        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        // Garante produto de teste
        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        $codProdTeste = 'TEST-IND-F14-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $codProdTeste,
            'descricao'      => 'Suco de Uva Teste F14',
            'preco_venda'    => 5.50,
            'tipo'           => 'industrializado_continuo',
            'estoque_atual'  => 10,
            'estoque_minimo' => 5
        ], [], $adminId);

        $chaveUnica = '352409' . sprintf('%038d', random_int(100000, 99999999));

        $notaId = NotaFiscal::criar([
            'chave_nfe'             => $chaveUnica,
            'numero_nota'           => '1001',
            'cnpj_emitente'         => '99.888.777/0001-66',
            'razao_social_emitente' => 'Fornecedor Teste Ltda',
            'data_emissao'          => date('Y-m-d H:i:s'),
            'valor_total'           => 120.00,
            'arquivo_xml_path'      => 'nfe_teste.xml',
            'importado_por'         => $adminId
        ]);

        $this->assert($notaId > 0, "Cabeçalho de NF-e criado com ID {$notaId}");

        $notaBuscada = NotaFiscal::buscarPorId($notaId);
        $this->assert($notaBuscada !== null && $notaBuscada['numero_nota'] === '1001', "NotaFiscal::buscarPorId localiza o registro correto");

        $notaPorChave = NotaFiscal::buscarPorChave($chaveUnica);
        $this->assert($notaPorChave !== null && (int)$notaPorChave['id'] === $notaId, "NotaFiscal::buscarPorChave localiza pela chave de 44 dígitos");

        // Cria item com fator multiplicador
        $itemId = ItemNotaFiscal::criar([
            'nota_fiscal_id'            => $notaId,
            'codigo_produto_fornecedor' => 'FORN-BEB-10',
            'descricao_fornecedor'      => 'Suco Caixa com 24 un',
            'unidade_fornecedor'        => 'CX',
            'quantidade_fornecedor'     => 2.0,
            'valor_unitario_fornecedor' => 60.00,
            'produto_id'                => $prodId,
            'fator_conversao'           => 24,
            'quantidade_convertida'     => 48
        ]);

        $this->assert($itemId > 0, "Item de NF-e inserido com ID {$itemId}");

        $itensDaNota = ItemNotaFiscal::listarPorNota($notaId);
        $this->assert(count($itensDaNota) === 1, "ItemNotaFiscal::listarPorNota retorna 1 item");
        $this->assert((int)$itensDaNota[0]['fator_conversao'] === 24, "Fator multiplicador registrado como 24");
        $this->assert((int)$itensDaNota[0]['quantidade_convertida'] === 48, "Quantidade convertida registrada como 48 unidades");

        // Memória de cálculo de conversão prévia
        $memoria = ItemNotaFiscal::buscarUltimoFatorConversao('FORN-BEB-10', '99.888.777/0001-66');
        $this->assert($memoria !== null && (int)$memoria['fator_conversao'] === 24, "buscarUltimoFatorConversao recupera memória do fator 24x");
    }

    /**
     * 3. Teste de entrada atômica no estoque com fator de conversão
     */
    private function testarEntradaEstoqueXmlEFatorConversao(): void
    {
        echo "\n3. Testando entrada atômica no estoque via NF-e...\n";

        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        // Cria produto com estoque inicial 5
        $codProd = 'TEST-IND-ENTRADA-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $codProd,
            'descricao'      => 'Achocolatado 200ml Teste Entrada',
            'preco_venda'    => 4.00,
            'tipo'           => 'industrializado_continuo',
            'estoque_atual'  => 5,
            'estoque_minimo' => 5
        ], [], $adminId);

        // Cria nota fiscal
        $chaveNota = '352409' . sprintf('%038d', random_int(100000, 99999999));
        $notaId = NotaFiscal::criar([
            'chave_nfe'             => $chaveNota,
            'numero_nota'           => '2002',
            'cnpj_emitente'         => '11.222.333/0001-44',
            'razao_social_emitente' => 'Distribuidora Láctea Ltda',
            'data_emissao'          => date('Y-m-d H:i:s'),
            'valor_total'           => 100.00,
            'arquivo_xml_path'      => 'nfe_lactea.xml',
            'importado_por'         => $adminId
        ]);

        // Simula entrada de 3 fardos com fator 10 (total = 30 unidades)
        $itensMapeados = [
            [
                'codigo_produto_fornecedor' => 'LACT-001',
                'descricao_fornecedor'      => 'Achocolatado Fardo com 10 un',
                'unidade_fornecedor'        => 'FD',
                'quantidade_fornecedor'     => 3.0,
                'valor_unitario_fornecedor' => 33.33,
                'produto_id'                => $prodId,
                'fator_conversao'           => 10
            ]
        ];

        $sucesso = Estoque::darEntradaXml($notaId, $itensMapeados, $adminId);
        $this->assert($sucesso === true, "Estoque::darEntradaXml executado com sucesso");

        // Verifica o novo saldo do produto (5 inicial + 30 convertidas = 35)
        $prodAtualizado = Produto::buscarPorId($prodId);
        $this->assert((int)$prodAtualizado['estoque_atual'] === 35, "Estoque físico atualizado de 5 para 35 un (5 + 3x10)");

        // Verifica gravação em estoque_movimentacoes
        $sqlMov = "SELECT * FROM estoque_movimentacoes WHERE produto_id = :p AND tipo_movimentacao = 'entrada_xml'";
        $stmtMov = $this->pdo->prepare($sqlMov);
        $stmtMov->execute(['p' => $prodId]);
        $mov = $stmtMov->fetch();

        $this->assert($mov !== false, "Movimentação 'entrada_xml' registrada em estoque_movimentacoes");
        $this->assert((int)$mov['quantidade'] === 30, "Quantidade movimentada registrada como 30 un");
        $this->assert((int)$mov['nota_fiscal_id'] === $notaId, "ID da nota fiscal vinculado à movimentação");
    }

    /**
     * 4. Testes de lançamento manual de perdas (RN-15) e regras de negócio
     */
    private function testarLancamentoPerdasERegrasNegocio(): void
    {
        echo "\n4. Testando lançamento manual de perdas (RN-15)...\n";

        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        // Cria produto com estoque 20
        $codProd = 'TEST-IND-PERDA-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $codProd,
            'descricao'      => 'Bolo Individual Teste Perda',
            'preco_venda'    => 6.00,
            'tipo'           => 'industrializado_continuo',
            'estoque_atual'  => 20,
            'estoque_minimo' => 5
        ], [], $adminId);

        // 4.1. Lança perda legítima por avaria (3 unidades)
        $sucessoAvaria = Estoque::registrarPerda(
            $prodId,
            3,
            Estoque::MOTIVO_AVARIA,
            'Embalagem violada no descarregamento da caixa',
            $adminId
        );

        $this->assert($sucessoAvaria === true, "Perda por avaria registrada com sucesso");
        $prodApos1 = Produto::buscarPorId($prodId);
        $this->assert((int)$prodApos1['estoque_atual'] === 17, "Estoque debitado de 20 para 17 un");

        // 4.2. Lança perda por vencimento (2 unidades)
        $sucessoVenc = Estoque::registrarPerda(
            $prodId,
            2,
            Estoque::MOTIVO_VENCIMENTO,
            'Data de validade expirada no mostruário',
            $adminId
        );
        $this->assert($sucessoVenc === true, "Perda por vencimento registrada com sucesso");
        $prodApos2 = Produto::buscarPorId($prodId);
        $this->assert((int)$prodApos2['estoque_atual'] === 15, "Estoque debitado de 17 para 15 un");

        // 4.3. Verifica registro em logs_auditoria
        $sqlAudit = "SELECT * FROM logs_auditoria WHERE acao = 'perda_estoque' AND registro_id = :prod_id ORDER BY id DESC LIMIT 1";
        $stmtAudit = $this->pdo->prepare($sqlAudit);
        $stmtAudit->execute(['prod_id' => $prodId]);
        $audit = $stmtAudit->fetch();

        $this->assert($audit !== false, "Trilha de auditoria gerada com ação 'perda_estoque'");
        $this->assert(str_contains($audit['justificativa'], 'Vencimento'), "Auditoria contém descrição do motivo da perda");

        // 4.4. Bloqueio de quantidade maior que o saldo em estoque (tentar baixar 50 tendo 15)
        $bloqueouExcesso = false;
        try {
            Estoque::registrarPerda($prodId, 50, Estoque::MOTIVO_AVARIA, 'Tentativa de saldo negativo', $adminId);
        } catch (\DomainException $e) {
            $bloqueouExcesso = true;
        }
        $this->assert($bloqueouExcesso, "Sistema bloqueia perda que exceda o saldo físico disponível");

        // 4.5. Bloqueio de motivo inválido
        $bloqueouMotivoInvalido = false;
        try {
            Estoque::registrarPerda($prodId, 1, 'motivo_fantasma', 'Justificativa válida', $adminId);
        } catch (\DomainException $e) {
            $bloqueouMotivoInvalido = true;
        }
        $this->assert($bloqueouMotivoInvalido, "Sistema bloqueia motivo de perda fora dos 4 padronizados pelo FSD");

        // 4.6. Bloqueio de justificativa curta (menos de 5 caracteres)
        $bloqueouJustificativaCurta = false;
        try {
            Estoque::registrarPerda($prodId, 1, Estoque::MOTIVO_AVARIA, 'abc', $adminId);
        } catch (\DomainException $e) {
            $bloqueouJustificativaCurta = true;
        }
        $this->assert($bloqueouJustificativaCurta, "Sistema exige justificativa com no mínimo 5 caracteres");
    }

    /**
     * 5. Teste de saldos analíticos e alerta de estoque mínimo
     */
    private function testarSaldosEAlertaEstoqueMinimo(): void
    {
        echo "\n5. Testando saldos analíticos e alerta de estoque mínimo...\n";

        $usuario = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = $usuario ? (int)$usuario['id'] : 1;

        $cat = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1")->fetch();
        $catId = $cat ? (int)$cat['id'] : 1;

        // Produto com estoque 4 e mínimo 5 (deve disparar alerta)
        $codProd = 'TEST-IND-ALERTA-' . bin2hex(random_bytes(3));
        $prodId = Produto::criar([
            'categoria_id'      => $catId,
            'codigo_interno'    => $codProd,
            'descricao'         => 'Refrigerante Orgânico Teste Alerta',
            'preco_venda'       => 7.00,
            'tipo'              => 'industrializado_continuo',
            'estoque_atual'     => 4,
            'estoque_reservado' => 0,
            'estoque_minimo'    => 5
        ], [], $adminId);

        $saldos = Estoque::listarSaldos(['busca' => $codProd]);
        $this->assert(count($saldos) === 1, "Estoque::listarSaldos localizou o produto de teste");
        $this->assert((int)$saldos[0]['em_alerta_minimo'] === 1, "Produto com estoque 4 e mínimo 5 é marcado como em_alerta_minimo = 1");

        // Atualiza estoque mínimo para 2
        Estoque::atualizarEstoqueMinimo($prodId, 2, $adminId);
        $saldosApos = Estoque::listarSaldos(['busca' => $codProd]);
        $this->assert((int)$saldosApos[0]['em_alerta_minimo'] === 0, "Após ajustar mínimo para 2, alerta é desativado (4 > 2)");

        // Métricas gerais
        $metricas = Estoque::obterMetricasGerais();
        $this->assert(isset($metricas['total_produtos']) && $metricas['total_produtos'] > 0, "obterMetricasGerais retorna total de produtos");
        $this->assert(isset($metricas['valor_estoque_venda']), "obterMetricasGerais calcula valor de estoque em venda");
    }

    /**
     * 6. Teste de extrato de movimentações e listagem de perdas
     */
    private function testarExtratoMovimentacoes(): void
    {
        echo "\n6. Testando extrato de movimentações e histórico de perdas...\n";

        $movs = Estoque::listarMovimentacoes(['limite' => 10]);
        $this->assert(is_array($movs), "Estoque::listarMovimentacoes retorna array de registros");
        $this->assert(!empty($movs), "Existem movimentações registradas na base");

        $perdas = Estoque::listarPerdas(['limite' => 10]);
        $this->assert(is_array($perdas), "Estoque::listarPerdas retorna array");
        $this->assert(!empty($perdas), "Histórico de perdas contém os registros criados nos testes");
        $this->assert($perdas[0]['tipo_movimentacao'] === 'saida_perda', "Primeiro item do histórico é do tipo 'saida_perda'");
    }
}

$teste = new Fase14Test();
$teste->executar();
