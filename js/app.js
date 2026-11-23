'use strict';

document.addEventListener('DOMContentLoaded', () => {
  inisialisasiPengalihTema();
  inisialisasiPencarianDanFilter();
  inisialisasiValidasiForm();
  inisialisasiModalHapus();
  inisialisasiIntipSandi();
});

/* -------------------------------------------------------------
   1. Pengelolaan Tema Terang / Gelap (Disimpan di Cookie)
------------------------------------------------------------- */
function ambilCookie(nama) {
  const cocokan = document.cookie.match(new RegExp('(^|;\\s*)' + nama + '=([^;]*)'));
  return cocokan ? decodeURIComponent(cocokan[2]) : null;
}

function simpanCookie(nama, nilai, hari = 365) {
  const kadaluarsa = new Date();
  kadaluarsa.setTime(kadaluarsa.getTime() + (hari * 24 * 60 * 60 * 1000));
  document.cookie = `${nama}=${encodeURIComponent(nilai)};path=/;expires=${kadaluarsa.toUTCString()};SameSite=Lax`;
}

function inisialisasiPengalihTema() {
  const tombolTema = document.getElementById('tombol-alih-tema');
  if (!tombolTema) return;

  function terapkanTema(tema) {
    if (tema === 'gelap') {
      document.body.classList.add('tema-gelap');
      document.documentElement.setAttribute('data-theme', 'gelap');
      tombolTema.innerHTML = '☀️ Terang';
      tombolTema.setAttribute('aria-label', 'Ganti ke tema terang');
    } else {
      document.body.classList.remove('tema-gelap');
      document.documentElement.setAttribute('data-theme', 'terang');
      tombolTema.innerHTML = '🌙 Gelap';
      tombolTema.setAttribute('aria-label', 'Ganti ke tema gelap');
    }
    simpanCookie('tema_merangkum', tema);
  }

  // Cek tema tersimpan di cookie
  const temaTersimpan = ambilCookie('tema_merangkum') || 'terang';
  terapkanTema(temaTersimpan);

  tombolTema.addEventListener('click', (e) => {
    e.preventDefault();
    const temaSekarang = document.body.classList.contains('tema-gelap') ? 'gelap' : 'terang';
    const temaBaru = temaSekarang === 'gelap' ? 'terang' : 'gelap';
    terapkanTema(temaBaru);
  });
}

/* -------------------------------------------------------------
   2. Pencarian Langsung & Filter Rangkuman (Tanpa Reload Halaman)
------------------------------------------------------------- */
function inisialisasiPencarianDanFilter() {
  const inputCari = document.getElementById('input-pencarian');
  const selectJenis = document.getElementById('filter-jenis');
  const selectSemester = document.getElementById('filter-semester');
  const tombolReset = document.getElementById('tombol-reset-filter');
  const wadahKartu = document.getElementById('wadah-kartu-rangkuman');
  const infoJumlah = document.getElementById('info-jumlah-tampil');
  const pesanKosong = document.getElementById('pesan-kosong-filter');

  if (!wadahKartu) return;

  const semuaKartu = Array.from(wadahKartu.querySelectorAll('.kartu-item-rangkuman'));
  const totalData = semuaKartu.length;

  function lakukanFilter() {
    const kataKunci = (inputCari?.value || '').trim().toLowerCase();
    const jenisDipilih = (selectJenis?.value || '').trim();
    const semesterDipilih = (selectSemester?.value || '').trim();

    let jumlahCocok = 0;

    semuaKartu.forEach((kartu) => {
      const judul = (kartu.getAttribute('data-judul') || '').toLowerCase();
      const matkul = (kartu.getAttribute('data-matkul') || '').toLowerCase();
      const kode = (kartu.getAttribute('data-kode') || '').toLowerCase();
      const topik = (kartu.getAttribute('data-topik') || '').toLowerCase();
      const jenis = kartu.getAttribute('data-jenis') || '';
      const semester = kartu.getAttribute('data-semester') || '';

      const cocokTeks = !kataKunci ||
        judul.includes(kataKunci) ||
        matkul.includes(kataKunci) ||
        kode.includes(kataKunci) ||
        topik.includes(kataKunci);

      const cocokJenis = !jenisDipilih || jenis === jenisDipilih;
      const cocokSemester = !semesterDipilih || semester === semesterDipilih;

      if (cocokTeks && cocokJenis && cocokSemester) {
        kartu.style.display = '';
        jumlahCocok++;
      } else {
        kartu.style.display = 'none';
      }
    });

    if (infoJumlah) {
      infoJumlah.textContent = `Menampilkan ${jumlahCocok} dari ${totalData} rangkuman`;
    }

    if (pesanKosong) {
      pesanKosong.style.display = (jumlahCocok === 0 && totalData > 0) ? 'block' : 'none';
    }
  }

  if (inputCari) {
    inputCari.addEventListener('input', lakukanFilter);
  }
  if (selectJenis) {
    selectJenis.addEventListener('change', lakukanFilter);
  }
  if (selectSemester) {
    selectSemester.addEventListener('change', lakukanFilter);
  }

  if (tombolReset) {
    tombolReset.addEventListener('click', (e) => {
      e.preventDefault();
      if (inputCari) inputCari.value = '';
      if (selectJenis) selectJenis.value = '';
      if (selectSemester) selectSemester.value = '';
      lakukanFilter();
    });
  }
}

