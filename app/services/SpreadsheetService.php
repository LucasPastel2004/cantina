<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Serviço de Manipulação e Streaming de Planilhas Escolares (CSV / XLSX)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;
use App\Models\Responsavel;
use App\Models\Turma;
use App\Models\Aluno;
use App\Models\RestricaoAlimentar;

class SpreadsheetService
{
    /**
     * Remove o Byte Order Mark (BOM) UTF-8 caso presente
     */
    public static function removerBom(string $texto): string
    {
        if (str_starts_with($texto, "\xEF\xBB\xBF")) {
            return substr($texto, 3);
        }
        return $texto;
    }

    /**
     * Normaliza uma string de cabeçalho (minúsculas, sem acentos, sem pontuações)
     */
    public static function normalizarCabecalho(string $coluna): string
    {
        $coluna = self::removerBom(trim($coluna));
        $coluna = mb_strtolower($coluna, 'UTF-8');
        $coluna = preg_replace('/[áàãâä]/u', 'a', $coluna);
        $coluna = preg_replace('/[éèêë]/u', 'e', $coluna);
        $coluna = preg_replace('/[íìîï]/u', 'i', $coluna);
        $coluna = preg_replace('/[óòõôö]/u', 'o', $coluna);
        $coluna = preg_replace('/[úùûü]/u', 'u', $coluna);
        $coluna = preg_replace('/[ç]/u', 'c', $coluna);
        $coluna = preg_replace('/[^a-z0-9_]/', '_', $coluna);
        return trim(preg_replace('/_+/', '_', $coluna), '_');
    }

    /**
     * Detecta o delimitador CSV mais provável (',', ';' ou '\t') a partir da primeira linha
     */
    public static function detectarDelimitador(string $primeiraLinha): string
    {
        $virgulas  = substr_count($primeiraLinha, ',');
        $pontos    = substr_count($primeiraLinha, ';');
        $tabs      = substr_count($primeiraLinha, "\t");

        if ($pontos >= $virgulas && $pontos >= $tabs && $pontos > 0) {
            return ';';
        }
        if ($tabs >= $virgulas && $tabs >= $pontos && $tabs > 0) {
            return "\t";
        }
        return ',';
    }

    /**
     * Converte codificação para UTF-8 caso o arquivo esteja em Windows-1252 / ISO-8859-1
     */
    private static function converterParaUtf8(string $texto): string
    {
        if (!mb_check_encoding($texto, 'UTF-8')) {
            return mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        }
        return $texto;
    }

    /**
     * Mapeia as colunas do cabeçalho da planilha para os campos canônicos do sistema
     */
    public static function identificarMapaColunas(array $cabecalhos): array
    {
        $mapa = [
            'nome_responsavel' => null,
            'cpf_responsavel'  => null,
            'whatsapp'         => null,
            'email'            => null,
            'matricula'        => null,
            'nome_aluno'       => null,
            'serie'            => null,
            'turma'            => null,
            'turno'            => null,
            'alergias'         => null
        ];

        foreach ($cabecalhos as $indice => $col) {
            $norm = self::normalizarCabecalho((string)$col);

            if (in_array($norm, ['nome_responsavel', 'responsavel', 'nome_do_responsavel', 'mae', 'pai', 'titular'], true) || (str_contains($norm, 'responsavel') && str_contains($norm, 'nome'))) {
                $mapa['nome_responsavel'] = $indice;
            } elseif (in_array($norm, ['cpf_responsavel', 'cpf', 'cpf_do_responsavel', 'documento_responsavel', 'doc_responsavel', 'cpf_titular'], true) || str_contains($norm, 'cpf')) {
                $mapa['cpf_responsavel'] = $indice;
            } elseif (in_array($norm, ['whatsapp_responsavel', 'whatsapp', 'celular', 'telefone', 'contato', 'fone', 'telefone_whatsapp', 'tel_whatsapp', 'celular_whatsapp'], true) || str_contains($norm, 'whatsapp') || str_contains($norm, 'celular') || str_contains($norm, 'telefone') || str_contains($norm, 'fone')) {
                $mapa['whatsapp'] = $indice;
            } elseif (in_array($norm, ['email_responsavel', 'email', 'e_mail'], true) || str_contains($norm, 'email')) {
                $mapa['email'] = $indice;
            } elseif (in_array($norm, ['matricula_aluno', 'matricula', 'ra', 'codigo_aluno', 'cod_aluno', 'id_aluno'], true) || str_contains($norm, 'matricula') || $norm === 'ra') {
                $mapa['matricula'] = $indice;
            } elseif (in_array($norm, ['nome_aluno', 'aluno', 'nome_do_aluno', 'estudante', 'dependente'], true) || (str_contains($norm, 'aluno') && str_contains($norm, 'nome'))) {
                $mapa['nome_aluno'] = $indice;
            } elseif (in_array($norm, ['serie', 'ano', 'ano_escolar', 'grau'], true) || str_contains($norm, 'serie')) {
                $mapa['serie'] = $indice;
            } elseif (in_array($norm, ['turma', 'nome_turma', 'turma_aluno'], true) || str_contains($norm, 'turma')) {
                $mapa['turma'] = $indice;
            } elseif (in_array($norm, ['turno', 'periodo'], true) || str_contains($norm, 'turno')) {
                $mapa['turno'] = $indice;
            } elseif (in_array($norm, ['alergias', 'restricoes', 'restricoes_alimentares', 'alergenos'], true) || str_contains($norm, 'alerg') || str_contains($norm, 'restric')) {
                $mapa['alergias'] = $indice;
            }
        }

        return $mapa;
    }

