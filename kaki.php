</main>

<!-- Modal Konfirmasi Hapus Data (Neo-Brutalism) -->
<div id="modal-konfirmasi-hapus" class="overlay-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="kotak-modal">
        <div class="lencana lencana-bahaya mb-2">KONFIRMASI PENGHAPUSAN</div>
        <h2 style="font-size: 1.4rem; margin-bottom: 0.75rem;">Hapus Rangkuman Ini?</h2>
        <p style="color: var(--teks-muted); margin-bottom: 1.25rem;">
            Tindakan ini tidak dapat dibatalkan. Catatan kuliah <strong id="judul-catatan-dihapus"></strong> beserta seluruh datanya akan dihapus permanen dari basis data.
        </p>

        <form id="form-hapus-rangkuman" action="hapus.php" method="POST">
            <?= inputCsrfToken() ?>
            <input type="hidden" name="id" id="input-id-catatan-hapus" value="">
            <div class="d-flex justify-between gap-2 mt-2">
                <button type="button" id="tombol-batal-hapus" class="tombol">
                    ✕ Batal
                </button>
                <button type="submit" class="tombol tombol-bahaya">
                    🗑️ Ya, Hapus Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Kaki Halaman (Footer) -->
<footer class="kaki-halaman">
    <div class="kaki-konten">
        <div class="d-flex align-center gap-1">
            <span style="font-size: 1.2rem;">📚</span>
            <strong>Merangkum</strong> &copy; <?= date('Y') ?> &mdash; Bank Catatan dan Rangkuman Kuliah Mahasiswa.
        </div>
    </div>
</footer>

<!-- Skrip Vanilla JS -->
<script src="js/app.js"></script>
</body>
</html>