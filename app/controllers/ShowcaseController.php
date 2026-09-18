<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador de Demonstração e Homologação Visual (Fase 5)
 * 
 * Conforme docs/DESIGN.md e docs/PLANO.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;

class ShowcaseController extends Controller
{
    /**
     * Demonstração do Layout Mestre do Portal da Família
     */
    public function portal(): void
    {
        $data = [
            'pageTitle' => 'Visão Geral da Família',
            'activeMenu' => 'inicio',
            'responsavelNome' => 'Mariana Silveira',
            'saldoAtual' => 148.50,
            'saldoReservado' => 24.00,
            'dependentes' => [
                ['nome' => 'Lucas Silveira Campos', 'turma' => '6º Ano B', 'turno' => 'Matutino', 'alergias' => ['Lactose']],
                ['nome' => 'Beatriz Silveira Campos', 'turma' => '3º Ano A', 'turno' => 'Vespertino', 'alergias' => []],
            ],
            'pedidosSemana' => [
                ['data' => date('d/m/Y', strtotime('+1 day')), 'aluno' => 'Lucas Silveira', 'item' => 'Esfiha de Carne Assada', 'valor' => 8.50, 'status' => 'Agendado'],
                ['data' => date('d/m/Y', strtotime('+1 day')), 'aluno' => 'Beatriz Silveira', 'item' => 'Pão de Queijo + Suco de Uva', 'valor' => 12.00, 'status' => 'Agendado'],
            ]
        ];

        $this->render('showcase/portal', $data, 'portal');
    }

    /**
     * Demonstração do Layout Mestre do Painel da Cantina (com Sidebar)
     */
    public function admin(): void
    {
        $data = [
            'pageTitle' => 'Corte do Turno Matutino',
            'pageHeading' => 'Consolidação de Demandas — Fornecedores',
            'activeMenu' => 'corte',
            'turnoAtivo' => 'Matutino',
            'statusCaixa' => 'Aberto',
            'user' => [
                'nome' => 'Administrador Geral',
                'usuario' => 'admin',
                'perfil' => 'administrador',
                'role' => 'administrador'
            ],
            'statItems' => [
                ['label' => 'Total Encomendas', 'value' => '128', 'subtext' => 'Turno Matutino', 'status' => 'primary'],
                ['label' => 'Salgados Assados', 'value' => '94 un', 'subtext' => 'Demanda Produção', 'status' => 'success'],
                ['label' => 'Industrializados', 'value' => '34 un', 'subtext' => 'Baixados do Estoque', 'status' => 'neutral'],
                ['label' => 'Total Faturado', 'value' => 'R$ 1.088,00', 'subtext' => 'Carteira Digital', 'status' => 'success'],
            ],
            'demandasSalgados' => [
                ['codigo' => 'SAL-001', 'descricao' => 'Coxinha de Frango com Requeijão', 'categoria' => 'Salgados Assados', 'qtd' => 38, 'unitario' => 8.50, 'total' => 323.00],
                ['codigo' => 'SAL-002', 'descricao' => 'Esfiha Integral de Carne', 'categoria' => 'Salgados Assados', 'qtd' => 32, 'unitario' => 8.50, 'total' => 272.00],
                ['codigo' => 'SAL-003', 'descricao' => 'Enroladinho de Presunto e Queijo', 'categoria' => 'Salgados Assados', 'qtd' => 24, 'unitario' => 8.00, 'total' => 192.00],
            ]
        ];

        $this->render('showcase/admin', $data, 'admin');
    }

    /**
     * Galeria de Componentes Parciais (Alertas, Modais, Empty State, Skeletons, etc.)
     */
    public function components(): void
    {
        $data = [
            'pageTitle' => 'Galeria de Componentes Parciais',
            'pageHeading' => 'Design System & Componentes Anti-Slop',
            'activeMenu' => 'configuracoes',
            'turnoAtivo' => 'Matutino',
            'statusCaixa' => 'Aberto',
            'user' => [
                'nome' => 'Administrador Geral',
                'usuario' => 'admin',
                'perfil' => 'administrador'
            ]
        ];

        $this->render('showcase/components', $data, 'admin');
    }
}
