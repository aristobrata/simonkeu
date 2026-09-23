/* SIMONKEU — perilaku umum: sidebar, konfirmasi hapus, format uang pada input */
(function () {
  'use strict';

  // Sidebar (mobile)
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-toggle-nav]')) { document.body.classList.toggle('nav-open'); return; }
    if (document.body.classList.contains('nav-open') && !e.target.closest('.sidebar')) document.body.classList.remove('nav-open');
  });

  // Konfirmasi (modal Bootstrap) untuk tombol dengan data-confirm di dalam <form>
  var modalEl = document.getElementById('confirmModal');
  if (modalEl && window.bootstrap) {
    var modal = new bootstrap.Modal(modalEl);
    var pending = null;
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-confirm]');
      if (!btn) return;
      e.preventDefault();
      pending = btn.closest('form');
      modalEl.querySelector('[data-confirm-text]').textContent = btn.getAttribute('data-confirm');
      var ok = modalEl.querySelector('[data-confirm-ok]');
      ok.textContent = btn.getAttribute('data-confirm-label') || 'Hapus';
      modal.show();
    });
    modalEl.querySelector('[data-confirm-ok]').addEventListener('click', function () { if (pending) pending.submit(); });
  }

  // Input uang: tampil "1.234.567", kirim angka murni
  window.Simonkeu = window.Simonkeu || {};
  Simonkeu.parseMoney = function (v) {
    v = String(v == null ? '' : v).replace(/[^0-9,\-]/g, '').replace(',', '.');
    var n = parseFloat(v);
    return isNaN(n) ? 0 : n;
  };
  Simonkeu.fmtMoney = function (n) {
    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(n || 0));
  };
  document.querySelectorAll('input[data-money]').forEach(function (inp) {
    var fmt = function () { if (inp.value.trim() === '') return; inp.value = Simonkeu.fmtMoney(Simonkeu.parseMoney(inp.value)); };
    inp.addEventListener('blur', fmt);
    inp.addEventListener('focus', function () { inp.select(); });
    fmt();
  });
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      f.querySelectorAll('input[data-money]').forEach(function (inp) {
        inp.value = inp.value.trim() === '' ? '' : String(Simonkeu.parseMoney(inp.value));
      });
    });
  });
})();
