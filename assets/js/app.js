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

  // Formatação dinâmica de CNPJ (00.000.000/0000-00)
  const cnpjInputs = document.querySelectorAll('#cnpj, input[name="cnpj"], .input-cnpj, [data-mask="cnpj"]');
  cnpjInputs.forEach(function (input) {
    if (input.value) {
      input.value = formatarCnpj(input.value);
    }
    input.addEventListener('input', function (e) {
      e.target.value = formatarCnpj(e.target.value);
    });
    input.addEventListener('blur', function (e) {
      e.target.value = formatarCnpj(e.target.value);
    });
  });

  // Formatação dinâmica de Telefone e WhatsApp celular (00) 00000-0000 e fixo (00) 0000-0000
  const phoneInputs = document.querySelectorAll('#telefone_contato, input[name="telefone_contato"], input[name*="telefone"], input[name*="whatsapp"], .input-phone, [data-mask="phone"], [data-mask="tel"]');
  phoneInputs.forEach(function (input) {
    if (input.value) {
      input.value = formatarTelefone(input.value);
    }
    input.addEventListener('input', function (e) {
      e.target.value = formatarTelefone(e.target.value);
    });
    input.addEventListener('blur', function (e) {
      e.target.value = formatarTelefone(e.target.value);
    });
  });

  // Formatação dinâmica de CPF (000.000.000-00)
  const cpfInputs = document.querySelectorAll('#cpf, input[name="cpf"], .input-cpf, [data-mask="cpf"]');
  cpfInputs.forEach(function (input) {
    if (input.value) {
      input.value = formatarCpf(input.value);
    }
    input.addEventListener('input', function (e) {
      e.target.value = formatarCpf(e.target.value);
    });
    input.addEventListener('blur', function (e) {
      e.target.value = formatarCpf(e.target.value);
    });
  });

  // Restaurar estado de colapso da sidebar no Desktop
  try {
    const savedSidebar = localStorage.getItem('cantina_sidebar_collapsed');
    const wrapper = document.querySelector('.app-admin-wrapper');
    if (wrapper && savedSidebar === 'true' && window.innerWidth >= 992) {
      wrapper.classList.add('sidebar-collapsed');
    }
  } catch (e) {}

  // Restaurar estado dos blocos de categoria na sidebar
  document.querySelectorAll('.nav-section-box').forEach(function (box) {
    const id = box.id;
    if (!id) return;
    // Se contiver item ativo, mantém SEMPRE expandido
    if (box.querySelector('.nav-link.active')) {
      box.classList.remove('collapsed');
      const btn = box.querySelector('.nav-section-header');
      if (btn) btn.setAttribute('aria-expanded', 'true');
      return;
    }
    try {
      const state = localStorage.getItem('cantina_nav_' + id);
      if (state === 'collapsed') {
        box.classList.add('collapsed');
        const btn = box.querySelector('.nav-section-header');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      }
    } catch (e) {}
  });

  // Auto-dismiss de alertas de notificação após 5 segundos (com pausa no hover)
  iniciarAutoDismissAlertas();
});

/**
 * Formata um valor numérico para padrão CNPJ (00.000.000/0000-00)
 * @param {string} valor 
 * @returns {string}
 */
function formatarCnpj(valor) {
  let v = (valor || '').toString().replace(/\D/g, '');
  if (v.length > 14) v = v.slice(0, 14);
  if (v.length > 12) {
    return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})$/, '$1.$2.$3/$4-$5');
  } else if (v.length > 8) {
    return v.replace(/^(\d{2})(\d{3})(\d{3})(\d{1,4})$/, '$1.$2.$3/$4');
  } else if (v.length > 5) {
    return v.replace(/^(\d{2})(\d{3})(\d{1,3})$/, '$1.$2.$3');
  } else if (v.length > 2) {
    return v.replace(/^(\d{2})(\d{1,3})$/, '$1.$2');
  }
  return v;
}

/**
 * Formata um valor numérico para padrão Telefone/WhatsApp
 * Suporta fixo: (00) 0000-0000 e celular: (00) 00000-0000
 * @param {string} valor 
 * @returns {string}
 */
function formatarTelefone(valor) {
  let v = (valor || '').toString().replace(/\D/g, '');
  if (v.length > 11) v = v.slice(0, 11);
  if (v.length > 10) {
    return v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
  } else if (v.length > 6) {
    return v.replace(/^(\d{2})(\d{4})(\d{1,4})$/, '($1) $2-$3');
  } else if (v.length > 2) {
    return v.replace(/^(\d{2})(\d{1,4})$/, '($1) $2');
  } else if (v.length > 0) {
    return v.replace(/^(\d{1,2})$/, '($1');
  }
  return v;
}

