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

/**
 * Alterna visibilidade da barra lateral (Sidebar) em dispositivos móveis
 */
function toggleSidebar() {
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (sidebar) {
    sidebar.classList.toggle('sidebar-open');
  }

  if (backdrop) {
    backdrop.classList.toggle('show');
  }
}

/**
 * Abre um modal do Bootstrap pelo ID
 * @param {string} modalId 
 */
function abrirModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
}

/**
 * Fecha um modal do Bootstrap pelo ID
 * @param {string} modalId 
 */
function fecharModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
      modal.hide();
    }
  }
}

