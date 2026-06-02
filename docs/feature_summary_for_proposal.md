# Feature Summary for Proposal

> Slide-friendly summary of KoperasiHub features.
> Non-technical language. No client-specific wording.
> Suitable for proposal and presentation slides.

---

## Public Website & CMS

**Apa yang dibuat:**
Laman web koperasi yang lengkap dengan kandungan dinamik. Admin boleh mengubah teks, gambar, dan susunan halaman tanpa perlu tahu coding.

**Fungsi utama:**
- Halaman utama dinamik (hero, stats, perkhidmatan, pengumuman, FAQ, contact, etc.)
- Halaman dalaman untuk kandungan statik
- Galeri poster/ banner
- Carian dan senarai perkhidmatan, pengumuman, dokumen
- Borang keahlian dalam talian
- Direktori Borang Online
- SEO metadata untuk setiap halaman

**Pengguna:** Public, Admin

**Nilai:** Koperasi tampil profesional dengan laman web yang sentiasa terkini tanpa perlu pembangun web.

---

## Admin Panel

**Apa yang dibuat:**
Panel kawalan tersendiri (bukan WordPress, bukan Filament) untuk pengurusan sepenuh operasi koperasi.

**Fungsi utama:**
- Dashboard ringkasan operasi
- Navigasi sidebar mengikut modul
- Carian, tapis, dan paginasi
- Status badges, loading states, empty states
- Paparan mesra telefon bimbit

**Pengguna:** Admin, Super Admin

**Nilai:** Semua operasi koperasi diurus dari satu tempat.

---

## Units / Department Management

**Apa yang dibuat:**
Urus bahagian atau unit dalam organisasi koperasi.

**Fungsi utama:**
- Daftar unit baru
- Susun atur ikut keutamaan
- Aktif / tidak aktifkan unit
- Unit dirujuk oleh borang dan staf

**Pengguna:** Admin, Super Admin

**Nilai:** Struktur organisasi dapat digambarkan dalam sistem.

---

## Staff & Access Management

**Apa yang dibuat:**
Urus kakitangan pentadbiran dan hak akses mengikut peranan.

**Fungsi utama:**
- Daftar akaun staf
- Tetapkan unit dan jawatan
- Aktif / tidak aktifkan staf
- Kawalan akses berdasarkan peranan (super_admin, admin, member)

**Pengguna:** Super Admin

**Nilai:** Keselamatan data terjamin. Setiap staf hanya boleh akses modul yang dibenarkan.

---

## Member Management

**Apa yang dibuat:**
Urus rekod ahli koperasi secara digital.

**Fungsi utama:**
- Senarai ahli dengan carian dan tapisan
- Tambah, kemas kini, dan padam rekod ahli
- Tukar status ahli (aktif / tidak aktif / digantung)
- Paparan profil lengkap ahli
- Dokumen berkaitan ahli

**Pengguna:** Admin, Super Admin

**Nilai:** Data ahli tersusun, mudah dicari, dan sentiasa dikemas kini.

---

## Member Import & Account Activation

**Apa yang dibuat:**
Import ahli sedia ada secara pukal dan aktifkan portal ahli.

**Fungsi utama:**
- Muat turun templat CSV
- Import fail dengan pralihat sebelum sahkan
- Pengesahan data semasa import
- Ahli mengaktifkan akaun portal sendiri
- Langkah pengesahan: nombor IC -> set kata laluan -> selesai
- Reset kata laluan sendiri

**Pengguna:** Admin (import), Member (aktivasi)

**Nilai:** Ribuan ahli dapat diimport dalam beberapa minit. Ahli aktifkan portal sendiri tanpa perlu bantuan staf.

---

## Member Portal

**Apa yang dibuat:**
Portal layan diri untuk ahli koperasi selepas log masuk.