/**
 * Formata um valor numérico para padrão CPF (000.000.000-00)
 * @param {string} valor 
 * @returns {string}
 */
function formatarCpf(valor) {
  let v = (valor || '').toString().replace(/\D/g, '');
  if (v.length > 11) v = v.slice(0, 11);
  if (v.length > 9) {
    return v.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
  } else if (v.length > 6) {
    return v.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
  } else if (v.length > 3) {
    return v.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
  }
  return v;
}

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
 * Alterna o colapso da barra lateral inteira no Desktop
 */
function toggleSidebarCollapse() {
  const wrapper = document.querySelector('.app-admin-wrapper');
  if (!wrapper) return;
  const isCollapsed = wrapper.classList.toggle('sidebar-collapsed');
  try {
    localStorage.setItem('cantina_sidebar_collapsed', isCollapsed ? 'true' : 'false');
  } catch (e) {}
}

/**
 * Manipulador inteligente do botão toggle da topbar
 * No desktop colapsa/expande; no mobile abre/fecha o offcanvas
 */
function handleSidebarToggle() {
  if (window.innerWidth >= 992) {
    toggleSidebarCollapse();
  } else {
    toggleSidebar();
  }
}

/**
 * Expande ou colapsa um bloco de categorias na sidebar
 * @param {string} sectionId 
 */
function toggleNavSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (!section) return;
  const isCollapsed = section.classList.toggle('collapsed');
  const btn = section.querySelector('.nav-section-header');
  if (btn) {
    btn.setAttribute('aria-expanded', !isCollapsed);
  }
  try {
    localStorage.setItem('cantina_nav_' + sectionId, isCollapsed ? 'collapsed' : 'expanded');
  } catch (e) {}
}

/**
 * Abre um modal pelo ID (suporta modais Bootstrap ou modais customizados .modal-backdrop-custom)
 * @param {string} modalId 
 */
function abrirModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (!modalEl) return;

  // Se for modal customizado (classe .modal-backdrop-custom ou não possuir a classe .modal do Bootstrap)
  if (modalEl.classList.contains('modal-backdrop-custom') || !modalEl.classList.contains('modal')) {
    modalEl.classList.remove('d-none');
    return;
  }

  // Se for modal padrão do Bootstrap
  if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
}

/**
 * Fecha um modal pelo ID (suporta modais Bootstrap ou modais customizados .modal-backdrop-custom)
 * @param {string} modalId 
 */
function fecharModal(modalId) {
  const modalEl = document.getElementById(modalId);
  if (!modalEl) return;

  // Se for modal customizado
  if (modalEl.classList.contains('modal-backdrop-custom') || !modalEl.classList.contains('modal')) {
    modalEl.classList.add('d-none');
    return;
  }

  // Se for modal padrão do Bootstrap
  if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
      modal.hide();
    }
  }
}

/**
 * Inicializa temporizadores de auto-fechamento para mensagens de notificação (.alert-enterprise)
 * Padrão: 5 segundos (5000ms), com pausa se o cursor do mouse estiver sobre o alerta
 */
function iniciarAutoDismissAlertas() {
  const alerts = document.querySelectorAll('.alert-enterprise');
  alerts.forEach(function (alertEl) {
    // Se o alerta estiver configurado explicitamente como permanente, não fecha
    if (alertEl.dataset.autoDismiss === 'false' || alertEl.classList.contains('alert-permanent')) {
      return;
    }

    // Tempo configurável via atributo data-auto-dismiss (padrão: 5000ms = 5 segundos)
    const timeoutMs = parseInt(alertEl.dataset.autoDismiss, 10) || 5000;
    let timer = null;

    function armarTimer() {
      desarmarTimer();
      timer = setTimeout(function () {
        fecharAlerta(alertEl);
      }, timeoutMs);
    }

    function desarmarTimer() {
      if (timer) {
        clearTimeout(timer);
        timer = null;
      }
    }

    // Inicia contagem regressiva
    armarTimer();

    // Pausa no Hover para o usuário poder ler sem pressa
    alertEl.addEventListener('mouseenter', desarmarTimer);
    alertEl.addEventListener('mouseleave', armarTimer);
  });
}

/**
 * Fecha um alerta com animação suave de recolhimento e remoção do DOM
 * @param {HTMLElement} alertEl
 */
function fecharAlerta(alertEl) {
  if (!alertEl || alertEl.classList.contains('alert-dismissing')) return;
  alertEl.classList.add('alert-dismissing');
  setTimeout(function () {
    if (alertEl.parentNode) {
      alertEl.parentNode.removeChild(alertEl);
    }
  }, 350);
}


