/* Tokopi admin – tabel: cari, urutkan, halaman.
   Pakai: <table data-enhance data-page-size="25"> + <input data-table-filter="#id"> */
(function () {
  function txt(td) { return (td ? (td.dataset.sort || td.textContent) : '').trim(); }
  function num(s) {
    var n = s.replace(/rp|\s|%/gi, '').replace(/\./g, '').replace(',', '.');
    return /^-?\d+(\.\d+)?$/.test(n) ? parseFloat(n) : null;
  }
  document.querySelectorAll('table[data-enhance]').forEach(function (table) {
    var tbody = table.tBodies[0]; if (!tbody) return;
    var rows = Array.prototype.slice.call(tbody.rows).filter(function (r) { return r.cells.length > 1 || !r.querySelector('[colspan]'); });
    if (!rows.length) return;
    var size = parseInt(table.dataset.pageSize || '25', 10), page = 1, query = '', sortCol = -1, sortDir = 1;
    var filtered = rows.slice();

    var host = table.closest('.table-responsive') || table;
    var pager = document.createElement('nav');
    pager.className = 'd-flex justify-content-between align-items-center gap-2 flex-wrap px-3 py-2 border-top small';
    pager.setAttribute('aria-label', 'Navigasi halaman tabel');
    host.parentNode.insertBefore(pager, host.nextSibling);

    function render() {
      var pages = Math.max(1, Math.ceil(filtered.length / size));
      if (page > pages) page = pages;
      rows.forEach(function (r) { r.style.display = 'none'; });
      filtered.forEach(function (r, i) {
        tbody.appendChild(r);
        if (i >= (page - 1) * size && i < page * size) r.style.display = '';
      });
      var from = filtered.length ? (page - 1) * size + 1 : 0, to = Math.min(page * size, filtered.length);
      pager.innerHTML = '<span class="text-muted">' + from + '–' + to + ' dari ' + filtered.length + (query ? ' (difilter)' : '') + '</span>' +
        (pages > 1 ? '<div class="btn-group btn-group-sm">' +
          '<button type="button" class="btn btn-outline-espresso" data-p="prev" ' + (page <= 1 ? 'disabled' : '') + ' aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>' +
          '<span class="btn btn-outline-espresso disabled num">' + page + ' / ' + pages + '</span>' +
          '<button type="button" class="btn btn-outline-espresso" data-p="next" ' + (page >= pages ? 'disabled' : '') + ' aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>' +
        '</div>' : '');
      pager.style.display = filtered.length <= size && !query ? 'none' : '';
    }
    pager.addEventListener('click', function (e) {
      var b = e.target.closest('[data-p]'); if (!b) return;
      page += b.dataset.p === 'next' ? 1 : -1; render();
    });

    function apply() {
      var q = query.toLowerCase();
      filtered = rows.filter(function (r) { return !q || r.textContent.toLowerCase().indexOf(q) !== -1; });
      if (sortCol >= 0) {
        filtered.sort(function (a, b) {
          var x = txt(a.cells[sortCol]), y = txt(b.cells[sortCol]), nx = num(x), ny = num(y);
          if (nx !== null && ny !== null) return (nx - ny) * sortDir;
          return x.localeCompare(y, 'id', { numeric: true }) * sortDir;
        });
      }
      render();
    }

    // Sort
    var ths = table.tHead ? table.tHead.rows[0].cells : [];
    Array.prototype.forEach.call(ths, function (th, i) {
      if (th.hasAttribute('data-nosort') || !th.textContent.trim()) return;
      th.style.cursor = 'pointer'; th.tabIndex = 0; th.setAttribute('aria-sort', 'none');
      th.title = 'Klik untuk mengurutkan';
      var go = function () {
        sortDir = sortCol === i ? -sortDir : 1; sortCol = i;
        Array.prototype.forEach.call(ths, function (h) { h.setAttribute('aria-sort', 'none'); h.classList.remove('sorted-asc', 'sorted-desc'); });
        th.setAttribute('aria-sort', sortDir > 0 ? 'ascending' : 'descending');
        th.classList.add(sortDir > 0 ? 'sorted-asc' : 'sorted-desc');
        apply();
      };
      th.addEventListener('click', go);
      th.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); } });
    });

    // Search
    if (table.id) {
      document.querySelectorAll('[data-table-filter="#' + table.id + '"]').forEach(function (inp) {
        inp.dataset.enhanced = '1';
        var t; inp.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { query = inp.value.trim(); page = 1; apply(); }, 120); });
      });
    }
    render();
  });
})();
