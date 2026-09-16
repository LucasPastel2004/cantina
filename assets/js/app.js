/**
 * Cantina Web — Colégio Monteiro Lobato
 * JavaScript Base & Utilitários de Interface (Vanilla JS)
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

document.addEventListener('DOMContentLoaded', function () {
  // Inicialização de tooltips do Bootstrap caso existam
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  // Formatação dinâmica de campos monetários (R$ 0,00)
  const moneyInputs = document.querySelectorAll('.input-money');
  moneyInputs.forEach(function (input) {
    input.addEventListener('input', function (e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value === '') {
        e.target.value = '';
        return;
      }
      value = (parseInt(value, 10) / 100).toFixed(2) + '';
      value = value.replace('.', ',');
      value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
      e.target.value = value;
    });
  });
});

/**
 * Formata um valor numérico para padrão Real Brasileiro (R$ 0,00)
 * @param {number} valor 
 * @returns {string}
 */
function formatarMoeda(valor) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
  }).format(valor || 0);
}
