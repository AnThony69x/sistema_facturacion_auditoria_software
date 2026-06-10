// assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
  // ── Feather Icons ───────────────────────────────────
  if (typeof feather !== 'undefined') feather.replace({ 'stroke-width': 1.8, width: 16, height: 16 });

  // ── Sidebar Mobile Toggle ───────────────────────────
  const menuToggle    = document.getElementById('menuToggle');
  const sidebar       = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      sidebarOverlay.classList.toggle('show');
    });
    sidebarOverlay?.addEventListener('click', () => {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('show');
    });
  }

  // ── Auto-dismiss flash alerts ───────────────────────
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => el.style.opacity = '0', 4000);
    setTimeout(() => el.remove(), 4300);
  });

  // ── Upload Preview ──────────────────────────────────
  document.querySelectorAll('[data-upload-preview]').forEach(input => {
    input.addEventListener('change', function () {
      const previewId = this.dataset.uploadPreview;
      const preview = document.getElementById(previewId);
      if (preview && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(this.files[0]);
      }
    });
  });

  // ── Confirm Delete ──────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
    });
  });

  // ── Modal ───────────────────────────────────────────
  document.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.modalOpen;
      document.getElementById(id)?.classList.add('show');
      document.getElementById(id)?.style.setProperty('display', 'flex');
    });
  });
  document.querySelectorAll('[data-modal-close], .modal-overlay').forEach(el => {
    el.addEventListener('click', function (e) {
      if (e.target === this || this.hasAttribute('data-modal-close')) {
        this.closest('.modal-overlay')?.style.setProperty('display', 'none');
      }
    });
  });
});

/* ── Invoice Builder ───────────────────────────────────── */
const InvoiceBuilder = {
  items: [],

  addProduct(id, nombre, precio, iva) {
    const existing = this.items.find(i => i.id === id);
    if (existing) {
      existing.qty++;
    } else {
      this.items.push({ id, nombre, precio: parseFloat(precio), iva: parseFloat(iva), qty: 1, descuento: 0 });
    }
    this.render();
  },

  remove(id) {
    this.items = this.items.filter(i => i.id !== id);
    this.render();
  },

  updateQty(id, qty) {
    const item = this.items.find(i => i.id === id);
    if (item) { item.qty = Math.max(1, parseFloat(qty) || 1); this.render(); }
  },

  updateDesc(id, desc) {
    const item = this.items.find(i => i.id === id);
    if (item) { item.descuento = Math.max(0, parseFloat(desc) || 0); this.render(); }
  },

  calcItem(item) {
    const subtotal = item.precio * item.qty;
    const desc     = item.descuento;
    const base     = subtotal - desc;
    const ivaVal   = base * (item.iva / 100);
    return { subtotal, desc, base, ivaVal, total: base + ivaVal };
  },

  render() {
    const tbody   = document.getElementById('invoiceLines');
    const totals  = document.getElementById('invoiceTotals');
    if (!tbody) return;

    let sumBase = 0, sumIva = 0, sumDesc = 0;

    tbody.innerHTML = this.items.map((item, idx) => {
      const calc = this.calcItem(item);
      sumBase += calc.base;
      sumIva  += calc.ivaVal;
      sumDesc += calc.desc;
      return `
        <tr>
          <td>${item.nombre}</td>
          <td><input type="number" class="form-control-sm" value="${item.qty}" min="0.01" step="0.01"
              onchange="InvoiceBuilder.updateQty(${item.id}, this.value)" style="width:80px"></td>
          <td>$${item.precio.toFixed(2)}</td>
          <td><input type="number" class="form-control-sm" value="${item.descuento}" min="0" step="0.01"
              onchange="InvoiceBuilder.updateDesc(${item.id}, this.value)" style="width:80px"></td>
          <td>${item.iva}%</td>
          <td>$${calc.total.toFixed(2)}</td>
          <td><button type="button" class="product-row-delete" onclick="InvoiceBuilder.remove(${item.id})">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
          </button></td>
          <input type="hidden" name="items[${idx}][producto_id]" value="${item.id}">
          <input type="hidden" name="items[${idx}][cantidad]"    value="${item.qty}">
          <input type="hidden" name="items[${idx}][precio]"      value="${item.precio}">
          <input type="hidden" name="items[${idx}][descuento]"   value="${item.descuento}">
          <input type="hidden" name="items[${idx}][iva]"         value="${item.iva}">
        </tr>`;
    }).join('');

    const total = sumBase + sumIva;
    if (totals) {
      totals.innerHTML = `
        <div class="totals-row"><span>Subtotal 0%</span><span>$${(sumBase - sumIva > 0 ? sumBase - sumIva : 0).toFixed(2)}</span></div>
        <div class="totals-row"><span>Subtotal ${document.getElementById('ivaRate')?.value || 15}% IVA</span><span>$${sumBase.toFixed(2)}</span></div>
        <div class="totals-row"><span>Descuento</span><span>-$${sumDesc.toFixed(2)}</span></div>
        <div class="totals-row"><span>IVA</span><span>$${sumIva.toFixed(2)}</span></div>
        <div class="totals-row total-final"><span>TOTAL</span><span>$${total.toFixed(2)}</span></div>`;
    }

    // Hidden totals for form submit
    ['subtotal_sin_iva','subtotal_con_iva','descuento_total','iva_total','total'].forEach(id => {
      const el = document.getElementById('h_' + id);
      if (!el) return;
      if (id === 'subtotal_sin_iva') el.value = (sumBase - sumIva).toFixed(4);
      if (id === 'subtotal_con_iva') el.value = sumBase.toFixed(4);
      if (id === 'descuento_total')  el.value = sumDesc.toFixed(4);
      if (id === 'iva_total')        el.value = sumIva.toFixed(4);
      if (id === 'total')            el.value = total.toFixed(4);
    });
  }
};

/* ── Product Search Autocomplete ────────────────────────── */
function setupProductSearch() {
  const input   = document.getElementById('productSearch');
  const results = document.getElementById('productResults');
  if (!input) return;

  let timeout;
  input.addEventListener('input', function () {
    clearTimeout(timeout);
    const q = this.value.trim();
    if (q.length < 2) { results.innerHTML = ''; results.style.display = 'none'; return; }
    timeout = setTimeout(() => {
      fetch(`${BASE_URL}ajax/productos_search.php?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
          if (!data.length) { results.style.display = 'none'; return; }
          results.innerHTML = data.map(p => `
            <div class="autocomplete-item" onclick="InvoiceBuilder.addProduct(${p.id},'${p.nombre.replace(/'/g,"\\'")}',${p.precio_venta},${p.iva})">
              <img src="${UPLOAD_URL}productos/${p.foto}" class="img-thumb" onerror="this.src='${BASE_URL}assets/img/default_product.png'">
              <div>
                <div style="font-weight:600">${p.nombre}</div>
                <div style="font-size:11px;color:var(--text-muted)">Stock: ${p.existencia} | $${parseFloat(p.precio_venta).toFixed(2)}</div>
              </div>
            </div>`).join('');
          results.style.display = 'block';
        });
    }, 300);
  });

  document.addEventListener('click', e => {
    if (!input.contains(e.target) && !results.contains(e.target)) {
      results.style.display = 'none';
    }
  });
}

document.addEventListener('DOMContentLoaded', setupProductSearch);