    /**
     * Valida se todas as colunas essenciais foram localizadas no arquivo
     */
    public static function validarColunasObrigatorias(array $mapa): array
    {
        $faltantes = [];
        $obrigatorias = [
            'nome_responsavel' => 'Nome do Responsável',
            'cpf_responsavel'  => 'CPF do Responsável',
            'whatsapp'         => 'WhatsApp do Responsável',
            'matricula'        => 'Matrícula do Aluno',
            'nome_aluno'       => 'Nome do Aluno',
            'turma'            => 'Turma'
        ];

        foreach ($obrigatorias as $campo => $rotulo) {
            if ($mapa[$campo] === null) {
                $faltantes[] = $rotulo;
            }
        }

        return $faltantes;
    }

    /**
     * Analisa o arquivo de planilha e gera a prévia (dry-run) com diagnósticos linha a linha
     * 
     * @param string $caminhoArquivo Caminho do arquivo físico no disco
     * @return array Relatório de conferência prévia
     */
    public static function analisarArquivo(string $caminhoArquivo): array
    {
        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            return [
                'valido'            => false,
                'erros_estruturais' => ['O arquivo submetido não foi localizado ou não pôde ser lido no servidor.'],
                'total_linhas'      => 0
            ];
        }

        $handle = fopen($caminhoArquivo, 'r');
        if (!$handle) {
            return [
                'valido'            => false,
                'erros_estruturais' => ['Falha ao abrir stream do arquivo.'],
                'total_linhas'      => 0
            ];
        }

        // Lê primeira linha para detectar delimitador e cabeçalhos
        $primeiraLinha = fgets($handle);
        if ($primeiraLinha === false) {
            fclose($handle);
            return [
                'valido'            => false,
                'erros_estruturais' => ['O arquivo está vazio.'],
                'total_linhas'      => 0
            ];
        }

        $primeiraLinha = self::converterParaUtf8($primeiraLinha);
        $delimitador = self::detectarDelimitador($primeiraLinha);

        // Volta ao início do arquivo
        rewind($handle);

        $cabecalho = fgetcsv($handle, 0, $delimitador);
        if (!$cabecalho || empty($cabecalho)) {
            fclose($handle);
            return [
                'valido'            => false,
                'erros_estruturais' => ['Não foi possível identificar o cabeçalho do arquivo.'],
                'total_linhas'      => 0
            ];
        }

        // Converte encoding dos cabeçalhos se necessário
        $cabecalho = array_map([self::class, 'converterParaUtf8'], $cabecalho);
        $mapa = self::identificarMapaColunas($cabecalho);
        $colunasFaltantes = self::validarColunasObrigatorias($mapa);

