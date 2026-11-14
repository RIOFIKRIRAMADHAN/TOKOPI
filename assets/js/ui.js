/**
 * Tokopi UI Utilities
 * Shared UI interactions: search filter, dropdown, modal, toast, etc.
 */

// =========================================
// Search & Filter (for menu pages)
// =========================================
function initSearchFilter(options = {}) {
  const {
    searchSelector = '#search-input',
    categorySelector = '.cat-pill',
    itemSelector = '.menu-item',
    categoryAttr = 'data-cat',
    nameAttr = 'data-name',
    emptySelector = '.empty-state.dynamic',
    listSelector = '#menu-list',
  } = options;

  const searchInput = document.querySelector(searchSelector);
  const catPills = document.querySelectorAll(categorySelector);
  const menuItems = document.querySelectorAll(itemSelector);
  const list = document.querySelector(listSelector);

  let activeCat = 'all';
  let searchQuery = '';

  function applyFilter() {
    let visibleCount = 0;
    menuItems.forEach(item => {
      const catMatch = activeCat === 'all' || item.dataset[categoryAttr.replace('data-', '')] === activeCat;
      const searchMatch = !searchQuery || (item.dataset[nameAttr.replace('data-', '')] || '').includes(searchQuery);
      const show = catMatch && searchMatch;
      item.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    // Dynamic empty state
    let empty = list?.querySelector(emptySelector);
    if (visibleCount === 0 && menuItems.length > 0) {
      if (!empty) {
        empty = document.createElement('div');
        empty.className = emptySelector.replace('.', '').replace('.', ' ') + ' dynamic';
        empty.innerHTML = '<i class="bi bi-search"></i><p>Tidak ada menu yang cocok.</p>';
        list?.appendChild(empty);
      }
      empty.style.display = 'flex';
    } else if (empty) {
      empty.style.display = 'none';
    }
  }

  catPills.forEach(pill => {
    pill.addEventListener('click', () => {
      catPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeCat = pill.dataset.cat || 'all';
      applyFilter();
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      searchQuery = e.target.value.trim().toLowerCase();
      applyFilter();
    });
  }

  return { applyFilter };
}

// =========================================
// Dropdown Toggle
// =========================================
function initDropdowns() {
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-dropdown-toggle]');
    if (toggle) {
      e.preventDefault();
      const dropdown = toggle.closest('.dropdown');
      if (dropdown) {
        // Close other dropdowns
        document.querySelectorAll('.dropdown.open').forEach(d => {
          if (d !== dropdown) d.classList.remove('open');
        });
        dropdown.classList.toggle('open');
      }
    } else if (!e.target.closest('.dropdown')) {
      // Close all when clicking outside
      document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    }
  });
}

// =========================================
// Modal / Offcanvas
// =========================================
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
    // Focus trap
    const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    focusable?.focus();
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}

// Close on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

// Close on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
  }
});

// =========================================
// Toast Notifications
// =========================================
const toasts = [];

function showToast(message, type = 'info', duration = 4000) {
  const container = getToastContainer();
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'polite');
  toast.innerHTML = `
    <div class="toast-content">
      <i class="bi bi-${getToastIcon(type)}"></i>
      <span>${escapeHtml(message)}</span>
    </div>
    <button class="toast-close" aria-label="Tutup"><i class="bi bi-x"></i></button>
  `;

  const icons = {
    success: 'check-circle-fill',
    error: 'x-octagon-fill',
    warning: 'exclamation-triangle-fill',
    info: 'info-circle-fill'
  };

  toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));
  container.appendChild(toast);

  // Animate in
  requestAnimationFrame(() => toast.classList.add('show'));

  // Auto remove
  const timer = setTimeout(() => removeToast(toast), duration);

  toasts.push({ toast, timer });
  return toast;
}

function getToastContainer() {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = `
      position: fixed; bottom: 20px; right: 20px; z-index: 9999;
      display: flex; flex-direction: column; gap: 8px; pointer-events: none;
    `;
    document.body.appendChild(container);
  }
  return container;
}

