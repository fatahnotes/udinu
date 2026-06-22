<?php
$pageTitle = "Alur Pendaftaran UDIN & UPKP";
$activePage = "alur";

/* CSS khusus halaman alur */
$customCSS = <<<CSS
/* Hero Section */
.alur-hero {
    position: relative;
    overflow: hidden;
    background: linear-gradient(rgba(1, 95, 201, 0.9), rgba(0, 0, 0, 0.2)), url(img/bg-breadcrumb.jpg);
    background-position: center center;
    background-repeat: no-repeat;
    background-size: cover;
    padding: 100px 0 60px 0;
    margin-bottom: 50px;
}

.alur-hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: white;
    text-align: center;
}

.alur-hero-text {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.85);
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.8;
    text-align: center;
}

/* Timeline */
.alur-timeline-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem 3rem; /* dikurangi dari 5rem menjadi 3rem */
}

.alur-section-title {
    text-align: center;
    margin-bottom: 2.5rem; /* sedikit dikurangi */
}

.alur-section-title h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0045a0;
}

.alur-section-title p {
    color: #6c757d;
}

.alur-timeline {
    position: relative;
    padding: 2rem 0; /* dikurangi dari 3rem */
}

.alur-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    background: linear-gradient(to bottom, #F9DA00, #FF9133);
}

.alur-timeline-item {
    display: flex;
    margin-bottom: 2rem; /* dikurangi dari 4rem */
}

.alur-timeline-item:nth-child(even) {
    flex-direction: row-reverse;
}

.alur-timeline-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 20px rgba(0,0,0,.15);
    z-index: 10;
}

.alur-icon-bg {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000;
    font-weight: 700;
}

.alur-timeline-content {
    background: #fff;
    border-radius: 15px;
    padding: 1.5rem; /* dikurangi dari 2rem */
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
    width: calc(50% - 70px);
    margin: 0 20px;
}

.alur-timeline-step {
    font-weight: 600;
    color: #FF9133;
}

.alur-timeline-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #0045a0;
}

.alur-timeline-desc {
    color: #6c757d;
}

.alur-timeline-highlight {
    background: rgba(255,145,51,.05);
    border-left: 4px solid #FF9133;
    padding: 0.75rem; /* sedikit lebih kecil */
    border-radius: 10px;
    line-height: 1.4;
}

.alur-timeline-highlight ul {
    margin-bottom: 0;
}

@media(max-width:992px){
    .alur-timeline::before{left:35px}
    .alur-timeline-item{flex-direction:row!important}
    .alur-timeline-content{width:calc(100% - 100px);margin-left:100px}
}
CSS;

include 'header.php';
?>

<!-- HERO -->
<div class="alur-hero wow fadeIn">
    <div class="container py-5">
        <h1 class="alur-hero-title">Alur Pendaftaran Ujian Dinas & UPKP</h1>
        <p class="alur-hero-text">
            Proses pendaftaran Ujian Dinas (UDIN) dan Ujian Penyesuaian Kenaikan Pangkat (UPKP) 
            dilaksanakan secara daring melalui portal ini. Ikuti setiap tahapan dengan saksama agar 
            pengajuan Anda berjalan lancar.
        </p>
    </div>
</div>

<!-- TIMELINE -->
<section class="alur-timeline-container">
    <div class="alur-section-title">
        <h2>Tahapan Pendaftaran</h2>
        <p>Setiap peserta wajib melalui seluruh tahapan berikut secara berurutan</p>
    </div>

    <div class="alur-timeline">

        <?php
        $steps = [
            [
                "Registrasi Akun",
                "Buat akun menggunakan email aktif dan data diri lengkap untuk masuk ke sistem pendaftaran.",
                ["Nama lengkap sesuai identitas", "Email aktif pribadi", "Verifikasi email otomatis"]
            ],
            [
                "Lengkapi Profil & Unggah Dokumen",
                "Lengkapi biodata, riwayat pendidikan, dan unggah seluruh dokumen persyaratan yang ditentukan.",
                ["Scan ijazah terbaru", "SK pangkat/jabatan terakhir", "Dokumen pendukung lainnya"]
            ],
            [
                "Seleksi Administrasi",
                "Panitia memverifikasi kelengkapan dan keabsahan dokumen. Hasil: Memenuhi Syarat (MS) atau Tidak Memenuhi Syarat (TMS).",
                ["Validasi dokumen oleh verifikator", "Catatan jika ada kekurangan", "Proses transparan"]
            ],
            [
                "Pengumuman Hasil Seleksi",
                "Peserta dapat melihat status lolos/tidak lolos seleksi administrasi melalui dashboard masing-masing.",
                ["Notifikasi di dashboard", "Status personal", "Informasi jadwal ujian"]
            ],
            [
                "Pelaksanaan Ujian",
                "Peserta yang lolos mengikuti ujian sesuai jadwal dan lokasi yang ditentukan (daring/luring).",
                ["Ujian berbasis kompetensi", "Pengawasan ketat", "Tata tertib peserta"]
            ],
            [
                "Pengumuman Hasil Ujian",
                "Hasil ujian diumumkan secara resmi melalui portal dan dashboard peserta. Status kelulusan dapat dilihat secara personal.",
                ["Skor dan status kelulusan", "Akses personal", "Diumumkan serentak"]
            ],
            [
                "Unduh Sertifikat Kelulusan",
                "Peserta yang dinyatakan lulus dapat mengunduh sertifikat resmi sebagai bukti kelulusan UDIN/UPKP.",
                ["Sertifikat digital", "Tersedia di dashboard", "Dapat dicetak mandiri"]
            ],
        ];

        foreach ($steps as $i => $step) {
        ?>
        <div class="alur-timeline-item wow fadeInUp" data-wow-delay="<?= $i * 0.2 ?>s">
            <div class="alur-timeline-icon">
                <div class="alur-icon-bg"><?= $i+1 ?></div>
            </div>
            <div class="alur-timeline-content">
                <div class="alur-timeline-step">Tahap <?= $i+1 ?></div>
                <h3 class="alur-timeline-title"><?= $step[0] ?></h3>
                <p class="alur-timeline-desc"><?= $step[1] ?></p>
                <div class="alur-timeline-highlight">
                    <ul>
                        <?php foreach ($step[2] as $li) echo "<li>$li</li>"; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php } ?>

    </div>
</section>

<?php include 'footer.php'; ?>