<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Template: Comprovante Térmico de Venda no PDV (Bobina 80mm)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$venda = $venda ?? [];
echo \App\Services\PdfService::gerarEspelhoComprovanteVendaBalcaoHtml($venda);