**Fungsi utama:**
- Dashboard ringkasan peribadi
- Lihat dan kemas kini profil
- Senarai dokumen peribadi
- Pengumuman untuk ahli
- Permohonan dalam talian
- Kad keahlian digital
- Aduan / pertanyaan
- Caruman / sumbangan
- Pembiayaan (permohonan dan status)
- Notifikasi dalam sistem

**Pengguna:** Member

**Nilai:** Ahli boleh urus sendiri tanpa perlu datang ke pejabat koperasi.

---

## Digital Membership Card

**Apa yang dibuat:**
Kad keahlian digital yang boleh diakses melalui telefon bimbit.

**Fungsi utama:**
- Paparan kad dengan foto dan nama ahli
- Kod QR untuk pengesahan
- Muat turun dan kongsi kad
- Halaman pengesahan awam (verifikasi melalui token)
- Butang dompet digital (boleh dikembangkan)

**Pengguna:** Member, Public (verifikasi)

**Nilai:** Ahli tidak perlu bawa kad fizikal. Pengesahan pantas melalui QR.

---

## Online Forms / Applications

**Apa yang dibuat:**
Sistem borang dalam talian yang fleksibel. Admin bina borang, ahli hantar permohonan.

**Fungsi utama:**
- Pembina borang dinamik (tambah seksyen, pelbagai jenis medan)
- Kategori borang mengikut unit
- Templat seksyen boleh diguna semula
- Borang boleh diisi dalam talian, cetak, atau gabungan
- Tangkapan tandatangan digital
- Persetujuan / akuan dalam borang
- Kotak untuk kegunaan pejabat
- Muat naik dokumen bercop / pengesahan
- Cetakan mesra printer
- Saluran permohonan bersatu (Permohonan)

**Pengguna:** Public (borang awam), Member (permohonan), Admin (urus borang dan semak)

**Nilai:** Proses permohonan menjadi cepat, teratur, dan kurang kertas.

---

## Review & Approval Center

**Apa yang dibuat:**
Pusat semakan bersatu untuk semua perkara yang menunggu kelulusan.

**Fungsi utama:**
- Paparan semua permohonan menunggu tindakan
- Tapis mengikut modul
- Pautan terus ke halaman semakan masing-masing

**Pengguna:** Admin, Super Admin

**Nilai:** Staf tidak perlu buka modul satu persatu. Semua yang perlu perhatian ada di satu tempat.

---

## Financing Module

**Apa yang dibuat:**
Pengurusan permohonan pembiayaan koperasi secara digital — daripada produk hingga kelulusan.

**Fungsi utama:**
- Kategori dan produk pembiayaan
- Borang permohonan dinamik (seperti Borang Online)
- Ahli memohon dengan isi borang dan muat naik dokumen
- Semak, lulus, atau tolak permohonan
- Cetak ringkasan permohonan
- Muat naik borang bercop selepas kelulusan
- Sejarah status permohonan
- Pengurusan penjamin

**Pengguna:** Member (memohon), Admin (semak dan lulus), Super Admin

**Nilai:** Proses pembiayaan lebih pantas, telus, dan kurang kertas.

---

## Guarantor Consent & Signature

**Apa yang dibuat:**
Pengurusan penjamin untuk permohonan pembiayaan. Penjamin memberi persetujuan melalui portal ahli.

**Fungsi utama:**
- Cari penjamin dalam kalangan ahli sedia ada
- Hantar permintaan kepada penjamin
- Penjamin lihat permintaan dan maklum balas (setuju / tolak)
- Rekod keputusan penjamin

**Pengguna:** Member (penjamin), Admin (urus), Super Admin

**Nilai:** Proses penjaminan selesai dalam talian, tanpa dokumen fizikal dan tanda tangan basah.

---

## Documents & Downloads

**Apa yang dibuat:**
Pengurusan dokumen dan muat turun untuk orang awam dan ahli.