function getToastIcon(type) {
  const icons = { success: 'check-circle-fill', error: 'x-octagon-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
  return icons[type] || icons.info;
}

function removeToast(toast) {
  toast.classList.remove('show');
  toast.addEventListener('transitionend', () => toast.remove(), { once: true });
  const idx = toasts.findIndex(t => t.toast === toast);
  if (idx >= 0) {
    clearTimeout(toasts[idx].timer);
    toasts.splice(idx, 1);
  }
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// =========================================
// Skeleton Loading
// =========================================
function showSkeleton(container, count = 5, template = 'card') {
  const skeletons = {
    card: '<div class="skeleton skeleton-card"><div class="skeleton-text" style="width:100%;height:120px;margin-bottom:12px;border-radius:12px;"></div><div class="skeleton-text"></div><div class="skeleton-text" style="width:60%;"></div><div class="skeleton-text" style="width:40%;margin-top:16px;"></div></div>',
    row: '<div class="skeleton skeleton-text" style="height:16px;width:100%;"></div>',
    avatar: '<div class="skeleton skeleton-avatar"></div>',
    list: '<div class="skeleton skeleton-text" style="height:56px;width:100%;border-radius:8px;"></div>',
  };
  container.innerHTML = Array(count).fill(skeletons[template] || skeletons.card).join('');
}

function hideSkeleton(container, html) {
  container.innerHTML = html;
}

// =========================================
// Button Loading State
// =========================================
function setButtonLoading(btn, loading = true, loadingText = 'Memproses...') {
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner spinner-sm me-2"></span>${loadingText}`;
    btn.disabled = true;
  } else {
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    btn.disabled = false;
    delete btn.dataset.originalText;
  }
}

// =========================================
// Form Validation Helpers
// =========================================
function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validatePhone(phone) {
  // Indonesia phone format
  return /^(\+62|62|0)8[1-9][0-9]{7,10}$/.test(phone.replace(/[\s-]/g, ''));
}

function validateRequired(value) {
  return value !== null && value !== undefined && String(value).trim() !== '';
}

function showFieldError(input, message) {
  clearFieldError(input);
  input.classList.add('is-invalid');
  const feedback = document.createElement('div');
  feedback.className = 'invalid-feedback';
  feedback.textContent = message;
  input.parentNode.appendChild(feedback);
}

function clearFieldError(input) {
  input.classList.remove('is-invalid');
  const feedback = input.parentNode?.querySelector('.invalid-feedback');
  feedback?.remove();
}

function clearFormErrors(form) {
  form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
}

// =========================================
// Price Formatting
// =========================================
function formatRupiah(amount) {
  return 'Rp ' + Number(amount).toLocaleString('id-ID');
}

function parseRupiah(str) {
  return Number(String(str).replace(/[^0-9]/g, '')) || 0;
}

// =========================================
// Debounce
// =========================================
function debounce(fn, delay = 250) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

// =========================================
// Initialize on DOM ready
// =========================================
document.addEventListener('DOMContentLoaded', () => {
  initDropdowns();

  // Auto-init search filter if elements exist
  if (document.querySelector('#search-input') && document.querySelector('.cat-pill')) {
    initSearchFilter();
  }

  // Auto-init cart badge sync
  if (window.TokopiCart) {
    window.addEventListener('tokopi:cart:changed', () => {
      const badge = document.getElementById('cart-badge');
      if (badge) {
        const { count } = window.TokopiCart.getState();
        if (count > 0) {
          badge.textContent = count > 99 ? '99+' : count;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
      }
    });
  }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    initSearchFilter,
    initDropdowns,
    openModal,
    closeModal,
    showToast,
    showSkeleton,
    hideSkeleton,
    setButtonLoading,
    validateEmail,
    validatePhone,
    validateRequired,
    showFieldError,
    clearFieldError,
    clearFormErrors,
    formatRupiah,
    parseRupiah,
    debounce,
  };
}