<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Bobina Térmica 80mm de Ticket Individual / 2ª Via
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16, Seção 11.18, Fluxos 6 e 7, RN-16, RN-17)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$ticket = $ticket ?? [];
$baseUrl = $baseUrl ?? '';

echo \App\Services\PdfService::gerarEspelhoTicketIndividualHtml($ticket, [
    'baseUrl' => $baseUrl
]);
