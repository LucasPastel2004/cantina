<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Espelho de Impressão Oficial do Recibo de Pagamento a Fornecedor
 * 
 * Emissão nativa via navegador pelo recurso "Print to PDF" (window.print())
 * Padrão A4 timbrado com quitação formal, valor por extenso e via dupla.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$recibo = $recibo ?? [];
$fornecedor = $fornecedor ?? [];
$valorExtenso = $valorExtenso ?? '';
$isPdf = $isPdf ?? false;

$logoPath = dirname(__DIR__, 2) . '/assets/img/logo-colegio.jpg';
$logoDataUri = '';
if (file_exists($logoPath)) {
    $logoDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
}

$dataPagamento = !empty($recibo['data_pagamento']) ? date('d/m/Y', strtotime($recibo['data_pagamento'])) : date('d/m/Y');
$formaPagamentoNome = ($recibo['forma_pagamento'] === 'pix') ? 'Transferência Instantânea Pix' : 'Espécie (Dinheiro em Caixa)';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pagamento — <?php echo htmlspecialchars($recibo['numero_recibo'] ?? 'REC', ENT_QUOTES, 'UTF-8'); ?> — Cantina Web</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 10pt;
            line-height: 1.4;
            padding: 20px;
        }

        .no-print {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-print-action {
            background-color: #1d4ed8;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-print-action:hover {
            background-color: #1e40af;
        }
        .btn-close-action {
            background: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 8px;
        }
        .btn-close-action:hover {
            background: #334155;
            color: #ffffff;
        }

        .recibo-container {
            max-width: 800px;
            margin: 50px auto 20px;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .via-box {
            border: 1px solid #0f172a;
            padding: 24px;
            margin-bottom: 25px;
            position: relative;
            background: #ffffff;
        }

        .via-tag {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #0f172a;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 10px;
            letter-spacing: 0.05em;
        }

        .header-recibo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logo img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
        }

        .header-title-box h1 {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
            text-transform: uppercase;
        }
        .header-title-box .sub {
            font-size: 8.5pt;
            color: #475569;
            font-weight: 500;
        }

        .header-meta {
            text-align: right;
        }
        .num-recibo {
            font-size: 12pt;
            font-weight: 800;
            color: #1d4ed8;
            font-family: monospace;
        }
        .data-emissao {
            font-size: 8.5pt;
            color: #64748b;
        }

        .valor-destaque-box {
            background: #f8fafc;
            border: 1.5px solid #0f172a;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .valor-label {
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
        }
        .valor-num {
            font-size: 16pt;
            font-weight: 800;
            color: #0f172a;
            font-family: 'Segoe UI', monospace;
        }

        .corpo-texto {
            font-size: 9.5pt;
            line-height: 1.6;
            text-align: justify;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .corpo-texto strong {
            color: #0f172a;
        }

        .dados-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 20px;
        }
        .dados-grid td {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
        }
        .dados-grid .bg-label {
            background-color: #f1f5f9;
            font-weight: 700;
            color: #334155;
            width: 25%;
            text-transform: uppercase;
            font-size: 7.5pt;
        }

        .assinaturas-row {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-top: 35px;
            padding-top: 10px;
        }
        .assinatura-box {
            flex: 1;
            text-align: center;
            border-top: 1px solid #0f172a;
            padding-top: 6px;
            font-size: 8pt;
            color: #334155;
        }
        .assinatura-box strong {
            display: block;
            font-size: 8.5pt;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .divisor-vias {
            border: none;
            border-top: 1px dashed #94a3b8;
            margin: 25px 0;
            position: relative;
            text-align: center;
        }
        .divisor-vias:after {
            content: "✂ Destaque aqui";
            position: relative;
            top: -10px;
            background: #ffffff;
            padding: 0 12px;
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .recibo-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 10mm 12mm !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .via-box {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 13px; font-weight: 600;">
        Recibo Oficial de Pagamento a Fornecedor — <?php echo htmlspecialchars($recibo['numero_recibo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <div>
        <button type="button" class="btn-print-action" onclick="window.print()">
            🖨 Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-close-action" onclick="window.close()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div class="recibo-container">

    <!-- VIA 1: DA CANTINA / CONTABILIDADE -->
    <div class="via-box">
        <span class="via-tag">1ª Via — Cantina / Financeiro</span>

        <div class="header-recibo">
            <div class="header-logo">
                <?php if (!empty($logoDataUri)): ?>
                    <img src="<?php echo $logoDataUri; ?>" alt="Logo Colégio Monteiro Lobato">
                <?php endif; ?>
                <div class="header-title-box">
                    <h1>Colégio Monteiro Lobato</h1>
                    <div class="sub">Cantina Web • Comprovante de Pagamento a Fornecedor</div>
                </div>
            </div>
            <div class="header-meta">
                <div class="num-recibo"><?php echo htmlspecialchars($recibo['numero_recibo'] ?? 'REC-0000', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="data-emissao">Data: <?php echo $dataPagamento; ?></div>
            </div>
        </div>

        <div class="valor-destaque-box">
            <span class="valor-label">Valor Quitado</span>
            <span class="valor-num">R$ <?php echo number_format((float)($recibo['valor'] ?? 0), 2, ',', '.'); ?></span>
        </div>

        <div class="corpo-texto">
            Recebemos de <strong>CANTINA ESCOLAR — COLÉGIO MONTEIRO LOBATO</strong> a quantia líquida de 
            <strong>R$ <?php echo number_format((float)($recibo['valor'] ?? 0), 2, ',', '.'); ?></strong> 
            (<em><?php echo htmlspecialchars($valorExtenso, ENT_QUOTES, 'UTF-8'); ?></em>), 
            referente a <strong><?php echo htmlspecialchars($recibo['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>, 
            efetuado mediante <strong><?php echo $formaPagamentoNome; ?></strong>, dando plena, geral e irrevogável quitação.
        </div>

        <table class="dados-grid">
            <tr>
                <td class="bg-label">Favorecido / Fornecedor</td>
                <td><strong><?php echo htmlspecialchars($recibo['fornecedor_nome'] ?? $fornecedor['nome_fantasia'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td class="bg-label">CNPJ / CPF</td>
                <td><?php echo htmlspecialchars($recibo['fornecedor_cnpj'] ?? $fornecedor['cnpj_cpf'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
                <td class="bg-label">Forma de Liquidação</td>
                <td><?php echo $formaPagamentoNome; ?></td>
                <td class="bg-label">Data de Liquidação</td>
                <td><?php echo $dataPagamento; ?></td>
            </tr>
            <?php if (!empty($recibo['observacoes'])): ?>
            <tr>
                <td class="bg-label">Observações / NF</td>
                <td colspan="3"><?php echo htmlspecialchars($recibo['observacoes'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="assinaturas-row">
            <div class="assinatura-box">
                <strong><?php echo htmlspecialchars($recibo['fornecedor_nome'] ?? 'Fornecedor Favorecido', ENT_QUOTES, 'UTF-8'); ?></strong>
                Assinatura do Favorecido / Recebedor
            </div>
            <div class="assinatura-box">
                <strong>Cantina Colégio Monteiro Lobato</strong>
                Operador / Responsável Financeiro
            </div>
        </div>
    </div>

    <div class="divisor-vias"></div>

    <!-- VIA 2: DO FORNECEDOR / PARCEIRO -->
    <div class="via-box">
        <span class="via-tag">2ª Via — Fornecedor Parceiro</span>

        <div class="header-recibo">
            <div class="header-logo">
                <?php if (!empty($logoDataUri)): ?>
                    <img src="<?php echo $logoDataUri; ?>" alt="Logo Colégio Monteiro Lobato">
                <?php endif; ?>
                <div class="header-title-box">
                    <h1>Colégio Monteiro Lobato</h1>
                    <div class="sub">Cantina Web • Comprovante de Pagamento a Fornecedor</div>
                </div>
            </div>
            <div class="header-meta">
                <div class="num-recibo"><?php echo htmlspecialchars($recibo['numero_recibo'] ?? 'REC-0000', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="data-emissao">Data: <?php echo $dataPagamento; ?></div>
            </div>
        </div>

        <div class="valor-destaque-box">
            <span class="valor-label">Valor Quitado</span>
            <span class="valor-num">R$ <?php echo number_format((float)($recibo['valor'] ?? 0), 2, ',', '.'); ?></span>
        </div>

        <div class="corpo-texto">
            Recebemos de <strong>CANTINA ESCOLAR — COLÉGIO MONTEIRO LOBATO</strong> a quantia líquida de 
            <strong>R$ <?php echo number_format((float)($recibo['valor'] ?? 0), 2, ',', '.'); ?></strong> 
            (<em><?php echo htmlspecialchars($valorExtenso, ENT_QUOTES, 'UTF-8'); ?></em>), 
            referente a <strong><?php echo htmlspecialchars($recibo['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>, 
            efetuado mediante <strong><?php echo $formaPagamentoNome; ?></strong>, dando plena, geral e irrevogável quitação.
        </div>

        <table class="dados-grid">
            <tr>
                <td class="bg-label">Favorecido / Fornecedor</td>
                <td><strong><?php echo htmlspecialchars($recibo['fornecedor_nome'] ?? $fornecedor['nome_fantasia'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                <td class="bg-label">CNPJ / CPF</td>
                <td><?php echo htmlspecialchars($recibo['fornecedor_cnpj'] ?? $fornecedor['cnpj_cpf'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
                <td class="bg-label">Forma de Liquidação</td>
                <td><?php echo $formaPagamentoNome; ?></td>
                <td class="bg-label">Data de Liquidação</td>
                <td><?php echo $dataPagamento; ?></td>
            </tr>
            <?php if (!empty($recibo['observacoes'])): ?>
            <tr>
                <td class="bg-label">Observações / NF</td>
                <td colspan="3"><?php echo htmlspecialchars($recibo['observacoes'], ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <div class="assinaturas-row">
            <div class="assinatura-box">
                <strong><?php echo htmlspecialchars($recibo['fornecedor_nome'] ?? 'Fornecedor Favorecido', ENT_QUOTES, 'UTF-8'); ?></strong>
                Assinatura do Favorecido / Recebedor
            </div>
            <div class="assinatura-box">
                <strong>Cantina Colégio Monteiro Lobato</strong>
                Operador / Responsável Financeiro
            </div>
        </div>
    </div>

</div>

</body>
</html>
