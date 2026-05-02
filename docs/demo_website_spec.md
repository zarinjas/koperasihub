# KoperasiHub Demo Website Spec

Purpose: define demo public website structure and seed content for KoperasiHub.

This is a demo/seed content spec only. Do not implement routes, models, seeders, or UI from this file unless a later task explicitly requests it.

The demo may be inspired by Malaysian cooperatives with many services and business units, but must remain white-label. Do not hardcode real cooperative names, logos, registration numbers, addresses, awards, statistics, affiliations, or operational claims.

Use Bahasa Malaysia for public copywriting. Keep code identifiers, slugs, and CMS section type names in English.

---

## 1. Demo Identity

Use dummy configurable content:

```txt
site_name: Koperasi Demo Berhad
tagline: Bersama membina kesejahteraan anggota
email: info@koperasidemo.test
phone: +603-0000 0000
whatsapp: +6012-000 0000
address: Alamat demo koperasi
```

All values above belong in seed data/settings, not application code.

---

## 2. Public Navigation Menu

Primary navigation:

```txt
Utama -> /
Tentang Kami -> /tentang-kami
Perkhidmatan -> /perkhidmatan
Perniagaan -> /perniagaan
Pengumuman -> /pengumuman
Muat Turun -> /muat-turun
Hubungi Kami -> /hubungi
Portal Ahli -> /member/login
```

Suggested service dropdown under `Perkhidmatan`:

```txt
Keanggotaan -> /perkhidmatan/keanggotaan
Pembiayaan Anggota -> /perkhidmatan/pembiayaan-anggota
Simpanan & Syer -> /perkhidmatan/simpanan-syer
Takaful Kenderaan -> /perkhidmatan/takaful-kenderaan
Ar-Rahnu -> /perkhidmatan/ar-rahnu
Kebajikan Anggota -> /perkhidmatan/kebajikan-anggota
```

Suggested business dropdown under `Perniagaan`:

```txt
Kedai Koperasi -> /perniagaan/kedai-koperasi
Hartanah & Sewaan -> /perniagaan/hartanah-sewaan
Stesen Minyak -> /perniagaan/stesen-minyak
E-Dagang -> /perniagaan/e-dagang
Bilik Seminar -> /perniagaan/bilik-seminar
```

---

## 3. Footer Navigation

Footer groups:

```txt
Koperasi
- Tentang Kami
- Lembaga & Pengurusan
- Tadbir Urus
- Hubungi Kami

Perkhidmatan
- Keanggotaan
- Pembiayaan Anggota
- Simpanan & Syer
- Takaful Kenderaan
- Kebajikan Anggota

Perniagaan
- Kedai Koperasi
- Hartanah & Sewaan
- Stesen Minyak
- E-Dagang
- Bilik Seminar

Sumber
- Pengumuman
- Muat Turun Borang
- Soalan Lazim
- Dasar Privasi
```

Footer short copy:

```txt
Koperasi Demo Berhad menyediakan platform maklumat, perkhidmatan dan sokongan anggota secara lebih tersusun.
```

---

## 4. Homepage Section Order

Use existing CMS section type identifiers:

```txt
1. hero
2. stats
3. feature_grid
4. service_grid
5. business_units
6. announcement_list
7. download_list
8. cta_banner
9. faq
10. contact_block
```

---

## 5. Dummy Homepage Copy

### `hero`

```txt
badge: Koperasi Demo Berhad
title: Koperasi moden untuk keperluan anggota
subtitle: Akses maklumat keanggotaan, perkhidmatan, pengumuman dan borang koperasi melalui satu laman rasmi yang mudah digunakan.
primary_button_text: Daftar Anggota
primary_button_url: /member/register
secondary_button_text: Lihat Perkhidmatan
secondary_button_url: /perkhidmatan
```

### `stats`

Use conservative demo figures only:

```txt
10+ Perkhidmatan demo
5 Kategori perniagaan
24/7 Akses maklumat online
```

### `feature_grid`

```txt
title: Urusan koperasi lebih mudah
subtitle: Maklumat penting disusun supaya anggota dan pelawat boleh mendapatkan bantuan dengan cepat.

items:
- Permohonan keanggotaan online
- Semakan status permohonan
- Pengumuman rasmi koperasi
- Borang dan dokumen muat turun
- Direktori perkhidmatan anggota
- Saluran pertanyaan dan maklum balas
```

### `service_grid`

```txt
title: Perkhidmatan anggota
subtitle: Pilih perkhidmatan yang berkaitan dengan keperluan anda.
source: services
limit: 6
```

### `business_units`

```txt
title: Perniagaan dan kemudahan koperasi
subtitle: Koperasi boleh memaparkan unit perniagaan, kemudahan dan aktiviti ekonomi yang tersedia.
```

### `announcement_list`

```txt
title: Pengumuman terkini
subtitle: Ikuti hebahan rasmi, tarikh penting dan makluman perkhidmatan koperasi.
source: latest
limit: 3
button_text: Lihat Semua Pengumuman
button_url: /pengumuman
```

### `download_list`

