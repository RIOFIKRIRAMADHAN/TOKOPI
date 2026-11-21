/**
 * Tokopi Cart Module
 * Shared cart logic for customer portal
 * Uses localStorage with namespace 'tokopi_cart'
 */

const CART_KEY = 'tokopi_cart';
const CART_EVENT = 'tokopi:cart:changed';

class Cart {
  constructor() {
    this.cart = this.load();
    this.listeners = new Set();
  }

  load() {
    try {
      const data = localStorage.getItem(CART_KEY);
      return data ? JSON.parse(data) : {};
    } catch {
      return {};
    }
  }

  save() {
    localStorage.setItem(CART_KEY, JSON.stringify(this.cart));
    this.emit();
  }

  emit() {
    const event = new CustomEvent(CART_EVENT, { detail: this.getState() });
    window.dispatchEvent(event);
    this.listeners.forEach(fn => fn(this.getState()));
  }

  subscribe(fn) {
    this.listeners.add(fn);
    return () => this.listeners.delete(fn);
  }

  getState() {
    const items = Object.values(this.cart);
    const count = items.reduce((sum, item) => sum + (item.qty || 0), 0);
    const subtotal = items.reduce((sum, item) => sum + (item.price * item.qty), 0);
    return { items, count, subtotal, raw: { ...this.cart } };
  }

  // Add item to cart
  add(variantId, data) {
    const key = String(variantId);
    const existing = this.cart[key];
    if (existing) {
      existing.qty = Math.min(999, existing.qty + (data.qty || 1));
    } else {
      this.cart[key] = {
        variant_id: variantId,
        product_id: data.product_id,
        product_nama: data.product_nama,
        variant_nama: data.variant_nama,
        price: data.price,
        qty: data.qty || 1,
        image: data.image || null,
        notes: data.notes || '',
        modifiers: data.modifiers || [],
      };
    }
    this.save();
    return this.getState();
  }

  // Update quantity
  setQty(variantId, qty) {
    const key = String(variantId);
    if (qty <= 0) {
      delete this.cart[key];
    } else if (this.cart[key]) {
      this.cart[key].qty = Math.min(999, qty);
    }
    this.save();
    return this.getState();
  }

  // Increment/decrement
  increment(variantId) {
    const key = String(variantId);
    if (this.cart[key]) {
      this.cart[key].qty = Math.min(999, this.cart[key].qty + 1);
      this.save();
    }
    return this.getState();
  }

  decrement(variantId) {
    const key = String(variantId);
    if (this.cart[key]) {
      this.cart[key].qty = Math.max(1, this.cart[key].qty - 1);
      if (this.cart[key].qty === 1) {
        // keep at 1, let user explicitly remove
      }
      this.save();
    }
    return this.getState();
  }

  // Remove item
  remove(variantId) {
    delete this.cart[String(variantId)];
    this.save();
    return this.getState();
  }

  // Clear cart
  clear() {
    this.cart = {};
    this.save();
    return this.getState();
  }

  // Get single item
  get(variantId) {
    return this.cart[String(variantId)] || null;
  }

  // Check if in cart
  has(variantId) {
    return String(variantId) in this.cart;
  }

  // Format price helper
  static formatPrice(amount) {
    return 'Rp ' + Number(amount).toLocaleString('id-ID');
  }

  // Generate unique client_request_id for idempotency
  static generateRequestId() {
    return 'cart_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11);
  }
}

// Singleton instance
window.TokopiCart = new Cart();

// Auto-update badge on any page with cart badge element
window.addEventListener(CART_EVENT, () => {
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

// Initialize badge on load
document.addEventListener('DOMContentLoaded', () => {
  const badge = document.getElementById('cart-badge');
  if (badge) {
    const { count } = window.TokopiCart.getState();
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'flex';
    }
  }
});

// Sync across tabs
window.addEventListener('storage', (e) => {
  if (e.key === CART_KEY) {
    window.TokopiCart.cart = window.TokopiCart.load();
    window.TokopiCart.emit();
  }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { Cart, CART_KEY, CART_EVENT };
}
// ---------------------------------------------------------------
// Bar keranjang: tombol bulat .cart-float diubah jadi bar
// "N item · Lihat keranjang · Rp X" yang hanya muncul bila ada isi.
// ---------------------------------------------------------------
(function () {
  function stateFromStorage() {
    var raw = {};
    try { raw = JSON.parse(localStorage.getItem(CART_KEY) || '{}'); } catch (e) {}
    var items = Object.values(raw), count = 0, total = 0;
    items.forEach(function (it) {
      var q = Number(it.qty) || 0;
      count += q;
      total += (Number(it.harga != null ? it.harga : it.price) || 0) * q;
    });
    return { count: count, total: total };
  }
  function render() {
    var el = document.querySelector('.cart-float');
    if (!el) return;
    var s = stateFromStorage();
    if (!el.querySelector('.cb-label')) {
      el.insertAdjacentHTML('beforeend', '<span class="cb-label">Lihat keranjang</span><span class="cb-total"></span>');
    }
    el.classList.toggle('has-items', s.count > 0);
    document.body.classList.toggle('has-cart-bar', s.count > 0);
    el.querySelector('.cb-total').textContent = 'Rp ' + Math.round(s.total).toLocaleString('id-ID');
    el.setAttribute('aria-label', 'Lihat keranjang, ' + s.count + ' item, total Rp ' + Math.round(s.total).toLocaleString('id-ID'));
    var badge = document.getElementById('cart-badge');
    if (badge) { badge.textContent = s.count > 99 ? '99+' : s.count; badge.style.display = s.count > 0 ? 'flex' : 'none'; }
  }
  window.addEventListener(CART_EVENT, render);
  window.addEventListener('storage', render);
  document.addEventListener('DOMContentLoaded', render);
  window.tokopiRenderCartBar = render;
})();