/* -------------------------------------------------------------
   3. Validasi Form Sisi Klien & Penghitung Karakter
------------------------------------------------------------- */
function inisialisasiValidasiForm() {
  const formRangkuman = document.getElementById('form-rangkuman');
  if (!formRangkuman) return;

  const textareaIsi = formRangkuman.querySelector('textarea[name="isi_ringkas"]');
  const counterElemen = document.getElementById('counter-isi');
  const inputTahun = formRangkuman.querySelector('input[name="tahun_akademik"]');
  const galatTahun = document.getElementById('galat-klien-tahun');

  // Live Karakter Counter
  if (textareaIsi && counterElemen) {
    function perbaruiCounter() {
      const panjang = textareaIsi.value.trim().length;
      counterElemen.textContent = `${panjang} / 2000 karakter (minimal 50)`;

      if (panjang >= 50 && panjang <= 2000) {
        counterElemen.className = 'penghitung-karakter valid';
      } else {
        counterElemen.className = 'penghitung-karakter tidak-valid';
      }
    }
    textareaIsi.addEventListener('input', perbaruiCounter);
    perbaruiCounter();
  }

  // Validasi format Tahun Akademik di Klien
  if (inputTahun) {
    function cekTahunKlien() {
      const val = inputTahun.value.trim();
      if (!val) return true;

      const cocok = val.match(/^(\d{4})\/(\d{4})$/);
      if (!cocok) {
        if (galatTahun) {
          galatTahun.textContent = 'Format harus YYYY/YYYY (contoh: 2025/2026)';
          galatTahun.style.display = 'block';
        }
        return false;
      }

      const y1 = parseInt(cocok[1], 10);
      const y2 = parseInt(cocok[2], 10);
      if (y2 !== y1 + 1) {
        if (galatTahun) {
          galatTahun.textContent = `Tahun kedua (${y2}) harus tepat 1 tahun setelah ${y1} (${y1}/${y1+1})`;
          galatTahun.style.display = 'block';
        }
        return false;
      }

      if (galatTahun) galatTahun.style.display = 'none';
      return true;
    }

    inputTahun.addEventListener('input', cekTahunKlien);
    inputTahun.addEventListener('blur', cekTahunKlien);
  }

  // Intersep Submit Form untuk peringatan dini jika belum memenuhi syarat
  formRangkuman.addEventListener('submit', (e) => {
    let adaGalat = false;

    if (textareaIsi) {
      const panjang = textareaIsi.value.trim().length;
      if (panjang < 50 || panjang > 2000) {
        alert(`Isi rangkuman wajib berukuran 50 hingga 2000 karakter. Saat ini: ${panjang} karakter.`);
        textareaIsi.focus();
        adaGalat = true;
      }
    }

    if (inputTahun && !adaGalat) {
      const cocok = inputTahun.value.trim().match(/^(\d{4})\/(\d{4})$/);
      if (!cocok || parseInt(cocok[2], 10) !== parseInt(cocok[1], 10) + 1) {
        alert('Tahun akademik harus berformat YYYY/YYYY di mana tahun kedua tepat 1 tahun setelah tahun pertama.');
        inputTahun.focus();
        adaGalat = true;
      }
    }

    if (adaGalat) {
      e.preventDefault();
    }
  });
}

/* -------------------------------------------------------------
   4. Modal Konfirmasi Penghapusan Data
------------------------------------------------------------- */
function inisialisasiModalHapus() {
  const overlayModal = document.getElementById('modal-konfirmasi-hapus');
  if (!overlayModal) return;

  const tombolBatal = document.getElementById('tombol-batal-hapus');
  const spanJudul = document.getElementById('judul-catatan-dihapus');
  const inputIdHapus = document.getElementById('input-id-catatan-hapus');
  const pemicuHapus = document.querySelectorAll('.pemicu-modal-hapus');

  pemicuHapus.forEach((tombol) => {
    tombol.addEventListener('click', (e) => {
      e.preventDefault();
      const id = tombol.getAttribute('data-id');
      const judul = tombol.getAttribute('data-judul');

      if (spanJudul) spanJudul.textContent = `"${judul}"`;
      if (inputIdHapus) inputIdHapus.value = id;

      overlayModal.classList.add('aktif');
      overlayModal.setAttribute('aria-hidden', 'false');
    });
  });

  function tutupModal() {
    overlayModal.classList.remove('aktif');
    overlayModal.setAttribute('aria-hidden', 'true');
  }

  if (tombolBatal) {
    tombolBatal.addEventListener('click', (e) => {
      e.preventDefault();
      tutupModal();
    });
  }

  // Tutup jika klik di area latar belakang gelap
  overlayModal.addEventListener('click', (e) => {
    if (e.target === overlayModal) {
      tutupModal();
    }
  });

  // Tutup dengan tombol keyboard ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlayModal.classList.contains('aktif')) {
      tutupModal();
    }
  });
}

/* -------------------------------------------------------------
   5. Tombol Intip Kata Sandi (ikon SVG)
------------------------------------------------------------- */
const SVG_ATTR = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

const IKON_MATA =
  `<svg ${SVG_ATTR}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;

const IKON_MATA_CORET =
  `<svg ${SVG_ATTR}><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

function inisialisasiIntipSandi() {
  document.querySelectorAll('.tombol-intip-sandi').forEach((tombol) => {
    const input = document.getElementById(tombol.getAttribute('data-target'));
    if (!input) return;

    tombol.innerHTML = IKON_MATA;

    tombol.addEventListener('click', () => {
      const sedangTersembunyi = input.type === 'password';
      input.type = sedangTersembunyi ? 'text' : 'password';
      tombol.innerHTML = sedangTersembunyi ? IKON_MATA_CORET : IKON_MATA;
      tombol.setAttribute(
        'aria-label',
        sedangTersembunyi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
      );
    });
  });
}