```txt
title: Borang dan dokumen
subtitle: Muat turun borang umum dan dokumen rujukan koperasi.
source: documents
category: forms
limit: 6
```

### `cta_banner`

```txt
title: Berminat menjadi anggota koperasi?
subtitle: Hantar permohonan awal secara online dan pihak koperasi akan menyemak maklumat anda.
primary_button_text: Daftar Sekarang
primary_button_url: /member/register
secondary_button_text: Semak Permohonan
secondary_button_url: /semak-permohonan
```

### `faq`

```txt
title: Soalan lazim
subtitle: Jawapan ringkas kepada pertanyaan umum berkaitan keanggotaan dan perkhidmatan koperasi.
```

### `contact_block`

```txt
title: Hubungi kami
subtitle: Ada pertanyaan? Sila hubungi koperasi melalui saluran rasmi yang dipaparkan.
show_contact_form: true
```

---

## 6. Demo Services

Seed as service records or manual CMS items:

```txt
Keanggotaan
Maklumat syarat keahlian, proses permohonan dan kemas kini status anggota.

Pembiayaan Anggota
Panduan permohonan pembiayaan anggota tertakluk kepada syarat koperasi.

Simpanan & Syer
Maklumat berkaitan caruman syer, simpanan anggota dan rekod pegangan.

Takaful Kenderaan
Rujukan permohonan dan pembaharuan perlindungan takaful kenderaan.

Ar-Rahnu
Maklumat umum pajak gadai Islam yang boleh ditawarkan oleh koperasi.

Kedai Koperasi
Jualan barangan keperluan harian, produk anggota atau barangan terpilih.

Hartanah & Sewaan
Maklumat premis, ruang niaga atau aset sewaan koperasi.

Stesen Minyak
Paparan unit perniagaan stesen minyak sebagai contoh aktiviti ekonomi koperasi.

E-Dagang
Saluran jualan online untuk produk koperasi atau produk anggota.

Bilik Seminar
Maklumat kemudahan bilik mesyuarat, latihan atau seminar untuk tempahan.

Kebajikan Anggota
Bantuan, sumbangan atau manfaat kebajikan mengikut polisi koperasi.
```

---

## 7. Quick Access Links

Show as compact cards/buttons near the hero or after `feature_grid`:

```txt
Portal Ahli -> /member/login
Daftar Anggota -> /member/register
Semak Permohonan -> /semak-permohonan
Muat Turun Borang -> /muat-turun
Hubungi Kami -> /hubungi
```

---

## 8. Dummy Announcements

```txt
Pembukaan Permohonan Keanggotaan Sesi Demo
Permohonan keanggotaan baharu kini boleh dibuat melalui borang online.

Notis Kemaskini Maklumat Anggota
Anggota digalakkan menyemak dan mengemaskini maklumat peribadi melalui portal ahli.

Hebahan Muat Turun Borang Terkini
Borang perkhidmatan koperasi telah dikemaskini untuk rujukan anggota.

Makluman Waktu Operasi Kaunter
Sila rujuk waktu operasi kaunter sebelum hadir untuk urusan fizikal.
```

All announcement dates should be generated as demo dates by seeders.

---

## 9. Dummy Downloads / Forms

```txt
Borang Permohonan Keanggotaan
Kategori: forms
Akses awam: ya

Borang Kemaskini Maklumat Anggota
Kategori: forms
Akses awam: ya

Borang Permohonan Pembiayaan Anggota
Kategori: forms
Akses awam: ya

Borang Penamaan Waris
Kategori: forms
Akses awam: ya

Borang Tuntutan Kebajikan
Kategori: forms
Akses awam: ya

Risalah Perkhidmatan Koperasi
Kategori: brochures
Akses awam: ya
```

Use placeholder PDF files or media records during demo seeding.

---

## 10. Public Page Sitemap

Recommended public pages:

```txt
/                         Utama
/tentang-kami             Tentang Kami
/tentang-kami/profil      Profil Koperasi
/tentang-kami/tadbir-urus Tadbir Urus
/perkhidmatan             Perkhidmatan
/perkhidmatan/{slug}      Butiran Perkhidmatan
/perniagaan               Perniagaan
/perniagaan/{slug}        Butiran Perniagaan
/pengumuman               Senarai Pengumuman
/pengumuman/{slug}        Butiran Pengumuman
/muat-turun               Muat Turun
/soalan-lazim             Soalan Lazim
/hubungi                  Hubungi Kami
/semak-permohonan         Semak Permohonan
/member/login             Portal Ahli
/member/register          Daftar Anggota
```

MVP seed pages may start with:

```txt
/
/tentang-kami
/perkhidmatan
/perniagaan
/pengumuman
/muat-turun
/hubungi
```

---

## 11. Seed Content Rules

- Demo content must be editable through settings, pages, sections, services, announcements, and documents.
- Do not place demo copy directly inside Vue components, routes, controllers, or migrations.
- Do not imply real licenses, approvals, assets, branches, financial figures, or membership counts.
- Do not use real cooperative branding unless the client provides explicit content later.
- Prefer neutral placeholder images such as office, counter service, meeting room, retail shelf, property, or generic business operations.
