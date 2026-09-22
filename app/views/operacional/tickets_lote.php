<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Bobina Térmica 80mm de Tickets em Lote (Impressão Contínua)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16, Seção 11.18, Fluxos 6 e 7, RN-16, RN-17)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$tickets = $tickets ?? [];
$dataConsumo = $dataConsumo ?? date('Y-m-d');
$turnoConsumo = $turnoConsumo ?? 'matutino';
$baseUrl = $baseUrl ?? '';

echo \App\Services\PdfService::gerarEspelhoTicketsLoteHtml($tickets, [
    'data'    => $dataConsumo,
    'turno'   => $turnoConsumo,
    'baseUrl' => $baseUrl
]);
