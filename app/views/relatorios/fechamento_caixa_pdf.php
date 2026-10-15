<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Espelho Oficial A4 de Fechamento de Caixa
 * 
 * Conforme docs/alterações sistema guideline/28 setembro matutino.pdf e 28 setembro vespertino.pdf
 * Contém as 4 seções escolares:
 * 1. Duplicatas (Recargas de alunos/responsáveis)
 * 2. Vendas (Balcão e Online com segregação e colaboradores unificados)
 * 3. Recebimento em Pix (Conferência bancária Nubank)
 * 4. Resumo do Caixa (Box contábil de Fundo, Entradas, Saídas e Saldo)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

if (!isset($baseUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
}

$sessao = $dadosFechamento['sessao'] ?? ($sessao ?? []);
$duplicatas = $dadosFechamento['duplicatas'] ?? ($duplicatas ?? []);
$subtotalDuplicatas = $dadosFechamento['subtotal_duplicatas'] ?? ($subtotalDuplicatas ?? 0.0);
$vendas = $dadosFechamento['vendas'] ?? ($vendas ?? []);
$vendasCantinaWeb = $dadosFechamento['vendas_cantina_web'] ?? [];
$vendasBalcao = $dadosFechamento['vendas_balcao'] ?? [];
$subtotalCantinaWeb = (float)($dadosFechamento['subtotal_cantina_web'] ?? 0.0);
$subtotalBalcao = (float)($dadosFechamento['subtotal_balcao'] ?? 0.0);
$subtotalVendas = (float)($dadosFechamento['subtotal_vendas'] ?? ($subtotalVendas ?? 0.0));

// Fallback defensivo para segregar vendas caso não fornecidas previamente separadas
if (empty($vendasCantinaWeb) && empty($vendasBalcao) && !empty($vendas)) {
    foreach ($vendas as $v) {
        $val = (float)($v['valor_pago'] ?? ($v['valor'] ?? 0));
        if (($v['origem'] ?? '') === 'online' || ($v['tipo'] ?? '') === 'Saldo') {
            $vendasCantinaWeb[] = $v;
            $subtotalCantinaWeb += $val;
        } else {
            $vendasBalcao[] = $v;
            $subtotalBalcao += $val;
        }
    }
}
if ($subtotalVendas <= 0.0) {
    $subtotalVendas = $subtotalCantinaWeb + $subtotalBalcao;
}

$pixRecebimentos = $dadosFechamento['pix_recebimentos'] ?? ($pixRecebimentos ?? []);
$totalPixRecebimentos = $dadosFechamento['total_pix_recebimentos'] ?? ($totalPixRecebimentos ?? 0.0);
$resumoCaixa = $dadosFechamento['resumo_caixa'] ?? ($resumoCaixa ?? []);

$sessaoId = (int)($sessao['id'] ?? 0);
$controleStr = sprintf('%06d', $sessaoId);
$operadorNome = mb_strtoupper($sessao['operador_nome'] ?? 'OPERADOR');
$turnoNome = mb_strtoupper($sessao['turno'] ?? 'MATUTINO');
$operadorTurno = "{$operadorNome} - {$turnoNome}";

$abertoEm = !empty($sessao['aberto_em']) ? date('d/m/Y H:i', strtotime($sessao['aberto_em'])) : '-';
$fechadoEm = !empty($sessao['fechado_em']) ? date('d/m/Y H:i', strtotime($sessao['fechado_em'])) : 'Em aberto';
$fundoCaixa = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
$emissaoDataHora = date('d/m/Y H:i');

// Totais do Resumo (exclusivamente dinheiro e Pix)
$fundo = $resumoCaixa['fundo'] ?? ['dinheiro' => $fundoCaixa, 'pix' => 0.0, 'total' => $fundoCaixa];
$entradas = $resumoCaixa['entradas'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$saidas = $resumoCaixa['saidas'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$saldo = $resumoCaixa['saldo'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa #<?php echo $controleStr; ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 14mm 10mm 14mm;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
            line-height: 1.25;
            width: 100%;
        }

        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 20px 10px;
            }
            .container {
                width: 210mm;
                max-width: calc(100% - 24px);
                min-height: 297mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 10mm 14mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                position: relative;
            }
            .no-print {
                margin-bottom: 15px;
                background: #f8fafc;
                padding: 8px 12px;
                border: 1px solid #cbd5e1;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 4px;
            }
        }

        @media print {
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .header-top, .resumo-box, .footer-note, .table-control { page-break-inside: avoid; break-inside: avoid; }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2px;
        }
        .school-name {
            font-size: 11pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        .emission-date {
            font-size: 8.5pt;
            color: #000000;
            padding-right: 2px;
        }
        .doc-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 2px 0 6px 0;
            color: #000000;
        }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            margin: 10px 0 3px 0;
            color: #000000;
        }
        table {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-bottom: 6px;
            font-size: 8pt;
            border: 1px solid #000000 !important;
        }
        th {
            font-weight: bold;
            border: 1px solid #000000 !important;
            padding: 2px 4px;
            text-align: left;
            background: #ffffff !important;
        }
        th:first-child,
        td:first-child {
            border-left: 1px solid #000000 !important;
        }
        th:last-child,
        td:last-child {
            border-right: 1px solid #000000 !important;
        }
        td {
            padding: 2px 4px;
            border: 1px solid #000000 !important; /* Linhas pretas estilo Excel */
            background: #ffffff !important; /* Fundo branco puro nos itens das listas */
            overflow-wrap: break-word;
            word-break: break-word;
        }
        tbody tr td {
            background: #ffffff !important;
        }
        .table-control th {
            border: 1px solid #000000 !important;
            background: #ffffff !important;
            padding: 3px 4px;
        }
        .table-control td {
            border: 1px solid #000000 !important;
            padding: 4px;
            font-weight: normal;
            background: #ffffff !important;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1, "zero" 1;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .row-subtotal td {
            font-weight: bold;
            border-top: 1px solid #000000 !important;
            border-bottom: 1.5px solid #000000 !important;
            background: #ffffff !important;
        }
        .tag-origem {
            font-size: 7pt;
            padding: 1px 3px;
            border-radius: 2px;
            text-transform: uppercase;
        }
        .tag-online {
            background: #ffffff !important;
            border: 1px solid #1d4ed8;
            color: #1d4ed8;
            font-weight: bold;
        }
        .tag-balcao {
            background: #ffffff !important;
            border: 1px solid #64748b;
            color: #334155;
        }
        
        /* Grid de Resumo do Caixa */
        .resumo-box {
            border: 1px solid #000000;
            margin-top: 6px;
            padding: 6px 8px;
            font-size: 8pt;
        }
        .resumo-table {
            width: 100%;
            border-collapse: collapse;
        }
        .resumo-table th {
            border-bottom: 1px solid #000000;
            padding: 2px 4px;
            font-weight: bold;
        }
        .resumo-table td {
            border: none;
            padding: 1.5px 4px;
        }
        .footer-note {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            color: #475569;
            border-top: 1px solid #000000;
            padding-top: 4px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="no-print">
        <span><strong>Fechamento de Caixa</strong> — Sessão #<?php echo $controleStr; ?> (<?php echo htmlspecialchars($operadorTurno); ?>)</span>
        <div>
            <button type="button" onclick="window.print()" style="padding: 5px 12px; font-weight: bold; background: #1d4ed8; color: #fff; border: 1px solid #1e40af; cursor: pointer; border-radius: 3px;">
                Imprimir Documento
            </button>
            <button type="button" onclick="fecharJanela()" style="padding: 5px 10px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer; border-radius: 3px; margin-left: 6px;">
                Fechar
            </button>
        </div>
    </div>

    <!-- CABEÇALHO INSTITUCIONAL -->
    <div class="header-top">
        <div style="display: flex; align-items: center; gap: 8px;">
            <?php if (!empty($logoDataUri)): ?>
                <img src="<?php echo $logoDataUri; ?>" alt="Logo" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
            <?php endif; ?>
            <div class="school-name">Colégio Monteiro Lobato</div>
        </div>
        <div class="emission-date font-tabular"><?php echo $emissaoDataHora; ?></div>
    </div>
    <div class="doc-title">Fechamento de Caixa #<?php echo (int)($sessao['id'] ?? 0); ?></div>

    <!-- BARRA DE CONTROLE DA SESSÃO -->
    <table class="table-control">
        <thead>
            <tr>
                <th style="width: 12%;">Controle</th>
                <th style="width: 38%;">Operador</th>
                <th style="width: 18%;">Abertura</th>
                <th style="width: 18%;">Fechamento</th>
                <th style="width: 14%;" class="text-right">Fundo de Troco Inicial</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-tabular"><strong><?php echo $controleStr; ?></strong></td>
                <td><?php echo htmlspecialchars($operadorTurno); ?></td>
                <td class="font-tabular"><?php echo $abertoEm; ?></td>
                <td class="font-tabular"><?php echo $fechadoEm; ?></td>
                <td class="font-tabular text-right">R$ <?php echo number_format($fundoCaixa, 2, ',', '.'); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- SEÇÃO 1: DUPLICATAS (RECARGAS) -->
    <div class="section-title">Duplicatas</div>
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">Duplicata</th>
                <th style="width: 31%;">Aluno</th>
                <th style="width: 8%;">Referência</th>
                <th style="width: 8%;">Finalidade</th>
                <th style="width: 8%;">Vencto</th>
                <th style="width: 8%;" class="text-right">Vlr Nominal</th>
                <th style="width: 5%;" class="text-right">Multa</th>
                <th style="width: 5%;" class="text-right">Juros</th>
                <th style="width: 5%;" class="text-right">Desconto</th>
                <th style="width: 8%;" class="text-right">Vlr Recebido</th>
                <th style="width: 6%;" class="text-center">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($duplicatas)): ?>
                <tr>
                    <td colspan="11" class="text-center" style="color: #64748b; padding: 6px;">Nenhuma duplicata ou recarga registrada nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($duplicatas as $dup): ?>
                    <tr>
                        <td class="font-tabular"><?php echo htmlspecialchars($dup['numero'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($dup['aluno'] ?? ''); ?></td>
                        <td class="font-tabular"><?php echo htmlspecialchars($dup['referencia'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($dup['finalidade'] ?? 'CANTINA'); ?></td>
                        <td class="font-tabular"><?php echo htmlspecialchars($dup['vencimento'] ?? ''); ?></td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($dup['valor_nominal'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="font-tabular text-right">0,00</td>
                        <td class="font-tabular text-right">0,00</td>
                        <td class="font-tabular text-right">0,00</td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($dup['valor_recebido'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($dup['tipo'] ?? 'Dinheiro'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="row-subtotal">
                    <td colspan="5">Subtotal</td>
                    <td class="font-tabular text-right"><?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></td>
                    <td class="font-tabular text-right">0,00</td>
                    <td class="font-tabular text-right">0,00</td>
                    <td class="font-tabular text-right">0,00</td>
                    <td class="font-tabular text-right"><?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 2: VENDAS CANTINA WEB (ONLINE) -->
    <div class="section-title">Vendas Cantina Web</div>
    <table>
        <thead>
            <tr>
                <th style="width: 32%;">Alimento</th>
                <th style="width: 32%;">Aluno</th>
                <th style="width: 10%;" class="text-right">PrcUnitário</th>
                <th style="width: 6%;" class="text-center">Qtde</th>
                <th style="width: 11%;" class="text-right">Vlr Pago</th>
                <th style="width: 9%;" class="text-center">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendasCantinaWeb)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #64748b; padding: 6px;">Nenhuma venda online do Cantina Web registrada nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vendasCantinaWeb as $v): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($v['alimento'] ?? ($v['produto_descricao'] ?? '')); ?>
                            <span class="tag-origem tag-online">Online</span>
                        </td>
                        <td><?php echo htmlspecialchars($v['pessoa'] ?? ($v['cliente_ident'] ?? '')); ?></td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($v['preco_unitario'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="font-tabular text-center"><?php echo (int)($v['quantidade'] ?? 1); ?></td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($v['valor_pago'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($v['tipo'] ?? 'Saldo'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="row-subtotal">
                    <td colspan="4">Subtotal Cantina Web</td>
                    <td class="font-tabular text-right"><?php echo number_format($subtotalCantinaWeb, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 3: VENDAS PRESENCIAIS NO BALCÃO -->
    <div class="section-title">Vendas Presenciais no Balcão</div>
    <table>
        <thead>
            <tr>
                <th style="width: 32%;">Alimento</th>
                <th style="width: 32%;">Pessoa / Cliente</th>
                <th style="width: 10%;" class="text-right">PrcUnitário</th>
                <th style="width: 6%;" class="text-center">Qtde</th>
                <th style="width: 11%;" class="text-right">Vlr Pago</th>
                <th style="width: 9%;" class="text-center">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendasBalcao)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #64748b; padding: 6px;">Nenhuma venda presencial no balcão registrada nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vendasBalcao as $v): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($v['alimento'] ?? ($v['produto_descricao'] ?? '')); ?>
                            <span class="tag-origem tag-balcao">Balcão</span>
                        </td>
                        <td><?php echo htmlspecialchars($v['pessoa'] ?? ($v['cliente_ident'] ?? 'Balcão')); ?></td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($v['preco_unitario'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="font-tabular text-center"><?php echo (int)($v['quantidade'] ?? 1); ?></td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($v['valor_pago'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($v['tipo'] ?? 'Dinheiro'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="row-subtotal">
                    <td colspan="4">Subtotal Balcão</td>
                    <td class="font-tabular text-right"><?php echo number_format($subtotalBalcao, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 4: RECEBIMENTO EM PIX -->
    <div class="section-title">Recebimento em Pix</div>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Banco</th>
                <th style="width: 45%;">Descrição</th>
                <th style="width: 15%;">Observação</th>
                <th style="width: 15%;" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pixRecebimentos)): ?>
                <tr>
                    <td colspan="4" class="text-center" style="color: #64748b; padding: 6px;">Nenhum recebimento Pix conciliado nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pixRecebimentos as $px): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($px['banco'] ?? 'NUBANK - CANTINA'); ?></td>
                        <td><?php echo htmlspecialchars($px['descricao'] ?? 'Credito transferência pix'); ?></td>
                        <td><?php echo htmlspecialchars($px['observacao'] ?? 'via pix'); ?></td>
                        <td class="font-tabular text-right"><?php echo number_format((float)($px['valor'] ?? 0), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="row-subtotal">
                    <td colspan="3">Subtotal Pix</td>
                    <td class="font-tabular text-right"><?php echo number_format($totalPixRecebimentos, 2, ',', '.'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 5: RESUMO DO CAIXA -->
    <div class="section-title">Resumo do Caixa - <?php echo htmlspecialchars($operadorTurno); ?></div>
    <div class="resumo-box">
        <table class="resumo-table">
            <thead>
                <tr>
                    <th style="width: 25%;">(+) Fundo de Caixa <?php echo number_format((float)($fundo['total'] ?? 0), 2, ',', '.'); ?></th>
                    <th style="width: 25%;">(+) Entradas <?php echo number_format((float)($entradas['total'] ?? 0), 2, ',', '.'); ?></th>
                    <th style="width: 25%;">(-) Saídas <?php echo number_format((float)($saidas['total'] ?? 0), 2, ',', '.'); ?></th>
                    <th style="width: 25%;">(=) Saldo de Caixa <?php echo number_format((float)($saldo['total'] ?? 0), 2, ',', '.'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="font-tabular">Em Dinheiro <?php echo number_format((float)($fundo['dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                    <td class="font-tabular">Em Dinheiro <?php echo number_format((float)($entradas['dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                    <td class="font-tabular">Em Dinheiro <?php echo number_format((float)($saidas['dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                    <td class="font-tabular"><strong>Em Dinheiro <?php echo number_format((float)($saldo['dinheiro'] ?? 0), 2, ',', '.'); ?></strong></td>
                </tr>
                <tr>
                    <td class="font-tabular"></td>
                    <td class="font-tabular">Em Pix <?php echo number_format((float)($entradas['pix'] ?? 0), 2, ',', '.'); ?></td>
                    <td class="font-tabular">Em Pix <?php echo number_format((float)($saidas['pix'] ?? 0), 2, ',', '.'); ?></td>
                    <td class="font-tabular"><strong>Em Pix <?php echo number_format((float)($saldo['pix'] ?? 0), 2, ',', '.'); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ASSINATURAS DO FECHAMENTO -->
    <div style="display: flex; justify-content: space-between; margin-top: 25px; padding-top: 15px;">
        <div style="width: 45%; text-align: center; border-top: 1px solid #000; font-size: 8pt; padding-top: 4px;">
            Assinatura do Operador de Caixa
        </div>
        <div style="width: 45%; text-align: center; border-top: 1px solid #000; font-size: 8pt; padding-top: 4px;">
            Visto da Gerência / Supervisão
        </div>
    </div>

    <!-- RODAPÉ OFICIAL -->
    <div class="footer-note">
        <span>Escolar Manager Softwares Para Gestão Escolar &bull; www.escolarmanager.com.br</span>
        <span>Colégio Monteiro Lobato &bull; Gestão de Cantina</span>
    </div>
</div>

<script>
function fecharJanela() {
    window.close();
    // Fallback defensivo: se a janela não puder ser fechada por ter sido aberta diretamente ou bloqueada
    setTimeout(function() {
        if (!window.closed) {
            if (window.opener) {
                window.close();
            } else if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?php echo $baseUrl; ?>/operacional/caixa';
            }
        }
    }, 150);
}
</script>
</body>
</html>
