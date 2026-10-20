<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Rodapé Base HTML5 e Scripts Locais
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$anoAtual = date('Y');
?>
    <footer class="app-footer mt-auto py-3">
        <div class="container-fluid px-4 d-flex flex-column flex-md-row align-items-center justify-content-between text-muted" style="font-size: 11px;">
            <div class="mb-1 mb-md-0 d-flex align-items-center gap-2">
                <span class="fw-semibold text-secondary">Colégio Monteiro Lobato</span>
                <span class="text-muted">&bull;</span>
                <span class="fst-italic text-secondary">Valores para o Futuro!</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span>Cantina Web &bull; Versão 2.0.0</span>
                <span class="text-muted">&bull;</span>
                <span class="font-tabular">&copy; <?php echo $anoAtual; ?> Todos os direitos reservados.</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle Compilado Localmente -->
    <script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts do Sistema e Utilitários Vanilla JS -->
    <script src="<?php echo $baseUrl; ?>/assets/js/app.js?v=<?php echo $assetVersion ?? '6.2.2'; ?>"></script>
</body>
</html>
