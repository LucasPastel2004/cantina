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
      let digits = e.target.value.replace(/\D/g, '');
      if (digits === '') {
        e.target.value = '';
      } else {
        let value = (parseInt(digits, 10) / 100).toFixed(2) + '';
        value = value.replace('.', ',');
        value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        e.target.value = value;
      }
      // Notifica listeners que o valor foi formatado
      input.dispatchEvent(new CustomEvent('moneychange', { bubbles: true }));
      input.dispatchEvent(new CustomEvent('moneyinput', { bubbles: true }));
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

  // Restaurar estado e inicializar Auto-Hide da sidebar no Desktop
  initSidebarAutoHide();

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

  // Inicialização e escuta do sistema corporativo de modais de confirmação
  inicializarConfirmacaoCorporativa();

  // Fechar menus em suspensão do dock ao clicar fora
  document.addEventListener('click', function (e) {
    const isDockClick = e.target.closest('.dock-btn') || e.target.closest('.sidebar-flyout');
    if (!isDockClick) {
      fecharFlyouts();
    }
  });

  // Fechar menus em suspensão com a tecla Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      fecharFlyouts();
    }
  });
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
 * Converte com segurança uma string monetária (formatada ou bruta com dígitos) para float
 * Compatível com centavos mascarados ("200,00", "20,000", "1.250,50", "200")
 * @param {string|number} valor 
 * @returns {number}
 */
function converterMoedaParaFloat(valor) {
  if (typeof valor === 'number') return isNaN(valor) ? 0 : valor;
  if (!valor) return 0;
  const str = String(valor).trim();
  if (!str) return 0;

  if (str.indexOf(',') !== -1) {
    const partes = str.split(',');
    if (partes.length === 2 && partes[1].length <= 2) {
      const num = parseFloat(partes[0].replace(/\./g, '') + '.' + partes[1]);
      return isNaN(num) ? 0 : num;
    }
    const digits = str.replace(/\D/g, '');
    return digits ? (parseInt(digits, 10) / 100) : 0;
  }

  if (/^\d+(\.\d{1,2})$/.test(str)) {
    return parseFloat(str) || 0;
  }

  const digits = str.replace(/\D/g, '');
  if (!digits) return 0;
  return parseInt(digits, 10) / 100;
}


/**
 * Alterna abertura do Menu em Suspensão (Flyout Popover) de uma categoria do Dock
 * @param {string} category Nome da categoria ('operacional', 'estoque', 'financeiro', 'admin', 'relatorios')
 */
function toggleCategoryFlyout(category) {
  const flyoutIdMap = {
    operacional: 'flyoutOperacional',
    estoque: 'flyoutEstoque',
    financeiro: 'flyoutFinanceiro',
    admin: 'navSectionAdmin',
    relatorios: 'flyoutRelatorios'
  };

  const targetFlyoutId = flyoutIdMap[category];
  const targetFlyout = document.getElementById(targetFlyoutId);
  const targetBtn = document.querySelector('.dock-btn[data-category="' + category + '"]');

  if (!targetFlyout || !targetBtn) return;

  const isAlreadyOpen = targetFlyout.classList.contains('show');

  // Fecha todos os flyouts abertos previamente
  fecharFlyouts();

  if (isAlreadyOpen) {
    return;
  }

  // Posiciona o flyout verticalmente em relação ao botão clicado
  const rect = targetBtn.getBoundingClientRect();
  const flyoutHeight = 320;
  let topPos = rect.top - 6;

  // Garante que o menu não transborde a parte inferior da tela
  if (topPos + flyoutHeight > window.innerHeight) {
    topPos = Math.max(10, window.innerHeight - flyoutHeight - 16);
  }
  if (topPos < 10) topPos = 10;

  targetFlyout.style.top = topPos + 'px';
  targetFlyout.classList.add('show');
  targetBtn.classList.add('flyout-open');
  targetBtn.setAttribute('aria-expanded', 'true');

  // No mobile, exibe backdrop
  if (window.innerWidth < 768) {
    const backdrop = document.getElementById('sidebarBackdrop');
    if (backdrop) backdrop.classList.add('show');
  }
}

