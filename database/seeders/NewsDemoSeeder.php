<?php

namespace Database\Seeders;

use App\Enums\NewsCategory;
use App\Enums\NewsStatus;
use App\Models\Cooperative;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $cooperative = Cooperative::query()->where('slug', 'koperasi-demo-berhad')->firstOrFail();
        $authorId = User::query()->where('email', 'admin@koperasihub.test')->value('id')
            ?? User::query()->where('email', 'superadmin@koperasihub.test')->value('id');

        $items = [
            [
                'title' => 'Koperasi Demo Berhad Raih Anugerah Koperasi Terbaik Negeri',
                'category' => NewsCategory::Achievement->value,
                'excerpt' => 'Koperasi Demo Berhad dinobatkan sebagai Koperasi Terbaik Kategori Perkhidmatan pada Majlis Anugerah Koperasi Negeri yang berlangsung baru-baru ini.',
                'content' => '<p>Koperasi Demo Berhad telah menerima pengiktirafan tertinggi dalam kategori Koperasi Terbaik Kategori Perkhidmatan pada Majlis Anugerah Koperasi Negeri yang berlangsung di Dewan Perdana, minggu lepas.</p><p>Pencapaian ini mencerminkan komitmen berterusan koperasi dalam meningkatkan kualiti perkhidmatan kepada anggota, memperluaskan akses digital, dan memastikan tadbir urus yang telus dan bertanggungjawab.</p><p>Pengerusi Koperasi Demo Berhad menyatakan rasa syukur dan berterima kasih kepada seluruh anggota dan warga kerja atas sokongan padu mereka. "Anugerah ini adalah milik semua anggota. Kami akan terus berusaha memberikan perkhidmatan yang lebih baik pada masa hadapan," katanya.</p><p>Koperasi ini berhasrat untuk menggunakan momentum ini bagi memperluaskan program kebajikan anggota dan meningkatkan keupayaan digital portal ahli pada tahun hadapan.</p>',
                'days_ago' => 3,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Mesyuarat Agung Tahunan 2025 Dijadualkan pada 15 Jun',
                'category' => NewsCategory::Announcement->value,
                'excerpt' => 'Anggota dijemput hadir ke Mesyuarat Agung Tahunan (MAT) 2025 yang akan diadakan pada 15 Jun 2025 di Dewan Utama Koperasi.',
                'content' => '<p>Mesyuarat Agung Tahunan (MAT) Koperasi Demo Berhad bagi tahun 2025 akan berlangsung pada <strong>15 Jun 2025</strong>, bermula jam 9.00 pagi di Dewan Utama Koperasi, Aras 3.</p><p>Agenda utama mesyuarat ini merangkumi:</p><ul><li>Pembentangan laporan kewangan tahunan 2024</li><li>Pemilihan ahli Lembaga Pengarah bagi tempoh 2025–2027</li><li>Perbincangan cadangan dividen anggota</li><li>Pengesahan pindaan perlembagaan koperasi</li></ul><p>Semua anggota yang berdaftar dan mempunyai hak mengundi adalah digalakkan hadir. Borang kehadiran boleh didaftarkan melalui portal ahli atau kaunter koperasi sebelum 8 Jun 2025.</p><p>Untuk maklumat lanjut, sila hubungi Urusetia MAT di talian pejabat atau melalui e-mel rasmi koperasi.</p>',
                'days_ago' => 7,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Program Literasi Kewangan Anggota Siri 3 Dibuka untuk Pendaftaran',
                'category' => NewsCategory::Education->value,
                'excerpt' => 'Koperasi Demo Berhad membuka pendaftaran untuk Program Literasi Kewangan Anggota Siri 3, sebuah program percuma khusus untuk anggota berdaftar.',
                'content' => '<p>Koperasi Demo Berhad dengan bangga mengumumkan pembukaan pendaftaran untuk <strong>Program Literasi Kewangan Anggota Siri 3</strong>, sebuah inisiatif pendidikan kewangan percuma yang direka khusus untuk anggota berdaftar.</p><p>Program ini akan merangkumi topik-topik berikut:</p><ul><li>Perancangan kewangan peribadi dan keluarga</li><li>Memahami produk simpanan dan pelaburan koperasi</li><li>Pengurusan hutang yang bijak</li><li>Asas takaful dan perlindungan kewangan</li></ul><p>Sesi akan diadakan secara dalam talian melalui platform Zoom selama tiga hari berturut-turut. Penceramah jemputan adalah pakar kewangan berpengalaman dari institusi kewangan terkemuka.</p><p>Tempat adalah terhad. Anggota yang berminat boleh mendaftar melalui portal ahli atau menghubungi pejabat koperasi.</p>',
                'days_ago' => 12,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Kempen Gotong-Royong dan Komuniti Bersih 2025 Berjaya Dilaksanakan',
                'category' => NewsCategory::Community->value,
                'excerpt' => 'Lebih 200 anggota dan keluarga menyertai Kempen Gotong-Royong dan Komuniti Bersih 2025 yang diadakan di kawasan perumahan sekitar pejabat koperasi.',
                'content' => '<p>Seramai lebih 200 orang anggota berserta keluarga masing-masing telah hadir menyertai <strong>Kempen Gotong-Royong dan Komuniti Bersih 2025</strong> yang diadakan pada hujung minggu lalu di sekitar kawasan perumahan berhampiran pejabat koperasi.</p><p>Aktiviti yang dijalankan termasuk pembersihan laluan awam, penanaman pokok hiasan, gotong-royong surau setempat, dan sarapan pagi percuma yang disediakan oleh jawatankuasa kebajikan koperasi.</p><p>Pengurus Koperasi Demo Berhad berkata program sebegini penting untuk memupuk semangat kekitaan dalam kalangan anggota sambil memberi sumbangan positif kepada komuniti setempat.</p><p>Program ini merupakan sebahagian daripada inisiatif Tanggungjawab Sosial Koperasi (TSK) yang dilaksanakan setiap suku tahun. Program seterusnya dijangka berlangsung pada Oktober 2025.</p>',
                'days_ago' => 18,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Portal Ahli Dinaik Taraf dengan Fungsi Semakan Penyata Syer',
                'category' => NewsCategory::Announcement->value,
                'excerpt' => 'Anggota kini boleh menyemak penyata syer terkini secara dalam talian melalui portal ahli yang telah dinaik taraf dengan ciri-ciri baharu.',
                'content' => '<p>Koperasi Demo Berhad dengan sukacitanya memaklumkan bahawa Portal Ahli telah berjaya dinaik taraf dengan penambahan <strong>fungsi semakan penyata syer dalam talian</strong>.</p><p>Melalui kemaskini ini, anggota kini boleh:</p><ul><li>Menyemak baki syer terkini pada bila-bila masa</li><li>Melihat sejarah transaksi syer selama 24 bulan</li><li>Memuat turun penyata syer dalam format PDF</li><li>Menerima pemberitahuan automatik apabila rekod syer dikemaskini</li></ul><p>Ciri-ciri baharu ini tersedia serta-merta untuk semua anggota berdaftar. Log masuk ke portal ahli menggunakan nombor keanggotaan dan kata laluan anda.</p><p>Sekiranya menghadapi sebarang kesulitan dalam mengakses fungsi baharu ini, sila hubungi bahagian sokongan ICT koperasi melalui e-mel atau talian bantuan yang disediakan.</p>',
                'days_ago' => 25,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Koperasi Demo Berhad Lancarkan Skim Pembiayaan Pendidikan Anak Anggota',
                'category' => NewsCategory::General->value,
                'excerpt' => 'Satu skim pembiayaan pendidikan baharu khas untuk anak anggota berdaftar kini tersedia, meliputi yuran pengajian di institusi pengajian tinggi awam dan swasta.',
                'content' => '<p>Koperasi Demo Berhad dengan sukacitanya memperkenalkan <strong>Skim Pembiayaan Pendidikan Anak Anggota</strong>, sebuah produk pembiayaan baharu yang direka untuk membantu anggota menanggung kos pendidikan tinggi anak-anak mereka.</p><p>Ciri-ciri utama skim ini:</p><ul><li>Pembiayaan sehingga RM30,000 untuk tempoh pengajian penuh</li><li>Kadar keuntungan yang kompetitif mengikut prinsip Islam</li><li>Tempoh bayaran balik sehingga 84 bulan (7 tahun)</li><li>Tidak memerlukan penjamin luar selain rekod syer yang mencukupi</li><li>Terbuka untuk anggota yang telah menjadi ahli selama sekurang-kurangnya 2 tahun</li></ul><p>Permohonan boleh dibuat secara dalam talian melalui portal ahli atau hadir ke kaunter koperasi. Borang permohonan dan garis panduan penuh tersedia di bahagian muat turun laman web ini.</p>',
                'days_ago' => 33,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Majlis Berbuka Puasa Anggota 2025 Penuh Kemeriahan',
                'category' => NewsCategory::Event->value,
                'excerpt' => 'Koperasi Demo Berhad menganjurkan Majlis Berbuka Puasa Anggota 2025 yang dihadiri lebih 350 anggota berserta keluarga dalam suasana penuh ukhuwah.',
                'content' => '<p>Koperasi Demo Berhad telah berjaya menganjurkan <strong>Majlis Berbuka Puasa Anggota 2025</strong> yang berlangsung pada malam Khamis lalu di Dewan Serbaguna Koperasi.</p><p>Majlis yang dihadiri lebih 350 anggota berserta keluarga ini berlangsung dalam suasana penuh kemeriahan dan ukhuwah. Antara program yang diadakan termasuk bacaan Yasin berjemaah, ceramah agama ringkas, pertukaran hadiah, dan jamuan berbuka puasa yang disediakan oleh katering pilihan anggota koperasi sendiri.</p><p>Pengerusi Koperasi Demo Berhad menyifatkan majlis ini sebagai platform penting untuk mempererat hubungan sesama anggota. "Ramadan adalah bulan yang penuh barakah. Kami berharap momen ini menguatkan ikatan kekeluargaan dalam koperasi kita," ujarnya.</p><p>Gambar dan video majlis ini akan dimuat naik ke laman media sosial rasmi koperasi dalam masa terdekat.</p>',
                'days_ago' => 45,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Perkhidmatan Kaunter Koperasi Beroperasi Sepanjang Cuti Umum Terpilih',
                'category' => NewsCategory::Announcement->value,
                'excerpt' => 'Anggota dimaklumkan bahawa kaunter perkhidmatan koperasi akan beroperasi pada waktu terhad semasa cuti umum terpilih sepanjang 2025.',
                'content' => '<p>Koperasi Demo Berhad ingin memaklumkan kepada seluruh anggota bahawa perkhidmatan kaunter akan beroperasi pada waktu terhad sempena <strong>cuti umum terpilih</strong> sepanjang tahun 2025.</p><p>Waktu operasi khas semasa cuti umum:</p><ul><li><strong>Hari Raya Aidilfitri (Hari 1 & 2)</strong>: Ditutup sepenuhnya</li><li><strong>Hari Raya Aidiladha</strong>: Ditutup sepenuhnya</li><li><strong>Cuti Umum Lain</strong>: 9.00 pagi – 1.00 tengah hari (perkhidmatan asas sahaja)</li></ul><p>Walau bagaimanapun, portal ahli dalam talian kekal beroperasi 24 jam sehari, 7 hari seminggu sepanjang tahun. Anggota digalakkan menggunakan platform digital untuk urusan yang boleh diselesaikan tanpa hadir ke kaunter.</p><p>Untuk pertanyaan segera semasa cuti, anggota boleh menghubungi talian WhatsApp rasmi koperasi yang akan dijawab dalam masa 24 jam bekerja.</p>',
                'days_ago' => 60,
                'status' => NewsStatus::Published->value,
            ],
            [
                'title' => 'Draf: Laporan Kegiatan Suku Pertama 2025',
                'category' => NewsCategory::General->value,
                'excerpt' => 'Laporan ringkasan aktiviti dan pencapaian koperasi bagi suku pertama tahun 2025 dalam penyediaan.',
                'content' => '<p>Laporan ini sedang dalam proses penyediaan.</p>',
                'days_ago' => 1,
                'status' => NewsStatus::Draft->value,
            ],
        ];

        foreach ($items as $item) {
            $title = $item['title'];
            $slug = Str::slug($title);
            $publishedAt = $item['status'] === NewsStatus::Published->value
                ? now()->subDays($item['days_ago'])
                : null;

            News::query()->updateOrCreate([
                'cooperative_id' => $cooperative->id,
                'slug' => $slug,
            ], [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $item['excerpt'],
                'content' => $item['content'],
                'image_path' => null,
                'category' => $item['category'],
                'status' => $item['status'],
                'published_at' => $publishedAt,
                'created_by' => $authorId,
                'updated_by' => $authorId,
            ]);
        }
    }
}
