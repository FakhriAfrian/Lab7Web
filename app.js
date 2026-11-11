/* app.js — peningkatan kecil di sisi browser (client-side).
   Semua validasi & perhitungan resmi tetap dilakukan oleh PHP di server;
   script ini hanya memberi kenyamanan (pratinjau, konfirmasi). */
"use strict";

document.addEventListener("DOMContentLoaded", function () {
  // 1. Tampilkan / sembunyikan password: <input type="checkbox" data-toggle-password="#idInput">
  document.querySelectorAll("[data-toggle-password]").forEach(function (cb) {
    cb.addEventListener("change", function () {
      var target = document.querySelector(cb.getAttribute("data-toggle-password"));
      if (target) target.type = cb.checked ? "text" : "password";
    });
  });

  // 2. Konfirmasi sebelum aksi berbahaya: <form data-konfirm="..."> atau <button data-konfirm-tombol="...">
  document.querySelectorAll("form[data-konfirm]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      if (!window.confirm(form.getAttribute("data-konfirm"))) e.preventDefault();
    });
  });
  document.querySelectorAll("[data-konfirm-tombol]").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      if (!window.confirm(btn.getAttribute("data-konfirm-tombol"))) e.preventDefault();
    });
  });

  // 3. Pratinjau ongkos kirim & total saat jenis paket dipilih (halaman pemesanan)
  var ringkasan = document.querySelector(".ringkasan[data-ongkir]");
  var paket = document.getElementById("paket");
  if (ringkasan && paket) {
    var tabel = JSON.parse(ringkasan.getAttribute("data-ongkir"));
    var subtotal = parseInt(ringkasan.getAttribute("data-subtotal"), 10) || 0;
    var diskon = parseInt(ringkasan.getAttribute("data-diskon"), 10) || 0;
    var adaItem = ringkasan.getAttribute("data-ada-item") === "1";
    var rp = function (n) { return "Rp " + Number(n).toLocaleString("id-ID"); };

    paket.addEventListener("change", function () {
      var ongkir = adaItem && tabel[paket.value] ? tabel[paket.value] : 0;
      document.getElementById("rOngkir").textContent = rp(ongkir);
      document.getElementById("rTotal").textContent = rp(subtotal - diskon + ongkir);
    });
  }
});
