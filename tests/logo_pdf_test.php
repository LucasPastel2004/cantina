<?php
/**
 * Teste Automatizado: Verificação da Inclusão do Logotipo Oficial
 * nos Documentos PDF e Espelhos HTML do Sistema
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/services/PdfService.php';

$totalTestes = 0;
$testesPassados = 0;

function assertTeste($condicao, $mensagem) {
    global $totalTestes, $testesPassados;
    $totalTestes++;
    if ($condicao) {
        $testesPassados++;
        echo "  [OK] $mensagem\n";
    } else {
        echo "  [FALHA] $mensagem\n";
    }
}

echo "=======================================================\n";
echo "INICIANDO TESTES DO LOGOTIPO NOS DOCUMENTOS E PDFS\n";
echo "=======================================================\n\n";

// 1. Helpers de Logotipo
echo "1. Métodos Auxiliares de Logotipo:\n";
$logoPath = \App\Services\PdfService::getLogoPath();
assertTeste(!empty($logoPath) && file_exists($logoPath), "PdfService::getLogoPath() localiza o arquivo do logotipo ($logoPath)");

$logoUri = \App\Services\PdfService::getLogoDataUri();
assertTeste(str_starts_with($logoUri, 'data:image/jpeg;base64,'), "PdfService::getLogoDataUri() retorna data URI JPEG válida");
assertTeste(strlen($logoUri) > 1000, "PdfService::getLogoDataUri() contém conteúdo base64 substancial (" . strlen($logoUri) . " bytes)");

// 2. PDFs Binários
echo "\n2. Geração e Estrutura dos Arquivos PDF 1.4:\n";

// 2.1 Relatório de Corte PDF
$cortePdf = \App\Services\PdfService::gerarRelatorioCortePdf([
    'data_consumo' => date('Y-m-d'),
    'turno_consumo' => 'matutino',
    'total_pecas' => 15,
    'total_pedidos' => 5,
    'total_alunos' => 5,
    'itens' => [
        ['produto_codigo' => 'SAL01', 'produto_nome' => 'Coxinha de Frango', 'categoria_nome' => 'Salgados', 'quantidade_total' => 10],
        ['produto_codigo' => 'SAL02', 'produto_nome' => 'Enroladinho de Presunto', 'categoria_nome' => 'Salgados', 'quantidade_total' => 5]
    ]
]);
assertTeste(str_starts_with($cortePdf, '%PDF-1.4'), "Relatório de Corte PDF inicia com %PDF-1.4");
assertTeste(str_contains($cortePdf, '/Im1 7 0 R'), "Relatório de Corte PDF referencia /Im1 como recurso XObject");
assertTeste(str_contains($cortePdf, '/DCTDecode'), "Relatório de Corte PDF contém stream JPEG /DCTDecode");
assertTeste(str_contains($cortePdf, '/Im1 Do'), "Relatório de Corte PDF executa desenho do logo (/Im1 Do)");
assertTeste(str_contains($cortePdf, "W n"), "Relatório de Corte PDF aplica recorte circular Bézier (W n) no logotipo");
assertTeste(str_ends_with(trim($cortePdf), '%%EOF'), "Relatório de Corte PDF finaliza corretamente com %%EOF");

// 2.2 Fechamento de Caixa PDF
$caixaPdf = \App\Services\PdfService::gerarEspelhoFechamentoCaixaPdf([
    'id' => 99,
    'turno' => 'matutino',
    'operador_nome' => 'Operador Teste',
    'aberto_em' => date('Y-m-d 07:00:00'),
    'fechado_em' => date('Y-m-d 13:00:00'),
    'status' => 'fechado',
    'fundo_troco_inicial' => 100.0,
    'total_dinheiro_avulso' => 250.0,
    'total_dinheiro_recargas' => 150.0,
    'total_pix_balcao' => 80.0,
    'total_informado_gaveta' => 500.0,
    'diferenca_quebra_sobra' => 0.0,
    'observacoes' => 'Sem divergências.'
]);
assertTeste(str_starts_with($caixaPdf, '%PDF-1.4'), "Fechamento de Caixa PDF inicia com %PDF-1.4");
assertTeste(str_contains($caixaPdf, '/Im1 7 0 R'), "Fechamento de Caixa PDF referencia /Im1 como recurso XObject");
assertTeste(str_contains($caixaPdf, '/DCTDecode'), "Fechamento de Caixa PDF contém stream JPEG /DCTDecode");
assertTeste(str_contains($caixaPdf, '/Im1 Do'), "Fechamento de Caixa PDF executa desenho do logo (/Im1 Do)");
assertTeste(str_contains($caixaPdf, "W n"), "Fechamento de Caixa PDF aplica recorte circular Bézier (W n) no logotipo");
assertTeste(str_contains($caixaPdf, "425.28 786.00 Td"), "Fechamento de Caixa PDF posiciona emissão na linha superior (X=425.28, Y=786.00)");
assertTeste(str_contains($caixaPdf, "94.00 769.00 Td"), "Fechamento de Caixa PDF posiciona subtítulo na linha inferior sem colisão (X=94.00, Y=769.00)");
assertTeste(str_ends_with(trim($caixaPdf), '%%EOF'), "Fechamento de Caixa PDF finaliza com %%EOF");

// 2.3 Comprovante de Venda Balcão PDF (80mm)
$vendaPdf = \App\Services\PdfService::gerarComprovanteVendaBalcaoPdf([
    'id' => 1234,
    'created_at' => date('Y-m-d H:i:s'),
    'operador_nome' => 'Operador Caixa',
    'forma_pagamento' => 'dinheiro',
    'valor_total' => 25.0,
    'valor_dinheiro_recebido' => 30.0,
    'troco_devolvido' => 5.0,
    'sessao_caixa_id' => 10,
    'sessao_turno' => 'matutino',
    'itens' => [
        ['produto_descricao' => 'Suco de Laranja 300ml', 'quantidade' => 1, 'subtotal' => 7.0],
        ['produto_descricao' => 'Pão de Queijo Grande', 'quantidade' => 2, 'subtotal' => 18.0]
    ]
]);
assertTeste(str_starts_with($vendaPdf, '%PDF-1.4'), "Comprovante Balcão PDF inicia com %PDF-1.4");
assertTeste(str_contains($vendaPdf, '/Im1 7 0 R'), "Comprovante Balcão PDF referencia /Im1 como recurso");
assertTeste(str_contains($vendaPdf, '/Im1 Do'), "Comprovante Balcão PDF executa desenho centralizado do logo");
assertTeste(str_ends_with(trim($vendaPdf), '%%EOF'), "Comprovante Balcão PDF finaliza com %%EOF");

// 2.4 Tickets Lote PDF e Individual PDF (80mm)
$ticketMock = [
    'id' => 1,
    'codigo_ticket' => 'TCK-2026-001',
    'data_consumo' => date('Y-m-d'),
    'turno_consumo' => 'matutino',
    'aluno_nome' => 'Lucas Silva',
    'aluno_matricula' => 'MAT-202601',
    'turma_id' => 5,
    'nome_turma' => '7º Ano A',
    'serie' => '7º Fundamental II',
    'ordem_impressao' => 1,
    'produto_descricao' => 'Pão de Queijo Grande',
    'item_quantidade' => 1,
    'produto_preco_venda' => 9.0,
    'tag_alergenos' => 'CONTÉM GLÚTEN E DERIVADOS DE LEITE',
    'numero_via' => 1
];

$ticketsLotePdf = \App\Services\PdfService::gerarTicketsLotePdf([$ticketMock]);
assertTeste(str_contains($ticketsLotePdf, '/Im1 7 0 R'), "Tickets em Lote PDF referencia /Im1");
assertTeste(str_contains($ticketsLotePdf, '/Im1 Do'), "Tickets em Lote PDF desenha o logotipo em cada ticket");

$ticketIndPdf = \App\Services\PdfService::gerarTicketIndividualPdf($ticketMock);
assertTeste(str_contains($ticketIndPdf, '/Im1 7 0 R'), "Ticket Individual PDF referencia /Im1");
assertTeste(str_contains($ticketIndPdf, '/Im1 Do'), "Ticket Individual PDF desenha o logotipo");

// 3. Espelhos e Documentos HTML de Impressão
echo "\n3. Espelhos HTML de Impressão com Data URI do Logotipo:\n";

$corteHtml = \App\Services\PdfService::gerarHtmlRelatorioCorte([
    'data_consumo' => date('Y-m-d'),
    'turno_consumo' => 'matutino',
    'total_pecas' => 5,
    'status_corte' => ['rotulo' => 'Corte Fechado'],
    'itens' => []
]);
assertTeste(str_contains($corteHtml, 'data:image/jpeg;base64,'), "HTML Relatório de Corte inclui tag img com data:image/jpeg");
assertTeste(str_contains($corteHtml, 'Colégio Monteiro Lobato — Cantina Escolar'), "HTML Relatório de Corte contém título institucional");

$caixaHtml = \App\Services\PdfService::gerarEspelhoFechamentoCaixaHtml([
    'id' => 99,
    'turno' => 'matutino',
    'operador_nome' => 'Operador Teste',
    'status' => 'fechado'
]);
assertTeste(str_contains($caixaHtml, 'data:image/jpeg;base64,'), "HTML Fechamento de Caixa inclui logotipo em data URI");
assertTeste(str_contains($caixaHtml, 'Colégio Monteiro Lobato'), "HTML Fechamento de Caixa contém denominação do colégio");

$vendaHtml = \App\Services\PdfService::gerarEspelhoComprovanteVendaBalcaoHtml([
    'id' => 1234,
    'created_at' => date('Y-m-d H:i:s'),
    'operador_nome' => 'Operador',
    'forma_pagamento' => 'dinheiro'
]);
assertTeste(str_contains($vendaHtml, 'data:image/jpeg;base64,'), "HTML Comprovante Balcão inclui logotipo em data URI");
assertTeste(str_contains($vendaHtml, 'COLÉGIO MONTEIRO LOBATO'), "HTML Comprovante Balcão contém título institucional");

$ticketsLoteHtml = \App\Services\PdfService::gerarEspelhoTicketsLoteHtml([$ticketMock]);
assertTeste(str_contains($ticketsLoteHtml, 'data:image/jpeg;base64,'), "HTML Tickets Lote inclui logotipo nos cartões de tickets");

$ticketIndHtml = \App\Services\PdfService::gerarEspelhoTicketIndividualHtml($ticketMock);
assertTeste(str_contains($ticketIndHtml, 'data:image/jpeg;base64,'), "HTML Ticket Individual inclui logotipo nos cartões");

echo "\n=======================================================\n";
echo "RESULTADO: $testesPassados de $totalTestes testes passaram com sucesso.\n";
echo "=======================================================\n";

if ($testesPassados === $totalTestes) {
    exit(0);
} else {
    exit(1);
}
