<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela 6.2: Upload de XML de NF-e
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Voltar ao Estoque
                </a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Entrada de Mercadorias via XML de NF-e</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/></svg>
                Ver Notas Importadas
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <!-- Card Principal de Upload -->
            <div class="card-enterprise p-4 mb-3">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light border p-3 mb-2">
                        <svg width="32" height="32" viewBox="0 0 16 16" fill="#1d4ed8">
                            <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                            <path d="M8.5 7.5a.5.5 0 0 0-1 0v3.793L6.354 10.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 11.293V7.5z"/>
                        </svg>
                    </div>
                    <h2 class="h6 fw-bold text-slate-900 mb-1">Upload da Nota Fiscal Eletrônica (NF-e)</h2>
                    <p class="text-muted small mb-0">Envie o arquivo <code class="code-tag">.xml</code> fornecido pela distribuidora ou fornecedor de alimentos.</p>
                </div>

                <form method="POST" action="<?php echo $baseUrl; ?>/estoque/importar-xml/analisar" enctype="multipart/form-data" id="formUploadXml" onsubmit="return validarUploadXml()">
                    <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">

                    <div class="p-4 border rounded-2 bg-light text-center mb-3" id="dropZone" style="border: 2px dashed #cbd5e1 !important; cursor: pointer;" onclick="document.getElementById('inputArquivoXml').click()">
                        <input type="file" name="arquivo_xml" id="inputArquivoXml" class="d-none" accept=".xml,text/xml" onchange="exibirArquivoSelecionado(this)">
                        <div id="dropPrompt">
                            <svg width="24" height="24" viewBox="0 0 16 16" fill="#64748b" class="mb-2"><path fill-rule="evenodd" d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2z"/><path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/></svg>
                            <span class="d-block fw-semibold text-slate-800 small">Clique aqui para selecionar o arquivo XML</span>
                            <span class="d-block text-muted" style="font-size: 11px;">Formatos aceitos: NF-e modelo 55 ou NFC-e modelo 65 (máx. 10 MB)</span>
                        </div>
                        <div id="dropSelected" class="d-none">
                            <span class="badge bg-primary-soft text-primary fw-semibold p-2 mb-1" id="lblNomeArquivo" style="font-size: 12px;">arquivo.xml</span>
                            <span class="d-block text-muted small" id="lblTamanhoArquivo">0 KB</span>
                            <span class="text-primary small text-decoration-underline mt-1 d-inline-block">Trocar arquivo</span>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" id="btnSubmitXml" class="btn btn-enterprise btn-primary py-2 d-inline-flex align-items-center justify-content-center gap-2">
                            <span>Carregar e Analisar Nota Fiscal</span>
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card Informativo de Procedimento -->
            <div class="card-enterprise p-3 bg-light border-0">
                <span class="small fw-bold text-slate-900 d-block mb-2">Como funciona a Entrada via XML:</span>
                <ol class="small text-secondary mb-0 ps-3" style="line-height: 1.55;">
                    <li class="mb-1"><strong>Extração Fiscal:</strong> O sistema lê a chave oficial de 44 dígitos, os dados do fornecedor e todos os itens faturados.</li>
                    <li class="mb-1"><strong>Conferência Prévia:</strong> Na próxima tela, você vincula cada item do distribuidor ao produto correspondente na cantina.</li>
                    <li class="mb-1"><strong>Fator de Conversão de Embalagem:</strong> Permite transformar fardos e caixas em unidades para venda (ex: 1 caixa com 12 sucos = fator 12 = 12 unidades adicionadas ao estoque).</li>
                    <li><strong>Segurança e LGPD:</strong> A cópia do XML é armazenada em pasta protegida (<code class="code-tag">storage/uploads/private/</code>) inacessível via web.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
function exibirArquivoSelecionado(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('dropPrompt').classList.add('d-none');
        document.getElementById('dropSelected').classList.remove('d-none');
        document.getElementById('lblNomeArquivo').textContent = file.name;
        document.getElementById('lblTamanhoArquivo').textContent = (file.size / 1024).toFixed(1) + ' KB';
    }
}

function validarUploadXml() {
    const input = document.getElementById('inputArquivoXml');
    if (!input.files || !input.files[0]) {
        alert('Por favor, selecione um arquivo XML da nota fiscal.');
        return false;
    }

    const file = input.files[0];
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'xml') {
        alert('O arquivo selecionado deve possuir a extensão .xml.');
        return false;
    }

    if (file.size > 10 * 1024 * 1024) {
        alert('O arquivo XML excede o tamanho máximo de 10 MB.');
        return false;
    }

    const btn = document.getElementById('btnSubmitXml');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processando XML...';
    return true;
}
</script>