/**
 * Fecha todos os Menus em Suspensão (Flyouts) abertos
 */
function fecharFlyouts() {
  const flyouts = document.querySelectorAll('.sidebar-flyout');
  flyouts.forEach(function (f) {
    f.classList.remove('show');
  });

  const dockBtns = document.querySelectorAll('.dock-btn');
  dockBtns.forEach(function (b) {
    b.classList.remove('flyout-open');
    b.setAttribute('aria-expanded', 'false');
  });

  const backdrop = document.getElementById('sidebarBackdrop');
  if (backdrop && window.innerWidth < 768) {
    backdrop.classList.remove('show');
  }
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

let sidebarPeekTimeout = null;

/**
 * Abre a barra lateral temporariamente por hover (Peek) quando estiver colapsada no Desktop/Notebook
 */
function abrirSidebarPeek() {
  if (window.innerWidth < 768) return;
  const wrapper = document.querySelector('.app-admin-wrapper');
  if (!wrapper || !wrapper.classList.contains('sidebar-collapsed')) return;
  clearTimeout(sidebarPeekTimeout);
  wrapper.classList.add('sidebar-peek-open');
}

/**
 * Agenda o fechamento suave da barra lateral temporária após o mouse sair
 */
function fecharSidebarPeekComDelay() {
  const wrapper = document.querySelector('.app-admin-wrapper');
  if (!wrapper || !wrapper.classList.contains('sidebar-peek-open')) return;
  clearTimeout(sidebarPeekTimeout);
  sidebarPeekTimeout = setTimeout(function () {
    wrapper.classList.remove('sidebar-peek-open');
  }, 250);
}

/**
 * Fecha imediatamente a barra lateral temporária
 */
function fecharSidebarPeek() {
  clearTimeout(sidebarPeekTimeout);
  const wrapper = document.querySelector('.app-admin-wrapper');
  if (wrapper) {
    wrapper.classList.remove('sidebar-peek-open');
  }
}

/**
 * Inicializa o estado da barra lateral (expandida ou colapsada) a partir das preferências do usuário
 * e configura os ouvintes de abertura por hover (borda esquerda) e fechamento ao sair
 */
function initSidebarAutoHide() {
  const wrapper = document.querySelector('.app-admin-wrapper');
  if (!wrapper) return;

  // 1. No novo dock vertical de 60px, a barra lateral fica sempre ativa e fixa sem esconder a tela
  if (wrapper) {
    wrapper.classList.remove('sidebar-collapsed');
    wrapper.classList.remove('sidebar-peek-open');
  }
  try {
    const pref = localStorage.getItem('cantina_sidebar_preference');
    if (pref === 'pinned_collapsed') {
      localStorage.removeItem('cantina_sidebar_preference');
    }
  } catch (e) {}

  // 2. Ouvintes da faixa invisível na borda esquerda (Edge Trigger) para abertura por hover
  const edgeTrigger = document.getElementById('sidebarEdgeTrigger');
  if (edgeTrigger) {
    edgeTrigger.addEventListener('mouseenter', abrirSidebarPeek);
    edgeTrigger.addEventListener('mouseleave', fecharSidebarPeekComDelay);
  }

  // 3. Ouvintes na própria barra lateral para mantê-la aberta durante navegação e fechar ao sair
  const sidebar = document.getElementById('appSidebar');
  if (sidebar) {
    sidebar.addEventListener('mouseenter', function () {
      clearTimeout(sidebarPeekTimeout);
    });
    sidebar.addEventListener('mouseleave', function () {
      fecharSidebarPeekComDelay();
    });
  }

  // 4. Tecla Escape ou clique fora fecha o hover temporário
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && wrapper.classList.contains('sidebar-peek-open')) {
      fecharSidebarPeek();
    }
  });

  document.addEventListener('click', function (e) {
    if (wrapper.classList.contains('sidebar-peek-open')) {
      const sidebarEl = document.getElementById('appSidebar');
      const triggerEl = document.getElementById('sidebarEdgeTrigger');
      if (sidebarEl && !sidebarEl.contains(e.target) && triggerEl && !triggerEl.contains(e.target)) {
        fecharSidebarPeek();
      }
    }
  });
}