        if (!empty($colunasFaltantes)) {
            fclose($handle);
            return [
                'valido'            => false,
                'erros_estruturais' => [
                    'Colunas obrigatórias não identificadas no cabeçalho: ' . implode(', ', $colunasFaltantes) . '.',
                    'Certifique-se de que a primeira linha da planilha contenha os nomes dos campos conforme o modelo padrão.'
                ],
                'total_linhas'      => 0
            ];
        }

        // Estrutura de auditoria prévia
        $totalLinhas      = 0;
        $linhasValidas    = 0;
        $linhasComErro    = 0;
        $linhasComAviso   = 0;
        $amostra          = [];
        $matriculasVistas = [];
        $cpfsVistos       = [];

        $novosRespCount    = 0;
        $existRespCount    = 0;
        $novasTurmasCount  = 0;
        $existTurmasCount  = 0;
        $novosAlunosCount  = 0;
        $atualAlunosCount  = 0;

        $numeroLinha = 1;

        while (($linhaRaw = fgetcsv($handle, 0, $delimitador)) !== false) {
            $numeroLinha++;

            // Pula linha completamente vazia
            if (empty(array_filter($linhaRaw, fn($v) => trim($v) !== ''))) {
                continue;
            }

            $totalLinhas++;
            $linha = array_map([self::class, 'converterParaUtf8'], $linhaRaw);

            $nomeResp  = trim($linha[$mapa['nome_responsavel']] ?? '');
            $cpfResp   = Responsavel::cleanCpf($linha[$mapa['cpf_responsavel']] ?? '');
            $whatsResp = trim($linha[$mapa['whatsapp']] ?? '');
            $emailResp = $mapa['email'] !== null ? trim($linha[$mapa['email']] ?? '') : '';
            $matricula = trim($linha[$mapa['matricula']] ?? '');
            $nomeAluno = trim($linha[$mapa['nome_aluno']] ?? '');
            $serie     = $mapa['serie'] !== null ? trim($linha[$mapa['serie']] ?? '') : '';
            $turma     = trim($linha[$mapa['turma']] ?? '');
            $turnoRaw  = $mapa['turno'] !== null ? strtolower(trim($linha[$mapa['turno']] ?? '')) : 'matutino';
            $turno     = in_array($turnoRaw, ['vespertino', 'tarde']) ? 'vespertino' : 'matutino';
            $alergias  = $mapa['alergias'] !== null ? trim($linha[$mapa['alergias']] ?? '') : '';

            if (empty($serie)) {
                $serie = $turma;
            }

            $errosLinha  = [];
            $avisosLinha = [];

            // Validação de CPF
            if (empty($cpfResp)) {
                $errosLinha[] = 'CPF do responsável ausente.';
            } elseif (strlen($cpfResp) !== 11) {
                $errosLinha[] = 'CPF inválido (deve conter 11 dígitos numéricos).';
            }

            // Validação de Matrícula
            if (empty($matricula)) {
                $errosLinha[] = 'Matrícula do aluno ausente.';
            } else {
                if (isset($matriculasVistas[$matricula])) {
                    $errosLinha[] = "Matrícula repetida dentro da própria planilha (já apareceu na linha {$matriculasVistas[$matricula]}).";
                } else {
                    $matriculasVistas[$matricula] = $numeroLinha;
                }
            }

            // Validação de nomes
            if (empty($nomeAluno)) {
                $errosLinha[] = 'Nome do aluno ausente.';
            }
            if (empty($nomeResp)) {
                $errosLinha[] = 'Nome do responsável ausente.';
            }
            if (empty($turma)) {
                $errosLinha[] = 'Turma ausente.';
            }

            // Checagens de banco de dados
            $alunoStatus = 'novo';
            $respStatus  = 'novo';
            $turmaStatus = 'nova';

            if (empty($errosLinha)) {
                // Responsável
                $respDb = Responsavel::fetchOne("SELECT id, nome, saldo_atual, senha_hash FROM responsaveis WHERE cpf = :cpf LIMIT 1", ['cpf' => $cpfResp]);
                if ($respDb) {
                    $respStatus = 'existente';
                    if (!isset($cpfsVistos[$cpfResp])) {
                        $existRespCount++;
                        $cpfsVistos[$cpfResp] = true;
                    }
                    if (!empty($respDb['senha_hash'])) {
                        $avisosLinha[] = "Conta familiar já ativa (saldo R$ " . number_format((float)$respDb['saldo_atual'], 2, ',', '.') . "). Saldo e senha serão integralmente preservados.";
                    }
                } else {
                    if (!isset($cpfsVistos[$cpfResp])) {
                        $novosRespCount++;
                        $cpfsVistos[$cpfResp] = true;
                    }
                }

                // Turma
                $turmaDb = Turma::buscarPorNomeETurno($turma, $turno);
                if ($turmaDb) {
                    $turmaStatus = 'existente';
                    $existTurmasCount++;
                } else {
                    $novasTurmasCount++;
                }

                // Aluno
                $alunoDb = Aluno::buscarPorMatricula($matricula, true);
                if ($alunoDb) {
                    $alunoStatus = 'atualizado';
                    $atualAlunosCount++;
                    if ((int)$alunoDb['ativo'] === 0 || !empty($alunoDb['deleted_at'])) {
                        $avisosLinha[] = "Aluno estava inativo e será reativado no sistema.";
                    }
                } else {
                    $novosAlunosCount++;
                }
            }

            if (!empty($errosLinha)) {
                $linhasComErro++;
                $statusLinha = 'erro';
            } elseif (!empty($avisosLinha)) {
                $linhasComAviso++;
                $linhasValidas++;
                $statusLinha = 'aviso';
            } else {
                $linhasValidas++;
                $statusLinha = 'ok';
            }

            // Guarda amostra para exibição no frontend (até 100 linhas)
            if (count($amostra) < 100) {
                $amostra[] = [
                    'linha_num'        => $numeroLinha,
                    'status'           => $statusLinha,
                    'erros'            => $errosLinha,
                    'avisos'           => $avisosLinha,
                    'nome_aluno'       => $nomeAluno,
                    'matricula'        => $matricula,
                    'serie'            => $serie,
                    'turma'            => $turma,
                    'turno'            => $turno,
                    'nome_responsavel' => $nomeResp,
                    'cpf_responsavel'  => $cpfResp,
                    'whatsapp'         => $whatsResp,
                    'alergias'         => $alergias,
                    'aluno_status'     => $alunoStatus,
                    'resp_status'      => $respStatus,
                    'turma_status'     => $turmaStatus
                ];
            }
        }

