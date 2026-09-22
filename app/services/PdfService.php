<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Serviço de Geração de Documentos e Relatórios em PDF
 * 
 * Gerador nativo e autônomo de arquivos PDF 1.4 compatíveis no formato A4 timbrado
 * e renderizador de espelhos de impressão contínua e relatórios operacionais.
 * 
 * Conforme docs/FSD.md (Área 2, Tela 3.1, Fluxo 5) e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class PdfService
{
    /**
     * Largura da página A4 em pontos (72 dpi)
     */
    public const PAGE_WIDTH = 595.28;

    /**
     * Altura da página A4 em pontos (72 dpi)
     */
    public const PAGE_HEIGHT = 841.89;

    /**
     * Largura da bobina térmica de 80mm em pontos (72 dpi): 80 / 25.4 * 72 = 226.77 pt
     */
    public const PAGE_WIDTH_THERMAL = 226.77;

    /**
     * Retorna o caminho absoluto do arquivo do logotipo oficial da instituição
     */
    public static function getLogoPath(): string
    {
        $caminho = dirname(__DIR__, 2) . '/assets/img/logo-colegio.jpg';
        if (file_exists($caminho)) {
            return $caminho;
        }

        $candidatos = [
            'assets/img/logo-colegio.jpg',
            __DIR__ . '/../../assets/img/logo-colegio.jpg',
            '/var/www/html/assets/img/logo-colegio.jpg'
        ];
        foreach ($candidatos as $c) {
            if (file_exists($c)) {
                return realpath($c) ?: $c;
            }
        }
        return '';
    }

    /**
     * Retorna a representação em base64 Data-URI do logotipo oficial para inclusão em HTML
     */
    public static function getLogoDataUri(): string
    {
        $path = self::getLogoPath();
        if (!empty($path) && file_exists($path)) {
            $data = file_get_contents($path);
            if ($data !== false) {
                return 'data:image/jpeg;base64,' . base64_encode($data);
            }
        }
        return '';
    }

    /**
     * Gera o relatório consolidado de compras para o fornecedor em formato binário PDF 1.4
     * 
     * @param array $dadosCorte Estrutura retornada por Pedido::consolidarCorte
     * @return string Conteúdo binário puro do PDF
     */
    public static function gerarRelatorioCortePdf(array $dadosCorte): string
    {
        $dataConsumo = $dadosCorte['data_consumo'] ?? date('Y-m-d');
        $dataFmt = date('d/m/Y', strtotime($dataConsumo));
        $turno = ucfirst($dadosCorte['turno_consumo'] ?? 'matutino');
        $itens = $dadosCorte['itens'] ?? [];
        $totalPecas = (int)($dadosCorte['total_pecas'] ?? 0);
        $totalPedidos = (int)($dadosCorte['total_pedidos'] ?? 0);
        $totalAlunos = (int)($dadosCorte['total_alunos'] ?? 0);
        $emissao = date('d/m/Y H:i:s');
        $statusCorte = $dadosCorte['status_corte']['rotulo'] ?? 'Corte Fechado';

        // Instruções de desenho da página no stream PDF (coordenadas de baixo para cima)
        $stream = "";

        // Dimensões úteis
        $left = 40.0;
        $right = self::PAGE_WIDTH - 40.0;
        $width = $right - $left;

        // Cabeçalho institucional: Faixa superior sutil e linhas divisórias
        // Topo: Y = 800
        $y = 800.0;

        // Faixa de topo institucional (azul cobalto sutil / slate 100)
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.94, 0.96, 0.98); // #f1f5f9
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 45, $width, 55);

        // Barra lateral de destaque (azul cobalto #1d4ed8)
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 45, 4, 55);

        // Logotipo institucional oficial (42x42 pt)
        $logoSize = 42.0;
        $logoX = $left + 12.0;
        $logoY = $y - 40.0;
        $stream .= "q\n";
        $stream .= sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $logoSize, $logoSize, $logoX, $logoY);
        $stream .= "/Im1 Do\n";
        $stream .= "Q\n";

        $textX = $logoX + $logoSize + 12.0;

        // Título Principal
        $stream .= "BT\n";
        $stream .= "/F2 13.5 Tf\n"; // Helvetica-Bold 13.5
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16); // #0f172a
        $stream .= sprintf("%.2f %.2f Td\n", $textX, $y - 12);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("COLÉGIO MONTEIRO LOBATO — CANTINA ESCOLAR"));
        $stream .= "ET\n";

        // Subtítulo
        $stream .= "BT\n";
        $stream .= "/F2 9.5 Tf\n"; // Helvetica-Bold 9.5
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
        $stream .= sprintf("%.2f %.2f Td\n", $textX, $y - 27);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("PEDIDO DE SALGADOS — CONSOLIDAÇÃO DO CORTE OPERACIONAL"));
        $stream .= "ET\n";

        // Lema institucional e emissão à direita
        $stream .= "BT\n";
        $stream .= "/F1 8 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.40, 0.45, 0.55); // #64748b
        $stream .= sprintf("%.2f %.2f Td\n", $textX, $y - 39);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Valores para o Futuro! • Emissão: " . $emissao));
        $stream .= "ET\n";

        // Box de Parâmetros Operacionais (Data, Turno, Horário de Corte, Status)
        $y -= 60.0;
        $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.88, 0.91, 0.94); // Borda #e2e8f0
        $stream .= "0.75 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - 36, $width, 42);

        // Colunas do Box
        $colW = $width / 4;

        $labels = [
            ['DATA DE CONSUMO', $dataFmt],
            ['TURNO DO CORTE', $turno],
            ['TOTAL DE SALGADOS', $totalPecas . ' peças'],
            ['STATUS DO CORTE', $statusCorte]
        ];

        for ($i = 0; $i < 4; $i++) {
            $colX = $left + ($i * $colW) + 8;
            $stream .= "BT\n";
            $stream .= "/F2 7 Tf\n"; // Negrito pequeno
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.35, 0.40, 0.48); // #5a6578
            $stream .= sprintf("%.2f %.2f Td\n", $colX, $y - 12);
            $stream .= sprintf("(%s) Tj\n", self::escapeText($labels[$i][0]));
            $stream .= "ET\n";

            $stream .= "BT\n";
            $stream .= "/F2 11 Tf\n"; // Negrito valor
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
            $stream .= sprintf("%.2f %.2f Td\n", $colX, $y - 28);
            $stream .= sprintf("(%s) Tj\n", self::escapeText($labels[$i][1]));
            $stream .= "ET\n";

            if ($i > 0) {
                // Divisor vertical
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($i * $colW), $y - 36, $left + ($i * $colW), $y + 6);
            }
        }

        // Título da Tabela
        $y -= 52.0;
        $stream .= "BT\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("RELAÇÃO DE SALGADOS CONSOLIDADOS"));
        $stream .= "ET\n";

        // Tabela: Cabeçalho
        $y -= 16.0;
        $rowHeight = 20.0;

        // Fundo do cabeçalho da tabela
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.96, 0.97, 0.98); // #f8fafc
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowHeight + 4, $width, $rowHeight);

        // Borda inferior do cabeçalho
        $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.80, 0.83, 0.88); // #cbd5e1
        $stream .= "1 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowHeight + 4, $right, $y - $rowHeight + 4);

        // Colunas e larguras
        // Código: 80pt, Produto: 240pt, Categoria: 115pt, Quantidade: 80pt (Total 515pt)
        $col1 = $left + 6;          // Código
        $col2 = $left + 90;         // Descrição
        $col3 = $left + 330;        // Categoria
        $col4 = $right - 10;        // Quantidade (alinhada à direita)

        $stream .= "BT\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.28, 0.33, 0.41); // #475569
        $stream .= sprintf("%.2f %.2f Td\n", $col1, $y - 10);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("CÓDIGO"));
        $stream .= sprintf("%.2f 0 Td\n", $col2 - $col1);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("PRODUTO / SALGADO"));
        $stream .= sprintf("%.2f 0 Td\n", $col3 - $col2);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("CATEGORIA"));
        $stream .= "ET\n";

        // Qtd cabeçalho à direita
        $stream .= "BT\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.28, 0.33, 0.41);
        $stream .= sprintf("%.2f %.2f Td\n", $col4 - 40, $y - 10);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("QUANTIDADE"));
        $stream .= "ET\n";

        $y -= $rowHeight;

        // Linhas de dados
        $stream .= "0.5 w\n";
        $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.90, 0.92, 0.95); // #e2e8f0

        if (empty($itens)) {
            $y -= 18;
            $stream .= "BT\n";
            $stream .= "/F1 9 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.40, 0.45, 0.55);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 150, $y);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("Nenhum pedido de salgado registrado para este corte do turno."));
            $stream .= "ET\n";
            $y -= 10;
        } else {
            foreach ($itens as $idx => $item) {
                $codigo = $item['codigo_interno'] ?? ('PROD-' . $item['produto_id']);
                $descricao = $item['descricao'] ?? 'Salgado';
                $categoria = $item['categoria_nome'] ?? 'Salgados';
                $qtd = (int)($item['quantidade_total'] ?? 0);

                // Alternância sutil de cor de fundo
                if ($idx % 2 === 1) {
                    $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.98, 0.99, 1.0);
                    $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowHeight + 6, $width, $rowHeight);
                }

                // Código
                $stream .= "BT\n";
                $stream .= "/F2 8 Tf\n"; // Bold
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.20, 0.25, 0.33);
                $stream .= sprintf("%.2f %.2f Td\n", $col1, $y - 8);
                $stream .= sprintf("(%s) Tj\n", self::escapeText($codigo));
                $stream .= "ET\n";

                // Descrição (truncada se muito longa)
                if (mb_strlen($descricao, 'UTF-8') > 42) {
                    $descricao = mb_substr($descricao, 0, 40, 'UTF-8') . '...';
                }
                $stream .= "BT\n";
                $stream .= "/F1 9 Tf\n"; // Regular
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
                $stream .= sprintf("%.2f %.2f Td\n", $col2, $y - 8);
                $stream .= sprintf("(%s) Tj\n", self::escapeText($descricao));
                $stream .= "ET\n";

                // Categoria
                $stream .= "BT\n";
                $stream .= "/F1 8 Tf\n";
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.35, 0.40, 0.48);
                $stream .= sprintf("%.2f %.2f Td\n", $col3, $y - 8);
                $stream .= sprintf("(%s) Tj\n", self::escapeText($categoria));
                $stream .= "ET\n";

                // Quantidade (destaque bold à direita)
                $qtdStr = $qtd . " un";
                $stream .= "BT\n";
                $stream .= "/F2 10 Tf\n"; // Bold
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
                $stream .= sprintf("%.2f %.2f Td\n", $col4 - 25, $y - 8);
                $stream .= sprintf("(%s) Tj\n", self::escapeText($qtdStr));
                $stream .= "ET\n";

                // Linha divisória fina
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowHeight + 6, $right, $y - $rowHeight + 6);

                $y -= $rowHeight;

                // Prevenção de estouro de página (caso haja muitos itens)
                if ($y < 160) {
                    break;
                }
            }
        }

        // Linha de Total Geral
        $y -= 6.0;
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.94, 0.96, 0.98); // #f1f5f9
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 18, $width, 24);

        $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.70, 0.75, 0.82);
        $stream .= "1 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - 18, $width, 24);

        $stream .= "BT\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
        $stream .= sprintf("%.2f %.2f Td\n", $col1, $y - 6);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("TOTAL GERAL DE PEÇAS A ENTREGAR:"));
        $stream .= "ET\n";

        $stream .= "BT\n";
        $stream .= "/F2 12 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
        $stream .= sprintf("%.2f %.2f Td\n", $col4 - 38, $y - 6);
        $stream .= sprintf("(%s) Tj\n", self::escapeText($totalPecas . " PEÇAS"));
        $stream .= "ET\n";

        // Bloco de Assinaturas e Conferência Física de Recebimento
        $y = 120.0;
        $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.85, 0.88, 0.92);
        $stream .= "0.75 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - 45, $width, 52);

        $stream .= "BT\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.28, 0.33, 0.41);
        $stream .= sprintf("%.2f %.2f Td\n", $left + 10, $y - 4);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("PROTOCOLO DE ENTREGA FÍSICA E CONFERÊNCIA NA CANTINA:"));
        $stream .= "ET\n";

        // Campo 1: Entregue por (Fornecedor)
        $signW = ($width - 30) / 2;
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + 15, $y - 32, $left + 15 + $signW - 10, $y - 32);
        $stream .= "BT\n";
        $stream .= "/F1 7 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.40, 0.45, 0.55);
        $stream .= sprintf("%.2f %.2f Td\n", $left + 15, $y - 42);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Entregue por (Assinatura do Fornecedor)"));
        $stream .= "ET\n";

        // Campo 2: Recebido e Conferido por (Cantina)
        $x2 = $left + 25 + $signW;
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x2, $y - 32, $x2 + $signW - 10, $y - 32);
        $stream .= "BT\n";
        $stream .= "/F1 7 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.40, 0.45, 0.55);
        $stream .= sprintf("%.2f %.2f Td\n", $x2, $y - 42);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Recebido e Conferido por (Equipe da Cantina)"));
        $stream .= "ET\n";

        // Rodapé Institucional
        $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.88, 0.91, 0.94);
        $stream .= "0.5 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, 45, $right, 45);

        $stream .= "BT\n";
        $stream .= "/F1 7 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.45, 0.50, 0.60);
        $stream .= sprintf("%.2f 32 Td\n", $left);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Cantina Web — Colégio Monteiro Lobato • Documento emitido eletronicamente para fins operacionais."));
        $stream .= "ET\n";

        $stream .= "BT\n";
        $stream .= "/F1 7 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.45, 0.50, 0.60);
        $stream .= sprintf("%.2f 32 Td\n", $right - 60);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Página 1 de 1"));
        $stream .= "ET\n";

        return self::montarPdfEstruturado($stream);
    }

    /**
     * Monta a estrutura binária oficial do documento PDF 1.4 com tabela xref e trailer.
     * Incorpora o logotipo institucional como XObject /Im1 caso o arquivo esteja presente.
     */
    public static function montarPdfEstruturado(string $contentStream, float $pageWidth = self::PAGE_WIDTH, float $pageHeight = self::PAGE_HEIGHT, ?string $imagePath = null): string
    {
        $imgPath = $imagePath ?? self::getLogoPath();
        $hasImage = (!empty($imgPath) && file_exists($imgPath));
        $imgData = $hasImage ? file_get_contents($imgPath) : '';
        $imgInfo = $hasImage ? @getimagesize($imgPath) : null;
        $imgWidth = $imgInfo[0] ?? 447;
        $imgHeight = $imgInfo[1] ?? 447;

        $objects = [];
        $offsets = [];

        // Header do PDF
        $pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";

        // Objeto 1: Catalog
        $offsets[1] = strlen($pdf);
        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= $obj1;

        // Objeto 2: Pages
        $offsets[2] = strlen($pdf);
        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= $obj2;

        // Objeto 3: Page (A4 ou Bobina Térmica customizada)
        $offsets[3] = strlen($pdf);
        $resXObject = $hasImage ? " /XObject << /Im1 7 0 R >>" : "";
        $obj3 = sprintf(
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >>%s >> >>\nendobj\n",
            $pageWidth,
            $pageHeight,
            $resXObject
        );
        $pdf .= $obj3;

        // Objeto 4: Contents (Stream desenhado)
        $offsets[4] = strlen($pdf);
        $streamLen = strlen($contentStream);
        $obj4 = "4 0 obj\n<< /Length " . $streamLen . " >>\nstream\n" . $contentStream . "\nendstream\nendobj\n";
        $pdf .= $obj4;

        // Objeto 5: Fonte F1 (Helvetica Regular com WinAnsiEncoding)
        $offsets[5] = strlen($pdf);
        $obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";
        $pdf .= $obj5;

        // Objeto 6: Fonte F2 (Helvetica-Bold com WinAnsiEncoding)
        $offsets[6] = strlen($pdf);
        $obj6 = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";
        $pdf .= $obj6;

        // Objeto 7: Imagem XObject /Im1 (Logotipo oficial em JPEG)
        if ($hasImage) {
            $offsets[7] = strlen($pdf);
            $imgLen = strlen($imgData);
            $obj7 = "7 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgWidth} /Height {$imgHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen} >>\nstream\n" . $imgData . "\nendstream\nendobj\n";
            $pdf .= $obj7;
        }

        $numObjs = $hasImage ? 7 : 6;

        // Tabela XREF
        $startXref = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . ($numObjs + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $numObjs; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . ($numObjs + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $startXref . "\n";
        $pdf .= "%%EOF\n";

        return $pdf;
    }

    /**
     * Escapa caracteres especiais e converte caracteres acentuados de UTF-8 para Windows-1252
     */
    protected static function escapeText(string $text): string
    {
        // Converte caracteres acentuados para Windows-1252 / ISO-8859-1 para as fontes Type1 padrão
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
        if ($converted === false) {
            $converted = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        }

        // Escapa barras invertidas e parênteses
        $escaped = str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $converted
        );

        return $escaped;
    }

    /**
     * Gera espelho em HTML limpo com CSS @media print para impressão visual direta no navegador
     */
    public static function gerarHtmlRelatorioCorte(array $dadosCorte): string
    {
        $dataConsumo = $dadosCorte['data_consumo'] ?? date('Y-m-d');
        $dataFmt = date('d/m/Y', strtotime($dataConsumo));
        $turno = ucfirst($dadosCorte['turno_consumo'] ?? 'matutino');
        $itens = $dadosCorte['itens'] ?? [];
        $totalPecas = (int)($dadosCorte['total_pecas'] ?? 0);
        $statusCorte = $dadosCorte['status_corte']['rotulo'] ?? 'Corte Fechado';
        $emissao = date('d/m/Y H:i:s');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Pedido Fornecedor — <?php echo htmlspecialchars($dataFmt . ' ' . $turno, ENT_QUOTES, 'UTF-8'); ?></title>
            <style>
                @page { size: A4 portrait; margin: 15mm; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    color: #0f172a;
                    background: #fff;
                    margin: 0;
                    padding: 0;
                    font-size: 12px;
                    line-height: 1.4;
                }
                .print-header {
                    border-bottom: 2px solid #1d4ed8;
                    padding-bottom: 12px;
                    margin-bottom: 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                }
                .school-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
                .report-title { font-size: 13px; font-weight: 600; color: #1d4ed8; margin: 2px 0 0 0; }
                .report-sub { font-size: 10px; color: #64748b; margin: 2px 0 0 0; }
                .stat-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 8px;
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 4px;
                    padding: 10px;
                    margin-bottom: 20px;
                }
                .stat-item { padding: 4px 8px; }
                .stat-label { font-size: 9px; font-weight: 600; color: #64748b; text-transform: uppercase; }
                .stat-value { font-size: 13px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .data-table th {
                    background: #f1f5f9;
                    color: #475569;
                    font-size: 10px;
                    font-weight: 600;
                    text-transform: uppercase;
                    padding: 8px 10px;
                    border-bottom: 1px solid #cbd5e1;
                    text-align: left;
                }
                .data-table td {
                    padding: 8px 10px;
                    border-bottom: 1px solid #e2e8f0;
                    font-size: 11px;
                }
                .data-table tr:nth-child(even) { background: #fafafa; }
                .cell-code { font-family: monospace; font-weight: 600; color: #334155; }
                .cell-right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
                .total-row { background: #eff6ff !important; font-weight: 700; }
                .total-row td { border-top: 1px solid #1d4ed8; border-bottom: 1px solid #1d4ed8; font-size: 13px; }
                .sign-box {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 30px;
                    border: 1px solid #e2e8f0;
                    border-radius: 4px;
                    padding: 16px;
                    margin-top: 24px;
                }
                .sign-line {
                    border-bottom: 1px solid #94a3b8;
                    margin-bottom: 6px;
                    height: 30px;
                }
                .sign-caption { font-size: 9px; color: #64748b; text-align: center; }
                .footer-bar {
                    margin-top: 30px;
                    border-top: 1px solid #e2e8f0;
                    padding-top: 8px;
                    display: flex;
                    justify-content: space-between;
                    font-size: 9px;
                    color: #94a3b8;
                }
                @media screen {
                    body { max-width: 800px; margin: 20px auto; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                    .no-print { display: flex; gap: 10px; margin-bottom: 20px; }
                    .btn-print { background: #1d4ed8; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; cursor: pointer; }
                }
            </style>
        </head>
        <body>
            <div class="no-print" style="justify-content: flex-end;">
                <button onclick="window.print()" class="btn-print">Imprimir Relatório</button>
            </div>
            <div class="print-header">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <?php $logoUri = self::getLogoDataUri(); if (!empty($logoUri)): ?>
                        <img src="<?php echo $logoUri; ?>" alt="Logo Colégio Monteiro Lobato" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                    <?php endif; ?>
                    <div>
                        <h1 class="school-title">Colégio Monteiro Lobato — Cantina Escolar</h1>
                        <h2 class="report-title">Pedido de Salgados — Consolidação do Corte Operacional</h2>
                        <p class="report-sub">Valores para o Futuro! • Documento Oficial de Encomenda</p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 10px; color: #64748b;">Emissão: <?php echo htmlspecialchars($emissao, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-item">
                    <div class="stat-label">Data de Consumo</div>
                    <div class="stat-value"><?php echo htmlspecialchars($dataFmt, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Turno</div>
                    <div class="stat-value"><?php echo htmlspecialchars($turno, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total de Salgados</div>
                    <div class="stat-value"><?php echo htmlspecialchars($totalPecas . ' peças', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Status do Corte</div>
                    <div class="stat-value"><?php echo htmlspecialchars($statusCorte, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Código</th>
                        <th style="width: 50%;">Produto / Salgado</th>
                        <th style="width: 20%;">Categoria</th>
                        <th style="width: 15%; text-align: right;">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itens)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">
                                Nenhum pedido de salgado registrado para este corte do turno.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($itens as $it): ?>
                            <tr>
                                <td class="cell-code"><?php echo htmlspecialchars($it['codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong><?php echo htmlspecialchars($it['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td style="color: #64748b;"><?php echo htmlspecialchars($it['categoria_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="cell-right"><?php echo (int)($it['quantidade_total'] ?? 0); ?> un</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right; padding-right: 15px;">TOTAL GERAL:</td>
                            <td class="cell-right" style="color: #1d4ed8; font-size: 14px;"><?php echo $totalPecas; ?> PEÇAS</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="sign-box">
                <div>
                    <div class="sign-line"></div>
                    <div class="sign-caption">Entregue por (Assinatura do Fornecedor)</div>
                </div>
                <div>
                    <div class="sign-line"></div>
                    <div class="sign-caption">Recebido e Conferido por (Equipe da Cantina)</div>
                </div>
            </div>

            <div class="footer-bar">
                <span>Cantina Web — Colégio Monteiro Lobato</span>
                <span>Página 1 de 1</span>
            </div>
        </body>
        </html>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Gera a bobina contínua de tickets térmicos de 80mm em formato binário PDF 1.4
     * 
     * @param array $tickets Lista de tickets retornada por Ticket::listarPorTurnoEData
     * @param array $parametros Parâmetros adicionais (data, turno, etc.)
     * @return string Conteúdo binário puro do PDF
     */
    public static function gerarTicketsLotePdf(array $tickets, array $parametros = []): string
    {
        $dataConsumo = $parametros['data'] ?? ($tickets[0]['data_consumo'] ?? date('Y-m-d'));
        $dataFmt = date('d/m/Y', strtotime($dataConsumo));
        $turno = ucfirst($parametros['turno'] ?? ($tickets[0]['turno_consumo'] ?? 'matutino'));

        $pageWidth = self::PAGE_WIDTH_THERMAL;
        $left = 8.0;
        $right = $pageWidth - 8.0;
        $width = $right - $left;

        if (empty($tickets)) {
            $pageHeight = 160.0;
            $stream = "";
            $y = 120.0;

            // Logotipo institucional
            $stream .= "q\n";
            $stream .= sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", 22.0, 22.0, $left, $y - 14);
            $stream .= "/Im1 Do\n";
            $stream .= "Q\n";

            $stream .= "BT\n/F2 10 Tf\n0.11 0.31 0.85 rg\n" . sprintf("%.2f %.2f Td\n", $left + 28, $y) . sprintf("(%s) Tj\nET\n", self::escapeText("COLÉGIO MONTEIRO LOBATO"));
            $y -= 14;
            $stream .= "BT\n/F1 8 Tf\n0.40 0.45 0.55 rg\n" . sprintf("%.2f %.2f Td\n", $left + 28, $y) . sprintf("(%s) Tj\nET\n", self::escapeText("Cantina Escolar • Fichas do Turno " . $turno));
            $y -= 25;
            $stream .= "BT\n/F1 9 Tf\n0.20 0.25 0.35 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(%s) Tj\nET\n", self::escapeText("Nenhum ticket pendente para " . $dataFmt . "."));

            return self::montarPdfEstruturado($stream, $pageWidth, $pageHeight);
        }

        // Calcula a altura necessária para a bobina contínua
        $totalHeight = 40.0;
        $currentTurmaId = null;
        foreach ($tickets as $t) {
            $tId = $t['turma_id'] ?? 0;
            if ($tId !== $currentTurmaId) {
                $totalHeight += 46.0;
                $currentTurmaId = $tId;
            }
            $totalHeight += empty($t['tag_alergenos']) ? 160.0 : 188.0;
        }
        $totalHeight = max(200.0, $totalHeight);

        $stream = "";
        $y = $totalHeight - 15.0;
        $currentTurmaId = null;

        foreach ($tickets as $t) {
            $tId = $t['turma_id'] ?? 0;
            $nomeTurma = $t['nome_turma'] ?? 'Sem Turma';
            $serie = $t['serie'] ?? 'Geral';
            $ordem = (int)($t['ordem_impressao'] ?? 999);

            // Bloco de Turma (separador na bobina contínua)
            if ($tId !== $currentTurmaId) {
                $currentTurmaId = $tId;

                // Fundo da barra de turma
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.93, 0.95, 0.98);
                $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32, $width, 34);

                // Borda lateral cobalto
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
                $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32, 3, 34);

                $stream .= "BT\n";
                $stream .= "/F2 9.5 Tf\n";
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.08, 0.12, 0.22);
                $stream .= sprintf("%.2f %.2f Td\n", $left + 8, $y - 12);
                $stream .= sprintf("(%s) Tj\n", self::escapeText("TURMA: " . mb_strtoupper($nomeTurma)));
                $stream .= "ET\n";

                $stream .= "BT\n";
                $stream .= "/F1 7.5 Tf\n";
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.35, 0.40, 0.50);
                $stream .= sprintf("%.2f %.2f Td\n", $left + 8, $y - 24);
                $stream .= sprintf("(%s) Tj\n", self::escapeText("Série: " . $serie . " • Ordem: " . $ordem . " • " . $turno));
                $stream .= "ET\n";

                $y -= 46.0;
            }

            // Ticket individual
            $hasAlergeno = !empty($t['tag_alergenos']);
            $ticketH = $hasAlergeno ? 180.0 : 152.0;

            // Borda do ticket
            $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.82, 0.85, 0.90);
            $stream .= "0.75 w\n";
            $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $ticketH, $width, $ticketH);

            // Cabeçalho institucional do ticket com logotipo oficial
            $stream .= "q\n";
            $stream .= sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", 18.0, 18.0, $left + 6, $y - 22);
            $stream .= "/Im1 Do\n";
            $stream .= "Q\n";

            $stream .= "BT\n";
            $stream .= "/F2 8.5 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 28, $y - 12);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("COLÉGIO MONTEIRO LOBATO"));
            $stream .= "ET\n";

            $stream .= "BT\n";
            $stream .= "/F1 7 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.40, 0.45, 0.55);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 28, $y - 21);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("CANTINA ESCOLAR • FICHA DE RECREIO"));
            $stream .= "ET\n";

            // Linha divisória interna
            $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.90, 0.92, 0.95);
            $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + 4, $y - 26, $right - 4, $y - 26);

            // Turno e Data
            $stream .= "BT\n";
            $stream .= "/F2 7.5 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.15, 0.20, 0.28);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 36);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("DATA: " . $dataFmt . "  •  " . mb_strtoupper($turno)));
            $stream .= "ET\n";

            // Aluno e Matrícula
            $alunoNome = mb_substr($t['aluno_nome'] ?? 'Aluno', 0, 26, 'UTF-8');
            $stream .= "BT\n";
            $stream .= "/F2 9.5 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 49);
            $stream .= sprintf("(%s) Tj\n", self::escapeText(mb_strtoupper($alunoNome)));
            $stream .= "ET\n";

            $matricula = $t['aluno_matricula'] ?? '—';
            $stream .= "BT\n";
            $stream .= "/F1 7.5 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.35, 0.40, 0.50);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 59);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("Matrícula: " . $matricula . "  •  Turma: " . $nomeTurma));
            $stream .= "ET\n";

            // Box do Produto em Destaque
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.96, 0.97, 0.99);
            $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left + 5, $y - 94, $width - 10, 31);
            $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.82, 0.86, 0.92);
            $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left + 5, $y - 94, $width - 10, 31);

            $prodNome = mb_substr($t['produto_descricao'] ?? 'Produto', 0, 28, 'UTF-8');
            $stream .= "BT\n";
            $stream .= "/F2 9.5 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.08, 0.12, 0.22);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 10, $y - 75);
            $stream .= sprintf("(%s) Tj\n", self::escapeText(">> " . mb_strtoupper($prodNome)));
            $stream .= "ET\n";

            $qtdItem = (int)($t['item_quantidade'] ?? 1);
            $precoItem = number_format((float)($t['produto_preco_venda'] ?? $t['item_preco_unitario'] ?? 0), 2, ',', '.');
            $stream .= "BT\n";
            $stream .= "/F1 8 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.30, 0.35, 0.45);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 10, $y - 87);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("Qtd: " . $qtdItem . " un   |   R$ " . $precoItem));
            $stream .= "ET\n";

            $offsetY = 94.0;

            // Box de Alérgeno (se aplicável)
            if ($hasAlergeno) {
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.99, 0.97, 0.88);
                $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left + 5, $y - 122, $width - 10, 22);
                $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.85, 0.60, 0.10);
                $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left + 5, $y - 122, $width - 10, 22);

                $stream .= "BT\n";
                $stream .= "/F2 8 Tf\n";
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.70, 0.30, 0.00);
                $stream .= sprintf("%.2f %.2f Td\n", $left + 8, $y - 108);
                $stream .= sprintf("(%s) Tj\n", self::escapeText($t['tag_alergenos']));
                $stream .= "ET\n";

                $offsetY += 28.0;
            }

            // Código do Ticket e Identificador de Via
            $numVia = (int)($t['numero_via'] ?? 1);
            $viaLabel = ($numVia > 1) ? sprintf("[2ª VIA - REIMP #%d]", $numVia) : "1ª VIA";
            $stream .= "BT\n";
            $stream .= "/F2 8 Tf\n";
            $stream .= ($numVia > 1) ? sprintf("%.2f %.2f %.2f rg\n", 0.85, 0.15, 0.15) : sprintf("%.2f %.2f %.2f rg\n", 0.20, 0.25, 0.35);
            $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - $offsetY - 14);
            $stream .= sprintf("(%s  •  %s) Tj\n", self::escapeText($t['codigo_ticket'] ?? ''), self::escapeText($viaLabel));
            $stream .= "ET\n";

            // Linha de Corte da Bobina
            $stream .= sprintf("%.2f %.2f %.2f RG\n", 0.60, 0.65, 0.75);
            $stream .= "[2 2] 0 d\n";
            $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $ticketH - 4, $right, $y - $ticketH - 4);
            $stream .= "[] 0 d\n";

            $stream .= "BT\n";
            $stream .= "/F1 7 Tf\n";
            $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.50, 0.55, 0.65);
            $stream .= sprintf("%.2f %.2f Td\n", $left + ($width / 2) - 16, $y - $ticketH - 8);
            $stream .= sprintf("(%s) Tj\n", self::escapeText("corte aqui"));
            $stream .= "ET\n";

            $y -= ($ticketH + 14.0);
        }

        return self::montarPdfEstruturado($stream, $pageWidth, $totalHeight);
    }

    /**
     * Gera o PDF 1.4 de um único ticket (bobina 80mm) para impressão avulsa ou 2ª via
     * 
     * @param array $ticket
     * @return string
     */
    public static function gerarTicketIndividualPdf(array $ticket): string
    {
        return self::gerarTicketsLotePdf([$ticket], [
            'data'  => $ticket['data_consumo'] ?? date('Y-m-d'),
            'turno' => $ticket['turno_consumo'] ?? 'matutino'
        ]);
    }

    /**
     * Renderiza o espelho de impressão contínua HTML para bobina térmica de 80mm
     * 
     * @param array $tickets
     * @param array $parametros
     * @return string
     */
    public static function gerarEspelhoTicketsLoteHtml(array $tickets, array $parametros = []): string
    {
        $dataConsumo = $parametros['data'] ?? ($tickets[0]['data_consumo'] ?? date('Y-m-d'));
        $dataFmt = date('d/m/Y', strtotime($dataConsumo));
        $turno = ucfirst($parametros['turno'] ?? ($tickets[0]['turno_consumo'] ?? 'matutino'));
        $baseUrl = $parametros['baseUrl'] ?? '';

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Tickets Térmicos 80mm — Colégio Monteiro Lobato</title>
            <style>
                @page {
                    size: 80mm auto;
                    margin: 0;
                }
                @media print {
                    .no-print { display: none !important; }
                    body {
                        background: #fff !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }
                    .receipt-roll {
                        box-shadow: none !important;
                        margin: 0 auto !important;
                        border: none !important;
                        width: 76mm !important;
                        padding: 0 !important;
                    }
                    .ticket-card {
                        page-break-inside: avoid;
                    }
                }
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    background: #f1f5f9;
                    color: #0f172a;
                    padding: 24px 12px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }
                .action-bar {
                    width: 80mm;
                    max-width: 100%;
                    background: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    padding: 10px;
                    margin-bottom: 16px;
                    display: flex;
                    gap: 8px;
                    justify-content: space-between;
                }
                .btn-action {
                    font-size: 11px;
                    font-weight: 600;
                    padding: 6px 10px;
                    border-radius: 4px;
                    cursor: pointer;
                    border: 1px solid #cbd5e1;
                    background: #ffffff;
                    color: #0f172a;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }
                .btn-action:hover {
                    background: #f8fafc;
                }
                .btn-primary-action {
                    background: #2563eb;
                    color: #ffffff;
                    border-color: #1d4ed8;
                }
                .btn-primary-action:hover {
                    background: #1d4ed8;
                }
                .receipt-roll {
                    width: 80mm;
                    max-width: 80mm;
                    background: #ffffff;
                    border: 1px solid #cbd5e1;
                    padding: 6mm 4mm;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                }
                .turma-divider {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-left: 4px solid #2563eb;
                    padding: 6px 8px;
                    margin-top: 12px;
                    margin-bottom: 10px;
                    text-align: left;
                }
                .turma-divider-title {
                    font-size: 12px;
                    font-weight: 700;
                    color: #0f172a;
                }
                .turma-divider-sub {
                    font-size: 9px;
                    color: #64748b;
                }
                .ticket-card {
                    border: 1px solid #94a3b8;
                    border-radius: 3px;
                    padding: 8px 6px;
                    margin-bottom: 12px;
                    background: #ffffff;
                }
                .ticket-header {
                    text-align: center;
                    border-bottom: 1px dashed #cbd5e1;
                    padding-bottom: 6px;
                    margin-bottom: 6px;
                }
                .ticket-title {
                    font-size: 11px;
                    font-weight: 800;
                    color: #0f172a;
                    letter-spacing: 0.5px;
                }
                .ticket-subtitle {
                    font-size: 8px;
                    color: #64748b;
                    text-transform: uppercase;
                }
                .ticket-meta {
                    font-size: 9px;
                    color: #334155;
                    margin-bottom: 4px;
                    display: flex;
                    justify-content: space-between;
                }
                .aluno-box {
                    margin: 6px 0;
                    padding: 4px 0;
                }
                .aluno-nome {
                    font-size: 12px;
                    font-weight: 800;
                    color: #0f172a;
                    line-height: 1.2;
                }
                .aluno-info {
                    font-size: 9px;
                    color: #475569;
                }
                .product-box {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 3px;
                    padding: 6px;
                    margin: 6px 0;
                    text-align: center;
                }
                .product-name {
                    font-size: 13px;
                    font-weight: 800;
                    color: #0f172a;
                }
                .product-details {
                    font-size: 9px;
                    color: #475569;
                    font-feature-settings: "tnum" 1, "zero" 1;
                }
                .allergen-box {
                    background: #fef9c3;
                    border: 1px solid #eab308;
                    color: #854d0e;
                    font-size: 9px;
                    font-weight: 800;
                    text-align: center;
                    padding: 4px;
                    margin: 6px 0;
                    border-radius: 3px;
                }
                .ticket-footer {
                    margin-top: 6px;
                    padding-top: 4px;
                    border-top: 1px dashed #cbd5e1;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 8px;
                    color: #475569;
                }
                .via-badge {
                    font-weight: 800;
                    font-size: 9px;
                    padding: 2px 4px;
                    border-radius: 2px;
                }
                .via-segunda {
                    background: #fee2e2;
                    color: #991b1b;
                    border: 1px solid #f87171;
                }
                .cut-separator {
                    text-align: center;
                    font-size: 10px;
                    color: #94a3b8;
                    margin: 10px 0;
                    letter-spacing: 2px;
                }
            </style>
        </head>
        <body>
            <div class="action-bar no-print">
                <button type="button" class="btn-action btn-primary-action" onclick="window.print()">
                    🖨️ Imprimir Bobina (80mm)
                </button>
                <a href="<?php echo htmlspecialchars($baseUrl . '/operacional/tickets?data=' . urlencode($dataConsumo) . '&turno=' . urlencode(strtolower($turno)), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action">
                    &larr; Voltar
                </a>
            </div>

            <div class="receipt-roll">
                <?php if (empty($tickets)): ?>
                    <div style="text-align: center; padding: 20px; color: #64748b; font-size: 11px;">
                        Nenhum ticket encontrado para o lote selecionado.
                    </div>
                <?php else: ?>
                    <?php 
                    $currentTurma = null;
                    foreach ($tickets as $t): 
                        $tId = $t['turma_id'] ?? 0;
                        if ($tId !== $currentTurma):
                            $currentTurma = $tId;
                    ?>
                        <div class="turma-divider">
                            <div class="turma-divider-title">TURMA: <?php echo htmlspecialchars(mb_strtoupper($t['nome_turma'] ?? 'Sem Turma'), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="turma-divider-sub">Série: <?php echo htmlspecialchars($t['serie'] ?? 'Geral', ENT_QUOTES, 'UTF-8'); ?> • Ordem: <?php echo (int)($t['ordem_impressao'] ?? 0); ?> • <?php echo htmlspecialchars($turno, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endif; ?>

                        <div class="ticket-card">
                            <div class="ticket-header" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <?php $logoUri = self::getLogoDataUri(); if (!empty($logoUri)): ?>
                                    <img src="<?php echo $logoUri; ?>" alt="Logo" style="width: 26px; height: 26px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                                <?php endif; ?>
                                <div style="text-align: left;">
                                    <div class="ticket-title">COLÉGIO MONTEIRO LOBATO</div>
                                    <div class="ticket-subtitle">CANTINA ESCOLAR • FICHA DE RECREIO</div>
                                </div>
                            </div>

                            <div class="ticket-meta">
                                <span><strong>DATA:</strong> <?php echo htmlspecialchars($dataFmt, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span><strong>TURNO:</strong> <?php echo htmlspecialchars(mb_strtoupper($turno), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>

                            <div class="aluno-box">
                                <div class="aluno-nome"><?php echo htmlspecialchars(mb_strtoupper($t['aluno_nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="aluno-info">
                                    Matrícula: <?php echo htmlspecialchars($t['aluno_matricula'] ?? '—', ENT_QUOTES, 'UTF-8'); ?> • Turma: <?php echo htmlspecialchars($t['nome_turma'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>

                            <div class="product-box">
                                <div class="product-name">
                                    <?php echo htmlspecialchars(mb_strtoupper($t['produto_descricao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="product-details">
                                    Qtd: <?php echo (int)($t['item_quantidade'] ?? 1); ?> un &nbsp;|&nbsp; 
                                    R$ <?php echo number_format((float)($t['produto_preco_venda'] ?? $t['item_preco_unitario'] ?? 0), 2, ',', '.'); ?>
                                </div>
                            </div>

                            <?php if (!empty($t['tag_alergenos'])): ?>
                                <div class="allergen-box">
                                    <?php echo htmlspecialchars($t['tag_alergenos'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="ticket-footer">
                                <span><?php echo htmlspecialchars($t['codigo_ticket'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php $via = (int)($t['numero_via'] ?? 1); ?>
                                <?php if ($via > 1): ?>
                                    <span class="via-badge via-segunda">[2ª VIA - REIMPRESSÃO #<?php echo $via; ?>]</span>
                                <?php else: ?>
                                    <span class="via-badge">1ª VIA</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="cut-separator">
                            ✂ - - - - - - - - - - - - - - - - - - - -
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Renderiza o espelho de impressão HTML para um único ticket térmico (80mm)
     * 
     * @param array $ticket
     * @param array $parametros
     * @return string
     */
    public static function gerarEspelhoTicketIndividualHtml(array $ticket, array $parametros = []): string
    {
        return self::gerarEspelhoTicketsLoteHtml([$ticket], $parametros);
    }

    /**
     * Gera o comprovante térmico de venda avulsa do PDV em formato binário PDF 1.4 (80mm)
     * 
     * @param array $venda Dados da venda retornados por VendaBalcao::buscarPorId
     * @param array $parametros Parâmetros adicionais
     * @return string
     */
    public static function gerarComprovanteVendaBalcaoPdf(array $venda, array $parametros = []): string
    {
        $vendaId = (int)($venda['id'] ?? 0);
        $dataHora = date('d/m/Y H:i:s', strtotime($venda['created_at'] ?? 'now'));
        $operador = $venda['operador_nome'] ?? 'Operador';
        $tipoVenda = $venda['tipo_venda'] ?? 'avulso_aluno';
        $formaPagamento = $venda['forma_pagamento'] ?? 'dinheiro';
        $valorTotal = (float)($venda['valor_total'] ?? 0.0);
        $valorRecebido = (float)($venda['valor_dinheiro_recebido'] ?? 0.0);
        $troco = (float)($venda['troco_devolvido'] ?? 0.0);
        $itens = $venda['itens'] ?? [];

        $pageWidth = self::PAGE_WIDTH_THERMAL;
        $left = 8.0;
        $right = $pageWidth - 8.0;
        $width = $right - $left;

        $alturaItens = count($itens) * 16.0;
        $pageHeight = max(280.0, 225.0 + $alturaItens);
        $stream = "";
        $y = $pageHeight - 16.0;

        // Logotipo oficial centralizado no topo da bobina (34x34 pt)
        $logoSize = 34.0;
        $logoX = ($pageWidth - $logoSize) / 2.0;
        $stream .= "q\n";
        $stream .= sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $logoSize, $logoSize, $logoX, $y - $logoSize);
        $stream .= "/Im1 Do\n";
        $stream .= "Q\n";
        $y -= ($logoSize + 8.0);

        // Cabeçalho
        $stream .= "BT\n/F2 10 Tf\n0.11 0.31 0.85 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(%s) Tj\nET\n", self::escapeText("COLÉGIO MONTEIRO LOBATO"));
        $y -= 14.0;
        $stream .= "BT\n/F1 8 Tf\n0.30 0.35 0.45 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(%s) Tj\nET\n", self::escapeText("Cantina Escolar • Comprovante de Venda"));
        $y -= 12.0;
        $stream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(%s) Tj\nET\n", self::escapeText("Venda #{$vendaId} • {$dataHora} • Op: {$operador}"));
        $y -= 10.0;

        // Cliente
        if (!empty($venda['colaborador_nome'])) {
            $stream .= "BT\n/F2 8 Tf\n0.10 0.15 0.25 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Colaborador: %s) Tj\nET\n", self::escapeText($venda['colaborador_nome']));
            $y -= 10.0;
        } elseif (!empty($venda['aluno_nome'])) {
            $stream .= "BT\n/F2 8 Tf\n0.10 0.15 0.25 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Aluno: %s) Tj\nET\n", self::escapeText($venda['aluno_nome']));
            $y -= 10.0;
        }

        // Divisor
        $stream .= sprintf("0.80 0.85 0.90 RG\n1 w\n%.2f %.2f m\n%.2f %.2f l\nS\n", $left, $y, $right, $y);
        $y -= 12.0;

        // Tabela de itens
        foreach ($itens as $it) {
            $qtd = (int)($it['quantidade'] ?? 1);
            $desc = mb_substr($it['produto_descricao'] ?? '', 0, 20);
            $sub = number_format((float)($it['subtotal'] ?? 0.0), 2, ',', '.');
            $stream .= "BT\n/F1 8 Tf\n0.10 0.15 0.25 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(%dx %s) Tj\nET\n", $qtd, self::escapeText($desc));
            $stream .= "BT\n/F2 8 Tf\n0.10 0.15 0.25 rg\n" . sprintf("%.2f %.2f Td\n", $right - 50.0, $y) . sprintf("(R$ %s) Tj\nET\n", self::escapeText($sub));
            $y -= 14.0;
        }

        // Divisor
        $stream .= sprintf("0.80 0.85 0.90 RG\n1 w\n%.2f %.2f m\n%.2f %.2f l\nS\n", $left, $y, $right, $y);
        $y -= 14.0;

        // Total
        $rotuloPagamento = match($formaPagamento) {
            'dinheiro' => 'Dinheiro',
            'pix_balcao' => 'Pix Balcão',
            'desconto_folha' => 'Desconto em Folha',
            default => ucfirst($formaPagamento)
        };
        $stream .= "BT\n/F2 10 Tf\n0.11 0.31 0.85 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(TOTAL: R$ %s) Tj\nET\n", self::escapeText(number_format($valorTotal, 2, ',', '.')));
        $y -= 12.0;
        $stream .= "BT\n/F1 8 Tf\n0.30 0.35 0.45 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Pagamento: %s) Tj\nET\n", self::escapeText($rotuloPagamento));
        $y -= 10.0;

        if ($formaPagamento === 'dinheiro' && $valorRecebido > 0) {
            $stream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Recebido: R$ %s  |  Troco: R$ %s) Tj\nET\n", 
                self::escapeText(number_format($valorRecebido, 2, ',', '.')),
                self::escapeText(number_format($troco, 2, ',', '.'))
            );
            $y -= 12.0;
        }

        // Rodapé institucional
        $stream .= "BT\n/F1 7 Tf\n0.55 0.60 0.70 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Valores para o Futuro! Obrigado pela compra.) Tj\nET\n");

        return self::montarPdfEstruturado($stream, $pageWidth, $pageHeight);
    }

    /**
     * Renderiza o espelho de impressão HTML para o comprovante térmico de venda no PDV (80mm)
     * 
     * @param array $venda
     * @param array $parametros
     * @return string
     */
    public static function gerarEspelhoComprovanteVendaBalcaoHtml(array $venda, array $parametros = []): string
    {
        ob_start();
        $vendaId = (int)($venda['id'] ?? 0);
        $dataHora = date('d/m/Y H:i:s', strtotime($venda['created_at'] ?? 'now'));
        $operador = $venda['operador_nome'] ?? 'Operador';
        $formaPagamento = $venda['forma_pagamento'] ?? 'dinheiro';
        $valorTotal = (float)($venda['valor_total'] ?? 0.0);
        $valorRecebido = (float)($venda['valor_dinheiro_recebido'] ?? 0.0);
        $troco = (float)($venda['troco_devolvido'] ?? 0.0);
        $itens = $venda['itens'] ?? [];

        $rotuloPagamento = match($formaPagamento) {
            'dinheiro' => 'Dinheiro Físico',
            'pix_balcao' => 'Pix Balcão',
            'desconto_folha' => 'Desconto em Folha',
            default => ucfirst($formaPagamento)
        };
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Comprovante de Venda #<?php echo $vendaId; ?> — Cantina Web</title>
            <style>
                @page { size: 80mm auto; margin: 0; }
                body {
                    margin: 0;
                    padding: 6mm 4mm;
                    font-family: 'Courier New', Courier, monospace, sans-serif;
                    font-size: 11px;
                    color: #000;
                    background: #fff;
                    line-height: 1.35;
                }
                .thermal-wrapper { width: 72mm; margin: 0 auto; }
                .center { text-align: center; }
                .bold { font-weight: bold; }
                .title { font-size: 13px; font-weight: bold; margin-bottom: 2px; }
                .sub { font-size: 10px; color: #333; margin-bottom: 6px; }
                .divider { border-top: 1px dashed #444; margin: 6px 0; }
                .info-line { font-size: 10px; margin: 2px 0; }
                .items-table { width: 100%; font-size: 10px; border-collapse: collapse; margin: 6px 0; }
                .items-table th { border-bottom: 1px solid #000; text-align: left; padding: 2px 0; font-size: 9px; text-transform: uppercase; }
                .items-table td { padding: 3px 0; vertical-align: top; }
                .text-right { text-align: right; }
                .total-box { font-size: 13px; font-weight: bold; margin: 8px 0 4px 0; }
                .footer { margin-top: 10px; font-size: 9px; text-align: center; color: #555; }
                @media screen {
                    body { background: #f1f5f9; padding: 20px; }
                    .thermal-wrapper {
                        background: #fff;
                        padding: 16px;
                        border: 1px dashed #cbd5e1;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
                    }
                    .no-print { display: flex; justify-content: center; margin-bottom: 16px; gap: 8px; }
                    .btn-print {
                        background: #1d4ed8; color: #fff; border: none; padding: 8px 16px;
                        border-radius: 4px; font-weight: 600; cursor: pointer; font-family: sans-serif;
                    }
                }
            </style>
        </head>
        <body>
            <div class="no-print">
                <button onclick="window.print()" class="btn-print">Imprimir Comprovante (80mm)</button>
            </div>
            <div class="thermal-wrapper">
                <div class="center">
                    <?php $logoUri = self::getLogoDataUri(); if (!empty($logoUri)): ?>
                        <img src="<?php echo $logoUri; ?>" alt="Logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin: 0 auto 6px auto; display: block; border: 1px solid #e2e8f0;">
                    <?php endif; ?>
                    <div class="title">COLÉGIO MONTEIRO LOBATO</div>
                    <div class="sub">Cantina Escolar • Valores para o Futuro!</div>
                </div>
                <div class="divider"></div>
                <div class="info-line"><strong>Comprovante de Venda #<?php echo $vendaId; ?></strong></div>
                <div class="info-line">Data/Hora: <?php echo htmlspecialchars($dataHora, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-line">Atendente: <?php echo htmlspecialchars($operador, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="info-line">Sessão Caixa: #<?php echo (int)($venda['sessao_caixa_id'] ?? 0); ?> (<?php echo htmlspecialchars(ucfirst($venda['sessao_turno'] ?? 'turno'), ENT_QUOTES, 'UTF-8'); ?>)</div>

                <?php if (!empty($venda['colaborador_nome'])): ?>
                    <div class="divider"></div>
                    <div class="info-line"><strong>CLIENTE: COLABORADOR</strong></div>
                    <div class="info-line">Nome: <?php echo htmlspecialchars($venda['colaborador_nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-line">Matrícula: <?php echo htmlspecialchars($venda['colaborador_matricula'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-line">Cargo: <?php echo htmlspecialchars($venda['colaborador_cargo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <?php elseif (!empty($venda['aluno_nome'])): ?>
                    <div class="divider"></div>
                    <div class="info-line"><strong>CLIENTE: ALUNO</strong></div>
                    <div class="info-line">Nome: <?php echo htmlspecialchars($venda['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-line">Turma: <?php echo htmlspecialchars($venda['aluno_turma'] ?? 'Geral', ENT_QUOTES, 'UTF-8'); ?></div>
                <?php else: ?>
                    <div class="divider"></div>
                    <div class="info-line">Cliente: Consumidor Final (Venda Balcão)</div>
                <?php endif; ?>

                <div class="divider"></div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Qtd</th>
                            <th style="width: 50%;">Item</th>
                            <th style="width: 35%;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $it): ?>
                            <tr>
                                <td><?php echo (int)($it['quantidade'] ?? 1); ?>x</td>
                                <td><?php echo htmlspecialchars($it['produto_descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right">R$ <?php echo number_format((float)($it['subtotal'] ?? 0.0), 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="divider"></div>
                <div class="total-box text-right">
                    TOTAL: R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?>
                </div>
                <div class="info-line text-right">Forma: <?php echo htmlspecialchars($rotuloPagamento, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if ($formaPagamento === 'dinheiro' && $valorRecebido > 0): ?>
                    <div class="info-line text-right">Recebido: R$ <?php echo number_format($valorRecebido, 2, ',', '.'); ?></div>
                    <div class="info-line text-right"><strong>Troco: R$ <?php echo number_format($troco, 2, ',', '.'); ?></strong></div>
                <?php endif; ?>
                <div class="divider"></div>
                <div class="footer">
                    Agradecemos a sua preferência!<br>
                    Colégio Monteiro Lobato
                </div>
            </div>
        </body>
        </html>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Gera o espelho HTML formatado para impressão A4 do Fechamento e Prestação de Contas de Caixa
     * 
     * @param array $sessao
     * @return string
     */
    public static function gerarEspelhoFechamentoCaixaHtml(array $sessao): string
    {
        $id = (int)($sessao['id'] ?? 0);
        $turno = ucfirst($sessao['turno'] ?? 'matutino');
        $operador = htmlspecialchars($sessao['operador_nome'] ?? 'Operador de Caixa', ENT_QUOTES, 'UTF-8');
        $abertoEm = !empty($sessao['aberto_em']) ? date('d/m/Y H:i', strtotime($sessao['aberto_em'])) : '-';
        $fechadoEm = !empty($sessao['fechado_em']) ? date('d/m/Y H:i', strtotime($sessao['fechado_em'])) : 'Em aberto';
        $emissao = date('d/m/Y H:i:s');

        $fundo = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
        $avulso = (float)($sessao['total_dinheiro_avulso'] ?? 0.0);
        $recargas = (float)($sessao['total_dinheiro_recargas'] ?? 0.0);
        $pix = (float)($sessao['total_pix_balcao'] ?? 0.0);
        $esperado = round($fundo + $avulso + $recargas, 2);
        $informado = ($sessao['total_informado_gaveta'] !== null) ? (float)$sessao['total_informado_gaveta'] : $esperado;
        $diferenca = ($sessao['diferenca_quebra_sobra'] !== null) ? (float)$sessao['diferenca_quebra_sobra'] : round($informado - $esperado, 2);
        $obs = htmlspecialchars($sessao['observacoes'] ?? '', ENT_QUOTES, 'UTF-8');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Fechamento de Caixa #<?php echo $id; ?> — Colégio Monteiro Lobato</title>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 15mm;
                }
                * { box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    font-size: 11pt;
                    color: #0f172a;
                    background: #ffffff;
                    margin: 0;
                    padding: 0;
                }
                .sheet {
                    max-width: 210mm;
                    margin: 0 auto;
                    padding: 10px;
                }
                .header-table {
                    width: 100%;
                    border-bottom: 2px solid #1d4ed8;
                    padding-bottom: 12px;
                    margin-bottom: 20px;
                }
                .school-title {
                    font-size: 14pt;
                    font-weight: 700;
                    color: #0f172a;
                    margin: 0;
                }
                .doc-title {
                    font-size: 11pt;
                    font-weight: 700;
                    color: #1d4ed8;
                    margin: 4px 0 0 0;
                    text-transform: uppercase;
                }
                .meta-grid {
                    width: 100%;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .meta-grid td {
                    padding: 8px 12px;
                    border: 1px solid #e2e8f0;
                    font-size: 9.5pt;
                }
                .meta-label {
                    color: #64748b;
                    font-size: 8pt;
                    text-transform: uppercase;
                    display: block;
                    margin-bottom: 2px;
                }
                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .data-table th {
                    background: #f8fafc;
                    border-bottom: 2px solid #cbd5e1;
                    padding: 8px 12px;
                    font-size: 9pt;
                    text-transform: uppercase;
                    color: #475569;
                    text-align: left;
                }
                .data-table td {
                    padding: 9px 12px;
                    border-bottom: 1px solid #e2e8f0;
                    font-size: 10pt;
                }
                .data-table tr.total-row td {
                    background: #f1f5f9;
                    font-weight: 700;
                    border-top: 2px solid #cbd5e1;
                    border-bottom: 2px solid #cbd5e1;
                }
                .font-tabular {
                    font-variant-numeric: tabular-nums;
                    font-feature-settings: "tnum" 1, "zero" 1;
                }
                .text-right { text-align: right; }
                .text-success { color: #16a34a; }
                .text-danger { color: #dc2626; }
                .obs-box {
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    background: #f8fafc;
                    padding: 10px 14px;
                    margin-bottom: 40px;
                    font-size: 9.5pt;
                }
                .signatures {
                    width: 100%;
                    margin-top: 50px;
                }
                .sig-box {
                    width: 45%;
                    border-top: 1px solid #0f172a;
                    padding-top: 6px;
                    text-align: center;
                    font-size: 9pt;
                }
                .no-print {
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    padding: 12px 16px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                @media print {
                    .no-print { display: none !important; }
                    body { font-size: 10pt; }
                }
            </style>
        </head>
        <body>
            <div class="sheet">
                <div class="no-print">
                    <div>
                        <strong>Espelho de Fechamento de Caixa #<?php echo $id; ?></strong>
                        <span style="color: #64748b; font-size: 12px; margin-left: 8px;">Pronto para impressão / arquivamento</span>
                    </div>
                    <button type="button" onclick="window.print()" style="padding: 6px 14px; font-weight: 600; cursor: pointer; background: #1d4ed8; color: #fff; border: none; border-radius: 4px;">
                        Imprimir Documento
                    </button>
                </div>

                <table class="header-table">
                    <tr>
                        <?php $logoUri = self::getLogoDataUri(); if (!empty($logoUri)): ?>
                            <td style="width: 58px; vertical-align: middle; padding-right: 12px;">
                                <img src="<?php echo $logoUri; ?>" alt="Logo Colégio Monteiro Lobato" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid #e2e8f0; display: block;">
                            </td>
                        <?php endif; ?>
                        <td style="vertical-align: middle;">
                            <h1 class="school-title">Colégio Monteiro Lobato</h1>
                            <div class="doc-title">Prestação de Contas e Fechamento de Caixa — Turno <?php echo $turno; ?></div>
                        </td>
                        <td class="text-right" style="vertical-align: top; font-size: 9pt; color: #64748b; white-space: nowrap;">
                            Emissão: <?php echo $emissao; ?>
                        </td>
                    </tr>
                </table>

                <table class="meta-grid">
                    <tr>
                        <td style="width: 20%;">
                            <span class="meta-label">Sessão</span>
                            <strong>#<?php echo $id; ?></strong>
                        </td>
                        <td style="width: 20%;">
                            <span class="meta-label">Turno</span>
                            <strong><?php echo $turno; ?></strong>
                        </td>
                        <td style="width: 30%;">
                            <span class="meta-label">Operador Responsável</span>
                            <?php echo $operador; ?>
                        </td>
                        <td style="width: 30%;">
                            <span class="meta-label">Status</span>
                            <strong><?php echo ($sessao['status'] === 'fechado') ? 'Encerrado / Fechado' : 'Em Aberto'; ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="meta-label">Data/Hora Abertura</span>
                            <?php echo $abertoEm; ?>
                        </td>
                        <td colspan="2">
                            <span class="meta-label">Data/Hora Fechamento</span>
                            <?php echo $fechadoEm; ?>
                        </td>
                    </tr>
                </table>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Discriminação dos Recursos Financeiros</th>
                            <th style="width: 30%;" class="text-right">Valor Apurado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Fundo de Troco Inicial (Gaveta)</td>
                            <td class="text-right font-tabular">R$ <?php echo number_format($fundo, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>(+) Vendas Avulsas no Balcão (Dinheiro Físico)</td>
                            <td class="text-right font-tabular">R$ <?php echo number_format($avulso, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>(+) Recargas de Carteira no Balcão (Dinheiro Físico)</td>
                            <td class="text-right font-tabular">R$ <?php echo number_format($recargas, 2, ',', '.'); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td>(=) Total de Dinheiro em Espécie Esperado na Gaveta</td>
                            <td class="text-right font-tabular" style="font-size: 11pt;">R$ <?php echo number_format($esperado, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total em Dinheiro Efetivamente Contado na Gaveta</strong></td>
                            <td class="text-right font-tabular" style="font-weight: 700; font-size: 11pt;">R$ <?php echo number_format($informado, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Divergência de Caixa (Quebra / Sobra)</strong>
                                <div style="font-size: 8.5pt; color: #64748b;">Diferença entre o montante contado e o valor esperado pelo sistema</div>
                            </td>
                            <td class="text-right font-tabular" style="font-weight: 700;">
                                <?php if ($diferenca == 0.0): ?>
                                    <span class="text-success">R$ 0,00 (Exato)</span>
                                <?php elseif ($diferenca > 0): ?>
                                    <span class="text-success">+ R$ <?php echo number_format($diferenca, 2, ',', '.'); ?> (Sobra)</span>
                                <?php else: ?>
                                    <span class="text-danger">- R$ <?php echo number_format(abs($diferenca), 2, ',', '.'); ?> (Quebra/Falta)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Entradas Digitais — Pix no Balcão (Creditado em Conta Bancária)</td>
                            <td class="text-right font-tabular">R$ <?php echo number_format($pix, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <?php if (!empty($obs)): ?>
                    <div class="obs-box">
                        <span class="meta-label">Observações e Justificativas Registradas:</span>
                        <?php echo $obs; ?>
                    </div>
                <?php endif; ?>

                <table class="signatures">
                    <tr>
                        <td class="sig-box">
                            <?php echo $operador; ?><br>
                            <span style="color: #64748b; font-size: 8pt;">Operador de Caixa</span>
                        </td>
                        <td style="width: 10%;"></td>
                        <td class="sig-box">
                            Gerência / Coordenação Administrativa<br>
                            <span style="color: #64748b; font-size: 8pt;">Visto de Conferência</span>
                        </td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Gera o arquivo binário PDF 1.4 de Fechamento de Caixa no formato A4 timbrado
     * com total fidelidade visual e estrutural ao espelho HTML de prestação de contas.
     * 
     * @param array $sessao
     * @return string
     */
    public static function gerarEspelhoFechamentoCaixaPdf(array $sessao): string
    {
        $id = (int)($sessao['id'] ?? 0);
        $turno = ucfirst($sessao['turno'] ?? 'matutino');
        $operador = $sessao['operador_nome'] ?? 'Operador de Caixa';
        $abertoEm = !empty($sessao['aberto_em']) ? date('d/m/Y H:i', strtotime($sessao['aberto_em'])) : '-';
        $fechadoEm = !empty($sessao['fechado_em']) ? date('d/m/Y H:i', strtotime($sessao['fechado_em'])) : 'Em aberto';
        $statusStr = ($sessao['status'] === 'fechado') ? 'Encerrado / Fechado' : 'Em Aberto';
        $emissao = date('d/m/Y H:i:s');
        $obs = trim($sessao['observacoes'] ?? '');

        $fundo = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
        $avulso = (float)($sessao['total_dinheiro_avulso'] ?? 0.0);
        $recargas = (float)($sessao['total_dinheiro_recargas'] ?? 0.0);
        $pix = (float)($sessao['total_pix_balcao'] ?? 0.0);
        $esperado = round($fundo + $avulso + $recargas, 2);
        $informado = ($sessao['total_informado_gaveta'] !== null) ? (float)$sessao['total_informado_gaveta'] : $esperado;
        $diferenca = ($sessao['diferenca_quebra_sobra'] !== null) ? (float)$sessao['diferenca_quebra_sobra'] : round($informado - $esperado, 2);

        $left = 40.0;
        $right = self::PAGE_WIDTH - 40.0;
        $width = $right - $left;
        $y = 800.0;

        $stream = "";

        // ==========================================
        // 1. Cabeçalho Institucional Oficial
        // ==========================================
        $logoSize = 42.0;
        $logoX = $left;
        $logoY = $y - 42.0;
        $stream .= "q\n";
        $stream .= sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $logoSize, $logoSize, $logoX, $logoY);
        $stream .= "/Im1 Do\n";
        $stream .= "Q\n";

        $textX = $left + $logoSize + 12.0;

        $stream .= "BT\n";
        $stream .= "/F2 14 Tf\n";
        $stream .= "0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $textX, $y - 14);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Colégio Monteiro Lobato"));
        $stream .= "ET\n";

        // Emissão alinhada à direita no topo (linha 1) para não sobrepor o subtítulo
        $stream .= "BT\n";
        $stream .= "/F1 8.5 Tf\n";
        $stream .= "0.39 0.45 0.55 rg\n"; // #64748b
        $stream .= sprintf("%.2f %.2f Td\n", $right - 130, $y - 14);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("Emissão: " . $emissao));
        $stream .= "ET\n";

        // Subtítulo operacional com largura total livre (linha 2)
        $stream .= "BT\n";
        $stream .= "/F2 10 Tf\n";
        $stream .= "0.11 0.31 0.85 rg\n"; // #1d4ed8
        $stream .= sprintf("%.2f %.2f Td\n", $textX, $y - 31);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("PRESTAÇÃO DE CONTAS E FECHAMENTO DE CAIXA — TURNO " . strtoupper($turno)));
        $stream .= "ET\n";

        // Linha divisória azul institucional (#1d4ed8)
        $y -= 48.0;
        $stream .= "0.11 0.31 0.85 RG\n";
        $stream .= "2 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y, $right, $y);

        // ==========================================
        // 2. Grade de Metadados (4 Colunas x 2 Linhas)
        // ==========================================
        $y -= 14.0;
        $metaHeight = 48.0;

        // Borda externa da grade
        $stream .= "0.80 0.84 0.88 RG\n";
        $stream .= "0.75 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $metaHeight, $width, $metaHeight);

        // Linha horizontal central
        $stream .= "0.88 0.91 0.94 RG\n";
        $stream .= "0.5 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - 24, $right, $y - 24);

        // Divisores verticais da Linha 1 (20%, 40%, 70%)
        $x1 = $left + ($width * 0.18);
        $x2 = $left + ($width * 0.38);
        $x3 = $left + ($width * 0.68);
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x1, $y, $x1, $y - 24);
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x2, $y, $x2, $y - 24);
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x3, $y, $x3, $y - 24);

        // Divisor vertical da Linha 2 (50%)
        $xm = $left + ($width * 0.50);
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $xm, $y - 24, $xm, $y - 48);

        // Textos da Linha 1:
        // Célula 1: SESSÃO
        $stream .= "BT\n/F2 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 9);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("SESSÃO"));
        $stream .= "BT\n/F2 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 20);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("#" . $id));

        // Célula 2: TURNO
        $stream .= "BT\n/F2 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x1 + 6, $y - 9);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("TURNO"));
        $stream .= "BT\n/F2 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x1 + 6, $y - 20);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($turno));

        // Célula 3: OPERADOR RESPONSÁVEL
        $stream .= "BT\n/F2 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x2 + 6, $y - 9);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("OPERADOR RESPONSÁVEL"));
        $stream .= "BT\n/F1 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x2 + 6, $y - 20);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($operador));

        // Célula 4: STATUS
        $stream .= "BT\n/F2 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x3 + 6, $y - 9);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("STATUS"));
        $stream .= "BT\n/F2 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x3 + 6, $y - 20);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($statusStr));

        // Textos da Linha 2:
        // Célula 5: DATA/HORA ABERTURA
        $stream .= "BT\n/F2 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 33);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("DATA/HORA ABERTURA"));
        $stream .= "BT\n/F1 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 6, $y - 44);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($abertoEm));

        // Célula 6: DATA/HORA FECHAMENTO
        $stream .= "BT\n/F2 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $xm + 6, $y - 33);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("DATA/HORA FECHAMENTO"));
        $stream .= "BT\n/F1 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $xm + 6, $y - 44);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($fechadoEm));

        // ==========================================
        // 3. Tabela Financeira Analítica
        // ==========================================
        $y -= ($metaHeight + 14.0);

        // Cabeçalho da Tabela
        $stream .= "0.97 0.98 0.99 rg\n"; // #f8fafc
        $stream .= sprintf("%.2f %.2f %.2f 22 re f\n", $left, $y - 22, $width);
        $stream .= "0.80 0.84 0.88 RG\n1.5 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - 22, $right, $y - 22);

        $stream .= "BT\n/F2 8 Tf\n0.28 0.33 0.41 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 8, $y - 15);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("DISCRIMINAÇÃO DOS RECURSOS FINANCEIROS"));

        $stream .= "BT\n/F2 8 Tf\n0.28 0.33 0.41 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $right - 90, $y - 15);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("VALOR APURADO"));

        $y -= 22.0;

        // Linhas de dados financeiros
        $linhas = [
            [
                'label'    => 'Fundo de Troco Inicial (Gaveta)',
                'sub'      => '',
                'valor'    => 'R$ ' . number_format($fundo, 2, ',', '.'),
                'isTotal'  => false,
                'isBold'   => false,
                'colorVal' => [0.06, 0.09, 0.16],
                'height'   => 22.0
            ],
            [
                'label'    => '(+) Vendas Avulsas no Balcão (Dinheiro Físico)',
                'sub'      => '',
                'valor'    => 'R$ ' . number_format($avulso, 2, ',', '.'),
                'isTotal'  => false,
                'isBold'   => false,
                'colorVal' => [0.06, 0.09, 0.16],
                'height'   => 22.0
            ],
            [
                'label'    => '(+) Recargas de Carteira no Balcão (Dinheiro Físico)',
                'sub'      => '',
                'valor'    => 'R$ ' . number_format($recargas, 2, ',', '.'),
                'isTotal'  => false,
                'isBold'   => false,
                'colorVal' => [0.06, 0.09, 0.16],
                'height'   => 22.0
            ],
            [
                'label'    => '(=) Total de Dinheiro em Espécie Esperado na Gaveta',
                'sub'      => '',
                'valor'    => 'R$ ' . number_format($esperado, 2, ',', '.'),
                'isTotal'  => true,
                'isBold'   => true,
                'colorVal' => [0.06, 0.09, 0.16],
                'height'   => 24.0
            ],
            [
                'label'    => 'Total em Dinheiro Efetivamente Contado na Gaveta',
                'sub'      => '',
                'valor'    => 'R$ ' . number_format($informado, 2, ',', '.'),
                'isTotal'  => false,
                'isBold'   => true,
                'colorVal' => [0.06, 0.09, 0.16],
                'height'   => 22.0
            ],
            [
                'label'    => 'Divergência de Caixa (Quebra / Sobra)',
                'sub'      => 'Diferença entre o montante contado e o valor esperado pelo sistema',
                'valor'    => ($diferenca == 0.0) 
                    ? 'R$ 0,00 (Exato)' 
                    : (($diferenca > 0) ? '+ R$ ' . number_format($diferenca, 2, ',', '.') . ' (Sobra)' : '- R$ ' . number_format(abs($diferenca), 2, ',', '.') . ' (Quebra/Falta)'),
                'isTotal'  => false,
                'isBold'   => true,
                'colorVal' => ($diferenca == 0.0) ? [0.08, 0.50, 0.24] : (($diferenca > 0) ? [0.08, 0.50, 0.24] : [0.86, 0.15, 0.15]),
                'height'   => 28.0
            ],
            [
                'label'    => 'Entradas Digitais — Pix no Balcão (Creditado em Conta Bancária)',
                'sub'      => '',
                'valor'    => 'R$ ' . number_format($pix, 2, ',', '.'),
                'isTotal'  => false,
                'isBold'   => false,
                'colorVal' => [0.06, 0.09, 0.16],
                'height'   => 22.0
            ]
        ];

        foreach ($linhas as $lin) {
            $h = $lin['height'];

            // Destaque de fundo para a linha de Total Esperado
            if ($lin['isTotal']) {
                $stream .= "0.94 0.96 0.98 rg\n"; // #f1f5f9
                $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $h, $width, $h);
                $stream .= "0.80 0.84 0.88 RG\n1 w\n";
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y, $right, $y);
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $h, $right, $y - $h);
            } else {
                // Linha divisória suave
                $stream .= "0.88 0.91 0.94 RG\n0.5 w\n";
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $h, $right, $y - $h);
            }

            // Rótulo principal
            $fontTag = $lin['isBold'] ? "/F2" : "/F1";
            $fontSize = $lin['isTotal'] ? "9.5" : "9";
            $textY = !empty($lin['sub']) ? ($y - 12) : ($y - ($h / 2) - 3);

            $stream .= "BT\n";
            $stream .= sprintf("%s %s Tf\n", $fontTag, $fontSize);
            $stream .= "0.06 0.09 0.16 rg\n";
            $stream .= sprintf("%.2f %.2f Td\n", $left + 8, $textY);
            $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($lin['label']));

            // Subtítulo / legenda explicativa
            if (!empty($lin['sub'])) {
                $stream .= "BT\n/F1 7.5 Tf\n0.39 0.45 0.55 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $left + 8, $y - 22);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($lin['sub']));
            }

            // Valor à direita com formatação tabular
            $c = $lin['colorVal'];
            $stream .= "BT\n";
            $stream .= sprintf("%s %s Tf\n", $fontTag, $fontSize);
            $stream .= sprintf("%.2f %.2f %.2f rg\n", $c[0], $c[1], $c[2]);
            $stream .= sprintf("%.2f %.2f Td\n", $right - 140, !empty($lin['sub']) ? ($y - 16) : ($y - ($h / 2) - 3));
            $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($lin['valor']));

            $y -= $h;
        }

        // ==========================================
        // 4. Observações e Justificativas Registradas
        // ==========================================
        if (!empty($obs)) {
            $y -= 14.0;
            $obsBoxHeight = 38.0;

            $stream .= "0.97 0.98 0.99 rg\n"; // #f8fafc
            $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $obsBoxHeight, $width, $obsBoxHeight);
            $stream .= "0.88 0.91 0.94 RG\n0.75 w\n";
            $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $obsBoxHeight, $width, $obsBoxHeight);

            $stream .= "BT\n/F2 7.5 Tf\n0.39 0.45 0.55 rg\n";
            $stream .= sprintf("%.2f %.2f Td\n", $left + 10, $y - 12);
            $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("OBSERVAÇÕES E JUSTIFICATIVAS REGISTRADAS:"));

            $stream .= "BT\n/F1 8.5 Tf\n0.06 0.09 0.16 rg\n";
            $stream .= sprintf("%.2f %.2f Td\n", $left + 10, $y - 25);
            $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($obs));

            $y -= $obsBoxHeight;
        }

        // ==========================================
        // 5. Bloco de Assinaturas Físicas
        // ==========================================
        $y = min($y - 45.0, 260.0);
        $signW = ($width - 50.0) / 2;

        // Assinatura 1: Operador de Caixa
        $stream .= "0.06 0.09 0.16 RG\n0.75 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + 10, $y, $left + 10 + $signW, $y);

        $stream .= "BT\n/F2 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 15, $y - 14);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($operador));

        $stream .= "BT\n/F1 7.5 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 15, $y - 24);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Operador de Caixa"));

        // Assinatura 2: Gerência / Coordenação
        $x2 = $right - 10 - $signW;
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x2, $y, $x2 + $signW, $y);

        $stream .= "BT\n/F2 9 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x2 + 5, $y - 14);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Gerência / Coordenação Administrativa"));

        $stream .= "BT\n/F1 7.5 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $x2 + 5, $y - 24);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Visto de Conferência"));

        // ==========================================
        // 6. Rodapé Institucional
        // ==========================================
        $stream .= "0.88 0.91 0.94 RG\n0.5 w\n";
        $stream .= sprintf("%.2f 42 m %.2f 42 l S\n", $left, $right);

        $stream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
        $stream .= sprintf("%.2f 30 Td\n", $left);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Cantina Web — Colégio Monteiro Lobato • Documento oficial de prestação de contas emitido eletronicamente."));

        $stream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
        $stream .= sprintf("%.2f 30 Td\n", $right - 55);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Página 1 de 1"));

        return self::montarPdfEstruturado($stream);
    }
}