**Fungsi utama:**
- Muat naik dan kategorikan dokumen
- Kawalan siapa boleh lihat (awam / ahli sahaja / staf sahaja / ahli tertentu)
- Dokumen boleh dimuat turun dengan kawalan akses
- Pusat muat turun awam dan portal ahli

**Pengguna:** Public (dokumen awam), Member (dokumen ahli), Admin (urus), Super Admin

**Nilai:** Dokumen penting sentiasa boleh diakses ahli tanpa perlu datang ke pejabat.

---

## News & Announcements

**Apa yang dibuat:**
Siar berita dan pengumuman untuk orang awam, ahli, atau staf.

**Fungsi utama:**
- Buat dan terbitkan berita / pengumuman
- Pilih sasaran (awam / ahli / staf)
- Pengumuman boleh disemat di atas
- Tarikh luput untuk pengumuman
- Berita untuk laman web awam

**Pengguna:** Public (berita + pengumuman awam), Member (pengumuman ahli), Admin (urus), Super Admin

**Nilai:** Makluman sampai kepada kumpulan sasaran dengan cepat.

---

## Services Management

**Apa yang dibuat:**
Paparkan perkhidmatan koperasi di laman web dan portal ahli.

**Fungsi utama:**
- Tambah, edit, dan susun perkhidmatan
- Terbit / sembunyikan perkhidmatan
- Tambah gambar dan pautan (whatsapp, borang, halaman)
- Paparan di halaman utama

**Pengguna:** Public (lihat), Admin (urus), Super Admin

**Nilai:** Ahli dan orang awam dapat tahu perkhidmatan yang ditawarkan.

---

## Complaints / Suggestions

**Apa yang dibuat:**
Sistem aduan dan cadangan secara tiket untuk ahli.

**Fungsi utama:**
- Ahli hantar aduan / cadangan
- Ahli lihat status aduan sendiri
- Staf balas aduan
- Staf ubah status (buka / sedang diurus / selesai / tutup)
- Nota dalaman untuk staf sahaja

**Pengguna:** Member (hantar), Admin (urus dan balas), Super Admin

**Nilai:** Aduan ahli tidak hilang. Setiap aduan direkod, dijawab, dan diselesaikan.

---

## Audit Logs

**Apa yang dibuat:**
Rekod semua tindakan penting dalam sistem untuk pengauditan.

**Fungsi utama:**
- Log automatik untuk tindakan sensitif
- Tapis mengikut pelakon, tindakan, modul, tarikh
- Paparan hanya-baca (boleh padam)

**Pengguna:** Super Admin

**Nilai:** Keselamatan dan ketelusan terjamin. Segala perubahan dapat dikesan.

---

## Branding / White-Label Settings

**Apa yang dibuat:**
Tetapan identiti koperasi — nama, logo, warna, dan maklumat hubungan.

**Fungsi utama:**
- Nama dan logo koperasi
- Warna jenama (primer, sekunder)
- Maklumat hubungan (alamat, telefon, emel, whatsapp)
- Pautan media sosial
- Teks footer
- SEO lalai

**Pengguna:** Super Admin

**Nilai:** Setiap koperasi kelihatan unik dengan jenama sendiri. Platform ini sesuai untuk pelbagai koperasi.

---

## Future Mobile App Expansion

**Apa yang dibuat:**
Semua modul web sedia ada dibina dengan struktur yang membolehkan pembangunan aplikasi mudah alih di masa hadapan.

**Fungsi utama (cadangan):**
- API untuk aplikasi telefon bimbit
- Pemberitahuan (push notification)
- Pendaftaran peranti ahli
- Laporan lanjutan
- Segmentasi ahli dan kempen

**Nota:** Ciri-ciri mudah alih boleh dibangunkan selepas platform web stabil dan setelah mendapat pengesahan keperluan.

**Nilai:** Pelaburan dalam platform web sekarang tidak akan rugi — data dan struktur sedia untuk diperluas ke aplikasi mudah alih bila-bila masa.