        fclose($handle);

        return [
            'valido'            => true,
            'delimitador'       => $delimitador,
            'total_linhas'      => $totalLinhas,
            'linhas_validas'    => $linhasValidas,
            'linhas_com_erro'   => $linhasComErro,
            'linhas_com_aviso'  => $linhasComAviso,
            'erros_estruturais' => [],
            'resumo'            => [
                'novos_responsaveis'  => $novosRespCount,
                'responsaveis_exist'  => $existRespCount,
                'novas_turmas'        => $novasTurmasCount,
                'turmas_exist'        => $existTurmasCount,
                'novos_alunos'        => $novosAlunosCount,
                'alunos_atualizados'  => $atualAlunosCount
            ],
            'amostra_linhas'    => $amostra
        ];
    }

    /**
     * Efetiva a importação atômica em lote da planilha escolar
     * 
     * @param string $caminhoArquivo Caminho do arquivo físico
     * @param int|null $usuarioId ID do administrador executor
     * @return array Resumo final da importação
     */
    public static function processarImportacao(string $caminhoArquivo, ?int $usuarioId = null): array
    {
        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            throw new \RuntimeException('Arquivo para importação não localizado.');
        }

        $handle = fopen($caminhoArquivo, 'r');
        if (!$handle) {
            throw new \RuntimeException('Não foi possível abrir o arquivo para processamento.');
        }

        $primeiraLinha = self::converterParaUtf8(fgets($handle));
        $delimitador   = self::detectarDelimitador($primeiraLinha);
        rewind($handle);

        $cabecalho = fgetcsv($handle, 0, $delimitador);
        $cabecalho = array_map([self::class, 'converterParaUtf8'], $cabecalho);
        $mapa      = self::identificarMapaColunas($cabecalho);

        // Catálogo de restrições alimentares do banco para vínculo automático caso informado
        $catalogoAlergenos = RestricaoAlimentar::listarTodas();

        // Inicia Transação Atômica no PDO
        Model::beginTransaction();

        $linhasProcessadas = 0;
        $linhasIgnoradas   = 0;
        $novosResp         = 0;
        $respAtualizados   = 0;
        $novasTurmas       = 0;
        $novosAlunos       = 0;
        $alunosAtualizados = 0;
        $vincAlergias      = 0;

        $numeroLinha = 1;

        try {
            while (($linhaRaw = fgetcsv($handle, 0, $delimitador)) !== false) {
                $numeroLinha++;

                if (empty(array_filter($linhaRaw, fn($v) => trim($v) !== ''))) {
                    continue;
                }

                $linha = array_map([self::class, 'converterParaUtf8'], $linhaRaw);

                $nomeResp  = trim($linha[$mapa['nome_responsavel']] ?? '');
                $cpfResp   = Responsavel::cleanCpf($linha[$mapa['cpf_responsavel']] ?? '');
                $whatsResp = trim($linha[$mapa['whatsapp']] ?? '');
                $emailResp = $mapa['email'] !== null ? trim($linha[$mapa['email']] ?? '') : '';
                $matricula = trim($linha[$mapa['matricula']] ?? '');
                $nomeAluno = trim($linha[$mapa['nome_aluno']] ?? '');
                $serie     = $mapa['serie'] !== null ? trim($linha[$mapa['serie']] ?? '') : '';
                $turma     = trim($linha[$mapa['turma']] ?? '');
                $turnoRaw  = $mapa['turno'] !== null ? strtolower(trim($linha[$mapa['turno']] ?? '')) : 'matutino';
                $turno     = in_array($turnoRaw, ['vespertino', 'tarde']) ? 'vespertino' : 'matutino';
                $alergias  = $mapa['alergias'] !== null ? trim($linha[$mapa['alergias']] ?? '') : '';

                if (empty($serie)) {
                    $serie = $turma;
                }

                // Pula linhas com inconsistências graves
                if (empty($cpfResp) || strlen($cpfResp) !== 11 || empty($matricula) || empty($nomeAluno) || empty($turma)) {
                    $linhasIgnoradas++;
                    continue;
                }

                // 1. Turma (localiza ou cria)
                $resTurma = Turma::localizarOuCriar($serie, $turma, $turno, $usuarioId);
                $turmaId  = $resTurma['id'];
                if ($resTurma['novo']) {
                    $novasTurmas++;
                }

                // 2. Responsável (localiza ou cria preservando saldo e senha)
                $resResp = Responsavel::localizarOuCriarPorCpf([
                    'cpf'      => $cpfResp,
                    'nome'     => $nomeResp,
                    'whatsapp' => $whatsResp,
                    'email'    => $emailResp
                ], $usuarioId);
                $respId = $resResp['id'];
                if ($resResp['novo']) {
                    $novosResp++;
                } else {
                    $respAtualizados++;
                }

                // 3. Aluno (localiza ou cria por matrícula)
                $resAluno = Aluno::localizarOuCriarPorMatricula([
                    'matricula'      => $matricula,
                    'nome'           => $nomeAluno,
                    'turma_id'       => $turmaId,
                    'responsavel_id' => $respId,
                    'turno'          => $turno,
                    'ativo'          => 1
                ], $usuarioId);
                $alunoId = $resAluno['id'];
                if ($resAluno['novo']) {
                    $novosAlunos++;
                } else {
                    $alunosAtualizados++;
                }

                // 4. Alérgenos declarados na planilha (opcional)
                if (!empty($alergias)) {
                    $termosAlergia = array_map('trim', preg_split('/[,;\/]+/', $alergias));
                    $idsParaVincular = [];

                    foreach ($termosAlergia as $termo) {
                        if (empty($termo)) continue;
                        $normTermo = self::normalizarCabecalho($termo);

                        foreach ($catalogoAlergenos as $alergItem) {
                            $normDb = self::normalizarCabecalho($alergItem['nome']);
                            if ($normTermo === $normDb || str_contains($normDb, $normTermo) || str_contains($normTermo, $normDb)) {
                                $idsParaVincular[] = (int)$alergItem['id'];
                                break;
                            }
                        }
                    }

                    if (!empty($idsParaVincular)) {
                        RestricaoAlimentar::sincronizarAluno($alunoId, $idsParaVincular);
                        $vincAlergias++;
                    }
                }

                $linhasProcessadas++;
            }

            fclose($handle);

            // Grava o arquivo de forma protegida e definitiva em storage/uploads/private/
            $destinoDir = dirname(__DIR__, 2) . '/storage/uploads/private';
            if (!is_dir($destinoDir)) {
                mkdir($destinoDir, 0750, true);
            }

            $nomeDefinitivo = 'importacao_escolar_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.csv';
            $caminhoFinal   = $destinoDir . '/' . $nomeDefinitivo;

            copy($caminhoArquivo, $caminhoFinal);

            // Comita a transação
            Model::commit();

            return [
                'sucesso'            => true,
                'linhas_processadas' => $linhasProcessadas,
                'linhas_ignoradas'   => $linhasIgnoradas,
                'novos_responsaveis' => $novosResp,
                'resp_atualizados'   => $respAtualizados,
                'novas_turmas'       => $novasTurmas,
                'novos_alunos'       => $novosAlunos,
                'alunos_atualizados' => $alunosAtualizados,
                'vinc_alergias'      => $vincAlergias,
                'arquivo_salvo'      => 'storage/uploads/private/' . $nomeDefinitivo
            ];
        } catch (\Throwable $e) {
            Model::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }

    /**
     * Gera e retorna o conteúdo em string de um modelo CSV oficial de importação escolar
     */
    public static function gerarModeloCsv(): string
    {
        $delimitador = ';';
        $linhas = [
            // Cabeçalho canônico
            ['nome_responsavel', 'cpf_responsavel', 'whatsapp_responsavel', 'email_responsavel', 'matricula_aluno', 'nome_aluno', 'serie', 'turma', 'turno', 'alergias'],
            // Exemplos com formatações reais
            ['Carlos Eduardo Silva', '111.222.333-44', '(11) 98765-4321', 'carlos.silva@email.com', 'MAT-2026-001', 'Lucas Silva', '6º Ano', '6º Ano A', 'matutino', 'Lactose'],
            ['Mariana Costa Dias', '222.333.444-55', '(11) 97654-3210', 'mariana.dias@email.com', 'MAT-2026-002', 'Beatriz Costa Dias', '6º Ano', '6º Ano A', 'matutino', 'Glúten, Amendoim'],
            ['Roberto Alves Pinto', '333.444.555-66', '(11) 96543-2109', 'roberto.alves@email.com', 'MAT-2026-003', 'Gabriel Alves Pinto', '7º Ano', '7º Ano B', 'vespertino', ''],
            ['Juliana Mendes Lima', '444.555.666-77', '(11) 95432-1098', 'juliana.lima@email.com', 'MAT-2026-004', 'Sofia Mendes Lima', '8º Ano', '8º Ano A', 'matutino', 'Ovos'],
            ['Juliana Mendes Lima', '444.555.666-77', '(11) 95432-1098', 'juliana.lima@email.com', 'MAT-2026-005', 'Mateus Mendes Lima', '6º Ano', '6º Ano B', 'matutino', '']
        ];

        // Adiciona BOM UTF-8 para que o Microsoft Excel abra com acentos corretos no Windows
        $csv = "\xEF\xBB\xBF";
        foreach ($linhas as $linha) {
            $csv .= implode($delimitador, array_map(function ($campo) {
                // Escapa se contiver delimitador, aspas ou quebra de linha
                if (str_contains($campo, ';') || str_contains($campo, '"') || str_contains($campo, "\n")) {
                    return '"' . str_replace('"', '""', $campo) . '"';
                }
                return $campo;
            }, $linha)) . "\r\n";
        }

        return $csv;
    }
}
