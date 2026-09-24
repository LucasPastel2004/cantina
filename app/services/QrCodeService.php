<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Gerador Autônomo de QR Code (SVG) em PHP Puro
 * 
 * Implementação completa de QR Code Modelo 2 com correção de erros Reed-Solomon,
 * gerando SVG vetorial nítido e compatível com todos os leitores de Pix do Brasil.
 * Não requer extensões gráficas adicionais nem APIs de terceiros.
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class QrCodeService
{
    // Níveis de correção de erro
    public const ERROR_CORRECT_L = 1; // ~7%
    public const ERROR_CORRECT_M = 0; // ~15%
    public const ERROR_CORRECT_Q = 3; // ~25%
    public const ERROR_CORRECT_H = 2; // ~30%

    /**
     * Gera o código SVG de um QR Code a partir de um texto
     * 
     * @param string $data Dados a serem codificados (ex: payload Pix Copia e Cola)
     * @param int $size Tamanho em pixels da imagem SVG resultante
     * @param int $margin Margem de módulos em volta do QR Code
     * @param string $colorCor Cor dos módulos (padrão #0f172a - Slate 900)
     * @param string $bgColor Cor de fundo (padrão #ffffff)
     * @return string Código XML/SVG completo
     */
    public static function svg(
        string $data,
        int $size = 260,
        int $margin = 2,
        string $colorCor = '#0f172a',
        string $bgColor = '#ffffff'
    ): string {
        $matrix = self::encodeToMatrix($data, self::ERROR_CORRECT_M);
        $moduleCount = count($matrix);
        $totalModules = $moduleCount + ($margin * 2);
        $scale = $size / $totalModules;

        $pathData = '';
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c]) {
                    $x = ($c + $margin) * $scale;
                    $y = ($r + $margin) * $scale;
                    $pathData .= sprintf("M%.2f,%.2fh%.2fv%.2fh-%.2fz ", $x, $y, $scale, $scale, $scale);
                }
            }
        }

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" class="pix-qrcode-svg" style="max-width: 100%%; height: auto; display: block;">' . "\n",
            $size,
            $size,
            $size,
            $size
        );
        $svg .= sprintf('<rect width="100%%" height="100%%" fill="%s"/>' . "\n", htmlspecialchars($bgColor, ENT_QUOTES));
        $svg .= sprintf('<path d="%s" fill="%s"/>' . "\n", trim($pathData), htmlspecialchars($colorCor, ENT_QUOTES));
        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Retorna o SVG codificado como Data URI (image/svg+xml;base64,...)
     */
    public static function dataUri(string $data, int $size = 260): string
    {
        $svg = self::svg($data, $size);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Codifica os dados em uma matriz booleana 2D [linha][coluna]
     */
    public static function encodeToMatrix(string $data, int $errorCorrection = self::ERROR_CORRECT_M): array
    {
        $qr = new self($data, $errorCorrection);
        return $qr->getMatrix();
    }

    // --- Implementação Interna do Motor QR Code ---

    private string $data;
    private int $ecLevel;
    private int $version = 1;
    private ?array $matrix = null;
    private int $moduleCount = 21;

    private function __construct(string $data, int $ecLevel)
    {
        $this->data = $data;
        $this->ecLevel = $ecLevel;
        $this->build();
    }

    private function getMatrix(): array
    {
        return $this->matrix;
    }

    private function build(): void
    {
        // Determina a versão necessária para o tamanho dos dados
        $dataLen = strlen($this->data);
        $this->version = $this->determineVersion($dataLen, $this->ecLevel);
        $this->moduleCount = $this->version * 4 + 17;

        // Inicializa a matriz com zeros
        $this->matrix = array_fill(0, $this->moduleCount, array_fill(0, $this->moduleCount, null));

        // 1. Padrões de busca (Finder patterns)
        $this->setupFinderPattern(0, 0);
        $this->setupFinderPattern($this->moduleCount - 7, 0);
        $this->setupFinderPattern(0, $this->moduleCount - 7);

        // 2. Padrões de temporização (Timing patterns)
        $this->setupTimingPatterns();

        // 3. Padrões de alinhamento (Alignment patterns para versão >= 2)
        $this->setupAlignmentPatterns();

        // 4. Padrão de ponto preto fixo (Dark module)
        $this->matrix[4 * $this->version + 9][8] = true;

        // 5. Reserva de áreas de informação de formato e versão
        $this->reserveFormatInfo();

        // 6. Codificação dos dados e Reed-Solomon
        $codewords = $this->createDataCodewords();

        // 7. Avalia as 8 máscaras e seleciona a de menor penalidade
        $bestMask = 0;
        $minPenalty = PHP_INT_MAX;
        $bestMatrix = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $testMatrix = $this->matrix;
            $this->placeData($testMatrix, $codewords, $mask);
            $this->applyFormatInfo($testMatrix, $mask);
            if ($this->version >= 7) {
                $this->applyVersionInfo($testMatrix);
            }

            $penalty = $this->calculatePenalty($testMatrix);
            if ($penalty < $minPenalty) {
                $minPenalty = $penalty;
                $bestMask = $mask;
                $bestMatrix = $testMatrix;
            }
        }

        $this->matrix = $bestMatrix;
    }

    private function setupFinderPattern(int $row, int $col): void
    {
        for ($r = -1; $r <= 7; $r++) {
            if ($row + $r < 0 || $this->moduleCount <= $row + $r) continue;
            for ($c = -1; $c <= 7; $c++) {
                if ($col + $c < 0 || $this->moduleCount <= $col + $c) continue;
                if ((0 <= $r && $r <= 6 && ($c == 0 || $c == 6))
                    || (0 <= $c && $c <= 6 && ($r == 0 || $r == 6))
                    || (2 <= $r && $r <= 4 && 2 <= $c && $c <= 4)
                ) {
                    $this->matrix[$row + $r][$col + $c] = true;
                } else {
                    $this->matrix[$row + $r][$col + $c] = false;
                }
            }
        }
    }

    private function setupTimingPatterns(): void
    {
        for ($i = 8; $i < $this->moduleCount - 8; $i++) {
            $val = ($i % 2 === 0);
            if ($this->matrix[6][$i] === null) {
                $this->matrix[6][$i] = $val;
            }
            if ($this->matrix[$i][6] === null) {
                $this->matrix[$i][6] = $val;
            }
        }
    }

    private function setupAlignmentPatterns(): void
    {
        $pos = $this->getAlignmentPatternPositions($this->version);
        $count = count($pos);

        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < $count; $j++) {
                $row = $pos[$i];
                $col = $pos[$j];

                if ($this->matrix[$row][$col] !== null) {
                    continue;
                }

                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        if ($r == -2 || $r == 2 || $c == -2 || $c == 2 || ($r == 0 && $c == 0)) {
                            $this->matrix[$row + $r][$col + $c] = true;
                        } else {
                            $this->matrix[$row + $r][$col + $c] = false;
                        }
                    }
                }
            }
        }
    }

    private function reserveFormatInfo(): void
    {
        for ($i = 0; $i < 9; $i++) {
            if ($i != 6 && $this->matrix[8][$i] === null) {
                $this->matrix[8][$i] = false;
            }
            if ($i != 6 && $this->matrix[$i][8] === null) {
                $this->matrix[$i][8] = false;
            }
        }
        for ($i = $this->moduleCount - 8; $i < $this->moduleCount; $i++) {
            if ($this->matrix[8][$i] === null) {
                $this->matrix[8][$i] = false;
            }
        }
        for ($i = $this->moduleCount - 7; $i < $this->moduleCount; $i++) {
            if ($this->matrix[$i][8] === null) {
                $this->matrix[$i][8] = false;
            }
        }
    }

    private function applyFormatInfo(array &$matrix, int $mask): void
    {
        $data = ($this->ecLevel << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $format = (($data << 10) | $rem) ^ 0x5412;

        for ($i = 0; $i < 15; $i++) {
            $bit = (($format >> $i) & 1) == 1;
            if ($i < 6) {
                $matrix[8][$i] = $bit;
            } elseif ($i < 8) {
                $matrix[8][$i + 1] = $bit;
            } else {
                $matrix[8][$this->moduleCount - 15 + $i] = $bit;
            }

            if ($i < 8) {
                $matrix[$this->moduleCount - 1 - $i][8] = $bit;
            } else {
                $matrix[14 - $i][8] = $bit;
            }
        }
        $matrix[$this->moduleCount - 8][8] = true;
    }

    private function applyVersionInfo(array &$matrix): void
    {
        if ($this->version < 7) return;
        $data = $this->version;
        $rem = $data;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $versionInfo = ($data << 12) | $rem;

        for ($i = 0; $i < 18; $i++) {
            $bit = (($versionInfo >> $i) & 1) == 1;
            $r = (int)floor($i / 3);
            $c = ($i % 3) + $this->moduleCount - 11;
            $matrix[$r][$c] = $bit;
            $matrix[$c][$r] = $bit;
        }
    }

    private function placeData(array &$matrix, array $codewords, int $maskPattern): void
    {
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $bitIdx = 0;
        $bitLen = strlen($bits);
        $dir = -1;
        $row = $this->moduleCount - 1;

        for ($col = $this->moduleCount - 1; $col > 0; $col -= 2) {
            if ($col == 6) $col--;

            while (true) {
                for ($c = 0; $c < 2; $c++) {
                    $currCol = $col - $c;
                    if ($matrix[$row][$currCol] === null) {
                        $bit = ($bitIdx < $bitLen) ? ($bits[$bitIdx++] === '1') : false;
                        $mask = $this->getMaskBit($maskPattern, $row, $currCol);
                        $matrix[$row][$currCol] = $bit ^ $mask;
                    }
                }

                $row += $dir;
                if ($row < 0 || $row >= $this->moduleCount) {
                    $row -= $dir;
                    $dir = -$dir;
                    break;
                }
            }
        }
    }

    private function getMaskBit(int $mask, int $r, int $c): bool
    {
        return match ($mask) {
            0 => (($r + $c) % 2) === 0,
            1 => ($r % 2) === 0,
            2 => ($c % 3) === 0,
            3 => (($r + $c) % 3) === 0,
            4 => (((int)floor($r / 2) + (int)floor($c / 3)) % 2) === 0,
            5 => (($r * $c) % 2 + ($r * $c) % 3) === 0,
            6 => ((($r * $c) % 2 + ($r * $c) % 3) % 2) === 0,
            7 => ((($r + $c) % 2 + ($r * $c) % 3) % 2) === 0,
            default => false,
        };
    }

    private function calculatePenalty(array $m): int
    {
        $penalty = 0;
        $size = $this->moduleCount;

        for ($r = 0; $r < $size; $r++) {
            $sameCount = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($m[$r][$c] === $m[$r][$c - 1]) {
                    $sameCount++;
                } else {
                    if ($sameCount >= 5) $penalty += 3 + ($sameCount - 5);
                    $sameCount = 1;
                }
            }
            if ($sameCount >= 5) $penalty += 3 + ($sameCount - 5);
        }

        for ($c = 0; $c < $size; $c++) {
            $sameCount = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($m[$r][$c] === $m[$r - 1][$c]) {
                    $sameCount++;
                } else {
                    if ($sameCount >= 5) $penalty += 3 + ($sameCount - 5);
                    $sameCount = 1;
                }
            }
            if ($sameCount >= 5) $penalty += 3 + ($sameCount - 5);
        }

        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $val = $m[$r][$c];
                if ($val === $m[$r][$c + 1] && $val === $m[$r + 1][$c] && $val === $m[$r + 1][$c + 1]) {
                    $penalty += 3;
                }
            }
        }

        return $penalty;
    }

    private function createDataCodewords(): array
    {
        $bits = '0100'; // Modo Byte
        $charCountBits = ($this->version < 10) ? 8 : 16;
        $dataLen = strlen($this->data);
        $bits .= str_pad(decbin($dataLen), $charCountBits, '0', STR_PAD_LEFT);

        for ($i = 0; $i < $dataLen; $i++) {
            $bits .= str_pad(decbin(ord($this->data[$i])), 8, '0', STR_PAD_LEFT);
        }

        $totalDataBytes = $this->getDataCapacity($this->version, $this->ecLevel);
        $totalDataBits = $totalDataBytes * 8;

        $bits .= str_repeat('0', min(4, max(0, $totalDataBits - strlen($bits))));

        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - (strlen($bits) % 8));
        }

        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (strlen($bits) < $totalDataBits) {
            $bits .= str_pad(decbin($padBytes[$padIdx % 2]), 8, '0', STR_PAD_LEFT);
            $padIdx++;
        }

        $rawBytes = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $rawBytes[] = bindec(substr($bits, $i, 8));
        }

        return $this->interleaveBlocks($rawBytes);
    }

    private function interleaveBlocks(array $dataBytes): array
    {
        $blockInfo = $this->getBlockInfo($this->version, $this->ecLevel);
        $ecBytesPerBlock = $blockInfo['ec_per_block'];
        $numBlocksG1 = $blockInfo['g1_blocks'];
        $dataBytesG1 = $blockInfo['g1_data'];
        $numBlocksG2 = $blockInfo['g2_blocks'];
        $dataBytesG2 = $blockInfo['g2_data'];

        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;

        for ($b = 0; $b < $numBlocksG1; $b++) {
            $block = array_slice($dataBytes, $offset, $dataBytesG1);
            $offset += $dataBytesG1;
            $dataBlocks[] = $block;
            $ecBlocks[] = $this->calculateReedSolomon($block, $ecBytesPerBlock);
        }

        for ($b = 0; $b < $numBlocksG2; $b++) {
            $block = array_slice($dataBytes, $offset, $dataBytesG2);
            $offset += $dataBytesG2;
            $dataBlocks[] = $block;
            $ecBlocks[] = $this->calculateReedSolomon($block, $ecBytesPerBlock);
        }

        $result = [];
        $maxDataLen = max($dataBytesG1, $dataBytesG2);
        $totalBlocks = count($dataBlocks);

        for ($i = 0; $i < $maxDataLen; $i++) {
            for ($b = 0; $b < $totalBlocks; $b++) {
                if ($i < count($dataBlocks[$b])) {
                    $result[] = $dataBlocks[$b][$i];
                }
            }
        }

        for ($i = 0; $i < $ecBytesPerBlock; $i++) {
            for ($b = 0; $b < $totalBlocks; $b++) {
                $result[] = $ecBlocks[$b][$i];
            }
        }

        return $result;
    }

    private function calculateReedSolomon(array $data, int $ecCount): array
    {
        $gen = $this->getRsGeneratorPoly($ecCount);
        $res = array_fill(0, $ecCount, 0);

        foreach ($data as $d) {
            $factor = $d ^ $res[0];
            array_shift($res);
            $res[] = 0;

            if ($factor !== 0) {
                $logFactor = self::$gfLog[$factor];
                for ($i = 0; $i < $ecCount; $i++) {
                    $res[$i] ^= self::$gfExp[(self::$gfLog[$gen[$i]] + $logFactor) % 255];
                }
            }
        }

        return $res;
    }

    private function getRsGeneratorPoly(int $ecCount): array
    {
        self::initGfTables();
        $g = [1];
        for ($i = 0; $i < $ecCount; $i++) {
            $term = [1, self::$gfExp[$i]];
            $newG = array_fill(0, count($g) + 1, 0);
            for ($j = 0; $j < count($g); $j++) {
                for ($k = 0; $k < count($term); $k++) {
                    $newG[$j + $k] ^= self::$gfExp[(self::$gfLog[$g[$j]] + self::$gfLog[$term[$k]]) % 255];
                }
            }
            $g = $newG;
        }
        return array_slice($g, 1);
    }

    private static ?array $gfExp = null;
    private static ?array $gfLog = null;

    private static function initGfTables(): void
    {
        if (self::$gfExp !== null) return;
        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);

        $val = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $val;
            self::$gfExp[$i + 255] = $val;
            self::$gfLog[$val] = $i;
            $val = $val << 1;
            if ($val & 0x100) {
                $val ^= 0x11D;
            }
        }
    }

    private function determineVersion(int $dataLen, int $ecLevel): int
    {
        for ($v = 1; $v <= 14; $v++) {
            $cap = $this->getDataCapacity($v, $ecLevel);
            $overhead = ($v < 10) ? 2 : 3;
            if ($dataLen + $overhead <= $cap) {
                return $v;
            }
        }
        throw new \InvalidArgumentException("Dados muito extensos para o QR Code (" . $dataLen . " bytes).");
    }

    private function getDataCapacity(int $version, int $ecLevel): int
    {
        $info = $this->getBlockInfo($version, $ecLevel);
        return ($info['g1_blocks'] * $info['g1_data']) + ($info['g2_blocks'] * $info['g2_data']);
    }

    private function getBlockInfo(int $v, int $ec): array
    {
        $table = [
            1 => [self::ERROR_CORRECT_M => ['ec_per_block' => 10, 'g1_blocks' => 1, 'g1_data' => 16, 'g2_blocks' => 0, 'g2_data' => 0]],
            2 => [self::ERROR_CORRECT_M => ['ec_per_block' => 16, 'g1_blocks' => 1, 'g1_data' => 28, 'g2_blocks' => 0, 'g2_data' => 0]],
            3 => [self::ERROR_CORRECT_M => ['ec_per_block' => 26, 'g1_blocks' => 1, 'g1_data' => 44, 'g2_blocks' => 0, 'g2_data' => 0]],
            4 => [self::ERROR_CORRECT_M => ['ec_per_block' => 18, 'g1_blocks' => 2, 'g1_data' => 32, 'g2_blocks' => 0, 'g2_data' => 0]],
            5 => [self::ERROR_CORRECT_M => ['ec_per_block' => 24, 'g1_blocks' => 2, 'g1_data' => 43, 'g2_blocks' => 0, 'g2_data' => 0]],
            6 => [self::ERROR_CORRECT_M => ['ec_per_block' => 16, 'g1_blocks' => 4, 'g1_data' => 27, 'g2_blocks' => 0, 'g2_data' => 0]],
            7 => [self::ERROR_CORRECT_M => ['ec_per_block' => 18, 'g1_blocks' => 4, 'g1_data' => 31, 'g2_blocks' => 0, 'g2_data' => 0]],
            8 => [self::ERROR_CORRECT_M => ['ec_per_block' => 22, 'g1_blocks' => 2, 'g1_data' => 38, 'g2_blocks' => 2, 'g2_data' => 39]],
            9 => [self::ERROR_CORRECT_M => ['ec_per_block' => 22, 'g1_blocks' => 3, 'g1_data' => 36, 'g2_blocks' => 2, 'g2_data' => 37]],
            10 => [self::ERROR_CORRECT_M => ['ec_per_block' => 26, 'g1_blocks' => 4, 'g1_data' => 43, 'g2_blocks' => 1, 'g2_data' => 44]],
            11 => [self::ERROR_CORRECT_M => ['ec_per_block' => 30, 'g1_blocks' => 1, 'g1_data' => 50, 'g2_blocks' => 4, 'g2_data' => 51]],
            12 => [self::ERROR_CORRECT_M => ['ec_per_block' => 22, 'g1_blocks' => 6, 'g1_data' => 36, 'g2_blocks' => 2, 'g2_data' => 37]],
            13 => [self::ERROR_CORRECT_M => ['ec_per_block' => 22, 'g1_blocks' => 8, 'g1_data' => 37, 'g2_blocks' => 4, 'g2_data' => 38]],
            14 => [self::ERROR_CORRECT_M => ['ec_per_block' => 24, 'g1_blocks' => 4, 'g1_data' => 40, 'g2_blocks' => 5, 'g2_data' => 41]],
        ];

        return $table[$v][$ec] ?? [
            'ec_per_block' => 24,
            'g1_blocks'    => 4,
            'g1_data'      => 40,
            'g2_blocks'    => 5,
            'g2_data'      => 41
        ];
    }

    private function getAlignmentPatternPositions(int $v): array
    {
        $table = [
            1 => [],
            2 => [6, 18],
            3 => [6, 22],
            4 => [6, 26],
            5 => [6, 30],
            6 => [6, 34],
            7 => [6, 22, 38],
            8 => [6, 24, 42],
            9 => [6, 26, 46],
            10 => [6, 28, 50],
            11 => [6, 30, 54],
            12 => [6, 32, 58],
            13 => [6, 34, 62],
            14 => [6, 26, 46, 66],
        ];

        return $table[$v] ?? [];
    }
}