/**
 * Alterna e trava a barra lateral (Pin / Unpin) no Desktop e Notebooks (>= 768px).
 * - Quando travada fechada: ao clicar na setinha, trava a barra lateral aberta (contrai a área principal e esconde o botão da topbar).
 * - Quando travada aberta: ao clicar na setinha, trava a barra lateral fechada (estica a área principal e exibe o botão da topbar com a setinha '>').
 */
function toggleSidebarCollapse() {
  const wrapper = document.querySelector('.app-admin-wrapper');
  if (!wrapper) return;
  
  // Se estava em modo hover peek, o usuário clicou na setinha para travar aberta!
  const estavaEmPeek = wrapper.classList.contains('sidebar-peek-open');
  fecharSidebarPeek();
  
  let isNowCollapsed;
  if (estavaEmPeek) {
    wrapper.classList.remove('sidebar-collapsed');
    isNowCollapsed = false;
  } else {
    isNowCollapsed = wrapper.classList.toggle('sidebar-collapsed');
  }
  
  try {
    localStorage.setItem('cantina_sidebar_preference', isNowCollapsed ? 'pinned_collapsed' : 'pinned_open');
    localStorage.setItem('cantina_sidebar_collapsed', isNowCollapsed ? 'true' : 'false');
  } catch (e) {}
}

/**
 * Manipulador inteligente do botão toggle da topbar
 * No desktop/notebook (>= 768px) contrai/estica o layout; no celular (< 768px) abre/fecha o offcanvas
 */
