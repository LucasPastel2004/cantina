<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Serviço de Leitura e Extração de XML de Nota Fiscal Eletrônica (NF-e)
 * 
 * Conforme docs/FSD.md (Área 5 e Fluxo 10) e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Models\Produto;
use App\Models\ItemNotaFiscal;

class XmlParserService
{
    /**
     * Efetua o parse completo do conteúdo XML de uma NF-e
     * 
     * @param string $xmlContent Conteúdo textual bruto do arquivo XML
     * @return array Dados estruturados da nota fiscal e seus itens
     * @throws \InvalidArgumentException Se o arquivo for inválido ou não for uma NF-e reconhecida
     */
    public static function parseXml(string $xmlContent): array
    {
        if (empty(trim($xmlContent))) {
            throw new \InvalidArgumentException('O arquivo XML fornecido está vazio.');
        }

        // Medida de segurança contra ataques XXE (XML External Entity)
        if (stripos($xmlContent, '<!DOCTYPE') !== false || stripos($xmlContent, '<!ENTITY') !== false) {
            throw new \InvalidArgumentException('Estruturas DTD ou entidades externas não são permitidas no XML por razões de segurança.');
        }

        // Suprime avisos do libxml e captura erros
        libxml_use_internal_errors(true);

        // Remove declarações de namespace para simplificar a navegação dos nós
        $xmlLimpo = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlContent);

        $xml = simplexml_load_string($xmlLimpo, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        if ($xml === false) {
            $erros = libxml_get_errors();
            libxml_clear_errors();
            $msgErro = !empty($erros) ? trim($erros[0]->message) : 'Formato de XML inválido.';
            throw new \InvalidArgumentException('Falha ao processar o arquivo XML: ' . $msgErro);
        }

        // Localiza o nó infNFe (suporta tanto nfeProc quanto NFe isolada)
        $infNFe = null;
        if (isset($xml->NFe->infNFe)) {
            $infNFe = $xml->NFe->infNFe;
        } elseif (isset($xml->infNFe)) {
            $infNFe = $xml->infNFe;
        } else {
            // Tenta via busca XPath genérica
            $busca = $xml->xpath('//infNFe');
            if (!empty($busca)) {
                $infNFe = $busca[0];
            }
        }

        if (!$infNFe) {
            throw new \InvalidArgumentException('O arquivo XML enviado não é uma Nota Fiscal Eletrônica (NF-e) válida. Estrutura "infNFe" não localizada.');
        }

        // 1. Extração da Chave da NF-e (44 dígitos numéricos)
        $chaveAcesso = '';
        if (isset($infNFe['Id'])) {
            $idAttr = (string)$infNFe['Id'];
            $chaveAcesso = preg_replace('/\D/', '', $idAttr);
        }

        // Fallback: se não estiver no Id do infNFe, procura em protNFe/infProt/chNFe
        if (strlen($chaveAcesso) !== 44) {
            $buscaChave = $xml->xpath('//chNFe');
            if (!empty($buscaChave)) {
                $chaveAcesso = preg_replace('/\D/', '', (string)$buscaChave[0]);
            }
        }

        if (strlen($chaveAcesso) !== 44) {
            throw new \InvalidArgumentException('A chave de acesso da NF-e não possui os 44 dígitos numéricos obrigatórios.');
        }

        // 2. Extração dos dados de Identificação da Nota
        $ide = $infNFe->ide ?? null;
        $numeroNota = $ide ? (string)$ide->nNF : '';
        $serieNota  = $ide ? (string)$ide->serie : '1';
        $dataEmissaoBruta = $ide ? (string)($ide->dhEmi ?: $ide->dEmi) : '';

        $dataEmissao = self::formatarDataHora($dataEmissaoBruta);

        // 3. Extração dos dados do Emitente (Fornecedor)
        $emit = $infNFe->emit ?? null;
        if (!$emit) {
            throw new \InvalidArgumentException('Dados do emitente da nota fiscal não foram encontrados no XML.');
        }

        $cnpjEmitenteBruto = preg_replace('/\D/', '', (string)($emit->CNPJ ?: $emit->CPF));
        $cnpjFormatado = self::formatarCnpj($cnpjEmitenteBruto);
        $razaoSocial = trim((string)$emit->xNome);
        $nomeFantasia = trim((string)($emit->xFant ?: $razaoSocial));

        // 4. Extração dos Totais da Nota
        $total = $infNFe->total->ICMSTot ?? null;
        $valorTotal = $total ? (float)$total->vNF : 0.0;

        // 5. Extração dos Itens de Produto (<det>)
        $itens = [];
        $itensXml = $infNFe->det ?? [];

        foreach ($itensXml as $det) {
            $prod = $det->prod ?? null;
            if (!$prod) {
                continue;
            }

            $numItem = (int)($det['nItem'] ?? count($itens) + 1);
            $cProd   = trim((string)$prod->cProd);
            $xProd   = trim((string)$prod->xProd);
            $uCom    = strtoupper(trim((string)$prod->uCom));
            $qCom    = (float)$prod->qCom;
            $vUnCom  = (float)$prod->vUnCom;
            $vProd   = (float)$prod->vProd;
            $ncm     = trim((string)($prod->NCM ?? ''));

            // Sugere mapeamento com produto cadastrado na cantina e memória de fator multiplicador prévio
            $sugestao = self::sugerirMapeamentoProduto($cProd, $xProd, $cnpjEmitenteBruto);

            $itens[] = [
                'numero_item'               => $numItem,
                'codigo_produto_fornecedor' => $cProd,
                'descricao_fornecedor'      => $xProd,
                'unidade_fornecedor'        => $uCom,
                'quantidade_fornecedor'     => $qCom,
                'valor_unitario_fornecedor' => $vUnCom,
                'valor_total_item'          => $vProd,
                'ncm'                       => $ncm,
                'produto_id_sugerido'       => $sugestao['produto_id'] ?? null,
                'fator_conversao_sugerido'  => $sugestao['fator_conversao'] ?? 1,
                'quantidade_convertida'     => (int)ceil($qCom * ($sugestao['fator_conversao'] ?? 1))
            ];
        }

        if (empty($itens)) {
            throw new \InvalidArgumentException('Nenhum item de mercadoria foi identificado na nota fiscal.');
        }

        return [
            'chave_nfe'             => $chaveAcesso,
            'numero_nota'           => $numeroNota,
            'serie'                 => $serieNota,
            'cnpj_emitente'         => $cnpjFormatado,
            'cnpj_emitente_bruto'   => $cnpjEmitenteBruto,
            'razao_social_emitente' => $razaoSocial,
            'nome_fantasia_emitente'=> $nomeFantasia,
            'data_emissao'          => $dataEmissao,
            'valor_total'           => $valorTotal,
            'total_itens'           => count($itens),
            'itens'                 => $itens
        ];
    }

