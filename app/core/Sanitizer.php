<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Utilitário Central de Sanitização e Reparação de Codificação UTF-8
 * 
 * Finalidade:
 * Corrige de forma transparente e defensiva caracteres corrompidos por mojibake
 * (especialmente sequências CP850/CP437 do terminal Windows e dupla codificação Latin1)
 * em todo o tráfego de dados entre banco de dados, controladores e templates.
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class Sanitizer
{
    /**
     * Tabela de substituição de sequências de mojibake
     * @var array<string, string>
     */
    private static array $mojibakeMap = [
        // 1. Mojibake decorrente de saída UTF-8 interceptada como CP850/CP437 (PowerShell/CMD Windows)
        '├║' => 'ú',
        '├¡' => 'í',
        '├ú' => 'ã',
        '├®' => 'é',
        '├ó' => 'â',
        '├¬' => 'ê',
        '├º' => 'ç',
        '├│' => 'ó',
        '├┤' => 'ô',
        '├á' => 'à',
        '├í' => 'á',
        '┬║' => 'º',
        '┬¬' => 'ª',
        '├ü' => 'Á',
        '├Ç' => 'À',
        '├â' => 'Ã',
        '├é' => 'Â',
        '├ë' => 'É',
        '├è' => 'Ê',
        '├ì' => 'Í',
        '├ô' => 'Ó',
        '├ö' => 'Ô',
        '├ò' => 'Õ',
        '├Ü' => 'Ú',
        '├ç' => 'Ç',
        '├Á' => 'õ',

        // 2. Mojibake decorrente de dupla codificação UTF-8 / Latin1 / Windows-1252
        'Ãº' => 'ú',
        'Ã­' => 'í',
        'Ã£' => 'ã',
        'Ã©' => 'é',
        'Ã¢' => 'â',
        'Ãª' => 'ê',
        'Ã§' => 'ç',
        'Ã³' => 'ó',
        'Ã´' => 'ô',
        'Ã ' => 'à',
        'Ã¡' => 'á',
        'Âº' => 'º',
        'Âª' => 'ª',
        'Ã ' => 'Á',
        'Ã€' => 'À',
        'Ãƒ' => 'Ã',
        'Ã‚' => 'Â',
        'Ã‰' => 'É',
        'ÃŠ' => 'Ê',
        'Ã ' => 'Í',
        'Ã“' => 'Ó',
        'Ã”' => 'Ô',
        'Ã•' => 'Õ',
        'Ãš' => 'Ú',
        'Ã‡' => 'Ç',
        'Ãµ' => 'õ',

        // 3. Variações pontuais com soft hyphens ou caracteres de controle
        "Sandu\xC2\xADches" => 'Sanduíches',
        'Sandu-íches'      => 'Sanduíches',
    ];

    /**
     * Repara strings corrompidas por mojibake ou dupla codificação UTF-8
     *
     * @param string $str String original
     * @return string String com acentuação e símbolos corrigidos em UTF-8 puro
     */
    public static function repararString(string $str): string
    {
        if ($str === '') {
            return '';
        }

        // Executa a substituição rápida das sequências mapeadas
        return strtr($str, self::$mojibakeMap);
    }

    /**
     * Repara recursivamente strings ou arrays contendo estruturas de dados
     *
     * @param mixed $input String, Array ou outro tipo de dado
     * @return mixed Dado com todas as strings internas sanitizadas
     */
    public static function repararUtf8(mixed $input): mixed
    {
        if (is_string($input)) {
            return self::repararString($input);
        }

        if (is_array($input)) {
            $resultado = [];
            foreach ($input as $chave => $valor) {
                $chaveLimpa = is_string($chave) ? self::repararString($chave) : $chave;
                $resultado[$chaveLimpa] = self::repararUtf8($valor);
            }
            return $resultado;
        }

        return $input;
    }
}

// Registra a função helper global caso ainda não exista
if (!function_exists('repararUtf8')) {
    function repararUtf8(mixed $input): mixed
    {
        return \App\Core\Sanitizer::repararUtf8($input);
    }
}
