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

    private static ?string $cachedLogoDataUri = null;
    private static ?string $cachedLogoDataUriOriginal = null;

    /**
     * Retorna o caminho do logotipo otimizado para bobinas térmicas (72x72px, ~2KB)
     */
    public static function getLogoThermalPath(): string
    {
        $candidatos = [
            dirname(__DIR__, 2) . '/assets/img/logo-colegio-thermal.jpg',
            'assets/img/logo-colegio-thermal.jpg',
            __DIR__ . '/../../assets/img/logo-colegio-thermal.jpg',
            '/var/www/html/assets/img/logo-colegio-thermal.jpg'
        ];
        foreach ($candidatos as $c) {
            if (file_exists($c)) {
                return realpath($c) ?: $c;
            }
        }
        return self::getLogoPath();
    }

    /**
     * Retorna a representação em base64 Data-URI do logotipo oficial para inclusão em HTML
     * Prioriza a versão leve para renderização ultrarrápida no diálogo de impressão do navegador
     */
    public static function getLogoDataUri(bool $usarMiniatura = true): string
    {
        if ($usarMiniatura) {
            if (self::$cachedLogoDataUri !== null) {
                return self::$cachedLogoDataUri;
            }
            $path = self::getLogoThermalPath();
            if (!empty($path) && file_exists($path)) {
                $data = file_get_contents($path);
                if ($data !== false) {
                    self::$cachedLogoDataUri = 'data:image/jpeg;base64,' . base64_encode($data);
                    return self::$cachedLogoDataUri;
                }
            }
        }

        if (self::$cachedLogoDataUriOriginal !== null) {
            return self::$cachedLogoDataUriOriginal;
        }

        $path = self::getLogoPath();
        if (!empty($path) && file_exists($path)) {
            $data = file_get_contents($path);
            if ($data !== false) {
                self::$cachedLogoDataUriOriginal = 'data:image/jpeg;base64,' . base64_encode($data);
                return self::$cachedLogoDataUriOriginal;
            }
        }
        return '';
    }

    /**
     * Renderiza o logotipo institucional recortado estritamente em círculo no stream PDF,
     * eliminando os cantos da imagem quadrada original via clipping path vetorial Bézier.
     *
     * @param float $x Posição horizontal inferior esquerda
     * @param float $y Posição vertical inferior esquerda
     * @param float $size Diâmetro / largura e altura do logotipo em pontos
     * @return string Comandos de desenho e clipping vetorial PDF
     */
    public static function desenharLogoCircular(float $x, float $y, float $size): string
    {
        $r = $size / 2.0;
        $cx = $x + $r;
        $cy = $y + $r;
        $k = $r * 0.55228474983;

        $out = "q\n";
        // Traça caminho do círculo perfeito para recorte (clipping path)
        $out .= sprintf("%.2f %.2f m\n", $cx + $r, $cy);
        $out .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx + $r, $cy + $k, $cx + $k, $cy + $r, $cx, $cy + $r);
        $out .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx - $k, $cy + $r, $cx - $r, $cy + $k, $cx - $r, $cy);
        $out .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx - $r, $cy - $k, $cx - $k, $cy - $r, $cx, $cy - $r);
        $out .= sprintf("%.2f %.2f %.2f %.2f %.2f %.2f c\n", $cx + $k, $cy - $r, $cx + $r, $cy - $k, $cx + $r, $cy);
        $out .= "h\n";
        $out .= "W n\n"; // Aplica o recorte (clip) sem preencher nem traçar
        // Desenha a imagem dentro da área recortada
        $out .= sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n", $size, $size, $x, $y);
        $out .= "/Im1 Do\n";
        $out .= "Q\n";

        return $out;
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
        $stream .= self::desenharLogoCircular($logoX, $logoY, $logoSize);

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
        $targetRight = $right - 12.0;
        $quantHeader = "QUANTIDADE";
        $quantX = $targetRight - (strlen($quantHeader) * 5.5);
        $stream .= "BT\n";
        $stream .= "/F2 8 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.28, 0.33, 0.41);
        $stream .= sprintf("%.2f %.2f Td\n", $quantX, $y - 10);
        $stream .= sprintf("(%s) Tj\n", self::escapeText($quantHeader));
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
                $codigo = $item['codigo_interno'] ?? $item['produto_codigo'] ?? ('PROD-' . ($item['produto_id'] ?? ($idx + 1)));
                $descricao = $item['descricao'] ?? $item['produto_nome'] ?? 'Salgado';
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

                // Quantidade (destaque bold alinhado à direita com margem interna de 12pt)
                $qtdStr = $qtd . " un";
                $qtdX = $targetRight - (strlen($qtdStr) * 6.5);
                $stream .= "BT\n";
                $stream .= "/F2 10 Tf\n"; // Bold
                $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.06, 0.09, 0.16);
                $stream .= sprintf("%.2f %.2f Td\n", $qtdX, $y - 8);
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
        $stream .= sprintf("%.2f %.2f Td\n", $left + 10, $y - 6);
        $stream .= sprintf("(%s) Tj\n", self::escapeText("TOTAL GERAL DE PEÇAS A ENTREGAR:"));
        $stream .= "ET\n";

        $totalPecasStr = $totalPecas . " PEÇAS";
        $totalX = $targetRight - (strlen($totalPecasStr) * 8.2);
        $stream .= "BT\n";
        $stream .= "/F2 12 Tf\n";
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
        $stream .= sprintf("%.2f %.2f Td\n", $totalX, $y - 6);
        $stream .= sprintf("(%s) Tj\n", self::escapeText($totalPecasStr));
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
     * Remove acentos e caracteres diacríticos para compatibilidade com fontes PDF padrão Type1
     */
    public static function removerAcentos(string $str): string
    {
        $map = [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u','ç'=>'c',
            'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','É'=>'E','Ê'=>'E','Í'=>'I','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ü'=>'U','Ç'=>'C',
            '•'=>'-','—'=>'-','–'=>'-','“'=>'"','”'=>'"','‘'=>"'",'’'=>"'",'º'=>'o','°'=>'o','ª'=>'a'
        ];
        return strtr($str, $map);
    }

    /**
     * Monta a estrutura binária oficial de um documento PDF 1.4 multi-páginas com tabela xref e trailer.
     * Suporta múltiplas páginas com streams de conteúdo independentes e logotipo institucional compartilhado.
     * 
     * @param array<int, string> $contentStreams Array de streams de desenho para cada página
     * @param float $pageWidth
     * @param float $pageHeight
     * @param string|null $imagePath
     * @return string Conteúdo binário puro do PDF
     */
    public static function montarPdfMultiPaginas(array $contentStreams, float $pageWidth = self::PAGE_WIDTH, float $pageHeight = self::PAGE_HEIGHT, ?string $imagePath = null): string
    {
        $numPages = count($contentStreams);
        if ($numPages <= 1) {
            $stream = $contentStreams[0] ?? '';
            return self::montarPdfEstruturado($stream, $pageWidth, $pageHeight, $imagePath);
        }

        $imgPath = $imagePath ?? self::getLogoPath();
        $hasImage = (!empty($imgPath) && file_exists($imgPath));
        $imgData = $hasImage ? file_get_contents($imgPath) : '';
        $imgInfo = $hasImage ? @getimagesize($imgPath) : null;
        $imgWidth = $imgInfo[0] ?? 447;
        $imgHeight = $imgInfo[1] ?? 447;

        $pdf = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n";
        $offsets = [];

        // Objeto 1: Catalog
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Mapeamento dos objetos por página:
        // Page Object k: id = 3 + (k * 2)
        // Contents Object k: id = 4 + (k * 2)
        $f1ObjId = 3 + ($numPages * 2);
        $f2ObjId = 4 + ($numPages * 2);
        $imgObjId = 5 + ($numPages * 2);

        $kids = [];
        for ($k = 0; $k < $numPages; $k++) {
            $pageObjId = 3 + ($k * 2);
            $kids[] = "{$pageObjId} 0 R";
        }
        $kidsStr = implode(' ', $kids);

        // Objeto 2: Pages
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [{$kidsStr}] /Count {$numPages} >>\nendobj\n";

        // Cria pares de Page e Contents
        $resXObject = $hasImage ? " /XObject << /Im1 {$imgObjId} 0 R >>" : "";
        for ($k = 0; $k < $numPages; $k++) {
            $pageObjId = 3 + ($k * 2);
            $contentObjId = 4 + ($k * 2);
            $cStream = $contentStreams[$k];

            $offsets[$pageObjId] = strlen($pdf);
            $pdf .= sprintf(
                "%d 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >>%s >> >>\nendobj\n",
                $pageObjId,
                $pageWidth,
                $pageHeight,
                $contentObjId,
                $f1ObjId,
                $f2ObjId,
                $resXObject
            );

            $offsets[$contentObjId] = strlen($pdf);
            $streamLen = strlen($cStream);
            $pdf .= "{$contentObjId} 0 obj\n<< /Length {$streamLen} >>\nstream\n{$cStream}\nendstream\nendobj\n";
        }

        // Objeto F1: Helvetica Regular
        $offsets[$f1ObjId] = strlen($pdf);
        $pdf .= "{$f1ObjId} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Objeto F2: Helvetica Bold
        $offsets[$f2ObjId] = strlen($pdf);
        $pdf .= "{$f2ObjId} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Objeto Im1: Logotipo Oficial
        if ($hasImage) {
            $offsets[$imgObjId] = strlen($pdf);
            $imgLen = strlen($imgData);
            $pdf .= "{$imgObjId} 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgWidth} /Height {$imgHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen} >>\nstream\n{$imgData}\nendstream\nendobj\n";
        }

        $totalObjs = $hasImage ? $imgObjId : $f2ObjId;

        // Tabela XREF
        $startXref = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . ($totalObjs + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $totalObjs; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . ($totalObjs + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= $startXref . "\n";
        $pdf .= "%%EOF\n";

        return $pdf;
    }

    /**
     * Escapa caracteres especiais e converte caracteres acentuados para compatibilidade com fontes Type1 padrão
     */
    protected static function escapeText(string $text): string
    {
        // 1. Normaliza caracteres tipográficos e especiais comuns para ASCII
        $replaces = [
            '•' => '-',
            '—' => '-',
            '–' => '-',
            '“' => '"',
            '”' => '"',
            '‘' => "'",
            '’' => "'",
            'º' => 'o',
            '°' => 'o',
            'ª' => 'a',
        ];
        $text = str_replace(array_keys($replaces), array_values($replaces), $text);

        // 2. Remove acentos e diacríticos para compatibilidade total com fontes Type1
        $text = self::removerAcentos($text);

        // 3. Converte para ASCII puro
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '', $text);
        }

        // 4. Escapa barras invertidas e parênteses reservados da sintaxe de strings do PDF
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $converted
        );
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
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';

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
            <div class="no-print" style="display: flex; justify-content: space-between; align-items: center;">
                <a href="<?php echo $baseUrl; ?>/operacional/corte" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 13px; font-weight: 600; text-decoration: none; color: #0f172a; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px;" title="Retornar à tela de Corte Operacional">
                    ← Voltar ao Corte
                </a>
                <button onclick="this.innerHTML='Preparando Impressão...'; const b=this; setTimeout(function(){ window.print(); b.innerHTML='Imprimir Relatório'; }, 50);" class="btn-print">Imprimir Relatório</button>
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
            $stream .= self::desenharLogoCircular($left, $y - 14, 22.0);

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
            $stream .= self::desenharLogoCircular($left + 6, $y - 22, 18.0);

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
                <div style="display: flex; gap: 6px; align-items: center; justify-content: space-between; width: 100%;">
                    <button type="button" class="btn-action btn-primary-action" onclick="window.print()">
                        🖨️ Imprimir (80mm)
                    </button>
                    <a href="<?php echo htmlspecialchars($baseUrl . '/operacional/tickets?data=' . urlencode($dataConsumo) . '&turno=' . urlencode(strtolower($turno)), ENT_QUOTES, 'UTF-8'); ?>" class="btn-action">
                        &larr; Voltar
                    </a>
                </div>
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

            <script>
                // Disparo de impressão automática para iframes e chamadas rápidas
                window.addEventListener('load', function() {
                    const params = new URLSearchParams(window.location.search);
                    if (params.get('autoprint') === '1') {
                        setTimeout(function() {
                            window.print();
                        }, 50);
                    }
                });
                // Tecla ESC fecha a janela
                window.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        window.close();
                    }
                });
            </script>
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
        $formaPagamento = $venda['forma_pagamento'] ?? 'dinheiro';
        $valorTotal = (float)($venda['valor_total'] ?? 0.0);
        $valorRecebido = (float)($venda['valor_dinheiro_recebido'] ?? 0.0);
        $troco = (float)($venda['troco_devolvido'] ?? 0.0);
        $itens = $venda['itens'] ?? [];
        $sessaoId = (int)($venda['sessao_caixa_id'] ?? 0);
        $sessaoTurno = ucfirst($venda['sessao_turno'] ?? 'Turno');

        $rotuloPagamento = match($formaPagamento) {
            'dinheiro' => 'Dinheiro Físico',
            'pix_balcao' => 'Pix Balcão',
            'desconto_folha' => 'Desconto em Folha',
            default => ucfirst($formaPagamento)
        };

        $pageWidth = self::PAGE_WIDTH_THERMAL; // 226.77 pt (80mm)
        $left = 10.0;
        $right = $pageWidth - 10.0;

        // Estimar a altura da bobina dinamicamente para corte exato sem sobras
        $linhasCliente = 1;
        if (!empty($venda['colaborador_nome'])) {
            $linhasCliente = 4;
        } elseif (!empty($venda['aluno_nome'])) {
            $linhasCliente = 3;
        }
        $linhasTroco = ($formaPagamento === 'dinheiro' && $valorRecebido > 0) ? 2 : 0;
        $linhasItens = max(1, count($itens));

        $alturaCalculada = 48.0 + 26.0 + 8.0 + 44.0 + 8.0 + ($linhasCliente * 11.0) + 8.0 + 16.0 + ($linhasItens * 12.0) + 8.0 + 26.0 + ($linhasTroco * 11.0) + 8.0 + 24.0 + 18.0;
        $pageHeight = max(240.0, $alturaCalculada);

        $stream = "";
        $y = $pageHeight - 14.0;

        // Logotipo oficial centralizado no topo (36x36 pt)
        $logoSize = 36.0;
        $logoX = ($pageWidth - $logoSize) / 2.0;
        $stream .= self::desenharLogoCircular($logoX, $y - $logoSize, $logoSize);
        $y -= ($logoSize + 8.0);

        // Helpers locais de texto e traçado
        $drawCenter = function(string $text, float $yPos, string $font, float $size) use ($pageWidth, &$stream) {
            $charW = ($font === '/F2') ? ($size * 0.58) : ($size * 0.52);
            $xPos = max(10.0, ($pageWidth - (mb_strlen($text) * $charW)) / 2.0);
            $stream .= "BT\n{$font} {$size} Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $xPos, $yPos) . sprintf("(%s) Tj\nET\n", self::escapeText($text));
        };

        $drawRight = function(string $text, float $yPos, string $font, float $size) use ($right, &$stream) {
            $charW = ($font === '/F2') ? ($size * 0.58) : ($size * 0.52);
            $xPos = max(10.0, $right - (mb_strlen($text) * $charW));
            $stream .= "BT\n{$font} {$size} Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $xPos, $yPos) . sprintf("(%s) Tj\nET\n", self::escapeText($text));
        };

        $drawDashed = function(float $yPos) use ($left, $right, &$stream) {
            $stream .= sprintf("[2 2] 0 d\n0.5 w\n0 0 0 RG\n%.2f %.2f m\n%.2f %.2f l\nS\n[] 0 d\n", $left, $yPos, $right, $yPos);
        };

        // Cabeçalho Centralizado
        $drawCenter("COLÉGIO MONTEIRO LOBATO", $y, '/F2', 10.0);
        $y -= 12.0;
        $drawCenter("Cantina Escolar • Valores para o Futuro!", $y, '/F1', 7.5);
        $y -= 9.0;

        // Divisória
        $drawDashed($y);
        $y -= 10.0;

        // Dados da Venda
        $stream .= "BT\n/F2 8.5 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Comprovante de Venda #%d) Tj\nET\n", $vendaId);
        $y -= 11.0;
        $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Data/Hora: %s) Tj\nET\n", self::escapeText($dataHora));
        $y -= 10.0;
        $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Atendente: %s) Tj\nET\n", self::escapeText($operador));
        $y -= 10.0;
        $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Sessao Caixa: #%d (%s)) Tj\nET\n", $sessaoId, self::escapeText($sessaoTurno));
        $y -= 9.0;

        // Divisória
        $drawDashed($y);
        $y -= 10.0;

        // Bloco do Cliente
        if (!empty($venda['colaborador_nome'])) {
            $stream .= "BT\n/F2 8.5 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . "(CLIENTE: COLABORADOR) Tj\nET\n";
            $y -= 11.0;
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Nome: %s) Tj\nET\n", self::escapeText($venda['colaborador_nome']));
            $y -= 10.0;
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Matricula: %s) Tj\nET\n", self::escapeText($venda['colaborador_matricula'] ?? ''));
            $y -= 10.0;
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Cargo: %s) Tj\nET\n", self::escapeText($venda['colaborador_cargo'] ?? ''));
            $y -= 9.0;
        } elseif (!empty($venda['aluno_nome'])) {
            $stream .= "BT\n/F2 8.5 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . "(CLIENTE: ALUNO) Tj\nET\n";
            $y -= 11.0;
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Nome: %s) Tj\nET\n", self::escapeText($venda['aluno_nome']));
            $y -= 10.0;
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(Turma: %s) Tj\nET\n", self::escapeText($venda['aluno_turma'] ?? 'Geral'));
            $y -= 9.0;
        } else {
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . "(Cliente: Consumidor Final (Venda Balcao)) Tj\nET\n";
            $y -= 9.0;
        }

        // Divisória
        $drawDashed($y);
        $y -= 10.0;

        // Cabeçalho da Tabela
        $stream .= "BT\n/F2 7.5 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . "(QTD) Tj\nET\n";
        $stream .= "BT\n/F2 7.5 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left + 26.0, $y) . "(ITEM) Tj\nET\n";
        $drawRight("TOTAL", $y, '/F2', 7.5);
        $y -= 4.0;
        // Linha sólida fina sob o cabeçalho
        $stream .= sprintf("0.5 w\n0 0 0 RG\n%.2f %.2f m\n%.2f %.2f l\nS\n", $left, $y, $right, $y);
        $y -= 9.0;

        // Itens
        foreach ($itens as $it) {
            $qtd = (int)($it['quantidade'] ?? 1);
            $desc = mb_substr($it['produto_descricao'] ?? '', 0, 22);
            $sub = 'R$ ' . number_format((float)($it['subtotal'] ?? 0.0), 2, ',', '.');
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left, $y) . sprintf("(%dx) Tj\nET\n", $qtd);
            $stream .= "BT\n/F1 8 Tf\n0 0 0 rg\n" . sprintf("%.2f %.2f Td\n", $left + 26.0, $y) . sprintf("(%s) Tj\nET\n", self::escapeText($desc));
            $drawRight($sub, $y, '/F1', 8.0);
            $y -= 11.0;
        }

        // Divisória
        $drawDashed($y);
        $y -= 12.0;

        // Total
        $totalStr = 'TOTAL: R$ ' . number_format($valorTotal, 2, ',', '.');
        $drawRight($totalStr, $y, '/F2', 10.5);
        $y -= 12.0;

        // Forma de Pagamento
        $drawRight('Forma: ' . $rotuloPagamento, $y, '/F1', 8.0);
        $y -= 10.0;

        if ($formaPagamento === 'dinheiro' && $valorRecebido > 0) {
            $drawRight('Recebido: R$ ' . number_format($valorRecebido, 2, ',', '.'), $y, '/F1', 8.0);
            $y -= 10.0;
            $drawRight('Troco: R$ ' . number_format($troco, 2, ',', '.'), $y, '/F2', 8.5);
            $y -= 10.0;
        }

        // Divisória
        $drawDashed($y);
        $y -= 11.0;

        // Rodapé
        $drawCenter("Agradecemos a sua preferência!", $y, '/F1', 7.5);
        $y -= 10.0;
        $drawCenter("Colégio Monteiro Lobato", $y, '/F1', 7.5);

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
        $baseUrl = $parametros['baseUrl'] ?? (defined('BASE_URL') ? BASE_URL : '');

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
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Comprovante de Venda #<?php echo $vendaId; ?> — Cantina Web</title>
            <style>
                @page {
                    size: auto;
                    margin: 0 auto;
                }
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: 'Courier New', Courier, monospace, monospace;
                    font-size: 12.5px;
                    color: #000;
                    line-height: 1.35;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .print-container {
                    display: flex;
                    justify-content: center;
                    width: 100%;
                }
                .thermal-wrapper {
                    width: 76mm;
                    margin: 0 auto;
                }
                .center { text-align: center; }
                .bold { font-weight: bold; }
                .title { font-size: 14px; font-weight: 800; margin-bottom: 2px; }
                .sub { font-size: 11px; color: #111; margin-bottom: 5px; }
                .divider { border-top: 1px dashed #000; margin: 6px 0; }
                .info-line { font-size: 12px; margin: 2.5px 0; }
                .items-table { width: 100%; font-size: 12px; border-collapse: collapse; margin: 6px 0; }
                .items-table th { border-bottom: 1px solid #000; text-align: left; padding: 3px 0; font-size: 11px; text-transform: uppercase; font-weight: 800; }
                .items-table td { padding: 3px 0; vertical-align: top; }
                .text-right { text-align: right; }
                .total-box { font-size: 15px; font-weight: 900; margin: 6px 0 3px 0; }
                .footer { margin-top: 10px; font-size: 11px; text-align: center; color: #222; }

                /* Impressão em bobina térmica de 80mm e visualização em impressoras A4 */
                @media print {
                    html, body {
                        width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        background: #fff !important;
                    }
                    .no-print {
                        display: none !important;
                    }
                    .print-container {
                        display: flex !important;
                        justify-content: center !important;
                        align-items: flex-start !important;
                        width: 100% !important;
                        margin: 0 auto !important;
                        padding: 0 !important;
                    }
                    .thermal-wrapper {
                        width: 76mm !important;
                        max-width: 76mm !important;
                        margin: 0 auto !important;
                        padding: 2mm 1mm !important;
                        border: none !important;
                        box-shadow: none !important;
                        page-break-inside: avoid !important;
                        color: #000 !important;
                        font-size: 13px !important;
                        line-height: 1.35 !important;
                    }
                    .title { font-size: 15px !important; font-weight: 900 !important; }
                    .sub { font-size: 11.5px !important; }
                    .info-line { font-size: 12.5px !important; margin: 3px 0 !important; }
                    .items-table { font-size: 12.5px !important; }
                    .items-table th { font-size: 11.5px !important; padding: 3px 0 !important; border-bottom: 1px solid #000 !important; }
                    .items-table td { font-size: 12.5px !important; padding: 3px 0 !important; }
                    .total-box { font-size: 16px !important; font-weight: 900 !important; margin: 6px 0 3px 0 !important; }
                    .footer { font-size: 11px !important; margin-top: 10px !important; }
                    .divider { border-top: 1px dashed #000 !important; margin: 6px 0 !important; }
                    .items-table tr { page-break-inside: avoid; }
                }

                @media screen {
                    body {
                        background: #f1f5f9;
                        padding: 24px 12px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                    }
                    .no-print {
                        width: 76mm;
                        margin-bottom: 14px;
                        display: flex;
                        flex-direction: column;
                        gap: 8px;
                    }
                    .action-buttons {
                        display: flex;
                        gap: 8px;
                        justify-content: center;
                    }
                    .btn-print {
                        background: #2563eb;
                        color: #ffffff;
                        border: 1px solid #1d4ed8;
                        padding: 8px 14px;
                        border-radius: 4px;
                        font-weight: 600;
                        cursor: pointer;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        font-size: 12px;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: background 0.15s ease;
                    }
                    .btn-print:hover { background: #1d4ed8; }
                    .btn-pdf {
                        background: #ffffff;
                        color: #0f172a;
                        border: 1px solid #cbd5e1;
                        padding: 8px 12px;
                        border-radius: 4px;
                        font-weight: 600;
                        cursor: pointer;
                        text-decoration: none;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        font-size: 12px;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: background 0.15s ease;
                    }
                    .btn-pdf:hover { background: #f8fafc; }
                    .btn-close {
                        background: #ffffff;
                        color: #475569;
                        border: 1px solid #cbd5e1;
                        padding: 8px 12px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        font-size: 12px;
                        font-weight: 600;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: all 0.15s ease;
                    }
                    .btn-close:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
                    .thermal-wrapper {
                        background: #ffffff;
                        padding: 16px 14px;
                        border: 1px dashed #cbd5e1;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
                    }
                }
            </style>
        </head>
        <body>
            <div class="no-print">
                <div class="action-buttons">
                    <button type="button" onclick="window.print()" class="btn-print" id="btnImprimir">
                        🖨️ Imprimir (80mm)
                    </button>
                    <a href="<?php echo htmlspecialchars($baseUrl . '/operacional/pdv/comprovante/' . $vendaId . '/pdf', ENT_QUOTES, 'UTF-8'); ?>" class="btn-pdf" target="_blank" download="comprovante_venda_<?php echo $vendaId; ?>.pdf">
                        📄 Baixar PDF
                    </a>
                    <button type="button" onclick="fecharComprovante()" class="btn-close" id="btnFecharJanela" title="Fechar comprovante">
                        ✕ Fechar
                    </button>
                </div>
            </div>

            <div class="print-container">
                <div class="thermal-wrapper">
                    <div class="center">
                        <?php $logoUri = self::getLogoDataUri(); if (!empty($logoUri)): ?>
                            <img src="<?php echo $logoUri; ?>" alt="Logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin: 0 auto 5px auto; display: block; border: 1px solid #e2e8f0;">
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
            </div>

            <script>
                // Função segura de fechar com fallback para Caixa
                function fecharComprovante() {
                    window.close();
                    setTimeout(function() {
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.location.href = '<?php echo $baseUrl; ?>/operacional/caixa';
                        }
                    }, 100);
                }

                // Disparo de impressão automática para iframes e chamadas rápidas
                window.addEventListener('load', function() {
                    const params = new URLSearchParams(window.location.search);
                    if (params.get('autoprint') === '1') {
                        setTimeout(function() {
                            window.print();
                        }, 50);
                    }
                });

                // Tecla ESC fecha a janela de comprovante
                window.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        fecharComprovante();
                    }
                });
            </script>
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
        $turnoRaw = strtolower($sessao['turno'] ?? 'matutino');
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';

        $fundo = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
        $avulso = (float)($sessao['total_dinheiro_avulso'] ?? 0.0);
        $recargas = (float)($sessao['total_dinheiro_recargas'] ?? 0.0);
        $pix = (float)($sessao['total_pix_balcao'] ?? 0.0);
        $esperado = round($fundo + $avulso + $recargas, 2);
        $informado = (isset($sessao['total_informado_gaveta']) && $sessao['total_informado_gaveta'] !== null) ? (float)$sessao['total_informado_gaveta'] : $esperado;
        $diferenca = (isset($sessao['diferenca_quebra_sobra']) && $sessao['diferenca_quebra_sobra'] !== null) ? (float)$sessao['diferenca_quebra_sobra'] : round($informado - $esperado, 2);
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
                    padding: 10px 16px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    flex-wrap: wrap;
                }
                .btn-action {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    padding: 7px 14px;
                    font-size: 12.5px;
                    font-weight: 600;
                    text-decoration: none;
                    border-radius: 4px;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    font-family: inherit;
                    line-height: 1.2;
                }
                .btn-secondary {
                    background: #ffffff;
                    color: #0f172a;
                    border: 1px solid #cbd5e1;
                }
                .btn-secondary:hover {
                    background: #f1f5f9;
                    border-color: #94a3b8;
                    color: #0f172a;
                }
                .btn-primary {
                    background: #1d4ed8;
                    color: #ffffff;
                    border: 1px solid #1d4ed8;
                }
                .btn-primary:hover {
                    background: #1e40af;
                    border-color: #1e40af;
                    color: #ffffff;
                }
                @media print {
                    .no-print { display: none !important; }
                    body { font-size: 10pt; }
                }
            </style>
        </head>
        <body tabindex="0" style="outline: none;">
            <div class="sheet">
                <div class="no-print" style="display: flex; flex-direction: column; align-items: stretch; gap: 10px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong style="color: #0f172a; font-size: 14px;">Espelho de Fechamento de Caixa #<?php echo $id; ?></strong>
                            <span style="color: #64748b; font-size: 12px; margin-left: 6px;">Pronto para impressão / arquivamento</span>
                        </div>
                        <span style="font-size: 11px; color: #94a3b8;">Atalho: Pressione <kbd style="background: #e2e8f0; color: #334155; padding: 1px 5px; border-radius: 3px; font-size: 11px;">Esc</kbd> para voltar</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                        <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo urlencode($turnoRaw); ?>" onclick="executarRetornoAoCaixa(); return false;" class="btn-action btn-secondary" id="btnVoltarCaixa" title="Retornar à tela de Gestão de Caixa">
                            ← Voltar ao Caixa
                        </a>
                        <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo $id; ?>/pdf" target="_blank" download="fechamento_caixa_<?php echo $id; ?>.pdf" class="btn-action btn-secondary" id="btnBaixarEspelhoPdf">
                            📄 Baixar PDF
                        </a>
                        <button type="button" onclick="window.print()" class="btn-action btn-primary" id="btnImprimirEspelho">
                            🖨️ Imprimir Documento
                        </button>
                    </div>
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

            <script>
                // Função universal e segura de retorno ao Caixa
                var voltandoAoCaixa = false;
                function executarRetornoAoCaixa() {
                    if (voltandoAoCaixa) return;
                    voltandoAoCaixa = true;

                    var destinoUrl = '<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo urlencode($turnoRaw); ?>';

                    // 1. Tenta fechar caso tenha sido aberta em nova aba/janela por script ou link
                    try {
                        if (window.opener && !window.opener.closed) {
                            window.close();
                        }
                    } catch (e) {}

                    try {
                        window.close();
                    } catch (e) {}

                    // 2. Se a janela não fechou (bloqueio de script do navegador ou mesma aba), redireciona
                    setTimeout(function() {
                        window.location.href = destinoUrl;
                    }, 40);
                }

                // Detecta tecla ESC em qualquer variação de navegador, sistema operacional ou teclado
                function tratarAtalhoEsc(e) {
                    var isEsc = (
                        e.key === 'Escape' || 
                        e.key === 'Esc' || 
                        e.code === 'Escape' || 
                        e.keyCode === 27 || 
                        e.which === 27
                    );

                    if (isEsc) {
                        try {
                            if (e.preventDefault) e.preventDefault();
                            if (e.stopPropagation) e.stopPropagation();
                            if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                        } catch (err) {}

                        executarRetornoAoCaixa();
                        return false;
                    }
                }

                // Captura a tecla tanto no keydown quanto no keyup (Chromium por padrão pode reter keydown para Stop)
                window.addEventListener('keydown', tratarAtalhoEsc, true);
                document.addEventListener('keydown', tratarAtalhoEsc, true);
                window.addEventListener('keyup', tratarAtalhoEsc, true);
                document.addEventListener('keyup', tratarAtalhoEsc, true);

                // Garante que o documento receba foco imediato para capturar o teclado
                function garantirFoco() {
                    try {
                        window.focus();
                        if (document.body) {
                            document.body.focus();
                        }
                    } catch (e) {}
                }
                window.addEventListener('load', garantirFoco);
                document.addEventListener('DOMContentLoaded', garantirFoco);
                document.addEventListener('click', garantirFoco);
            </script>
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
        $stream .= self::desenharLogoCircular($logoX, $logoY, $logoSize);

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

    /**
     * Renderiza o espelho de impressão HTML para o comprovante térmico de recarga presencial em dinheiro (80mm)
     * Conforme Tela 5.4 e Área 6 do docs/FSD.md
     * 
     * @param array $dados
     * @return string
     */
    public static function gerarComprovanteRecargaDinheiroHtml(array $dados): string
    {
        ob_start();
        $transacaoId = (int)($dados['transacao_id'] ?? ($dados['id'] ?? 0));
        $sessaoId = (int)($dados['sessao_caixa_id'] ?? ($dados['referencia_id'] ?? 0));
        $dataHora = !empty($dados['created_at']) ? date('d/m/Y H:i:s', strtotime($dados['created_at'])) : date('d/m/Y H:i:s');
        $operador = $dados['operador_nome'] ?? 'Operador de Caixa';
        $responsavel = $dados['responsavel_nome'] ?? 'Responsável Titular';
        $cpfRaw = preg_replace('/\D/', '', (string)($dados['responsavel_cpf'] ?? ''));
        $cpfMascarado = (strlen($cpfRaw) === 11)
            ? substr($cpfRaw, 0, 3) . '.***.***-' . substr($cpfRaw, -2)
            : $dados['responsavel_cpf'] ?? '';

        $valor = (float)($dados['valor'] ?? 0.0);
        $saldoAnterior = (float)($dados['saldo_anterior'] ?? 0.0);
        $saldoResultante = (float)($dados['saldo_resultante'] ?? 0.0);
        $dependentes = $dados['dependentes'] ?? [];
        $baseUrl = defined('BASE_URL') ? BASE_URL : '';
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Comprovante de Recarga #<?php echo $transacaoId; ?> — Cantina Web</title>
            <style>
                @page {
                    size: auto;
                    margin: 0 auto;
                }
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: 'Courier New', Courier, monospace, monospace;
                    font-size: 12.5px;
                    color: #000;
                    line-height: 1.35;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .print-container {
                    display: flex;
                    justify-content: center;
                    width: 100%;
                }
                .thermal-wrapper {
                    width: 76mm;
                    margin: 0 auto;
                }
                .center { text-align: center; }
                .bold { font-weight: bold; }
                .title { font-size: 14px; font-weight: 800; margin-bottom: 2px; }
                .sub { font-size: 11px; color: #111; margin-bottom: 5px; }
                .divider { border-top: 1px dashed #000; margin: 6px 0; }
                .info-line { font-size: 12px; margin: 2.5px 0; }
                .text-right { text-align: right; }
                .total-box { font-size: 14px; font-weight: 900; margin: 6px 0; text-align: center; background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px; }
                .footer { margin-top: 10px; font-size: 11px; text-align: center; color: #222; }

                @media print {
                    html, body {
                        width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        background: #fff !important;
                    }
                    .no-print {
                        display: none !important;
                    }
                    .print-container {
                        display: flex !important;
                        justify-content: center !important;
                        align-items: flex-start !important;
                        width: 100% !important;
                        margin: 0 auto !important;
                        padding: 0 !important;
                    }
                    .thermal-wrapper {
                        width: 76mm !important;
                        max-width: 76mm !important;
                        margin: 0 auto !important;
                        padding: 2mm 1mm !important;
                        border: none !important;
                        box-shadow: none !important;
                        page-break-inside: avoid !important;
                        color: #000 !important;
                        font-size: 13px !important;
                        line-height: 1.35 !important;
                    }
                    .title { font-size: 15px !important; font-weight: 900 !important; }
                    .sub { font-size: 11.5px !important; }
                    .info-line { font-size: 12.5px !important; margin: 3px 0 !important; }
                    .total-box { font-size: 15px !important; font-weight: 900 !important; background: #fff !important; border: 1px solid #000 !important; }
                    .footer { font-size: 11px !important; margin-top: 10px !important; }
                    .divider { border-top: 1px dashed #000 !important; margin: 6px 0 !important; }
                }

                @media screen {
                    body {
                        background: #f1f5f9;
                        padding: 24px 12px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                    }
                    .no-print {
                        width: 76mm;
                        margin-bottom: 14px;
                        display: flex;
                        flex-direction: column;
                        gap: 8px;
                    }
                    .action-buttons {
                        display: flex;
                        gap: 8px;
                        justify-content: center;
                    }
                    .btn-print {
                        background: #2563eb;
                        color: #ffffff;
                        border: 1px solid #1d4ed8;
                        padding: 8px 14px;
                        border-radius: 4px;
                        font-weight: 600;
                        cursor: pointer;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        font-size: 12px;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: background 0.15s ease;
                    }
                    .btn-print:hover { background: #1d4ed8; }
                    .btn-pdf {
                        background: #ffffff;
                        color: #0f172a;
                        border: 1px solid #cbd5e1;
                        padding: 8px 12px;
                        border-radius: 4px;
                        font-weight: 600;
                        cursor: pointer;
                        text-decoration: none;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        font-size: 12px;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: background 0.15s ease;
                    }
                    .btn-pdf:hover { background: #f8fafc; }
                    .btn-close {
                        background: #ffffff;
                        color: #475569;
                        border: 1px solid #cbd5e1;
                        padding: 8px 12px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        font-size: 12px;
                        font-weight: 600;
                        display: inline-flex;
                        align-items: center;
                        gap: 4px;
                        transition: all 0.15s ease;
                    }
                    .btn-close:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
                    .thermal-wrapper {
                        background: #ffffff;
                        padding: 16px 14px;
                        border: 1px dashed #cbd5e1;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
                    }
                }
            </style>
        </head>
        <body>
            <div class="no-print">
                <div class="action-buttons">
                    <button type="button" onclick="window.print()" class="btn-print" id="btnImprimir">
                        🖨️ Imprimir (80mm)
                    </button>
                    <a href="<?php echo htmlspecialchars($baseUrl . '/operacional/caixa/comprovante-recarga/' . $transacaoId . '/pdf', ENT_QUOTES, 'UTF-8'); ?>" class="btn-pdf" target="_blank" download="comprovante_recarga_<?php echo $transacaoId; ?>.pdf">
                        📄 Baixar PDF
                    </a>
                    <button type="button" onclick="fecharComprovante()" class="btn-close" id="btnFecharJanela" title="Fechar comprovante">
                        ✕ Fechar
                    </button>
                </div>
            </div>

            <div class="print-container">
                <div class="thermal-wrapper">
                    <div class="center">
                        <?php $logoUri = self::getLogoDataUri(); if (!empty($logoUri)): ?>
                            <img src="<?php echo $logoUri; ?>" alt="Logo" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; margin: 0 auto 5px auto; display: block; border: 1px solid #e2e8f0;">
                        <?php endif; ?>
                        <div class="title">COLÉGIO MONTEIRO LOBATO</div>
                        <div class="sub">Cantina Escolar • Valores para o Futuro!</div>
                    </div>
                    <div class="divider"></div>
                    <div class="center bold" style="font-size: 12px; margin: 3px 0;">COMPROVANTE DE RECARGA</div>
                    <div class="center" style="font-size: 10px; color: #222;">PAGAMENTO EM DINHEIRO FÍSICO</div>
                    <div class="divider"></div>
                    <div class="info-line">Transação: #<?php echo $transacaoId; ?></div>
                    <div class="info-line">Sessão Caixa: #<?php echo $sessaoId; ?></div>
                    <div class="info-line">Data/Hora: <?php echo htmlspecialchars($dataHora, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-line">Atendente: <?php echo htmlspecialchars($operador, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="divider"></div>
                    <div class="info-line"><strong>TITULAR DA CARTEIRA:</strong></div>
                    <div class="info-line">Nome: <?php echo htmlspecialchars($responsavel, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="info-line">CPF: <?php echo htmlspecialchars($cpfMascarado, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (!empty($dependentes)): ?>
                        <div class="info-line" style="color: #222; font-size: 10.5px;">Dependentes: <?php 
                            $nomesAlunos = array_map(fn($a) => $a['nome'] . (!empty($a['nome_turma']) ? ' (' . $a['nome_turma'] . ')' : ''), $dependentes);
                            echo htmlspecialchars(implode(', ', $nomesAlunos), ENT_QUOTES, 'UTF-8'); 
                        ?></div>
                    <?php endif; ?>
                    <div class="divider"></div>
                    <div class="total-box">
                        VALOR RECEBIDO: R$ <?php echo number_format($valor, 2, ',', '.'); ?>
                    </div>
                    <table style="width: 100%; font-size: 12px; margin-top: 4px;">
                        <tr>
                            <td>Saldo Anterior:</td>
                            <td class="text-right">R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?></td>
                        </tr>
                        <tr style="font-weight: bold; font-size: 12.5px;">
                            <td>NOVO SALDO DISPONÍVEL:</td>
                            <td class="text-right">R$ <?php echo number_format($saldoResultante, 2, ',', '.'); ?></td>
                        </tr>
                    </table>
                    <div class="divider"></div>
                    <div class="footer">
                        Crédito disponibilizado instantaneamente.<br>
                        Consulte o extrato em tempo real no Portal da Família.<br>
                        Obrigado pela confiança!
                    </div>
                </div>
            </div>

            <script>
                // Função segura de fechar com fallback para Caixa
                var fechandoComp = false;
                function fecharComprovante() {
                    if (fechandoComp) return;
                    fechandoComp = true;

                    try {
                        if (window.opener && !window.opener.closed) {
                            window.close();
                        }
                    } catch (e) {}

                    try {
                        window.close();
                    } catch (e) {}

                    setTimeout(function() {
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.location.href = '<?php echo $baseUrl; ?>/operacional/caixa';
                        }
                    }, 80);
                }

                // Disparo de impressão automática para iframes e chamadas rápidas
                window.addEventListener('load', function() {
                    const params = new URLSearchParams(window.location.search);
                    if (params.get('autoprint') === '1') {
                        setTimeout(function() {
                            window.print();
                        }, 50);
                    }
                    try {
                        window.focus();
                        if (document.body) document.body.focus();
                    } catch(e) {}
                });

                // Tecla ESC fecha a janela de comprovante em qualquer navegador
                function tratarEscComp(e) {
                    var isEsc = (
                        e.key === 'Escape' || 
                        e.key === 'Esc' || 
                        e.code === 'Escape' || 
                        e.keyCode === 27 || 
                        e.which === 27
                    );
                    if (isEsc) {
                        try {
                            if (e.preventDefault) e.preventDefault();
                            if (e.stopPropagation) e.stopPropagation();
                        } catch (err) {}
                        fecharComprovante();
                    }
                }
                window.addEventListener('keydown', tratarEscComp, true);
                document.addEventListener('keydown', tratarEscComp, true);
                window.addEventListener('keyup', tratarEscComp, true);
                document.addEventListener('keyup', tratarEscComp, true);
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Gera o arquivo binário PDF 1.4 de Comprovante Térmico de Recarga em Dinheiro (80mm)
     * 
     * @param array $dados
     * @return string
     */
    public static function gerarComprovanteRecargaDinheiroPdf(array $dados): string
    {
        $transacaoId = (int)($dados['transacao_id'] ?? ($dados['id'] ?? 0));
        $sessaoId = (int)($dados['sessao_caixa_id'] ?? ($dados['referencia_id'] ?? 0));
        $dataHora = !empty($dados['created_at']) ? date('d/m/Y H:i:s', strtotime($dados['created_at'])) : date('d/m/Y H:i:s');
        $operador = $dados['operador_nome'] ?? 'Operador de Caixa';
        $responsavel = $dados['responsavel_nome'] ?? 'Responsavel Titular';
        $cpfRaw = preg_replace('/\D/', '', (string)($dados['responsavel_cpf'] ?? ''));
        $cpfMascarado = (strlen($cpfRaw) === 11)
            ? substr($cpfRaw, 0, 3) . '.***.***-' . substr($cpfRaw, -2)
            : $dados['responsavel_cpf'] ?? '';

        $valor = (float)($dados['valor'] ?? 0.0);
        $saldoAnterior = (float)($dados['saldo_anterior'] ?? 0.0);
        $saldoResultante = (float)($dados['saldo_resultante'] ?? 0.0);

        $pageWidth = self::PAGE_WIDTH_THERMAL;
        $left = 8.0;
        $right = $pageWidth - 8.0;
        $width = $right - $left;
        $pageHeight = 310.0;

        $stream = "";
        $y = $pageHeight - 16.0;

        // Logotipo oficial circular
        $logoSize = 34.0;
        $logoX = ($pageWidth - $logoSize) / 2.0;
        $stream .= self::desenharLogoCircular($logoX, $y - $logoSize, $logoSize);
        $y -= ($logoSize + 8.0);

        // Cabeçalho institucional centralizado
        $stream .= "BT\n/F2 11 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - 140.0) / 2.0, $y);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("COLÉGIO MONTEIRO LOBATO"));

        $y -= 11.0;
        $stream .= "BT\n/F1 7.5 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - 145.0) / 2.0, $y);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Cantina Escolar • Valores para o Futuro!"));

        // Linha divisória
        $y -= 8.0;
        $stream .= "0.75 0.75 0.75 RG\n0.8 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y, $right, $y);

        // Título do documento
        $y -= 12.0;
        $stream .= "BT\n/F2 9.5 Tf\n0.11 0.31 0.85 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - 150.0) / 2.0, $y);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("COMPROVANTE DE RECARGA"));

        $y -= 10.0;
        $stream .= "BT\n/F1 7 Tf\n0.39 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - 110.0) / 2.0, $y);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("PAGAMENTO EM DINHEIRO"));

        $y -= 8.0;
        $stream .= "0.75 0.75 0.75 RG\n0.8 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y, $right, $y);

        // Metadados da transação
        $y -= 11.0;
        $stream .= "BT\n/F1 8 Tf\n0.20 0.20 0.20 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(Transacao: #%d    Sessao: #%d) Tj\nET\n", $transacaoId, $sessaoId);

        $y -= 11.0;
        $stream .= "BT\n/F1 7.5 Tf\n0.20 0.20 0.20 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(Data/Hora: %s) Tj\nET\n", self::escapeText($dataHora));

        $y -= 11.0;
        $stream .= "BT\n/F1 7.5 Tf\n0.20 0.20 0.20 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(Atendente: %s) Tj\nET\n", self::escapeText(substr($operador, 0, 26)));

        $y -= 8.0;
        $stream .= "0.75 0.75 0.75 RG\n0.8 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y, $right, $y);

        // Dados do titular
        $y -= 11.0;
        $stream .= "BT\n/F2 8 Tf\n0.10 0.10 0.10 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(TITULAR: %s) Tj\nET\n", self::escapeText(substr($responsavel, 0, 24)));

        $y -= 10.0;
        $stream .= "BT\n/F1 7.5 Tf\n0.30 0.30 0.30 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(CPF: %s) Tj\nET\n", self::escapeText($cpfMascarado));

        // Box de valor em destaque
        $y -= 16.0;
        $boxHeight = 22.0;
        $stream .= "0.95 0.97 0.99 rg\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxHeight, $width, $boxHeight);
        $stream .= "0.80 0.85 0.90 RG\n1 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxHeight, $width, $boxHeight);

        $valorStr = "RECEBIDO: R$ " . number_format($valor, 2, ',', '.');
        $stream .= "BT\n/F2 10.5 Tf\n0.08 0.50 0.20 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - (strlen($valorStr) * 5.8)) / 2.0, $y - 15.0);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($valorStr));

        $y -= ($boxHeight + 8.0);
        $stream .= "BT\n/F1 8 Tf\n0.25 0.25 0.25 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(Saldo Anterior: R$ %s) Tj\nET\n", number_format($saldoAnterior, 2, ',', '.'));

        $y -= 12.0;
        $stream .= "BT\n/F2 8.5 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $stream .= sprintf("(NOVO SALDO: R$ %s) Tj\nET\n", number_format($saldoResultante, 2, ',', '.'));

        // Linha divisória de corte pontilhada
        $y -= 10.0;
        $stream .= "0.75 0.75 0.75 RG\n0.8 w\n[2 2] 0 d\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y, $right, $y);
        $stream .= "[] 0 d\n";

        // Mensagem de rodapé
        $y -= 11.0;
        $stream .= "BT\n/F1 6.5 Tf\n0.45 0.50 0.60 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - 170.0) / 2.0, $y);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Credito disponibilizado na Carteira Familiar."));

        $y -= 9.0;
        $stream .= "BT\n/F1 6.5 Tf\n0.45 0.50 0.60 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", ($pageWidth - 165.0) / 2.0, $y);
        $stream .= sprintf("(%s) Tj\nET\n", self::escapeText("Consulte o extrato no Portal da Familia."));

        return self::montarPdfEstruturado($stream, $pageWidth, $pageHeight);
    }

    /**
     * Gera o Relatório de Famílias com Saldo Devedor (Negativados MED) em formato binário PDF A4
     * Conforme FSD Tela 8.4 e DESIGN.md
     * 
     * @param array $dados Estrutura com lista de famílias, totais e parâmetros de emissão
     * @return string Conteúdo binário puro do PDF A4
     */
    public static function gerarRelatorioNegativadosMedPdf(array $dados): string
    {
        $familias = $dados['familias'] ?? [];
        $totalDevedor = (float)($dados['total_devedor'] ?? 0.0);
        $totalFamilias = (int)($dados['total_familias'] ?? count($familias));
        $emissao = date('d/m/Y H:i:s');
        $operador = $dados['operador'] ?? 'Administração Geral';

        $stream = "";
        $left = 36.0;
        $right = self::PAGE_WIDTH - 36.0;
        $width = $right - $left;

        // Topo da página A4
        $y = 800.0;

        // Faixa de cabeçalho institucional (fundo slate 100 suave)
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, $width, 58.0);

        // Borda de acento azul cobalto institucional (#1d4ed8)
        $stream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, 4.0, 58.0);

        // Brasão circular vetorial oficial
        $logoSize = 42.0;
        $stream .= self::desenharLogoCircular($left + 12.0, $y - 42.0, $logoSize);

        // Textos institucionais do cabeçalho
        $textLeft = $left + 62.0;
        $stream .= "BT\n/F2 13 Tf\n0.06 0.09 0.16 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 2.0);
        $stream .= sprintf("(COLEGIO MONTEIRO LOBATO) Tj\nET\n");

        $stream .= "BT\n/F2 10.5 Tf\n0.73 0.15 0.11 rg\n"; // Vermelho institucional de alerta
        $stream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 17.0);
        $stream .= sprintf("(RELATORIO DE FAMILIAS COM SALDO DEVEDOR - NEGATIVADOS MED) Tj\nET\n");

        $stream .= "BT\n/F1 7.5 Tf\n0.28 0.33 0.41 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 31.0);
        $stream .= sprintf("(Emitido em: %s | Operador: %s) Tj\nET\n", $emissao, self::escapeText(substr($operador, 0, 30)));

        $y -= 65.0;

        // Bloco de Resumo Executivo / Métricas Rápidas
        $boxHeight = 36.0;
        $stream .= "0.98 0.98 0.99 rg\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxHeight, $width, $boxHeight);
        $stream .= "0.85 0.88 0.92 RG\n0.8 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxHeight, $width, $boxHeight);

        // Métrica 1: Famílias Inadimplentes
        $stream .= "BT\n/F1 7.5 Tf\n0.40 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 14.0, $y - 13.0);
        $stream .= sprintf("(TOTAL DE FAMILIAS INADIMPLENTES) Tj\nET\n");

        $stream .= "BT\n/F2 12 Tf\n0.10 0.10 0.15 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left + 14.0, $y - 28.0);
        $stream .= sprintf("(%d familia%s) Tj\nET\n", $totalFamilias, $totalFamilias > 1 ? 's' : '');

        // Métrica 2: Saldo Total Devedor
        $col2 = $left + 195.0;
        $stream .= "BT\n/F1 7.5 Tf\n0.40 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $col2, $y - 13.0);
        $stream .= sprintf("(MONTANTE TOTAL DEVEDOR EM ABERTO) Tj\nET\n");

        $totalFmt = "R$ " . number_format($totalDevedor, 2, ',', '.');
        $stream .= "BT\n/F2 12 Tf\n0.73 0.15 0.11 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $col2, $y - 28.0);
        $stream .= sprintf("(%s) Tj\nET\n", $totalFmt);

        // Métrica 3: Base Regulamentar
        $col3 = $left + 375.0;
        $stream .= "BT\n/F1 7.5 Tf\n0.40 0.45 0.55 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $col3, $y - 13.0);
        $stream .= sprintf("(DIRETRIZ DE SEGURANCA) Tj\nET\n");

        $stream .= "BT\n/F2 8.5 Tf\n0.11 0.31 0.85 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $col3, $y - 26.0);
        $stream .= sprintf("(Res. BCB no 103 - MED Pix) Tj\nET\n");

        $y -= ($boxHeight + 18.0);

        // Cabeçalho da Tabela Data-Grid
        $thHeight = 18.0;
        $stream .= "0.92 0.94 0.97 rg\n"; // Cinza claro corporativo
        $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thHeight, $width, $thHeight);
        $stream .= "0.80 0.83 0.88 RG\n1 w\n";
        $stream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thHeight, $width, $thHeight);

        // Larguras das colunas (Total: 523.28 pt)
        $c1 = $left + 8.0;   // CPF: ~66 pt
        $c2 = $left + 85.0;  // Titular: ~125 pt
        $c3 = $left + 215.0; // WhatsApp: ~65 pt
        $c4 = $left + 285.0; // Dependentes: ~105 pt
        $c5 = $left + 395.0; // Data Estorno: ~40 pt
        $c6 = $right - 8.0;  // Saldo Devedor: 55 pt (alinhado à direita)

        $stream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $c1, $y - 12.0);
        $stream .= sprintf("(CPF) Tj\nET\n");

        $stream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $c2, $y - 12.0);
        $stream .= sprintf("(TITULAR FAMILIAR) Tj\nET\n");

        $stream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $c3, $y - 12.0);
        $stream .= sprintf("(WHATSAPP) Tj\nET\n");

        $stream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $c4, $y - 12.0);
        $stream .= sprintf("(DEPENDENTES) Tj\nET\n");

        $stream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $c5, $y - 12.0);
        $stream .= sprintf("(ESTORNO) Tj\nET\n");

        $stream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $thSaldoStr = "SALDO DEVEDOR";
        $stream .= sprintf("%.2f %.2f Td\n", $c6 - (strlen($thSaldoStr) * 4.8), $y - 12.0);
        $stream .= sprintf("(%s) Tj\nET\n", $thSaldoStr);

        $y -= $thHeight;

        // Linhas de famílias
        $rowHeight = 22.0;
        $i = 0;

        if (empty($familias)) {
            $stream .= "BT\n/F1 8.5 Tf\n0.45 0.50 0.60 rg\n";
            $stream .= sprintf("%.2f %.2f Td\n", ($left + $right) / 2.0 - 100.0, $y - 20.0);
            $stream .= sprintf("(Nenhuma familia com saldo devedor ou bloqueio MED no momento.) Tj\nET\n");
            $y -= 35.0;
        } else {
            foreach ($familias as $fam) {
                if ($y < 70.0) {
                    break; // Mantém em folha única segura
                }

                $i++;
                $zebra = ($i % 2 === 0);
                if ($zebra) {
                    $stream .= "0.98 0.98 0.99 rg\n";
                    $stream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowHeight, $width, $rowHeight);
                }

                // Linha divisória inferior sutil
                $stream .= "0.89 0.91 0.94 RG\n0.5 w\n";
                $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowHeight, $right, $y - $rowHeight);

                $cpfStr = $fam['cpf_formatado'] ?? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', str_pad($fam['cpf'] ?? '', 11, '0', STR_PAD_LEFT));
                $nomeTitular = mb_substr(self::removerAcentos($fam['nome'] ?? ''), 0, 26, 'UTF-8');
                $whatsStr = $fam['whatsapp'] ?? '-';
                $depsStr = mb_substr(self::removerAcentos($fam['dependentes_str'] ?? '-'), 0, 26, 'UTF-8');
                $dataEstorno = !empty($fam['data_ultimo_estorno_med']) ? date('d/m/y', strtotime($fam['data_ultimo_estorno_med'])) : '-';
                $saldoNum = (float)($fam['saldo_atual'] ?? 0.0);
                $saldoStr = "- R$ " . number_format(abs($saldoNum), 2, ',', '.');

                // Coluna 1: CPF
                $stream .= "BT\n/F1 7.5 Tf\n0.15 0.18 0.25 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $c1, $y - 14.0);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($cpfStr));

                // Coluna 2: Nome Titular
                $stream .= "BT\n/F2 7.5 Tf\n0.08 0.10 0.15 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $c2, $y - 14.0);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($nomeTitular));

                // Coluna 3: WhatsApp
                $stream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $c3, $y - 14.0);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($whatsStr));

                // Coluna 4: Dependentes
                $stream .= "BT\n/F1 7.5 Tf\n0.20 0.25 0.35 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $c4, $y - 14.0);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($depsStr));

                // Coluna 5: Data Estorno
                $stream .= "BT\n/F1 7.5 Tf\n0.40 0.45 0.55 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $c5, $y - 14.0);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($dataEstorno));

                // Coluna 6: Saldo Devedor (Vermelho Alinhado à Direita)
                $stream .= "BT\n/F2 8 Tf\n0.73 0.15 0.11 rg\n";
                $stream .= sprintf("%.2f %.2f Td\n", $c6 - (strlen($saldoStr) * 5.0), $y - 14.0);
                $stream .= sprintf("(%s) Tj\nET\n", self::escapeText($saldoStr));

                $y -= $rowHeight;
            }
        }

        // Rodapé Oficial da Instituição
        $yRodape = 35.0;
        $stream .= "0.85 0.88 0.92 RG\n0.8 w\n";
        $stream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $yRodape + 12.0, $right, $yRodape + 12.0);

        $stream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $left, $yRodape);
        $stream .= sprintf("(COLEGIO MONTEIRO LOBATO - Valores para o Futuro! | Sistema de Gestao de Cantina Escolar) Tj\nET\n");

        $stream .= "BT\n/F2 7 Tf\n0.11 0.31 0.85 rg\n";
        $stream .= sprintf("%.2f %.2f Td\n", $right - 70.0, $yRodape);
        $stream .= sprintf("(Pagina 1 de 1) Tj\nET\n");

        return self::montarPdfEstruturado($stream, self::PAGE_WIDTH, self::PAGE_HEIGHT);
    }

    /**
     * Gera o Relatório de Extrato de Consumo por Aluno em PDF A4 Timbrado Oficial (FSD Tela 8.1 & Seção 22.1)
     * 
     * @param array $dados
     * @return string Conteúdo binário puro do PDF 1.4
     */
    public static function gerarExtratoConsumoAlunoPdf(array $dados): string
    {
        $aluno = $dados['aluno'] ?? [];
        $saldoAtual = (float)($dados['saldo_atual'] ?? 0.0);
        $dataInicio = $dados['data_inicio'] ?? date('Y-m-01');
        $dataFim = $dados['data_fim'] ?? date('Y-m-d');
        $dataIniFmt = date('d/m/Y', strtotime($dataInicio));
        $dataFimFmt = date('d/m/Y', strtotime($dataFim));
        $recargas = $dados['recargas'] ?? [];
        $consumo = $dados['consumo'] ?? [];
        $operador = $dados['operador'] ?? 'Atendimento da Cantina';
        $emissao = date('d/m/Y H:i:s');

        $totalRecargas = 0.0;
        foreach ($recargas as $r) {
            $totalRecargas += (float)($r['valor'] ?? 0.0);
        }
        $totalConsumo = 0.0;
        foreach ($consumo as $c) {
            $totalConsumo += (float)($c['subtotal'] ?? 0.0);
        }

        $left = 36.0;
        $right = self::PAGE_WIDTH - 36.0;
        $width = $right - $left;

        $pages = [];
        $currentStream = "";
        $y = 800.0;

        // Função interna para iniciar página
        $iniciarPagina = function(bool $isFirstPage) use (&$currentStream, &$y, $left, $right, $width, $aluno, $operador, $emissao, $dataIniFmt, $dataFimFmt, $saldoAtual, $totalRecargas, $totalConsumo) {
            $currentStream = "";
            $y = 800.0;

            if ($isFirstPage) {
                // Cabeçalho Principal Timbrado A4
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, $width, 58.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, 4.0, 58.0);

                // Brasão circular vetorial
                $currentStream .= self::desenharLogoCircular($left + 12.0, $y - 42.0, 42.0);

                $textLeft = $left + 62.0;
                $currentStream .= "BT\n/F2 13 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 2.0);
                $currentStream .= "(COLEGIO MONTEIRO LOBATO - CANTINA ESCOLAR) Tj\nET\n";

                $currentStream .= "BT\n/F2 10.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 17.0);
                $currentStream .= "(EXTRATO ANALITICO DE CONSUMO DO ALUNO) Tj\nET\n";

                $currentStream .= "BT\n/F1 7.5 Tf\n0.28 0.33 0.41 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 31.0);
                $operadorLimpo = self::removerAcentos($operador);
                $currentStream .= sprintf("(Emitido em: %s | Atendente: %s) Tj\nET\n", $emissao, self::escapeText(substr($operadorLimpo, 0, 30)));

                $y -= 62.0;

                // Box 1: Dados do Aluno e Responsável (altura 48 pt)
                $boxH = 48.0;
                $currentStream .= "0.98 0.99 1.00 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH, $width, $boxH);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH, $width, $boxH);

                $nomeAluno = self::removerAcentos($aluno['nome'] ?? 'Aluno Não Informado');
                $matAluno  = $aluno['matricula'] ?? '-';
                $turmaAluno= self::removerAcentos(($aluno['serie'] ?? '') . ' ' . ($aluno['nome_turma'] ?? ''));
                $turnoAluno= ucfirst($aluno['turno'] ?? '-');
                $respNome  = self::removerAcentos($aluno['responsavel_nome'] ?? '-');
                $respWhats = $aluno['responsavel_whatsapp'] ?? '-';
                $respCpf   = $aluno['responsavel_cpf'] ?? '-';

                // Coluna Esquerda: Aluno
                $currentStream .= "BT\n/F2 7.5 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
                $currentStream .= sprintf("(ESTUDANTE BENEFICIARIO:) Tj\nET\n");

                $currentStream .= "BT\n/F2 9.5 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 24.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($nomeAluno));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 36.0);
                $currentStream .= sprintf("(Matricula: %s  |  Turma: %s  |  Turno: %s) Tj\nET\n", self::escapeText($matAluno), self::escapeText($turmaAluno), self::escapeText($turnoAluno));

                // Coluna Direita: Responsável Titular
                $colDirX = $left + 290.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $colDirX - 10.0, $y - $boxH, $colDirX - 10.0, $y);

                $currentStream .= "BT\n/F2 7.5 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $colDirX, $y - 12.0);
                $currentStream .= sprintf("(RESPONSAVEL FINANCEIRO (CARTEIRA FAMILIAR):) Tj\nET\n");

                $currentStream .= "BT\n/F2 9.0 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $colDirX, $y - 24.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($respNome, 0, 32)));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $colDirX, $y - 36.0);
                $currentStream .= sprintf("(WhatsApp: %s  |  CPF: %s) Tj\nET\n", self::escapeText($respWhats), self::escapeText($respCpf));

                $y -= ($boxH + 12.0);

                // Box 2: Resumo Financeiro / Stat-Strip (altura 34 pt)
                $boxH2 = 34.0;
                $currentStream .= "0.96 0.97 0.99 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH2, $width, $boxH2);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH2, $width, $boxH2);

                $colW = $width / 4.0;

                // Métrica 1: Saldo da Carteira
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
                $currentStream .= sprintf("(SALDO ATUAL DA CARTEIRA) Tj\nET\n");

                $saldoStr = "R$ " . number_format($saldoAtual, 2, ',', '.');
                $saldoColor = ($saldoAtual >= 0) ? "0.08 0.55 0.24" : "0.73 0.15 0.11";
                $currentStream .= "BT\n/F2 11 Tf\n{$saldoColor} rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $saldoStr);

                // Métrica 2: Recargas no período
                $xM2 = $left + $colW + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + $colW, $y - $boxH2, $left + $colW, $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 12.0);
                $currentStream .= sprintf("(RECARGAS NO PERIODO) Tj\nET\n");

                $recStr = "R$ " . number_format($totalRecargas, 2, ',', '.');
                $currentStream .= "BT\n/F2 11 Tf\n0.08 0.55 0.24 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $recStr);

                // Métrica 3: Consumo no período
                $xM3 = $left + ($colW * 2) + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($colW * 2), $y - $boxH2, $left + ($colW * 2), $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 12.0);
                $currentStream .= sprintf("(CONSUMO NO PERIODO) Tj\nET\n");

                $conStr = "R$ " . number_format($totalConsumo, 2, ',', '.');
                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $conStr);

                // Métrica 4: Período
                $xM4 = $left + ($colW * 3) + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($colW * 3), $y - $boxH2, $left + ($colW * 3), $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM4, $y - 12.0);
                $currentStream .= sprintf("(PERIODO CONSULTADO) Tj\nET\n");

                $currentStream .= "BT\n/F2 8.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM4, $y - 25.0);
                $currentStream .= sprintf("(%s a %s) Tj\nET\n", $dataIniFmt, $dataFimFmt);

                $y -= ($boxH2 + 16.0);
            } else {
                // Mini Cabeçalho para Páginas Subsequentes
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, $width, 38.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, 3.0, 38.0);

                $currentStream .= self::desenharLogoCircular($left + 8.0, $y - 28.0, 28.0);

                $textLeft = $left + 46.0;
                $currentStream .= "BT\n/F2 10 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 6.0);
                $currentStream .= sprintf("(COLEGIO MONTEIRO LOBATO - EXTRATO DE CONSUMO - CONTINUACAO) Tj\nET\n");

                $nomeAluno = self::removerAcentos($aluno['nome'] ?? 'Aluno');
                $matAluno  = $aluno['matricula'] ?? '-';
                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 20.0);
                $currentStream .= sprintf("(Estudante: %s  |  Matricula: %s  |  Periodo: %s a %s) Tj\nET\n", self::escapeText($nomeAluno), self::escapeText($matAluno), $dataIniFmt, $dataFimFmt);

                $y -= 44.0;
            }
        };

        // Função interna para fechar página com rodapé oficial
        $fecharPagina = function() use (&$pages, &$currentStream, $left, $right) {
            $yRodape = 32.0;
            $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $yRodape + 10.0, $right, $yRodape + 10.0);

            $currentStream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left, $yRodape);
            $currentStream .= sprintf("(Cantina Web - Colegio Monteiro Lobato  |  Documento oficial emitido eletronicamente para fins de prestacao de contas) Tj\nET\n");

            $currentStream .= "BT\n/F2 7 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $right - 75.0, $yRodape);
            $currentStream .= "(Pagina __PAG_NUM__ de __PAG_TOT__) Tj\nET\n";

            $pages[] = $currentStream;
        };

        // Inicia Página 1
        $iniciarPagina(true);

        // --- SEÇÃO 1: RECARGAS NO PERÍODO ---
        $currentStream .= "BT\n/F2 8.5 Tf\n0.11 0.31 0.85 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $currentStream .= sprintf("(1. HISTORICO DE RECARGAS NA CARTEIRA FAMILIAR NO PERIODO) Tj\nET\n");
        $y -= 14.0;

        // Cabeçalho da Tabela de Recargas
        $thH = 16.0;
        $currentStream .= "0.92 0.94 0.97 rg\n";
        $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thH, $width, $thH);
        $currentStream .= "0.80 0.83 0.88 RG\n0.75 w\n";
        $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thH, $width, $thH);

        $rc1 = $left + 6.0;   // Data/Hora (100 pt)
        $rc2 = $left + 110.0; // Forma/Tipo (140 pt)
        $rc3 = $left + 260.0; // Atendente/Canal (150 pt)
        $rc4 = $right - 8.0;  // Valor (alinhado à direita)

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $rc1, $y - 11.0);
        $currentStream .= sprintf("(DATA/HORA) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $rc2, $y - 11.0);
        $currentStream .= sprintf("(FORMA DE PAGAMENTO) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $rc3, $y - 11.0);
        $currentStream .= sprintf("(ATENDENTE / CANAL) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $rc4 - 38.0, $y - 11.0);
        $currentStream .= sprintf("(VALOR (R$)) Tj\nET\n");

        $y -= $thH;

        if (empty($recargas)) {
            $rowH = 18.0;
            $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);
            $currentStream .= "BT\n/F1 7.5 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 12.0, $y - 12.0);
            $currentStream .= sprintf("(Nenhuma recarga registrada na carteira durante este periodo.) Tj\nET\n");
            $y -= ($rowH + 12.0);
        } else {
            $rowH = 16.0;
            $ri = 0;
            foreach ($recargas as $rec) {
                if ($y < 70.0) {
                    $fecharPagina();
                    $iniciarPagina(false);
                }

                $ri++;
                if ($ri % 2 === 0) {
                    $currentStream .= "0.98 0.98 0.99 rg\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowH, $width, $rowH);
                }
                $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);

                $dataR = !empty($rec['created_at']) ? date('d/m/Y H:i', strtotime($rec['created_at'])) : '-';
                $tipoR = ($rec['tipo_transacao'] === 'recarga_pix') ? 'Recarga Pix Online' : 'Recarga em Dinheiro (Balcao)';
                $opR   = self::removerAcentos($rec['operador_nome'] ?? 'Sistema / Gateway Pix');
                $valR  = "R$ " . number_format((float)($rec['valor'] ?? 0.0), 2, ',', '.');

                $currentStream .= "BT\n/F1 7.5 Tf\n0.15 0.18 0.25 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $rc1, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($dataR));

                $currentStream .= "BT\n/F2 7.5 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $rc2, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tipoR));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $rc3, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($opR, 0, 30)));

                $currentStream .= "BT\n/F2 8 Tf\n0.08 0.55 0.24 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $rc4 - (strlen($valR) * 4.8), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($valR));

                $y -= $rowH;
            }
            $y -= 12.0;
        }

        // --- SEÇÃO 2: CONSUMO DO ALUNO ---
        if ($y < 110.0) {
            $fecharPagina();
            $iniciarPagina(false);
        }

        $currentStream .= "BT\n/F2 8.5 Tf\n0.11 0.31 0.85 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $left, $y);
        $currentStream .= sprintf("(2. HISTORICO DE CONSUMO DE LANCHES DO ALUNO NO PERIODO) Tj\nET\n");
        $y -= 14.0;

        // Cabeçalho da Tabela de Consumo
        $thH = 16.0;
        $currentStream .= "0.92 0.94 0.97 rg\n";
        $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thH, $width, $thH);
        $currentStream .= "0.80 0.83 0.88 RG\n0.75 w\n";
        $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thH, $width, $thH);

        $cc1 = $left + 6.0;   // Data (55 pt)
        $cc2 = $left + 66.0;  // Turno/Canal (75 pt)
        $cc3 = $left + 146.0; // Descrição (200 pt)
        $cc4 = $left + 355.0; // Qtd (35 pt)
        $cc5 = $left + 400.0; // Unit. (50 pt)
        $cc6 = $right - 8.0;  // Subtotal (alinhado à direita)

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $cc1, $y - 11.0);
        $currentStream .= sprintf("(DATA) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $cc2, $y - 11.0);
        $currentStream .= sprintf("(TURNO / CANAL) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $cc3, $y - 11.0);
        $currentStream .= sprintf("(ITEM / PRODUTO) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $cc4, $y - 11.0);
        $currentStream .= sprintf("(QTD) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $cc5, $y - 11.0);
        $currentStream .= sprintf("(UNITARIO) Tj\nET\n");

        $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
        $currentStream .= sprintf("%.2f %.2f Td\n", $cc6 - 45.0, $y - 11.0);
        $currentStream .= sprintf("(SUBTOTAL (R$)) Tj\nET\n");

        $y -= $thH;

        if (empty($consumo)) {
            $rowH = 18.0;
            $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);
            $currentStream .= "BT\n/F1 7.5 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 12.0, $y - 12.0);
            $currentStream .= sprintf("(Nenhum consumo de lanches registrado para este aluno no periodo.) Tj\nET\n");
            $y -= ($rowH + 12.0);
        } else {
            $rowH = 16.0;
            $ci = 0;
            foreach ($consumo as $item) {
                if ($y < 70.0) {
                    $fecharPagina();
                    $iniciarPagina(false);

                    // Repete cabeçalho de consumo na nova página
                    $currentStream .= "0.92 0.94 0.97 rg\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thH, $width, $thH);
                    $currentStream .= "0.80 0.83 0.88 RG\n0.75 w\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thH, $width, $thH);

                    $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
                    $currentStream .= sprintf("%.2f %.2f Td\n", $cc1, $y - 11.0);
                    $currentStream .= sprintf("(DATA) Tj\nET\n");
                    $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
                    $currentStream .= sprintf("%.2f %.2f Td\n", $cc2, $y - 11.0);
                    $currentStream .= sprintf("(TURNO / CANAL) Tj\nET\n");
                    $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
                    $currentStream .= sprintf("%.2f %.2f Td\n", $cc3, $y - 11.0);
                    $currentStream .= sprintf("(ITEM / PRODUTO) Tj\nET\n");
                    $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
                    $currentStream .= sprintf("%.2f %.2f Td\n", $cc4, $y - 11.0);
                    $currentStream .= sprintf("(QTD) Tj\nET\n");
                    $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
                    $currentStream .= sprintf("%.2f %.2f Td\n", $cc5, $y - 11.0);
                    $currentStream .= sprintf("(UNITARIO) Tj\nET\n");
                    $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
                    $currentStream .= sprintf("%.2f %.2f Td\n", $cc6 - 45.0, $y - 11.0);
                    $currentStream .= sprintf("(SUBTOTAL (R$)) Tj\nET\n");

                    $y -= $thH;
                }

                $ci++;
                if ($ci % 2 === 0) {
                    $currentStream .= "0.98 0.98 0.99 rg\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowH, $width, $rowH);
                }
                $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);

                $dataC = !empty($item['data_consumo']) ? date('d/m/Y', strtotime($item['data_consumo'])) : date('d/m/Y', strtotime($item['created_at'] ?? 'now'));
                $origem = $item['origem'] ?? $item['canal'] ?? 'balcao';
                $isAgend = ($origem === 'agendamento' || stripos($origem, 'online') !== false || stripos($origem, 'agendamento') !== false);
                $canalC = $isAgend ? 'Online (' . ucfirst(substr($item['turno'] ?? 'M', 0, 1)) . ')' : 'Balcao (' . ucfirst(substr($item['turno'] ?? 'M', 0, 1)) . ')';
                $prodC = self::removerAcentos($item['produto_nome'] ?? $item['itens_descricao'] ?? 'Itens avulsos');
                $qtdC  = (int)($item['quantidade'] ?? $item['total_itens'] ?? 1) . " un";
                $precoUnit = (float)($item['preco_unitario'] ?? ($item['valor_total'] ?? 0.0));
                $unitC = "R$ " . number_format($precoUnit, 2, ',', '.');
                $subtC = "R$ " . number_format((float)($item['subtotal'] ?? $item['valor_total'] ?? 0.0), 2, ',', '.');

                $currentStream .= "BT\n/F1 7.5 Tf\n0.15 0.18 0.25 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cc1, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($dataC));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cc2, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($canalC, 0, 16)));

                $currentStream .= "BT\n/F2 7.5 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cc3, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($prodC, 0, 36)));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.20 0.25 0.35 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cc4, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($qtdC));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cc5, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($unitC));

                $currentStream .= "BT\n/F2 8 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cc6 - (strlen($subtC) * 4.8), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($subtC));

                $y -= $rowH;
            }

            // Linha de Totalização de Consumo
            $totH = 18.0;
            $currentStream .= "0.94 0.96 0.98 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $totH, $width, $totH);
            $currentStream .= "0.80 0.83 0.88 RG\n1 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $totH, $width, $totH);

            $currentStream .= "BT\n/F2 8 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
            $currentStream .= sprintf("(TOTAL CONSOLIDADO DE CONSUMO NO PERIODO:) Tj\nET\n");

            $totConsumoFmt = "R$ " . number_format($totalConsumo, 2, ',', '.');
            $currentStream .= "BT\n/F2 9.5 Tf\n0.10 0.12 0.18 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cc6 - (strlen($totConsumoFmt) * 5.6), $y - 12.5);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($totConsumoFmt));

            $y -= ($totH + 12.0);
        }

        // Fecha a última página
        $fecharPagina();

        // Substituição do contador de páginas (Página X de Y) em todas as páginas
        $totalPaginas = count($pages);
        $finalStreams = [];
        foreach ($pages as $idx => $stream) {
            $pageNum = $idx + 1;
            $stream = str_replace(
                ['__PAG_NUM__', '__PAG_TOT__', '%PAGE_NUM%', '%PAGE_TOTAL%', '%%PAGE_NUM%%', '%%PAGE_TOTAL%%'],
                [(string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas],
                $stream
            );
            $finalStreams[] = $stream;
        }

        return self::montarPdfMultiPaginas($finalStreams);
    }

    /**
     * Gera Espelho HTML limpo e responsivo para Impressão do Extrato do Aluno com @media print
     * 
     * @param array $dados
     * @return string Código HTML completo
     */
    public static function gerarEspelhoExtratoConsumoAlunoHtml(array $dados): string
    {
        $aluno = $dados['aluno'] ?? [];
        $saldoAtual = (float)($dados['saldo_atual'] ?? 0.0);
        $dataInicio = $dados['data_inicio'] ?? date('Y-m-01');
        $dataFim = $dados['data_fim'] ?? date('Y-m-d');
        $dataIniFmt = date('d/m/Y', strtotime($dataInicio));
        $dataFimFmt = date('d/m/Y', strtotime($dataFim));
        $recargas = $dados['recargas'] ?? [];
        $consumo = $dados['consumo'] ?? [];
        $operador = $dados['operador'] ?? 'Atendente da Cantina';
        $emissao = date('d/m/Y H:i:s');
        $baseUrl = $dados['baseUrl'] ?? (defined('BASE_URL') ? BASE_URL : '');
        $logoDataUri = self::getLogoDataUri(true);

        $totalRecargas = 0.0;
        foreach ($recargas as $r) {
            $totalRecargas += (float)($r['valor'] ?? 0.0);
        }
        $totalConsumo = 0.0;
        foreach ($consumo as $c) {
            $totalConsumo += (float)($c['subtotal'] ?? 0.0);
        }

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Extrato de Consumo — <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno', ENT_QUOTES, 'UTF-8'); ?></title>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 12mm 10mm;
                }
                * {
                    box-sizing: border-box;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    background-color: #ffffff;
                    color: #0f172a;
                    margin: 0;
                    padding: 20px;
                    font-size: 11.5px;
                    line-height: 1.35;
                }
                .font-tabular {
                    font-variant-numeric: tabular-nums;
                    font-feature-settings: 'tnum' 1, 'zero' 1;
                }
                .actions-bar {
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    padding: 10px 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                    gap: 10px;
                }
                .btn {
                    padding: 7px 14px;
                    font-size: 12.5px;
                    font-weight: 600;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-family: inherit;
                    line-height: 1.2;
                }
                .btn-primary { background: #1d4ed8; color: #ffffff; border: 1px solid #1e40af; }
                .btn-primary:hover { background: #1e40af; }
                .btn-secondary { background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; }
                .btn-secondary:hover { background: #f1f5f9; }
                .header-box {
                    border-left: 4px solid #1d4ed8;
                    background: #f8fafc;
                    border-radius: 4px;
                    padding: 12px 16px;
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    margin-bottom: 16px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .header-logo { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; border: 1px solid #cbd5e1; }
                .student-box {
                    border: 1px solid #e2e8f0;
                    background: #ffffff;
                    border-radius: 6px;
                    padding: 12px 16px;
                    margin-bottom: 16px;
                    display: flex;
                    justify-content: space-between;
                    gap: 16px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .student-box > div {
                    flex: 1;
                    min-width: 0;
                }
                .stat-strip {
                    display: flex;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    background: #f8fafc;
                    margin-bottom: 20px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .stat-item {
                    flex: 1;
                    min-width: 0;
                    padding: 10px 14px;
                    border-right: 1px solid #e2e8f0;
                }
                .stat-item:last-child { border-right: none; }
                .stat-label { font-size: 10px; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
                .stat-val { font-size: 15px; font-weight: 700; color: #0f172a; }
                .table-relatorio {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 24px;
                    font-size: 11px;
                    table-layout: fixed;
                }
                .table-relatorio thead {
                    display: table-header-group;
                }
                .table-relatorio th {
                    background: #f1f5f9 !important;
                    border-top: 1px solid #cbd5e1;
                    border-bottom: 1px solid #cbd5e1;
                    color: #475569;
                    font-size: 10.5px;
                    font-weight: 600;
                    text-transform: uppercase;
                    padding: 8px 10px;
                    text-align: left;
                }
                .table-relatorio td {
                    padding: 6px 10px;
                    border-bottom: 1px solid #e2e8f0;
                    color: #0f172a;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                }
                .table-relatorio tr {
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }
                .table-relatorio tr:nth-child(even) { background-color: #f8fafc; }
                .text-end { text-align: right; }
                .sec-title {
                    font-size: 13px;
                    font-weight: 700;
                    color: #1d4ed8;
                    margin: 20px 0 8px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.02em;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .footer-doc {
                    border-top: 1px solid #e2e8f0;
                    padding-top: 10px;
                    font-size: 10px;
                    color: #64748b;
                    display: flex;
                    justify-content: space-between;
                    margin-top: 30px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                @media print {
                    @page {
                        size: A4 portrait;
                        margin: 12mm 10mm;
                    }
                    html, body {
                        width: 100% !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        background: #ffffff !important;
                        font-size: 10pt !important;
                    }
                    .actions-bar {
                        display: none !important;
                    }
                    .table-relatorio {
                        width: 100% !important;
                        table-layout: fixed !important;
                    }
                    .table-relatorio thead {
                        display: table-header-group !important;
                    }
                    .table-relatorio tr {
                        break-inside: avoid !important;
                        page-break-inside: avoid !important;
                    }
                    .table-relatorio th {
                        background-color: #f1f5f9 !important;
                    }
                    .table-relatorio tr:nth-child(even) td {
                        background-color: #f8fafc !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="actions-bar">
                <div>
                    <strong>Espelho de Impressão A4</strong> — Extrato Oficial do Aluno
                    <span style="font-size: 11px; color: #64748b; margin-left: 8px;">(Atalho: <kbd style="background:#e2e8f0; color:#334155; padding:1px 5px; border-radius:3px; font-size:11px;">Ctrl</kbd> + <kbd style="background:#e2e8f0; color:#334155; padding:1px 5px; border-radius:3px; font-size:11px;">P</kbd>)</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a href="<?php echo htmlspecialchars($baseUrl . '/operacional/extrato-aluno/pdf?aluno_id=' . (int)($aluno['id'] ?? 0) . '&data_inicio=' . urlencode($dataInicio) . '&data_fim=' . urlencode($dataFim), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary" title="Baixar Extrato em PDF A4 Timbrado Direto">
                        📄 Baixar PDF Direto
                    </a>
                    <button type="button" class="btn btn-primary" onclick="this.innerHTML='Preparando...'; const b=this; setTimeout(function(){ window.print(); b.innerHTML='🖨️ Imprimir Extrato'; }, 50);">
                        🖨️ Imprimir Extrato
                    </button>
                </div>
            </div>

            <div class="header-box">
                <?php if (!empty($logoDataUri)): ?>
                    <img src="<?php echo $logoDataUri; ?>" alt="Colégio Monteiro Lobato" class="header-logo">
                <?php endif; ?>
                <div>
                    <h1 style="font-size: 16px; margin: 0 0 2px 0; font-weight: 700;">COLÉGIO MONTEIRO LOBATO — CANTINA ESCOLAR</h1>
                    <div style="font-size: 12px; font-weight: 600; color: #1d4ed8;">EXTRATO ANALÍTICO DE CONSUMO DO ALUNO</div>
                    <div style="font-size: 10px; color: #64748b; margin-top: 2px;">
                        Emitido em: <?php echo $emissao; ?> • Atendente: <?php echo htmlspecialchars($operador); ?>
                    </div>
                </div>
            </div>

            <div class="student-box">
                <div>
                    <div style="font-size: 10px; color: #64748b; font-weight: 600; text-transform: uppercase;">Estudante Beneficiário</div>
                    <div style="font-size: 14px; font-weight: 700; margin: 2px 0;"><?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></div>
                    <div style="color: #475569; font-size: 11px;">
                        Matrícula: <strong><?php echo htmlspecialchars($aluno['matricula'] ?? '-'); ?></strong> • 
                        Turma: <?php echo htmlspecialchars(($aluno['serie'] ?? '') . ' ' . ($aluno['nome_turma'] ?? '')); ?> (<?php echo ucfirst($aluno['turno'] ?? '-'); ?>)
                    </div>
                </div>
                <div>
                    <div style="font-size: 10px; color: #64748b; font-weight: 600; text-transform: uppercase;">Responsável Financeiro (Carteira Familiar)</div>
                    <div style="font-size: 13px; font-weight: 600; margin: 2px 0;"><?php echo htmlspecialchars($aluno['responsavel_nome'] ?? '-'); ?></div>
                    <div style="color: #475569; font-size: 11px;">
                        WhatsApp: <?php echo htmlspecialchars($aluno['responsavel_whatsapp'] ?? '-'); ?> • 
                        CPF: <?php echo htmlspecialchars($aluno['responsavel_cpf'] ?? '-'); ?>
                    </div>
                </div>
            </div>

            <div class="stat-strip">
                <div class="stat-item">
                    <div class="stat-label">Saldo Atual Carteira</div>
                    <div class="stat-val font-tabular" style="color: <?php echo ($saldoAtual >= 0) ? '#16a34a' : '#dc2626'; ?>;">
                        R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Recargas no Período</div>
                    <div class="stat-val font-tabular" style="color: #16a34a;">
                        R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Consumo no Período</div>
                    <div class="stat-val font-tabular">
                        R$ <?php echo number_format($totalConsumo, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Período Consultado</div>
                    <div class="stat-val font-tabular" style="font-size: 12.5px; color: #1d4ed8; padding-top: 3px;">
                        <?php echo $dataIniFmt; ?> a <?php echo $dataFimFmt; ?>
                    </div>
                </div>
            </div>

            <div class="sec-title">1. Histórico de Recargas na Carteira Familiar (No Período)</div>
            <table class="table-relatorio">
                <thead>
                    <tr>
                        <th style="width: 130px;">Data / Hora</th>
                        <th>Forma de Pagamento</th>
                        <th>Atendente / Canal</th>
                        <th class="text-end" style="width: 120px;">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recargas)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #64748b; padding: 12px;">
                                Nenhuma recarga registrada na carteira durante este período.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recargas as $r): ?>
                            <tr>
                                <td class="font-tabular"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <?php echo ($r['tipo_transacao'] === 'recarga_pix') ? 'Recarga Pix Online' : 'Recarga em Dinheiro (Balcão)'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($r['operador_nome'] ?? 'Sistema Pix'); ?></td>
                                <td class="text-end font-tabular" style="font-weight: 600; color: #16a34a;">
                                    R$ <?php echo number_format((float)($r['valor'] ?? 0.0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="sec-title">2. Histórico de Consumo de Lanches do Aluno (No Período)</div>
            <table class="table-relatorio">
                <thead>
                    <tr>
                        <th style="width: 90px;">Data</th>
                        <th style="width: 140px;">Turno / Canal</th>
                        <th>Item / Produto</th>
                        <th class="text-end" style="width: 70px;">Qtd</th>
                        <th class="text-end" style="width: 90px;">Unitário</th>
                        <th class="text-end" style="width: 100px;">Subtotal (R$)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($consumo)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748b; padding: 12px;">
                                Nenhum consumo de lanches registrado para este aluno no período selecionado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consumo as $c): ?>
                            <tr>
                                <td class="font-tabular">
                                    <?php echo !empty($c['data_consumo']) ? date('d/m/Y', strtotime($c['data_consumo'])) : date('d/m/Y', strtotime($c['created_at'])); ?>
                                </td>
                                <td>
                                    <?php echo ($c['origem'] === 'agendamento') ? 'Agendamento (' . ucfirst($c['turno'] ?? '-') . ')' : 'Balcão (' . ucfirst($c['turno'] ?? '-') . ')'; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($c['produto_nome'] ?? '-'); ?></strong></td>
                                <td class="text-end font-tabular"><?php echo (int)($c['quantidade'] ?? 1); ?> un</td>
                                <td class="text-end font-tabular">R$ <?php echo number_format((float)($c['preco_unitario'] ?? 0.0), 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular" style="font-weight: 600;">
                                    R$ <?php echo number_format((float)($c['subtotal'] ?? 0.0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f1f5f9; font-weight: 700;">
                            <td colspan="5" class="text-end" style="color: #1d4ed8; text-transform: uppercase;">Total de Consumo no Período:</td>
                            <td class="text-end font-tabular" style="font-size: 13px; color: #0f172a;">
                                R$ <?php echo number_format($totalConsumo, 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer-doc">
                <div>Cantina Web — Colégio Monteiro Lobato • Documento oficial emitido eletronicamente para fins de prestação de contas.</div>
                <div>Página 1 de 1</div>
            </div>
        </body>
        </html>
        <?php
        return (string)ob_get_clean();
    }

    /**
     * Gera o Relatório de Vendas por Alimento e Curva ABC em PDF A4 Timbrado Oficial (FSD Tela 8.2 & Seção 22.2)
     * 
     * @param array $dados
     * @return string Conteúdo binário puro do PDF 1.4
     */
    public static function gerarRelatorioVendasAlimentoPdf(array $dados): string
    {
        $periodo = $dados['periodo'] ?? date('d/m/Y') . ' a ' . date('d/m/Y');
        $turnoStr = $dados['turno'] ?? 'Todos os Turnos';
        $categoriaStr = $dados['categoria'] ?? 'Todas as Categorias';
        $itens = $dados['itens'] ?? [];
        $totais = $dados['totais'] ?? [];
        $operador = $dados['operador'] ?? 'Administracao Geral';
        $emissao = date('d/m/Y H:i:s');

        $qtdTotal = (int)($totais['quantidade_total'] ?? 0);
        $fatTotal = (float)($totais['faturamento_total'] ?? 0.0);
        $ticketMedio = ($qtdTotal > 0) ? ($fatTotal / $qtdTotal) : 0.0;
        $topItem = $itens[0]['descricao'] ?? 'Nenhum item';

        $periodoLimpo = self::removerAcentos($periodo);
        $turnoLimpo = self::removerAcentos($turnoStr);
        $categoriaLimpo = self::removerAcentos($categoriaStr);
        $operadorLimpo = self::removerAcentos($operador);
        $topItemLimpo = self::removerAcentos($topItem);

        $left = 36.0;
        $right = self::PAGE_WIDTH - 36.0; // 559.28
        $width = $right - $left; // 523.28

        $pages = [];
        $currentStream = "";
        $y = 800.0;

        $iniciarPagina = function(bool $isFirstPage) use (&$currentStream, &$y, $left, $width, $periodoLimpo, $turnoLimpo, $categoriaLimpo, $operadorLimpo, $emissao, $fatTotal, $qtdTotal, $ticketMedio, $topItemLimpo) {
            $currentStream = "";
            $y = 800.0;

            if ($isFirstPage) {
                // Cabeçalho Principal A4
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, $width, 58.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, 4.0, 58.0);

                $currentStream .= self::desenharLogoCircular($left + 12.0, $y - 42.0, 42.0);

                $textLeft = $left + 62.0;
                $currentStream .= "BT\n/F2 13 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 2.0);
                $currentStream .= "(COLEGIO MONTEIRO LOBATO - CANTINA ESCOLAR) Tj\nET\n";

                $currentStream .= "BT\n/F2 10.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 17.0);
                $currentStream .= "(RELATORIO DE VENDAS POR ALIMENTO & CURVA ABC) Tj\nET\n";

                $currentStream .= "BT\n/F1 7.5 Tf\n0.28 0.33 0.41 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 31.0);
                $currentStream .= sprintf("(Emitido em: %s | Operador: %s) Tj\nET\n", $emissao, self::escapeText(substr($operadorLimpo, 0, 30)));

                $y -= 62.0;

                // Box de Filtros Aplicados
                $boxH1 = 26.0;
                $currentStream .= "0.98 0.99 1.00 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH1, $width, $boxH1);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH1, $width, $boxH1);

                $currentStream .= "BT\n/F2 7.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 16.5);
                $currentStream .= sprintf("(FILTROS:) Tj\nET\n");

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 54.0, $y - 16.5);
                $currentStream .= sprintf("(Periodo: %s  |  Turno: %s  |  Categoria: %s) Tj\nET\n", self::escapeText($periodoLimpo), self::escapeText($turnoLimpo), self::escapeText($categoriaLimpo));

                $y -= ($boxH1 + 10.0);

                // Box de Stat-Strip
                $boxH2 = 34.0;
                $currentStream .= "0.96 0.97 0.99 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH2, $width, $boxH2);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH2, $width, $boxH2);

                $colW = $width / 4.0;

                // Métrica 1: Faturamento Bruto
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
                $currentStream .= sprintf("(FATURAMENTO BRUTO TOTAL) Tj\nET\n");

                $fatStr = "R$ " . number_format($fatTotal, 2, ',', '.');
                $currentStream .= "BT\n/F2 11 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $fatStr);

                // Métrica 2: Peças Comercializadas
                $xM2 = $left + $colW + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + $colW, $y - $boxH2, $left + $colW, $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 12.0);
                $currentStream .= sprintf("(QUANTIDADE COMERCIALIZADA) Tj\nET\n");

                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 25.0);
                $currentStream .= sprintf("(%d itens) Tj\nET\n", $qtdTotal);

                // Métrica 3: Preço Médio por Peça
                $xM3 = $left + ($colW * 2) + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($colW * 2), $y - $boxH2, $left + ($colW * 2), $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 12.0);
                $currentStream .= sprintf("(PRECO MEDIO POR ITEM) Tj\nET\n");

                $tmStr = "R$ " . number_format($ticketMedio, 2, ',', '.');
                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $tmStr);

                // Métrica 4: Campeão de Vendas
                $xM4 = $left + ($colW * 3) + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($colW * 3), $y - $boxH2, $left + ($colW * 3), $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM4, $y - 12.0);
                $currentStream .= sprintf("(CAMPEAO DE VENDAS) Tj\nET\n");

                $currentStream .= "BT\n/F2 9.0 Tf\n0.08 0.55 0.24 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM4, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($topItemLimpo, 0, 18)));

                $y -= ($boxH2 + 14.0);
            } else {
                // Mini cabeçalho subsequente
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, $width, 38.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, 3.0, 38.0);

                $currentStream .= self::desenharLogoCircular($left + 8.0, $y - 28.0, 28.0);

                $textLeft = $left + 46.0;
                $currentStream .= "BT\n/F2 10 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 6.0);
                $currentStream .= "(COLEGIO MONTEIRO LOBATO - VENDAS POR ALIMENTO - CONTINUACAO) Tj\nET\n";

                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 20.0);
                $currentStream .= sprintf("(Periodo: %s  |  Turno: %s  |  Emissao: %s) Tj\nET\n", self::escapeText($periodoLimpo), self::escapeText($turnoLimpo), $emissao);

                $y -= 44.0;
            }
        };

        $fecharPagina = function() use (&$pages, &$currentStream, $left, $right) {
            $yRodape = 32.0;
            $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $yRodape + 10.0, $right, $yRodape + 10.0);

            $currentStream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left, $yRodape);
            $currentStream .= sprintf("(Cantina Web - Colegio Monteiro Lobato  |  Relatorio Gerencial de Vendas e Curva ABC) Tj\nET\n");

            $currentStream .= "BT\n/F2 7 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $right - 75.0, $yRodape);
            $currentStream .= "(Pagina __PAG_NUM__ de __PAG_TOT__) Tj\nET\n";

            $pages[] = $currentStream;
        };

        // Definição precisa das colunas dentro de 523.28 pt de largura (left=36.0, right=559.28)
        $cCodX   = 40.0;  // Código (max 13 chars, de 40 até ~102)
        $cDescX  = 108.0; // Alimento / Produto (max 26 chars, de 108 até ~232)
        $cCatX   = 246.0; // Categoria (max 16 chars, de 246 até ~320)
        $cQtdR   = 360.0; // Qtd (alinhado à direita em 360.0)
        $cMedR   = 415.0; // Preço Médio (alinhado à direita em 415.0)
        $cTotR   = 496.0; // Faturamento Total (alinhado à direita em 496.0 - 81pt após Preço Médio!)
        $cPartR  = 536.0; // Part. % (alinhado à direita em 536.0)
        $cAbcC   = 550.0; // Curva ABC (centralizado em 550.0)

        $imprimirCabecalhoTabela = function() use (&$currentStream, &$y, $left, $right, $width, $cCodX, $cDescX, $cCatX, $cQtdR, $cMedR, $cTotR, $cPartR, $cAbcC) {
            $thH = 17.0;
            $currentStream .= "0.92 0.94 0.97 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thH, $width, $thH);
            $currentStream .= "0.80 0.83 0.88 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thH, $width, $thH);

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cCodX, $y - 11.5);
            $currentStream .= "(CODIGO) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cDescX, $y - 11.5);
            $currentStream .= "(ALIMENTO / PRODUTO) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cCatX, $y - 11.5);
            $currentStream .= "(CATEGORIA) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cQtdR - 20.0, $y - 11.5);
            $currentStream .= "(QTD) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cMedR - 50.0, $y - 11.5);
            $currentStream .= "(PRECO MED.) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cTotR - 44.0, $y - 11.5);
            $currentStream .= "(TOTAL R$) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cPartR - 26.0, $y - 11.5);
            $currentStream .= "(PART.) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cAbcC - 8.0, $y - 11.5);
            $currentStream .= "(ABC) Tj\nET\n";

            $y -= $thH;
        };

        $iniciarPagina(true);
        $imprimirCabecalhoTabela();

        if (empty($itens)) {
            $rowH = 22.0;
            $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);
            $currentStream .= "BT\n/F1 8 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", ($left + $right) / 2.0 - 100.0, $y - 14.0);
            $currentStream .= sprintf("(Nenhuma venda registrada para os filtros selecionados.) Tj\nET\n");
            $y -= $rowH;
        } else {
            $rowH = 16.5;
            $idx = 0;
            foreach ($itens as $item) {
                if ($y < 70.0) {
                    $fecharPagina();
                    $iniciarPagina(false);
                    $imprimirCabecalhoTabela();
                }

                $idx++;
                if ($idx % 2 === 0) {
                    $currentStream .= "0.98 0.98 0.99 rg\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowH, $width, $rowH);
                }
                $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);

                $codRaw  = $item['codigo_interno'] ?? $item['codigo'] ?? '-';
                $codStr  = substr($codRaw, 0, 13);
                $descStr = substr(self::removerAcentos($item['descricao'] ?? '-'), 0, 26);
                $catStr  = substr(self::removerAcentos($item['categoria_nome'] ?? '-'), 0, 16);
                $qtdStr  = (int)($item['quantidade_total'] ?? 0) . " un";
                $medStr  = "R$ " . number_format((float)($item['preco_medio'] ?? 0.0), 2, ',', '.');
                $totStr  = "R$ " . number_format((float)($item['faturamento_total'] ?? 0.0), 2, ',', '.');
                $partVal = (float)($item['participacao_percentual'] ?? $item['percentual_participacao'] ?? 0.0);
                $partStr = number_format($partVal, 1, ',', '.') . "%";
                $abcStr  = $item['classe_abc'] ?? 'C';

                // 1. Código
                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cCodX, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($codStr));

                // 2. Alimento / Produto
                $currentStream .= "BT\n/F2 7.5 Tf\n0.08 0.10 0.15 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cDescX, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($descStr));

                // 3. Categoria
                $currentStream .= "BT\n/F1 7.5 Tf\n0.35 0.40 0.50 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cCatX, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($catStr));

                // 4. Quantidade
                $currentStream .= "BT\n/F1 7.5 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cQtdR - (strlen($qtdStr) * 4.8), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($qtdStr));

                // 5. Preço Médio
                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cMedR - (strlen($medStr) * 4.8), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($medStr));

                // 6. Faturamento Total
                $currentStream .= "BT\n/F2 8 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cTotR - (strlen($totStr) * 5.0), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($totStr));

                // 7. Participação %
                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cPartR - (strlen($partStr) * 4.8), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($partStr));

                // 8. Classe ABC
                $abcColor = ($abcStr === 'A') ? "0.08 0.55 0.24" : (($abcStr === 'B') ? "0.11 0.31 0.85" : "0.35 0.40 0.50");
                $currentStream .= "BT\n/F2 8 Tf\n{$abcColor} rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $cAbcC - 3.0, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($abcStr));

                $y -= $rowH;
            }

            // Linha Totalizadora
            $totH = 18.0;
            $currentStream .= "0.94 0.96 0.98 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $totH, $width, $totH);
            $currentStream .= "0.80 0.83 0.88 RG\n1 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $totH, $width, $totH);

            $currentStream .= "BT\n/F2 8 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
            $currentStream .= "(TOTAIS CONSOLIDADOS NO PERIODO:) Tj\nET\n";

            $tQtdStr = $qtdTotal . " un";
            $currentStream .= "BT\n/F2 8 Tf\n0.10 0.12 0.18 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cQtdR - (strlen($tQtdStr) * 4.8), $y - 12.0);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tQtdStr));

            $tMedStr = "R$ " . number_format($ticketMedio, 2, ',', '.');
            $currentStream .= "BT\n/F2 8 Tf\n0.10 0.12 0.18 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cMedR - (strlen($tMedStr) * 4.8), $y - 12.0);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tMedStr));

            $tTotStr = "R$ " . number_format($fatTotal, 2, ',', '.');
            $currentStream .= "BT\n/F2 9 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cTotR - (strlen($tTotStr) * 5.4), $y - 12.5);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tTotStr));

            $currentStream .= "BT\n/F2 8 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $cPartR - 28.0, $y - 12.0);
            $currentStream .= sprintf("(100,0%%) Tj\nET\n");

            $y -= ($totH + 12.0);
        }

        $fecharPagina();

        $totalPaginas = count($pages);
        $finalStreams = [];
        foreach ($pages as $idx => $stream) {
            $pageNum = $idx + 1;
            $stream = str_replace(
                ['__PAG_NUM__', '__PAG_TOT__', '%PAGE_NUM%', '%PAGE_TOTAL%', '%%PAGE_NUM%%', '%%PAGE_TOTAL%%'],
                [(string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas],
                $stream
            );
            $finalStreams[] = $stream;
        }

        return self::montarPdfMultiPaginas($finalStreams);
    }

    /**
     * Gera o Relatório de Desconto em Folha para o RH em PDF A4 Timbrado Oficial (FSD Tela 8.3 & Seção 22.4)
     * 
     * @param array $dados
     * @return string Conteúdo binário puro do PDF 1.4
     */
    public static function gerarRelatorioDescontoFolhaPdf(array $dados): string
    {
        $competencia = $dados['competencia'] ?? date('m/Y');
        $colaboradores = $dados['colaboradores'] ?? [];
        $totais = $dados['totais'] ?? [];
        $operador = $dados['operador'] ?? 'Administração Geral';
        $emissao = date('d/m/Y H:i:s');

        $totalColabs = (int)($totais['total_colaboradores'] ?? count($colaboradores));
        $totalLanches = (int)($totais['total_lanches'] ?? 0);
        $totalDesconto = (float)($totais['valor_total'] ?? 0.0);

        $left = 36.0;
        $right = self::PAGE_WIDTH - 36.0;
        $width = $right - $left;

        $pages = [];
        $currentStream = "";
        $y = 800.0;

        $iniciarPagina = function(bool $isFirstPage) use (&$currentStream, &$y, $left, $width, $competencia, $operador, $emissao, $totalColabs, $totalLanches, $totalDesconto) {
            $currentStream = "";
            $y = 800.0;

            if ($isFirstPage) {
                // Cabeçalho Principal A4 Timbrado
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, $width, 58.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, 4.0, 58.0);

                $currentStream .= self::desenharLogoCircular($left + 12.0, $y - 42.0, 42.0);

                $textLeft = $left + 62.0;
                $currentStream .= "BT\n/F2 13 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 2.0);
                $currentStream .= "(COLEGIO MONTEIRO LOBATO - CANTINA ESCOLAR) Tj\nET\n";

                $currentStream .= "BT\n/F2 10.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 17.0);
                $currentStream .= "(RELATORIO MENSAL DE DESCONTO EM FOLHA - RECURSOS HUMANOS) Tj\nET\n";

                $currentStream .= "BT\n/F1 7.5 Tf\n0.28 0.33 0.41 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 31.0);
                $competenciaLimpa = self::removerAcentos($competencia);
                $operadorLimpo = self::removerAcentos($operador);
                $currentStream .= sprintf("(Competencia: %s | Emitido em: %s | Responsavel: %s) Tj\nET\n", self::escapeText($competenciaLimpa), $emissao, self::escapeText(substr($operadorLimpo, 0, 30)));

                $y -= 62.0;

                // Stat-Strip Executivo
                $boxH = 34.0;
                $currentStream .= "0.96 0.97 0.99 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH, $width, $boxH);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH, $width, $boxH);

                $colW = $width / 3.0;

                // Métrica 1: Total Colaboradores
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
                $currentStream .= sprintf("(COLABORADORES COM CONSUMO) Tj\nET\n");

                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 25.0);
                $currentStream .= sprintf("(%d colaboradores) Tj\nET\n", $totalColabs);

                // Métrica 2: Lanches Consumidos
                $xM2 = $left + $colW + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + $colW, $y - $boxH, $left + $colW, $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 12.0);
                $currentStream .= sprintf("(TOTAL DE LANCHES NO MES) Tj\nET\n");

                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 25.0);
                $currentStream .= sprintf("(%d lanches consumidos) Tj\nET\n", $totalLanches);

                // Métrica 3: Montante Total a Descontar
                $xM3 = $left + ($colW * 2) + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($colW * 2), $y - $boxH, $left + ($colW * 2), $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 12.0);
                $currentStream .= sprintf("(TOTAL CONSOLIDADO A DESCONTAR) Tj\nET\n");

                $totStr = "R$ " . number_format($totalDesconto, 2, ',', '.');
                $currentStream .= "BT\n/F2 11 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 25.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $totStr);

                $y -= ($boxH + 16.0);
            } else {
                // Mini Cabeçalho Subsequente
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, $width, 38.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, 3.0, 38.0);

                $currentStream .= self::desenharLogoCircular($left + 8.0, $y - 28.0, 28.0);

                $textLeft = $left + 46.0;
                $currentStream .= "BT\n/F2 10 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 6.0);
                $currentStream .= "(COLEGIO MONTEIRO LOBATO - DESCONTO EM FOLHA RH - CONTINUACAO) Tj\nET\n";

                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 20.0);
                $competenciaLimpa = self::removerAcentos($competencia);
                $currentStream .= sprintf("(Competencia: %s  |  Emissao: %s) Tj\nET\n", self::escapeText($competenciaLimpa), $emissao);

                $y -= 44.0;
            }
        };

        $fecharPagina = function() use (&$pages, &$currentStream, $left, $right) {
            $yRodape = 32.0;
            $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $yRodape + 10.0, $right, $yRodape + 10.0);

            $currentStream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left, $yRodape);
            $currentStream .= sprintf("(Cantina Web - Colegio Monteiro Lobato  |  Documento oficial de prestacao de contas para Recursos Humanos) Tj\nET\n");

            $currentStream .= "BT\n/F2 7 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $right - 70.0, $yRodape);
            $currentStream .= sprintf("(Pagina __PAG_NUM__ de __PAG_TOT__) Tj\nET\n");

            $pages[] = $currentStream;
        };

        $imprimirCabecalhoTabela = function() use (&$currentStream, &$y, $left, $right, $width) {
            $thH = 17.0;
            $currentStream .= "0.92 0.94 0.97 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thH, $width, $thH);
            $currentStream .= "0.80 0.83 0.88 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thH, $width, $thH);

            $c1 = $left + 6.0;   // Matrícula (x = 42.0)
            $c2 = $left + 80.0;  // Nome (x = 116.0)
            $c3 = $left + 270.0; // Cargo (x = 306.0)
            $c4 = $left + 420.0; // Lanches (alinhado à direita, x = 456.0)
            $c5 = $right - 8.0;  // Valor (alinhado à direita, x = 551.28)

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c1, $y - 11.5);
            $currentStream .= "(MATRICULA) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c2, $y - 11.5);
            $currentStream .= "(NOME COMPLETO DO COLABORADOR) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c3, $y - 11.5);
            $currentStream .= "(CARGO / FUNCAO) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c4 - 38.0, $y - 11.5);
            $currentStream .= "(LANCHES) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c5 - 82.0, $y - 11.5);
            $currentStream .= "(VALOR A DESCONTAR) Tj\nET\n";

            $y -= $thH;
        };

        $iniciarPagina(true);
        $imprimirCabecalhoTabela();

        $c1 = $left + 6.0;
        $c2 = $left + 80.0;
        $c3 = $left + 270.0;
        $c4 = $left + 420.0; // right
        $c5 = $right - 8.0;  // right

        if (empty($colaboradores)) {
            $rowH = 22.0;
            $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);
            $currentStream .= "BT\n/F1 8 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", ($left + $right) / 2.0 - 110.0, $y - 14.0);
            $currentStream .= sprintf("(Nenhum consumo sob desconto em folha registrado nesta competencia.) Tj\nET\n");
            $y -= $rowH;
        } else {
            $rowH = 17.5;
            $idx = 0;
            foreach ($colaboradores as $colab) {
                // Se estiver chegando próximo do final e precisar de espaço para assinaturas (~120pt)
                if ($y < 130.0) {
                    $fecharPagina();
                    $iniciarPagina(false);
                    $imprimirCabecalhoTabela();
                }

                $idx++;
                if ($idx % 2 === 0) {
                    $currentStream .= "0.98 0.98 0.99 rg\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowH, $width, $rowH);
                }
                $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);

                $matStr   = $colab['matricula_funcional'] ?? '-';
                $nomeStr  = self::removerAcentos($colab['colaborador_nome'] ?? $colab['nome'] ?? '-');
                $cargoStr = self::removerAcentos($colab['cargo'] ?? '-');
                $lanches  = (int)($colab['total_lanches'] ?? 0) . " un";
                $valStr   = "R$ " . number_format((float)($colab['total_desconto'] ?? 0.0), 2, ',', '.');

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c1, $y - 12.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($matStr));

                $currentStream .= "BT\n/F2 8 Tf\n0.08 0.10 0.15 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c2, $y - 12.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($nomeStr, 0, 36)));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.35 0.40 0.50 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c3, $y - 12.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($cargoStr, 0, 24)));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c4 - (strlen($lanches) * 4.8), $y - 12.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($lanches));

                $currentStream .= "BT\n/F2 8.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c5 - (strlen($valStr) * 5.2), $y - 12.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($valStr));

                $y -= $rowH;
            }

            // Linha Totalizadora
            $totH = 19.0;
            $currentStream .= "0.94 0.96 0.98 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $totH, $width, $totH);
            $currentStream .= "0.80 0.83 0.88 RG\n1 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $totH, $width, $totH);

            $currentStream .= "BT\n/F2 8 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.5);
            $currentStream .= sprintf("(MONTANTE TOTAL CONSOLIDADO PARA DESCONTO EM FOLHA:) Tj\nET\n");

            $tLanchesStr = $totalLanches . " un";
            $currentStream .= "BT\n/F2 8.5 Tf\n0.10 0.12 0.18 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c4 - (strlen($tLanchesStr) * 5.2), $y - 12.5);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tLanchesStr));

            $tDescStr = "R$ " . number_format($totalDesconto, 2, ',', '.');
            $currentStream .= "BT\n/F2 9.5 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c5 - (strlen($tDescStr) * 5.8), $y - 13.0);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tDescStr));

            $y -= ($totH + 20.0);

            // Campos para Assinaturas e Carimbos
            $signBoxW = ($width - 30.0) / 2.0;

            // Assinatura 1: Diretoria / Cantina
            $xSign1 = $left;
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $xSign1 + 10.0, $y - 25.0, $xSign1 + $signBoxW - 10.0, $y - 25.0);
            $currentStream .= "BT\n/F2 7.5 Tf\n0.20 0.25 0.35 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign1 + 30.0, $y - 36.0);
            $currentStream .= "(Diretoria Administrativa / Financeira) Tj\nET\n";
            $currentStream .= "BT\n/F1 6.5 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign1 + 42.0, $y - 46.0);
            $currentStream .= "(Visto de Aprovacao da Cantina) Tj\nET\n";

            // Assinatura 2: Recursos Humanos
            $xSign2 = $left + $signBoxW + 30.0;
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $xSign2 + 10.0, $y - 25.0, $xSign2 + $signBoxW - 10.0, $y - 25.0);
            $currentStream .= "BT\n/F2 7.5 Tf\n0.20 0.25 0.35 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign2 + 35.0, $y - 36.0);
            $currentStream .= "(Departamento de Recursos Humanos) Tj\nET\n";
            $currentStream .= "BT\n/F1 6.5 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign2 + 40.0, $y - 46.0);
            $currentStream .= "(Recebido para Lancamento em Folha) Tj\nET\n";

            $y -= 60.0;
        }

        $fecharPagina();

        $totalPaginas = count($pages);
        $finalStreams = [];
        foreach ($pages as $idx => $stream) {
            $pageNum = $idx + 1;
            $stream = str_replace(
                ['__PAG_NUM__', '__PAG_TOT__', '%PAGE_NUM%', '%PAGE_TOTAL%', '%%PAGE_NUM%%', '%%PAGE_TOTAL%%'],
                [(string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas],
                $stream
            );
            $finalStreams[] = $stream;
        }

        return self::montarPdfMultiPaginas($finalStreams);
    }

    /**
     * Gera os streams de desenho de página para o Demonstrativo Individual de Desconto em Folha
     * de um colaborador específico na competência.
     *
     * @param array $dados
     * @return array<int, string> Streams brutos com marcadores de página __PAG_NUM__ e __PAG_TOT__
     */
    public static function obterStreamsPaginasIndividualColaborador(array $dados): array
    {
        $colaborador = $dados['colaborador'] ?? [];
        $competencia = $dados['competencia'] ?? date('m/Y');
        $operador = $dados['operador'] ?? 'Administração Geral';
        $emissao = $dados['emissao'] ?? date('d/m/Y H:i:s');
        $itens = $dados['itens'] ?? [];
        $totais = $dados['totais'] ?? [];

        $totalDesconto = (float)($totais['total_desconto'] ?? 0.0);
        $totalLanches = (int)($totais['total_lanches'] ?? 0);
        $totalCompras = (int)($totais['total_compras'] ?? 0);

        $nomeColab = self::removerAcentos($colaborador['nome'] ?? '-');
        $matColab  = $colaborador['matricula_funcional'] ?? '-';
        $cargoColab= self::removerAcentos($colaborador['cargo'] ?? '-');

        $left = 36.0;
        $right = self::PAGE_WIDTH - 36.0;
        $width = $right - $left;

        $pages = [];
        $currentStream = "";
        $y = 800.0;

        $iniciarPagina = function(bool $isFirstPage) use (&$currentStream, &$y, $left, $width, $colaborador, $nomeColab, $matColab, $cargoColab, $competencia, $operador, $emissao, $totalDesconto, $totalLanches, $totalCompras) {
            $currentStream = "";
            $y = 800.0;

            if ($isFirstPage) {
                // Cabeçalho Principal A4 Timbrado
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, $width, 58.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85); // #1d4ed8
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 48.0, 4.0, 58.0);

                $currentStream .= self::desenharLogoCircular($left + 12.0, $y - 42.0, 42.0);

                $textLeft = $left + 62.0;
                $currentStream .= "BT\n/F2 13 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 2.0);
                $currentStream .= "(COLEGIO MONTEIRO LOBATO - CANTINA ESCOLAR) Tj\nET\n";

                $currentStream .= "BT\n/F2 10.5 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 17.0);
                $currentStream .= "(DEMONSTRATIVO INDIVIDUAL DE DESCONTO EM FOLHA) Tj\nET\n";

                $currentStream .= "BT\n/F1 7.5 Tf\n0.28 0.33 0.41 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 31.0);
                $competenciaLimpa = self::removerAcentos($competencia);
                $operadorLimpo = self::removerAcentos($operador);
                $currentStream .= sprintf("(Competencia: %s | Emitido em: %s | Responsavel: %s) Tj\nET\n", 
                    self::escapeText($competenciaLimpa), 
                    $emissao, 
                    self::escapeText(substr($operadorLimpo, 0, 30))
                );

                $y -= 62.0;

                // Box 1: Identificação do Colaborador (altura 44 pt)
                $boxH = 44.0;
                $currentStream .= "0.98 0.99 1.00 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH, $width, $boxH);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH, $width, $boxH);

                $nomeColab = self::removerAcentos($colaborador['nome'] ?? '-');
                $matColab  = $colaborador['matricula_funcional'] ?? '-';
                $cargoColab= self::removerAcentos($colaborador['cargo'] ?? '-');

                // Coluna Esquerda: Colaborador
                $currentStream .= "BT\n/F2 7.5 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.0);
                $currentStream .= "(COLABORADOR / PROFESSOR:) Tj\nET\n";

                $currentStream .= "BT\n/F2 9.5 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 24.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($nomeColab, 0, 42)));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 35.0);
                $currentStream .= sprintf("(Matricula: %s  |  Cargo: %s) Tj\nET\n", self::escapeText($matColab), self::escapeText(substr($cargoColab, 0, 32)));

                // Coluna Direita: Competência
                $colDirX = $left + 300.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $colDirX - 10.0, $y - $boxH, $colDirX - 10.0, $y);

                $currentStream .= "BT\n/F2 7.5 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $colDirX, $y - 12.0);
                $currentStream .= "(COMPETENCIA & CONFERENCIA:) Tj\nET\n";

                $currentStream .= "BT\n/F2 9.0 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $colDirX, $y - 24.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($competenciaLimpa));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $colDirX, $y - 35.0);
                $currentStream .= sprintf("(Atendimentos: %d  |  Total de Lanches: %d un) Tj\nET\n", $totalCompras, $totalLanches);

                $y -= ($boxH + 12.0);

                // Box 2: Resumo Financeiro / Stat-Strip (altura 32 pt)
                $boxH2 = 32.0;
                $currentStream .= "0.96 0.97 0.99 rg\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $boxH2, $width, $boxH2);
                $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $boxH2, $width, $boxH2);

                $colW = $width / 3.0;

                // Métrica 1: Total a Descontar
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 11.0);
                $currentStream .= "(TOTAL A DESCONTAR EM FOLHA) Tj\nET\n";

                $totStr = "R$ " . number_format($totalDesconto, 2, ',', '.');
                $currentStream .= "BT\n/F2 11 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 24.0);
                $currentStream .= sprintf("(%s) Tj\nET\n", $totStr);

                // Métrica 2: Lanches
                $xM2 = $left + $colW + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + $colW, $y - $boxH2, $left + $colW, $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 11.0);
                $currentStream .= "(TOTAL DE LANCHES / ITENS) Tj\nET\n";

                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM2, $y - 24.0);
                $currentStream .= sprintf("(%d un) Tj\nET\n", $totalLanches);

                // Métrica 3: Atendimentos
                $xM3 = $left + ($colW * 2) + 10.0;
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left + ($colW * 2), $y - $boxH2, $left + ($colW * 2), $y);
                $currentStream .= "BT\n/F1 7 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 11.0);
                $currentStream .= "(QUANTIDADE DE ATENDIMENTOS) Tj\nET\n";

                $currentStream .= "BT\n/F2 11 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $xM3, $y - 24.0);
                $currentStream .= sprintf("(%d compras) Tj\nET\n", $totalCompras);

                $y -= ($boxH2 + 14.0);
            } else {
                // Mini Cabeçalho Subsequente (Página 2+)
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.95, 0.96, 0.98);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, $width, 38.0);
                $currentStream .= sprintf("%.2f %.2f %.2f rg\n", 0.11, 0.31, 0.85);
                $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - 32.0, 3.0, 38.0);

                $currentStream .= self::desenharLogoCircular($left + 8.0, $y - 28.0, 28.0);

                $textLeft = $left + 46.0;
                $currentStream .= "BT\n/F2 10 Tf\n0.06 0.09 0.16 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 6.0);
                $nomeColab = self::removerAcentos($colaborador['nome'] ?? '-');
                $matColab  = $colaborador['matricula_funcional'] ?? '-';
                $currentStream .= sprintf("(COLEGIO MONTEIRO LOBATO - DESCONTO EM FOLHA: %s [%s] - CONTINUACAO) Tj\nET\n", self::escapeText(substr($nomeColab, 0, 30)), self::escapeText($matColab));

                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $textLeft, $y - 20.0);
                $competenciaLimpa = self::removerAcentos($competencia);
                $currentStream .= sprintf("(Competencia: %s  |  Emissao: %s) Tj\nET\n", self::escapeText($competenciaLimpa), $emissao);

                $y -= 44.0;
            }
        };

        $fecharPagina = function() use (&$pages, &$currentStream, $left, $right) {
            $yRodape = 32.0;
            $currentStream .= "0.85 0.88 0.92 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $yRodape + 10.0, $right, $yRodape + 10.0);

            $currentStream .= "BT\n/F1 7 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left, $yRodape);
            $currentStream .= sprintf("(Cantina Web - Colegio Monteiro Lobato  |  Demonstrativo Individual de Desconto em Folha para RH e Colaborador) Tj\nET\n");

            $currentStream .= "BT\n/F2 7 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $right - 70.0, $yRodape);
            $currentStream .= sprintf("(Pagina __PAG_NUM__ de __PAG_TOT__) Tj\nET\n");

            $pages[] = $currentStream;
        };

        $c1 = $left + 6.0;   // Data/Hora (x = 42.0)
        $c2 = $left + 88.0;  // Turno (x = 124.0)
        $c3 = $left + 148.0; // Venda # (x = 184.0)
        $c4 = $left + 196.0; // Produto (x = 232.0)
        $c5 = $left + 400.0; // Qtd (right x = 436.0)
        $c6 = $left + 465.0; // Unit (right x = 501.0)
        $c7 = $right - 8.0;  // Subtotal (right x = 551.28)

        $imprimirCabecalhoTabela = function() use (&$currentStream, &$y, $left, $width, $c1, $c2, $c3, $c4, $c5, $c6, $c7) {
            $thH = 17.0;
            $currentStream .= "0.92 0.94 0.97 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $thH, $width, $thH);
            $currentStream .= "0.80 0.83 0.88 RG\n0.75 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $thH, $width, $thH);

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c1, $y - 11.5);
            $currentStream .= "(DATA / HORA) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c2, $y - 11.5);
            $currentStream .= "(TURNO) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c3, $y - 11.5);
            $currentStream .= "(VENDA) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c4, $y - 11.5);
            $currentStream .= "(DISCRIMINACAO DO ITEM / LANCHE) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c5 - 20.0, $y - 11.5);
            $currentStream .= "(QTD) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c6 - 42.0, $y - 11.5);
            $currentStream .= "(UNITARIO) Tj\nET\n";

            $currentStream .= "BT\n/F2 7.5 Tf\n0.25 0.30 0.40 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c7 - 45.0, $y - 11.5);
            $currentStream .= "(SUBTOTAL) Tj\nET\n";

            $y -= $thH;
        };

        $iniciarPagina(true);
        $imprimirCabecalhoTabela();

        if (empty($itens)) {
            $rowH = 22.0;
            $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);
            $currentStream .= "BT\n/F1 8 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", ($left + $right) / 2.0 - 120.0, $y - 14.0);
            $currentStream .= sprintf("(Nenhum consumo sob desconto em folha registrado nesta competencia.) Tj\nET\n");
            $y -= $rowH;
        } else {
            $rowH = 16.5;
            $idx = 0;
            foreach ($itens as $item) {
                // Checa espaço (deixa ~130pt para totalizador + assinaturas)
                if ($y < 140.0) {
                    $fecharPagina();
                    $iniciarPagina(false);
                    $imprimirCabecalhoTabela();
                }

                $idx++;
                if ($idx % 2 === 0) {
                    $currentStream .= "0.98 0.98 0.99 rg\n";
                    $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $rowH, $width, $rowH);
                }
                $currentStream .= "0.89 0.91 0.94 RG\n0.5 w\n";
                $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $left, $y - $rowH, $right, $y - $rowH);

                $dataHoraStr = date('d/m H:i', strtotime($item['created_at']));
                $turnoStr    = ucfirst(substr($item['turno'] ?? '-', 0, 3)) . '.'; // Mat. ou Vesp.
                $vendaStr    = '#' . ($item['venda_id'] ?? '-');
                $prodStr     = self::removerAcentos($item['produto_nome'] ?? '-');
                $qtdStr      = (int)($item['quantidade'] ?? 1) . ' un';
                $unitStr     = 'R$ ' . number_format((float)($item['preco_unitario'] ?? 0.0), 2, ',', '.');
                $subStr      = 'R$ ' . number_format((float)($item['subtotal'] ?? 0.0), 2, ',', '.');

                // Data/Hora
                $currentStream .= "BT\n/F1 7.5 Tf\n0.25 0.30 0.40 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c1, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($dataHoraStr));

                // Turno
                $currentStream .= "BT\n/F1 7.5 Tf\n0.35 0.40 0.50 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c2, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($turnoStr));

                // Venda
                $currentStream .= "BT\n/F1 7.5 Tf\n0.40 0.45 0.55 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c3, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($vendaStr));

                // Produto
                $currentStream .= "BT\n/F2 7.5 Tf\n0.08 0.10 0.15 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c4, $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText(substr($prodStr, 0, 38)));

                // Qtd
                $currentStream .= "BT\n/F1 7.5 Tf\n0.10 0.12 0.18 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c5 - (strlen($qtdStr) * 4.6), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($qtdStr));

                // Unitário
                $currentStream .= "BT\n/F1 7.5 Tf\n0.30 0.35 0.45 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c6 - (strlen($unitStr) * 4.8), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($unitStr));

                // Subtotal
                $currentStream .= "BT\n/F2 8.0 Tf\n0.11 0.31 0.85 rg\n";
                $currentStream .= sprintf("%.2f %.2f Td\n", $c7 - (strlen($subStr) * 5.0), $y - 11.5);
                $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($subStr));

                $y -= $rowH;
            }

            // Linha Totalizadora
            $totH = 19.0;
            $currentStream .= "0.94 0.96 0.98 rg\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $left, $y - $totH, $width, $totH);
            $currentStream .= "0.80 0.83 0.88 RG\n1 w\n";
            $currentStream .= sprintf("%.2f %.2f %.2f %.2f re S\n", $left, $y - $totH, $width, $totH);

            $currentStream .= "BT\n/F2 8 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 10.0, $y - 12.5);
            $currentStream .= sprintf("(MONTANTE TOTAL A DESCONTAR EM FOLHA DESTE COLABORADOR:) Tj\nET\n");

            $tLanchesStr = $totalLanches . " un";
            $currentStream .= "BT\n/F2 8.5 Tf\n0.10 0.12 0.18 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c5 - (strlen($tLanchesStr) * 5.0), $y - 12.5);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tLanchesStr));

            $tDescStr = "R$ " . number_format($totalDesconto, 2, ',', '.');
            $currentStream .= "BT\n/F2 9.5 Tf\n0.11 0.31 0.85 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $c7 - (strlen($tDescStr) * 5.8), $y - 13.0);
            $currentStream .= sprintf("(%s) Tj\nET\n", self::escapeText($tDescStr));

            $y -= ($totH + 16.0);

            // Verifica se há espaço para termo de ciência e assinaturas (~95pt)
            if ($y < 115.0) {
                $fecharPagina();
                $iniciarPagina(false);
            }

            // Termo de Ciência
            $currentStream .= "BT\n/F1 6.5 Tf\n0.35 0.40 0.50 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $left + 2.0, $y);
            $compAviso = self::removerAcentos($competencia);
            $currentStream .= sprintf("(Declaro estar ciente e de acordo com o consumo discriminado acima, autorizando o desconto em folha referente a competencia %s.) Tj\nET\n", self::escapeText($compAviso));

            $y -= 8.0;

            // Campos para Assinaturas e Carimbos
            $signBoxW = ($width - 30.0) / 2.0;

            // Assinatura 1: Colaborador / Professor
            $xSign1 = $left;
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $xSign1 + 10.0, $y - 25.0, $xSign1 + $signBoxW - 10.0, $y - 25.0);
            $currentStream .= "BT\n/F2 7.5 Tf\n0.20 0.25 0.35 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign1 + 25.0, $y - 36.0);
            $currentStream .= "(Assinatura do Colaborador / Professor) Tj\nET\n";
            $currentStream .= "BT\n/F1 6.5 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign1 + 20.0, $y - 46.0);
            $currentStream .= sprintf("(Matricula: %s  |  Data: _____ / _____ / 2026) Tj\nET\n", self::escapeText($matColab));

            // Assinatura 2: Visto da Cantina
            $xSign2 = $left + $signBoxW + 30.0;
            $currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $xSign2 + 10.0, $y - 25.0, $xSign2 + $signBoxW - 10.0, $y - 25.0);
            $currentStream .= "BT\n/F2 7.5 Tf\n0.20 0.25 0.35 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign2 + 25.0, $y - 36.0);
            $currentStream .= "(Visto da Cantina / Diretoria Administrativa) Tj\nET\n";
            $currentStream .= "BT\n/F1 6.5 Tf\n0.45 0.50 0.60 rg\n";
            $currentStream .= sprintf("%.2f %.2f Td\n", $xSign2 + 35.0, $y - 46.0);
            $currentStream .= "(Colegio Monteiro Lobato  |  Data: _____ / _____ / 2026) Tj\nET\n";

            $y -= 60.0;
        }

        $fecharPagina();

        return $pages;
    }

    /**
     * Gera o arquivo PDF A4 Timbrado oficial do Demonstrativo Individual de Desconto em Folha do Colaborador
     *
     * @param array $dados
     * @return string Conteúdo binário puro do PDF 1.4
     */
    public static function gerarRelatorioDescontoFolhaIndividualPdf(array $dados): string
    {
        $pages = self::obterStreamsPaginasIndividualColaborador($dados);
        $totalPaginas = count($pages);
        $finalStreams = [];
        foreach ($pages as $idx => $stream) {
            $pageNum = $idx + 1;
            $stream = str_replace(
                ['__PAG_NUM__', '__PAG_TOT__', '%PAGE_NUM%', '%PAGE_TOTAL%', '%%PAGE_NUM%%', '%%PAGE_TOTAL%%'],
                [(string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas, (string)$pageNum, (string)$totalPaginas],
                $stream
            );
            $finalStreams[] = $stream;
        }

        return self::montarPdfMultiPaginas($finalStreams);
    }

    /**
     * Gera lote consolidado com os demonstrativos individuais de todos os colaboradores
     * que tiveram consumo na competência, com folha individual para cada um.
     *
     * @param array $dadosLote
     * @return string Conteúdo binário puro do PDF 1.4
     */
    public static function gerarRelatorioDescontoFolhaIndividualLotePdf(array $dadosLote): string
    {
        $listaColabs = $dadosLote['colaboradores_detalhados'] ?? [];
        if (empty($listaColabs)) {
            return self::gerarRelatorioDescontoFolhaPdf([
                'competencia'   => $dadosLote['competencia'] ?? date('m/Y'),
                'colaboradores' => [],
                'totais'        => ['valor_total' => 0.0, 'total_lanches' => 0, 'total_colaboradores' => 0]
            ]);
        }

        $todasPaginas = [];
        foreach ($listaColabs as $dadosColab) {
            $paginasColab = self::obterStreamsPaginasIndividualColaborador($dadosColab);
            $totalPaginasColab = count($paginasColab);
            foreach ($paginasColab as $idx => $stream) {
                $pageNum = $idx + 1;
                $stream = str_replace(
                    ['__PAG_NUM__', '__PAG_TOT__', '%PAGE_NUM%', '%PAGE_TOTAL%', '%%PAGE_NUM%%', '%%PAGE_TOTAL%%'],
                    [(string)$pageNum, (string)$totalPaginasColab, (string)$pageNum, (string)$totalPaginasColab, (string)$pageNum, (string)$totalPaginasColab],
                    $stream
                );
                $todasPaginas[] = $stream;
            }
        }

        return self::montarPdfMultiPaginas($todasPaginas);
    }

    /**
     * Gera espelho HTML limpo e responsivo para visualização e impressão direta
     * do Demonstrativo Individual de Desconto em Folha do Colaborador.
     *
     * @param array $dados
     * @return string Conteúdo HTML completo
     */
    public static function gerarEspelhoDescontoFolhaIndividualHtml(array $dados): string
    {
        $colaborador = $dados['colaborador'] ?? [];
        $competencia = $dados['competencia'] ?? date('m/Y');
        $competenciaChave = $dados['competencia_chave'] ?? '';
        $operador = $dados['operador'] ?? 'Administração Geral';
        $emissao = $dados['emissao'] ?? date('d/m/Y H:i:s');
        $baseUrl = $dados['baseUrl'] ?? (defined('BASE_URL') ? BASE_URL : '');
        $itens = $dados['itens'] ?? [];
        $totais = $dados['totais'] ?? [];
        $logoDataUri = self::getLogoDataUri(true);

        $totalDesconto = (float)($totais['total_desconto'] ?? 0.0);
        $totalLanches = (int)($totais['total_lanches'] ?? 0);
        $totalCompras = (int)($totais['total_compras'] ?? 0);
        $colabId = (int)($colaborador['id'] ?? 0);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Demonstrativo de Folha — <?php echo htmlspecialchars($colaborador['nome'] ?? 'Colaborador', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($competencia, ENT_QUOTES, 'UTF-8'); ?>)</title>
            <style>
                @page {
                    size: A4 portrait;
                    margin: 12mm 10mm;
                }
                * {
                    box-sizing: border-box;
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    background-color: #ffffff;
                    color: #0f172a;
                    margin: 0;
                    padding: 20px;
                    font-size: 11.5px;
                    line-height: 1.35;
                }
                .font-tabular {
                    font-variant-numeric: tabular-nums;
                    font-feature-settings: 'tnum' 1, 'zero' 1;
                }
                .actions-bar {
                    background: #f8fafc;
                    border: 1px solid #cbd5e1;
                    border-radius: 6px;
                    padding: 10px 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                    flex-wrap: wrap;
                    gap: 10px;
                }
                .btn {
                    padding: 7px 14px;
                    font-size: 12.5px;
                    font-weight: 600;
                    border-radius: 4px;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-family: inherit;
                    line-height: 1.2;
                }
                .btn-primary { background: #1d4ed8; color: #ffffff; border: 1px solid #1e40af; }
                .btn-primary:hover { background: #1e40af; }
                .btn-secondary { background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; }
                .btn-secondary:hover { background: #f1f5f9; }
                .header-box {
                    border-left: 4px solid #1d4ed8;
                    background: #f8fafc;
                    border-radius: 4px;
                    padding: 12px 16px;
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    margin-bottom: 16px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .header-logo { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; border: 1px solid #cbd5e1; }
                .profile-box {
                    border: 1px solid #e2e8f0;
                    background: #ffffff;
                    border-radius: 6px;
                    padding: 12px 16px;
                    margin-bottom: 16px;
                    display: flex;
                    justify-content: space-between;
                    gap: 16px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .profile-box > div {
                    flex: 1;
                    min-width: 0;
                }
                .stat-strip {
                    display: flex;
                    border: 1px solid #e2e8f0;
                    border-radius: 6px;
                    background: #f8fafc;
                    margin-bottom: 20px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .stat-item {
                    flex: 1;
                    min-width: 0;
                    padding: 10px 14px;
                    border-right: 1px solid #e2e8f0;
                }
                .stat-item:last-child { border-right: none; }
                .stat-label {
                    font-size: 10px;
                    text-transform: uppercase;
                    color: #64748b;
                    font-weight: 600;
                    letter-spacing: 0.03em;
                    display: block;
                    margin-bottom: 3px;
                }
                .stat-value {
                    font-size: 16px;
                    font-weight: 700;
                    color: #0f172a;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th {
                    background: #f1f5f9;
                    color: #475569;
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    padding: 8px 10px;
                    border: 1px solid #cbd5e1;
                    text-align: left;
                }
                td {
                    padding: 7px 10px;
                    border: 1px solid #e2e8f0;
                    font-size: 11px;
                }
                tr:nth-child(even) td { background-color: #f8fafc; }
                .text-end { text-align: right; }
                .text-center { text-align: center; }
                .signatures {
                    margin-top: 30px;
                    display: flex;
                    justify-content: space-between;
                    gap: 30px;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .signature-box {
                    flex: 1;
                    text-align: center;
                    border-top: 1px solid #0f172a;
                    padding-top: 6px;
                    margin-top: 40px;
                }
                .footer-box {
                    margin-top: 25px;
                    padding-top: 10px;
                    border-top: 1px solid #e2e8f0;
                    display: flex;
                    justify-content: space-between;
                    font-size: 9.5px;
                    color: #64748b;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                @media print {
                    .actions-bar { display: none !important; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="actions-bar">
                <div class="d-flex align-items-center gap-2">
                    <span style="font-weight: 600; color: #1e293b;">Demonstrativo Individual para RH</span>
                    <span style="color: #64748b; font-size: 11px;">• Competência <?php echo htmlspecialchars($competencia, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                        Imprimir Demonstrativo
                    </button>
                    <a href="<?php echo $baseUrl; ?>/relatorios/desconto-folha/individual-pdf?colaborador_id=<?php echo $colabId; ?>&competencia=<?php echo urlencode($competenciaChave); ?>" class="btn btn-secondary">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
                        Baixar PDF A4 Timbrado
                    </a>
                    <a href="<?php echo $baseUrl; ?>/relatorios/desconto-folha?competencia=<?php echo urlencode($competenciaChave); ?>" class="btn btn-secondary">
                        ← Voltar ao Relatório de Folha
                    </a>
                </div>
            </div>

            <!-- Cabeçalho Oficial -->
            <div class="header-box">
                <?php if (!empty($logoDataUri)): ?>
                    <img src="<?php echo $logoDataUri; ?>" alt="Logo Colégio Monteiro Lobato" class="header-logo">
                <?php endif; ?>
                <div>
                    <h1 style="font-size: 15px; font-weight: 700; margin: 0; color: #0f172a;">COLÉGIO MONTEIRO LOBATO — CANTINA ESCOLAR</h1>
                    <h2 style="font-size: 13px; font-weight: 600; margin: 2px 0 0 0; color: #1d4ed8;">DEMONSTRATIVO INDIVIDUAL DE DESCONTO EM FOLHA</h2>
                    <div style="font-size: 10px; color: #475569; margin-top: 3px;">
                        Competência: <strong class="text-dark font-tabular"><?php echo htmlspecialchars($competencia, ENT_QUOTES, 'UTF-8'); ?></strong> • 
                        Emitido em: <span class="font-tabular"><?php echo htmlspecialchars($emissao, ENT_QUOTES, 'UTF-8'); ?></span> • 
                        Responsável: <?php echo htmlspecialchars($operador, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            </div>

            <!-- Identificação do Colaborador -->
            <div class="profile-box">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <?php if (!empty($colaborador['foto_url'])): ?>
                        <img src="<?php echo htmlspecialchars($colaborador['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1; flex-shrink: 0;">
                    <?php else: ?>
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: #f1f5f9; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; font-size: 16px; flex-shrink: 0;">
                            <?php echo htmlspecialchars(strtoupper(substr($colaborador['nome'] ?? 'C', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div style="font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600;">Colaborador / Professor:</div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($colaborador['nome'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div style="font-size: 11px; color: #475569;">
                            Matrícula Funcional: <strong class="font-tabular text-dark"><?php echo htmlspecialchars($colaborador['matricula_funcional'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong> • 
                            Cargo / Função: <strong><?php echo htmlspecialchars($colaborador['cargo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    </div>
                </div>
                <div style="text-align: right; border-left: 1px solid #e2e8f0; padding-left: 16px; max-width: 200px;">
                    <div style="font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600;">Competência Ativa:</div>
                    <div style="font-size: 13px; font-weight: 700; color: #1d4ed8;" class="font-tabular"><?php echo htmlspecialchars($competencia, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size: 10.5px; color: #64748b;" class="font-tabular">
                        <?php echo $totalCompras; ?> atendimento(s) no balcão
                    </div>
                </div>
            </div>

            <!-- Resumo Financeiro / Stat-Strip -->
            <div class="stat-strip">
                <div class="stat-item">
                    <span class="stat-label">Total a Descontar em Folha</span>
                    <span class="stat-value font-tabular" style="color: #1d4ed8;">
                        R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total de Lanches / Itens</span>
                    <span class="stat-value font-tabular">
                        <?php echo $totalLanches; ?> <small style="font-size: 12px; font-weight: normal; color: #64748b;">unidades</small>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Atendimentos no Balcão</span>
                    <span class="stat-value font-tabular">
                        <?php echo $totalCompras; ?> <small style="font-size: 12px; font-weight: normal; color: #64748b;">compras</small>
                    </span>
                </div>
            </div>

            <!-- Tabela de Itens Consumidos -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">Data / Hora</th>
                        <th style="width: 90px;">Turno</th>
                        <th style="width: 80px;">Venda #</th>
                        <th>Discriminação do Item / Lanche</th>
                        <th class="text-end" style="width: 80px;">Qtd</th>
                        <th class="text-end" style="width: 110px;">Unitário</th>
                        <th class="text-end" style="width: 120px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itens)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4" style="color: #64748b;">
                                Nenhum consumo registrado sob desconto em folha nesta competência.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($itens as $i): ?>
                            <tr>
                                <td class="font-tabular font-weight-bold"><?php echo date('d/m/Y H:i', strtotime($i['created_at'])); ?></td>
                                <td><?php echo ucfirst($i['turno'] ?? '-'); ?></td>
                                <td class="font-tabular text-secondary">#<?php echo htmlspecialchars((string)($i['venda_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <strong style="color: #0f172a;"><?php echo htmlspecialchars($i['produto_nome'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if (!empty($i['produto_codigo'])): ?>
                                        <small style="color: #64748b; font-family: monospace;">(<?php echo htmlspecialchars($i['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-tabular"><?php echo (int)($i['quantidade'] ?? 1); ?> un</td>
                                <td class="text-end font-tabular text-secondary">R$ <?php echo number_format((float)($i['preco_unitario'] ?? 0.0), 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular font-weight-bold" style="color: #1d4ed8;">R$ <?php echo number_format((float)($i['subtotal'] ?? 0.0), 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 700;">
                        <td colspan="4" class="text-end" style="text-transform: uppercase;">Montante Total a Descontar em Folha:</td>
                        <td class="text-end font-tabular"><?php echo $totalLanches; ?> un</td>
                        <td></td>
                        <td class="text-end font-tabular" style="font-size: 13px; color: #1d4ed8;">R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>

            <!-- Termo de Ciência e Assinaturas -->
            <div style="font-size: 10px; color: #475569; margin-top: 15px; margin-bottom: 25px; line-height: 1.4; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 12px;">
                <strong>Termo de Ciência do Colaborador:</strong> Declaro estar ciente e de acordo com os lançamentos de consumo acima discriminados, autorizando o respectivo desconto no holerite/folha de pagamento referente à competência <strong><?php echo htmlspecialchars($competencia, ENT_QUOTES, 'UTF-8'); ?></strong>.
            </div>

            <div class="signatures">
                <div class="signature-box">
                    <div style="font-weight: 700; font-size: 11px;"><?php echo htmlspecialchars($colaborador['nome'] ?? 'Colaborador', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div style="font-size: 10px; color: #475569;">Matrícula: <?php echo htmlspecialchars($colaborador['matricula_funcional'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> • Assinatura do Colaborador</div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 4px;">Data: _____ / _____ / <?php echo date('Y'); ?></div>
                </div>
                <div class="signature-box">
                    <div style="font-weight: 700; font-size: 11px;">Colégio Monteiro Lobato — Cantina Escolar</div>
                    <div style="font-size: 10px; color: #475569;">Visto da Cantina / Diretoria Administrativa</div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 4px;">Data: _____ / _____ / <?php echo date('Y'); ?></div>
                </div>
            </div>

            <!-- Rodapé Institucional -->
            <div class="footer-box">
                <div>Cantina Web — Colégio Monteiro Lobato | Documento oficial de conferência para Recursos Humanos</div>
                <div>Emissão: <?php echo htmlspecialchars($emissao, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}