    /**
     * Tenta identificar o produto correspondente na base interna e seu fator de conversão histórico
     */
    private static function sugerirMapeamentoProduto(string $codigoFornecedor, string $descricaoFornecedor, string $cnpjEmitente): array
    {
        // 1. Verifica memória prévia em itens de notas anteriores do mesmo fornecedor
        try {
            $historico = ItemNotaFiscal::buscarUltimoFatorConversao($codigoFornecedor, $cnpjEmitente);
            if ($historico) {
                return [
                    'produto_id'      => (int)$historico['produto_id'],
                    'fator_conversao' => (int)$historico['fator_conversao']
                ];
            }
        } catch (\Throwable $e) {
            // Fallback silencioso caso a tabela ainda não tenha histórico
        }

        // 2. Busca produto por correspondência direta de código interno
        $porCodigo = Produto::buscarPorCodigo($codigoFornecedor);
        if ($porCodigo) {
            return [
                'produto_id'      => (int)$porCodigo['id'],
                'fator_conversao' => 1
            ];
        }

        // 3. Busca por descrição textual similar
        $todos = Produto::listarTodos();
        $descLimpa = mb_strtolower(trim($descricaoFornecedor), 'UTF-8');

        foreach ($todos as $p) {
            $pNome = mb_strtolower(trim($p['descricao']), 'UTF-8');
            if (str_contains($descLimpa, $pNome) || str_contains($pNome, $descLimpa)) {
                return [
                    'produto_id'      => (int)$p['id'],
                    'fator_conversao' => 1
                ];
            }
        }

        return [
            'produto_id'      => null,
            'fator_conversao' => 1
        ];
    }

    /**
     * Converte datas em diversos formatos da SEFAZ para DATETIME MySQL (Y-m-d H:i:s)
     */
    public static function formatarDataHora(string $dataIso): string
    {
        if (empty($dataIso)) {
            return date('Y-m-d H:i:s');
        }

        try {
            $dt = new \DateTimeImmutable($dataIso);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Aplica máscara de CNPJ (00.000.000/0000-00) ou CPF (000.000.000-00)
     */
    public static function formatarCnpj(string $doc): string
    {
        $d = preg_replace('/\D/', '', $doc);
        if (strlen($d) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d);
        }
        if (strlen($d) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d);
        }
        return $doc;
    }
}
