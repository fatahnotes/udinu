<?php
$pageTitle = "Alur Seleksi Guru Garuda";
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
    padding: 0 2rem 5rem;
}

.alur-section-title {
    text-align: center;
    margin-bottom: 3rem;
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
    padding: 3rem 0;
}

.alur-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    background: linear-gradient(to bottom, #00c2ff, #015fc9);
}

.alur-timeline-item {
    display: flex;
    margin-bottom: 4rem;
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
    background: linear-gradient(135deg,#015fc9,#00c2ff);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.alur-timeline-content {
    background: #fff;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
    width: calc(50% - 70px);
    margin: 0 20px;
}

.alur-timeline-step {
    font-weight: 600;
    color: #015fc9;
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
    background: rgba(1,95,201,.05);
    border-left: 4px solid #015fc9;
    padding: 1rem;
    border-radius: 10px;
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
        <h1 class="alur-hero-title">Alur Seleksi Guru & Tenaga Kependidikan</h1>
        <p class="alur-hero-text">
            Proses seleksi dilaksanakan secara sistematis, objektif, dan transparan melalui sistem digital terintegrasi
            untuk memastikan terpilihnya pendidik dan tenaga kependidikan terbaik bagi SMA Unggul Garuda.
        </p>
    </div>
</div>

<!-- TIMELINE -->
<section class="alur-timeline-container">
    <div class="alur-section-title">
        <h2>Tahapan Seleksi Nasional</h2>
        <p>Setiap peserta akan melalui tahapan berikut secara berurutan melalui portal seleksi</p>
    </div>

    <div class="alur-timeline">

        <?php
        $steps = [
    [
        "Registrasi Akun",
        "Peserta membuat akun menggunakan nama lengkap dan email aktif sebagai pintu masuk ke sistem seleksi.",
        ["Nama lengkap", "Email aktif", "Verifikasi email otomatis"]
    ],
    [
        "Lengkapi Profil",
        "Peserta wajib melengkapi data pribadi sebagai dasar validasi identitas dan kelayakan administrasi.",
        ["Identitas diri", "Riwayat pendidikan", "Instansi asal"]
    ],
    [
        "Pilih Jalur Seleksi",
        "Peserta memilih jalur seleksi yang tersedia: Kepala Sekolah, Guru, atau Tenaga Kependidikan sesuai kualifikasi.",
        ["Sesuai kompetensi", "Formasi berbeda", "Persyaratan khusus tiap jalur"]
    ],
    [
        "Unggah Dokumen Persyaratan",
        "Peserta mengunggah seluruh dokumen sesuai persyaratan yang telah ditentukan pada masing-masing formasi.",
        ["Ijazah dan transkrip", "Dokumen pengalaman kerja", "Sertifikat pendukung"]
    ],
    [
        "Seleksi Administrasi",
        "Panitia melakukan verifikasi dan validasi dokumen dengan status Memenuhi Syarat (MS) atau Tidak Memenuhi Syarat (TMS).",
        ["Validasi keabsahan dokumen", "Catatan verifikator", "Proses terdokumentasi"]
    ],
    [
        "Pengumuman Hasil Administrasi",
        "Peserta dapat melihat hasil seleksi administrasi secara personal melalui akun masing-masing di portal seleksi.",
        ["Notifikasi di dashboard", "Status lolos/tidak lolos", "Transparan dan personal"]
    ],
    [
        "Tes Kompetensi",
        "Peserta yang lolos administrasi mengikuti tes kompetensi sesuai formasi untuk mengukur kemampuan akademik dan profesional.",
        ["Berbasis bidang keahlian", "Instrumen terukur", "Hasil terekam sistem"]
    ],
    [
        "Asesmen Center",
        "Peserta mengikuti asesmen lanjutan untuk menilai aspek kompetensi, integritas, dan kesiapan peran secara lebih komprehensif.",
        ["Simulasi dan studi kasus", "Penilaian oleh asesor", "Pendekatan objektif"]
    ],
    [
        "Pengumuman Akhir",
        "Hasil akhir seleksi diumumkan secara resmi melalui dashboard peserta sesuai jadwal yang ditetapkan panitia.",
        ["Status akhir kelulusan", "Akses melalui akun masing-masing", "Terdokumentasi dalam sistem"]
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
