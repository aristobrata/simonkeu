/* SIMONKEU — dashboard: memuat data JSON dan menggambar diagram (Chart.js) */
(function () {
  'use strict';

  var COLORS = ['#0B7A75', '#E8A317', '#3B5B92', '#B5412B', '#7BA05B', '#7A4E7E', '#4FA3C7', '#B8A07E', '#56616E', '#D9784A'];
  var TEAL = '#0B7A75', GOLD = '#F0C860', INK2 = '#4A5563', LINE = '#E3E6EA';
  var BULAN_PENUH = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  var $ = function (id) { return document.getElementById(id); };
  var charts = {};
  var nf = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
  var nf1 = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
  var nf2 = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function rp(n) { return (n < 0 ? '-' : '') + 'Rp ' + nf.format(Math.abs(Math.round(n || 0))); }
  /** 3.180.327.735 -> "Rp 3,18 M" ; 288.443.525 -> "Rp 288,4 jt" */
  function rpC(n) {
    var a = Math.abs(n || 0), s = n < 0 ? '-' : '';
    if (a >= 1e12) return s + 'Rp ' + nf2.format(a / 1e12) + ' T';
    if (a >= 1e9) return s + 'Rp ' + nf2.format(a / 1e9) + ' M';
    if (a >= 1e6) return s + 'Rp ' + nf1.format(a / 1e6) + ' jt';
    if (a >= 1e3) return s + 'Rp ' + nf.format(a / 1e3) + ' rb';
    return s + 'Rp ' + nf.format(a);
  }
  function short(n) { return rpC(n).replace('Rp ', ''); }
  function axis(v) {
    var a = Math.abs(v), f = function (x) { return x.toLocaleString('id-ID', { maximumFractionDigits: 1 }); };
    if (a >= 1e9) return f(v / 1e9) + ' M';
    if (a >= 1e6) return f(v / 1e6) + ' jt';
    if (a >= 1e3) return f(v / 1e3) + ' rb';
    return String(v);
  }
  function pct(n, d) { return nf1.format(n) + (d === false ? '' : '%'); }
  function trunc(s, n) { s = String(s || ''); return s.length > n ? s.slice(0, n - 1).trimEnd() + '…' : s; }
  function setK(k, v, html) {
    document.querySelectorAll('[data-k="' + k + '"]').forEach(function (el) { if (html) el.innerHTML = v; else el.textContent = v; });
  }

  // ---- Konfigurasi dasar Chart.js
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = INK2;
  Chart.defaults.animation.duration = 500;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.boxWidth = 8;
  Chart.defaults.plugins.tooltip.backgroundColor = '#18212B';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 6;
  Chart.defaults.plugins.tooltip.titleFont = { weight: '700' };
  Chart.defaults.maintainAspectRatio = false;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) Chart.defaults.animation = false;

  // Plugin: angka di ujung batang horizontal
  var valueLabels = {
    id: 'valueLabels',
    afterDatasetsDraw: function (chart) {
      var ctx = chart.ctx, ds = chart.data.datasets[0], meta = chart.getDatasetMeta(0);
      ctx.save();
      ctx.fillStyle = INK2; ctx.font = '600 11px "Plus Jakarta Sans", sans-serif'; ctx.textBaseline = 'middle'; ctx.textAlign = 'left';
      meta.data.forEach(function (bar, i) { ctx.fillText(short(ds.data[i]), bar.x + 6, bar.y); });
      ctx.restore();
    }
  };

  function make(id, cfg) {
    if (charts[id]) { charts[id].destroy(); }
    charts[id] = new Chart($(id), cfg);
    return charts[id];
  }
  var gridY = { color: LINE, drawBorder: false };

  // ---- Render
  function renderKpi(d) {
    var k = d.kpi, f = d.filter;
    var periode = f.dari === f.sampai ? BULAN_PENUH[f.dari] + ' ' + f.tahun : BULAN_PENUH[f.dari].slice(0, 3) + '–' + BULAN_PENUH[f.sampai].slice(0, 3) + ' ' + f.tahun;
    setK('periode', periode);
    $('subHeading').textContent = 'Periode ' + BULAN_PENUH[f.dari] + (f.dari === f.sampai ? '' : ' – ' + BULAN_PENUH[f.sampai]) + ' ' + f.tahun + ' · nilai dalam rupiah';

    var over = k.serapan > 100.05;
    $('serapanWrap').classList.toggle('over', over);
    $('budgetBar').classList.toggle('over', over);
    setK('serapan', k.anggaran > 0 ? nf1.format(k.serapan) : '–');
    $('budgetBar').firstElementChild.style.width = Math.min(100, Math.max(0, k.serapan)) + '%';
    setK('realisasi_full', rp(k.realisasi));
    setK('anggaran_full', rp(k.anggaran));

    setK('realisasi_c', rpC(k.realisasi));
    setK('rata_sub', k.bulan_berdata ? 'Rata-rata ' + rpC(k.rata_bulanan) + ' per bulan' : '');
    setK('sisa_c', rpC(k.sisa));
    setK('sisa_sub', 'Rencana ' + rpC(k.rencana) + (k.tambahan > 0 ? ' + tambahan ' + rpC(k.tambahan) : ''));
    setK('jumlah', nf.format(k.jumlah));
    setK('jumlah_sub', k.peserta > 0 ? nf.format(k.peserta) + ' peserta tercatat' : nf.format(d.jenis.length) + ' jenis aktivitas');

    var bl = k.bulan_terakhir;
    setK('bl_label', bl ? 'Bulan ' + BULAN_PENUH[bl.bulan] : 'Bulan terakhir');
    setK('bl_c', bl ? rpC(bl.realisasi) : '–');
    var dl = document.querySelector('[data-k="bl_delta"]');
    if (k.perubahan_pct != null) {
      var naik = k.perubahan_pct > 0;
      dl.hidden = false;
      dl.className = 'delta ' + (naik ? 'up' : 'down');
      dl.innerHTML = '<i class="bi bi-arrow-' + (naik ? 'up' : 'down') + '-short"></i>' + nf1.format(Math.abs(k.perubahan_pct)) + '%';
      dl.title = 'Dibanding ' + k.bulan_sebelum.label;
    } else { dl.hidden = true; }
    setK('bl_sub', k.bulan_puncak ? 'Tertinggi: ' + k.bulan_puncak.label + ' (' + rpC(k.bulan_puncak.realisasi) + ')' : '');

    var strip = $('alertStrip');
    if (k.perlu_diperiksa > 0) {
      strip.hidden = false;
      strip.querySelector('span').innerHTML = '<b>' + nf.format(k.perlu_diperiksa) + ' transaksi</b> memiliki catatan yang perlu diperiksa (mis. realisasi tidak sama dengan total biaya). <a href="' + SIMONKEU.baseUrl + '/validasi?tahun=' + d.filter.tahun + '">Lihat rinciannya</a>';
    } else { strip.hidden = true; }
  }

  function renderBulanan(d) {
    var b = d.bulanan, labels = b.map(function (x) { return x.label; });
    make('chBulanan', {
      type: 'bar',
      data: { labels: labels, datasets: [
        { label: 'Anggaran', data: b.map(function (x) { return x.anggaran; }), backgroundColor: GOLD, borderRadius: 3, categoryPercentage: .72, barPercentage: .92 },
        { label: 'Realisasi', data: b.map(function (x) { return x.realisasi; }), backgroundColor: TEAL, borderRadius: 3, categoryPercentage: .72, barPercentage: .92 }
      ] },
      options: {
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: {
          label: function (c) { return ' ' + c.dataset.label + ': ' + rp(c.parsed.y); },
          afterBody: function (items) { var x = b[items[0].dataIndex]; return x.anggaran > 0 ? ['', 'Serapan ' + pct(x.realisasi / x.anggaran * 100) + ' · ' + nf.format(x.jumlah) + ' transaksi'] : []; }
        } } },
        scales: { x: { grid: { display: false } }, y: { grid: gridY, border: { display: false }, ticks: { callback: axis } } }
      }
    });
  }

  function renderKum(d) {
    var b = d.bulanan;
    make('chKum', {
      type: 'line',
      data: { labels: b.map(function (x) { return x.label; }), datasets: [
        { label: 'Anggaran', data: b.map(function (x) { return x.kum_anggaran; }), borderColor: '#D39A0F', borderDash: [6, 4], borderWidth: 2, pointRadius: 0, pointStyle: 'line', tension: .2, spanGaps: false },
        { label: 'Realisasi', data: b.map(function (x) { return x.kum_realisasi; }), borderColor: TEAL, backgroundColor: 'rgba(11,122,117,.12)', fill: 'origin', borderWidth: 2.5, pointRadius: 3, pointStyle: 'line', pointBackgroundColor: TEAL, tension: .2, spanGaps: false }
      ] },
      options: {
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top', align: 'end' }, tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + rp(c.parsed.y); } } } },
        scales: { x: { grid: { display: false } }, y: { grid: gridY, border: { display: false }, beginAtZero: true, ticks: { callback: axis } } }
      }
    });
  }

  function renderJenis(d) {
    var j = d.jenis.filter(function (x) { return x.realisasi > 0; });
    var total = j.reduce(function (s, x) { return s + x.realisasi; }, 0);
    make('chJenis', {
      type: 'doughnut',
      data: { labels: j.map(function (x) { return x.nama; }), datasets: [{ data: j.map(function (x) { return x.realisasi; }), backgroundColor: j.map(function (_, i) { return COLORS[i % COLORS.length]; }), borderWidth: 2, borderColor: '#fff', hoverOffset: 4 }] },
      options: { cutout: '66%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + rp(c.parsed) + ' (' + pct(c.parsed / total * 100) + ')'; } } } } }
    });
    $('legendJenis').innerHTML = j.map(function (x, i) {
      return '<li><span class="sw" style="background:' + COLORS[i % COLORS.length] + '"></span><span>' + esc(x.nama) + '</span><b>' + rpC(x.realisasi).replace('Rp ', '') + '</b><span class="pc">' + pct(total ? x.realisasi / total * 100 : 0) + '</span></li>';
    }).join('');
  }

  function renderJenisBulan(d) {
    var jb = d.jenis_bulan;
    make('chJenisBulan', {
      type: 'bar',
      data: { labels: jb.labels, datasets: jb.seri.map(function (s, i) { return { label: s.nama, data: s.nilai, backgroundColor: s.nama === 'Lainnya' ? '#AEB6C0' : COLORS[i % COLORS.length], borderWidth: 0, categoryPercentage: .78, barPercentage: .95 }; }) },
      options: {
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top', align: 'start' }, tooltip: { itemSort: function (a, b) { return b.parsed.y - a.parsed.y; }, filter: function (i) { return i.parsed.y > 0; }, callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + rp(c.parsed.y); } } } },
        scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, grid: gridY, border: { display: false }, ticks: { callback: axis } } }
      }
    });
  }

  function hbar(id, labels, values, color, fullLabels) {
    make(id, {
      type: 'bar',
      data: { labels: labels, datasets: [{ data: values, backgroundColor: color, borderRadius: 3, barPercentage: .7 }] },
      options: {
        indexAxis: 'y', layout: { padding: { right: 64 } },
        plugins: { legend: { display: false }, tooltip: { callbacks: { title: function (i) { return fullLabels ? fullLabels[i[0].dataIndex] : i[0].label; }, label: function (c) { return ' ' + rp(c.parsed.x); } } } },
        scales: { x: { grid: gridY, border: { display: false }, ticks: { callback: axis } }, y: { grid: { display: false }, ticks: { autoSkip: false } } }
      },
      plugins: [valueLabels]
    });
  }

  function renderKomponen(d) {
    var k = d.komponen;
    hbar('chKomponen', k.map(function (x) { return x.label.replace(' / ', '/'); }), k.map(function (x) { return x.nilai; }), '#3B5B92');
  }
  function renderTop(d) {
    var t = d.top;
    hbar('chTop', t.map(function (x) { return trunc(x.aktivitas, 34); }), t.map(function (x) { return x.realisasi; }), TEAL, t.map(function (x) { return x.aktivitas; }));
  }

  function renderPelaksanaan(d) {
    var p = d.pelaksanaan.filter(function (x) { return x.realisasi > 0; });
    var total = p.reduce(function (s, x) { return s + x.realisasi; }, 0);
    var col = function (nama, i) { return nama === 'Tidak diisi' ? '#AEB6C0' : COLORS[i % COLORS.length]; };
    make('chPel', {
      type: 'bar',
      data: { labels: [''], datasets: p.map(function (x, i) { return { label: x.nama, data: [total ? x.realisasi / total * 100 : 0], backgroundColor: col(x.nama, i), borderWidth: 2, borderColor: '#fff', borderRadius: 4 }; }) },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + pct(c.parsed.x) + ' (' + rp(p[c.datasetIndex].realisasi) + ')'; } } } },
        scales: { x: { stacked: true, display: false, max: 100 }, y: { stacked: true, display: false } }
      }
    });
    $('legendPel').innerHTML = p.map(function (x, i) {
      return '<li><span class="sw" style="background:' + col(x.nama, i) + '"></span><span>' + esc(x.nama) + ' <span class="small-2">· ' + nf.format(x.jumlah) + ' transaksi</span></span><b>' + short(x.realisasi) + '</b><span class="pc">' + pct(total ? x.realisasi / total * 100 : 0) + '</span></li>';
    }).join('');
  }

  function renderStatus(d) {
    var s = (d.status || []).filter(function (x) { return x.jumlah > 0; });
    var total = s.reduce(function (sum, x) { return sum + x.jumlah; }, 0);
    var WARNA = { 'Belum': '#B5412B', 'Diproses': '#E8A317', 'Lunas': '#0B7A75', 'Belum diisi': '#AEB6C0' };
    var col = function (st) { return WARNA[st] || '#56616E'; };
    make('chStatus', {
      type: 'bar',
      data: { labels: [''], datasets: s.map(function (x) { return { label: x.status, data: [total ? x.jumlah / total * 100 : 0], backgroundColor: col(x.status), borderWidth: 2, borderColor: '#fff', borderRadius: 4 }; }) },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + nf.format(s[c.datasetIndex].jumlah) + ' transaksi (' + pct(c.parsed.x) + ')'; } } } },
        scales: { x: { stacked: true, display: false, max: 100 }, y: { stacked: true, display: false } }
      }
    });
    $('legendStatus').innerHTML = s.map(function (x) {
      return '<li><span class="sw" style="background:' + col(x.status) + '"></span><span>' + esc(x.status) + '</span><b>' + nf.format(x.jumlah) + '</b><span class="pc">' + pct(total ? x.jumlah / total * 100 : 0) + '</span></li>';
    }).join('') + (s.length ? '<li class="small-2 mt-1" style="grid-template-columns:1fr">Nilai realisasi: ' + s.map(function (x) { return esc(x.status) + ' ' + short(x.realisasi); }).join(' · ') + '</li>' : '');
  }

  function bars(el, rows, label) {
    var max = Math.max.apply(null, rows.map(function (r) { return r.realisasi; }).concat([1]));
    el.innerHTML = rows.filter(function (r) { return r.realisasi > 0; }).map(function (r) {
      return '<li><span class="nm" title="' + esc(label(r)) + '">' + esc(label(r)) + '</span><b class="num">' + rpC(r.realisasi) + '</b><span class="t"><i style="width:' + (r.realisasi / max * 100) + '%"></i></span></li>';
    }).join('') || '<li class="small-2">Tidak ada data.</li>';
  }

  function renderHeat(d) {
    var jb = d.jenis_bulan, rows = jb.matriks.filter(function (r) { return r.total > 0; });
    // Hanya bulan yang memiliki nilai, agar tabel tetap muat.
    var idx = [];
    jb.labels.forEach(function (_, i) { if (rows.some(function (r) { return r.nilai[i] > 0; })) idx.push(i); });
    var max = 0;
    rows.forEach(function (r) { idx.forEach(function (i) { if (r.nilai[i] > max) max = r.nilai[i]; }); });
    var colTot = idx.map(function (i) { return rows.reduce(function (s, r) { return s + r.nilai[i]; }, 0); });
    var grand = colTot.reduce(function (a, b) { return a + b; }, 0);
    var jt = function (v) { return v === 0 ? '–' : (v < 5e4 ? '<0,1' : nf1.format(v / 1e6)); };
    var h = '<thead><tr><th>Jenis aktivitas</th>' + idx.map(function (i) { return '<th>' + jb.labels[i] + '</th>'; }).join('') + '<th>Total</th></tr></thead><tbody>';
    rows.forEach(function (r) {
      h += '<tr><td>' + esc(r.nama) + '</td>' + idx.map(function (i) {
        var v = r.nilai[i];
        var a = v > 0 && max > 0 ? Math.min(1, Math.sqrt(v / max)) * .82 : 0;
        var style = a > 0 ? 'background:rgba(11,122,117,' + a.toFixed(2) + ');' + (a > .5 ? 'color:#fff;' : '') : 'color:#B4BBC4;';
        return '<td class="c" style="' + style + '">' + jt(v) + '</td>';
      }).join('') + '<td class="fw-bold">' + nf1.format(r.total / 1e6) + '</td></tr>';
    });
    h += '</tbody><tfoot><tr><td>Total</td>' + colTot.map(function (v) { return '<td>' + nf1.format(v / 1e6) + '</td>'; }).join('') + '<td>' + nf1.format(grand / 1e6) + '</td></tr></tfoot>';
    $('heat').innerHTML = h;
  }

  function renderAnggaranTahunan(d) {
    var panel = $('anggaranTahunanPanel'), list = $('anggaranTahunanList');
    var rows = d.anggaran_tahunan || [];
    if (!rows.length) { panel.hidden = true; return; }
    panel.hidden = false;
    list.innerHTML = rows.map(function (r) {
      var over = r.serapan > 100.05;
      var label = r.jenis_aktivitas_id === null
        ? '<span class="chip chip-gold">Semua jenis (total tahunan)</span>'
        : '<span class="chip chip-teal">' + esc(r.jenis_nama || '') + '</span>';
      return '' +
        '<div class="col-md-6 col-xl-4">' +
          '<div class="p-3" style="border:1px solid var(--line-soft);border-radius:8px;height:100%">' +
            '<div class="mb-2">' + label + '</div>' +
            '<div class="d-flex justify-content-between align-items-baseline">' +
              '<span class="small-2">Pagu ' + rpC(r.nominal) + '</span>' +
              '<span class="' + (over ? 'text-over fw-600' : 'fw-600') + '" style="font-size:.82rem">' + pct(r.serapan) + '</span>' +
            '</div>' +
            '<div class="budget-bar ' + (over ? 'over' : '') + '" style="margin:.4rem 0">' +
              '<i style="width:' + Math.min(100, Math.max(0, r.serapan)) + '%"></i>' +
            '</div>' +
            '<div class="d-flex justify-content-between small-2">' +
              '<span>Terpakai ' + rpC(r.realisasi) + '</span>' +
              '<span class="' + (r.sisa < 0 ? 'text-over' : '') + '">Sisa ' + rpC(r.sisa) + '</span>' +
            '</div>' +
          '</div>' +
        '</div>';
    }).join('');
  }

  function renderTerbaru(d) {
    var h = '<thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Jenis</th><th class="num">Rencana</th><th class="num">Realisasi</th></tr></thead><tbody>';
    d.terbaru.forEach(function (r) {
      var t = new Date(r.tgl + 'T00:00:00');
      var tgl = t.getDate() + ' ' + ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][t.getMonth()] + ' ' + t.getFullYear();
      h += '<tr><td class="text-nowrap">' + tgl + '</td><td><a class="cell-title text-body" href="' + SIMONKEU.baseUrl + '/transaksi/' + r.id + '">' + esc(r.aktivitas) + '</a></td>' +
        '<td><span class="chip chip-teal">' + esc(r.jenis) + '</span>' + (r.pelaksanaan ? ' <span class="chip">' + esc(r.pelaksanaan) + '</span>' : '') + '</td>' +
        '<td class="num">' + nf.format(r.rencana) + '</td><td class="num">' + nf.format(r.realisasi) + '</td></tr>';
    });
    $('tblTerbaru').innerHTML = h + '</tbody>';
  }

  function render(d) {
    var kosong = d.kpi.jumlah === 0;
    $('emptyState').hidden = !kosong;
    $('dash').hidden = kosong;
    if (kosong) { $('subHeading').textContent = 'Belum ada data untuk filter ini'; return; }
    renderKpi(d); renderBulanan(d); renderKum(d); renderJenis(d); renderJenisBulan(d);
    renderAnggaranTahunan(d);
    renderKomponen(d); renderTop(d); renderPelaksanaan(d); renderStatus(d);
    bars($('barsAkun'), d.akun, function (r) { return r.kode + ' · ' + r.nama; });
    bars($('barsCc'), d.cost_center, function (r) { return r.kode + (r.nama && r.nama.indexOf(r.kode) < 0 ? ' · ' + r.nama : ''); });
    renderHeat(d); renderTerbaru(d);
  }

  // ---- Filter & pemuatan data
  function params() {
    var p = new URLSearchParams();
    ['tahun', 'dari', 'sampai', 'jenis'].forEach(function (n) { var v = document.querySelector('#filterForm [name="' + n + '"]').value; if (v) p.set(n, v); });
    return p;
  }
  var seq = 0;
  function load() {
    var p = params(), my = ++seq;
    $('dash').style.opacity = .55;
    var lap = document.getElementById('lnkLaporan');
    lap.href = SIMONKEU.baseUrl + '/laporan?' + p.toString();
    history.replaceState(null, '', '?' + p.toString());
    fetch(SIMONKEU.dataUrl + '?' + p.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { if (r.status === 401) { location.href = SIMONKEU.loginUrl; throw new Error('sesi'); } if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (d) { if (my === seq) { render(d); } })
      .catch(function (e) { if (e.message !== 'sesi') { $('subHeading').textContent = 'Data gagal dimuat. Muat ulang halaman atau coba lagi.'; console.error(e); } })
      .finally(function () { if (my === seq) $('dash').style.opacity = 1; });
  }

  document.querySelectorAll('#filterForm select').forEach(function (s) { s.addEventListener('change', load); });
  $('btnReset').addEventListener('click', function () {
    $('fTahun').value = SIMONKEU.tahunDefault; $('fDari').value = 1; $('fSampai').value = 12; $('fJenis').value = '';
    load();
  });
  load();
})();