function handleSidebarToggle() {
  if (window.innerWidth >= 768) {
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

// ============================================================================
// SISTEMA CORPORATIVO DE CONFIRMAÇÃO DE AÇÕES (MODAL ANTI-SLOP ENTERPRISE)
// Substitui categoricamente as caixas nativas confirm() do navegador
// ============================================================================

/**
 * Cria ou recupera a instância única do Modal Corporativo de Confirmação
 * @returns {HTMLElement}
 */
function getOrCreateConfirmModal() {
  let modalEl = document.getElementById('modalConfirmacaoSistema');
  if (!modalEl) {
    modalEl = document.createElement('div');
    modalEl.id = 'modalConfirmacaoSistema';
    modalEl.className = 'modal fade modal-confirm-enterprise';
    modalEl.tabIndex = -1;
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.setAttribute('role', 'dialog');
    modalEl.innerHTML = `
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content modal-enterprise shadow" style="border: 1px solid #cbd5e1; border-radius: 8px;">
          <div class="modal-header py-2 px-3 bg-light d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #e2e8f0;">
            <div class="d-flex align-items-center gap-2">
              <span id="modalConfirmacaoIcone" class="d-inline-flex align-items-center justify-content-center" style="font-size: 15px;"></span>
              <span class="font-weight-bold text-dark font-13" id="modalConfirmacaoTitulo">Confirmação Operacional</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar" style="font-size: 10px;"></button>
          </div>
          <div class="modal-body py-3 px-3 text-secondary font-13" id="modalConfirmacaoMensagem" style="line-height: 1.5; color: #334155;">
          </div>
          <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-end gap-2" style="border-top: 1px solid #e2e8f0;">
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btnModalConfirmacaoCancelar" data-bs-dismiss="modal">
              Cancelar
            </button>
            <button type="button" class="btn btn-enterprise btn-primary btn-sm px-3" id="btnModalConfirmacaoConfirmar">
              Confirmar
            </button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modalEl);

    // Ajusta z-index do backdrop se houver outros modais abertos no fundo (como o modal de fechar caixa)
    modalEl.addEventListener('show.bs.modal', function () {
      setTimeout(function () {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
          backdrops[backdrops.length - 1].style.zIndex = '1070';
        }
      }, 15);
    });

    // Ao fechar, garante que o body preserve 'modal-open' se houver outro modal aberto
    modalEl.addEventListener('hidden.bs.modal', function () {
      if (document.querySelectorAll('.modal.show').length > 0) {
        document.body.classList.add('modal-open');
      }
    });
  }
  return modalEl;
}

/**
 * Exibe modal corporativo de confirmação com suporte a Promise
 * @param {Object|string} options
 * @returns {Promise<boolean>}
 */
window.confirmarAcao = function (options) {
  return new Promise(function (resolve) {
    if (typeof options === 'string') {
      options = { mensagem: options };
    }
    options = options || {};

    const titulo = options.titulo || 'Confirmação Operacional';
    const mensagem = options.mensagem || 'Deseja realmente confirmar esta operação?';
    const textoConfirmar = options.textoConfirmar || 'Confirmar';
    const textoCancelar = options.textoCancelar || 'Cancelar';
    const tipo = options.tipo || 'primary'; // 'danger', 'primary', 'warning', 'success'

    const modalEl = getOrCreateConfirmModal();
    const tituloEl = modalEl.querySelector('#modalConfirmacaoTitulo');
    const mensagemEl = modalEl.querySelector('#modalConfirmacaoMensagem');
    const iconeEl = modalEl.querySelector('#modalConfirmacaoIcone');
    const btnConfirmar = modalEl.querySelector('#btnModalConfirmacaoConfirmar');
    const btnCancelar = modalEl.querySelector('#btnModalConfirmacaoCancelar');

    tituloEl.textContent = titulo;
    mensagemEl.innerHTML = mensagem;
    btnConfirmar.textContent = textoConfirmar;
    btnCancelar.textContent = textoCancelar;

    // Configurar cor do botão e ícone corporativo sóbrio
    btnConfirmar.className = 'btn btn-enterprise btn-sm px-3 font-semibold';
    if (tipo === 'danger') {
      btnConfirmar.classList.add('btn-danger');
      iconeEl.innerHTML = '<svg width="15" height="15" viewBox="0 0 16 16" fill="#dc2626"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>';
    } else if (tipo === 'warning') {
      btnConfirmar.classList.add('btn-warning');
      iconeEl.innerHTML = '<svg width="15" height="15" viewBox="0 0 16 16" fill="#d97706"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>';
    } else if (tipo === 'success') {
      btnConfirmar.classList.add('btn-success');
      iconeEl.innerHTML = '<svg width="15" height="15" viewBox="0 0 16 16" fill="#16a34a"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>';
    } else {
      btnConfirmar.classList.add('btn-primary');
      iconeEl.innerHTML = '<svg width="15" height="15" viewBox="0 0 16 16" fill="#1d4ed8"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>';
    }

    let modalInstance = null;
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: 'static',
        keyboard: true
      });
    }

    let resolvido = false;

    function cleanup() {
      btnConfirmar.removeEventListener('click', onConfirm);
      modalEl.removeEventListener('hidden.bs.modal', onHidden);
    }

    function onConfirm() {
      if (!resolvido) {
        resolvido = true;
        cleanup();
        if (modalInstance) {
          modalInstance.hide();
        } else {
          modalEl.classList.remove('show');
          modalEl.style.display = 'none';
        }
        resolve(true);
      }
    }

    function onHidden() {
      if (!resolvido) {
        resolvido = true;
        cleanup();
        resolve(false);
      }
    }

    btnConfirmar.addEventListener('click', onConfirm);
    modalEl.addEventListener('hidden.bs.modal', onHidden);

    if (modalInstance) {
      modalInstance.show();
    } else {
      modalEl.classList.add('show');
      modalEl.style.display = 'block';
    }
  });
};

// Alias global conveniente
window.confirmModal = window.confirmarAcao;

/**
 * Inicializa interceptação declarativa e migração de chamadas legadas
 */
function inicializarConfirmacaoCorporativa() {
  // Varredura de migração automática para formulários legados com onsubmit="return confirm(...)"
  document.querySelectorAll('form[onsubmit*="confirm("]').forEach(function (form) {
    const code = form.getAttribute('onsubmit') || '';
    const match = code.match(/confirm\(\s*['"]([^'"]+)['"]\s*\)/);
    if (match && match[1]) {
      form.removeAttribute('onsubmit');
      form.setAttribute('data-confirm', match[1]);
      if (!form.hasAttribute('data-confirm-title')) {
        form.setAttribute('data-confirm-title', 'Confirmação Operacional');
      }
      if (!form.hasAttribute('data-confirm-type')) {
        form.setAttribute('data-confirm-type', 'danger');
      }
    }
  });

  // Varredura de migração automática para links legados com onclick="return confirm(...)"
  document.querySelectorAll('a[onclick*="confirm("], button[onclick*="confirm("]').forEach(function (btn) {
    const code = btn.getAttribute('onclick') || '';
    const match = code.match(/confirm\(\s*['"]([^'"]+)['"]\s*\)/);
    if (match && match[1]) {
      btn.removeAttribute('onclick');
      btn.setAttribute('data-confirm', match[1]);
      if (!btn.hasAttribute('data-confirm-title')) {
        btn.setAttribute('data-confirm-title', 'Confirmação');
      }
      if (!btn.hasAttribute('data-confirm-type')) {
        btn.setAttribute('data-confirm-type', 'warning');
      }
    }
  });

  // Interceptor universal para formulários com data-confirm
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;

    const msg = form.getAttribute('data-confirm');
    if (msg) {
      if (form.dataset.confirmed === 'true') {
        delete form.dataset.confirmed;
        return;
      }
      e.preventDefault();
      e.stopPropagation();

      const titulo = form.getAttribute('data-confirm-title') || 'Confirmação Operacional';
      const tipo = form.getAttribute('data-confirm-type') || 'danger';
      const btn = form.getAttribute('data-confirm-btn') || 'Confirmar';

      window.confirmarAcao({
        titulo: titulo,
        mensagem: msg,
        tipo: tipo,
        textoConfirmar: btn
      }).then(function (confirmado) {
        if (confirmado) {
          form.dataset.confirmed = 'true';
          if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
          } else {
            form.submit();
          }
        }
      });
    }
  }, true);

  // Interceptor universal para links e botões com data-confirm
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-confirm]');
    if (!trigger) return;
    if (trigger.tagName === 'BUTTON' && trigger.type === 'submit' && trigger.form && trigger.form.hasAttribute('data-confirm')) {
      return;
    }

    const msg = trigger.getAttribute('data-confirm');
    if (msg) {
      if (trigger.dataset.confirmed === 'true') {
        delete trigger.dataset.confirmed;
        return;
      }
      e.preventDefault();
      e.stopPropagation();

      const titulo = trigger.getAttribute('data-confirm-title') || 'Confirmação';
      const tipo = trigger.getAttribute('data-confirm-type') || 'warning';
      const btn = trigger.getAttribute('data-confirm-btn') || 'Confirmar';

      window.confirmarAcao({
        titulo: titulo,
        mensagem: msg,
        tipo: tipo,
        textoConfirmar: btn
      }).then(function (confirmado) {
        if (confirmado) {
          trigger.dataset.confirmed = 'true';
          if (trigger.tagName === 'A' && trigger.href) {
            window.location.href = trigger.href;
          } else if (trigger.tagName === 'BUTTON' && trigger.type === 'submit' && trigger.form) {
            trigger.form.submit();
          }
        }
      });
    }
  }, true);
}


