/* Form transaksi: total otomatis, periode dari tanggal, petunjuk aturan biaya */
(function () {
  'use strict';
  var $ = function (id) { return document.getElementById(id); };
  var komponen = document.querySelectorAll('.js-komponen');
  var totalEl = $('totalBiaya');
  var realisasi = $('realisasi_anggaran');
  var rencana = $('rencana_anggaran');

  function hitung() {
    var t = 0;
    komponen.forEach(function (i) { t += Simonkeu.parseMoney(i.value); });
    totalEl.textContent = 'Rp ' + Simonkeu.fmtMoney(t);
    // Kolom anggaran kosong = otomatis; tampilkan nilainya sebagai placeholder.
    realisasi.placeholder = 'otomatis: ' + Simonkeu.fmtMoney(t);
    var r = realisasi.value.trim() === '' ? t : Simonkeu.parseMoney(realisasi.value);
    rencana.placeholder = 'otomatis: ' + Simonkeu.fmtMoney(r);
  }
  komponen.forEach(function (i) { i.addEventListener('input', hitung); i.addEventListener('blur', hitung); });
  realisasi.addEventListener('input', hitung);
  hitung();

  // Bulan & tahun laporan mengikuti tanggal mulai selama belum diubah manual.
  var bulan = $('periode_bulan'), tahun = $('periode_tahun'), mulai = $('tgl_mulai');
  var manual = false;
  [bulan, tahun].forEach(function (el) { el.addEventListener('change', function () { manual = true; }); });
  mulai.addEventListener('change', function () {
    if (manual || !mulai.value) return;
    var p = mulai.value.split('-');
    tahun.value = p[0];
    bulan.value = String(parseInt(p[1], 10));
  });

  // Petunjuk aturan pengisian (dari catatan pada template Excel)
  var pel = $('pelaksanaan_id'), box = $('petunjukAturan');
  var aturan = {
    'public': 'Public: tidak ada biaya materi, konsumsi, dan perlengkapan.',
    'in house': 'In House dengan instruktur eksternal: tidak ada biaya materi dan perlengkapan. ' +
                'In House dengan instruktur internal: tidak ada biaya tiket, hotel, dan transportasi.'
  };
  function petunjuk() {
    var nama = pel.options[pel.selectedIndex] ? pel.options[pel.selectedIndex].text.toLowerCase().trim() : '';
    if (aturan[nama]) { box.textContent = aturan[nama]; box.hidden = false; } else { box.hidden = true; }
  }
  pel.addEventListener('change', petunjuk);
  petunjuk();
})();
