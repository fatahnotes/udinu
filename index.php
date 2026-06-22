<?php
$pageTitle = "Pendaftaran UDIN & UPKP";
$activePage = "home";
$customCSS = "
<style>
    /* Gradasi ornamen kuning-oranye */
    .btn-grad {
        background: linear-gradient(135deg, #F9DA00, #FF9133);
        border: none;
        color: #000 !important;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-grad:hover {
        background: linear-gradient(135deg, #FF9133, #F9DA00);
        color: #000 !important;
    }
    .feature-icon {
        background: linear-gradient(135deg, #F9DA00, #FF9133);
        color: #fff;
    }
    .service-icon {
        background: linear-gradient(135deg, #F9DA00, #FF9133) !important;
    }
    .text-grad {
        background: linear-gradient(135deg, #F9DA00, #FF9133);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: bold;
    }
    
    /* Style khusus untuk persyaratan collapse */
    .persyaratan-card .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }
    .persyaratan-card .card-header {
        background: #fff;
        border-bottom: none;
    }
    .persyaratan-list {
        list-style: none;
        padding-left: 0;
    }
    .persyaratan-list li {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.95rem;
    }
    .persyaratan-list li:last-child {
        border-bottom: none;
    }
    .persyaratan-list li i {
        color: #FF9133;
        margin-right: 8px;
    }
</style>
";
$customJS = "";

include 'header.php';
?>

<!-- Carousel Start -->
<div class="header-carousel owl-carousel">

    <!-- Slide 1 -->
    <div class="header-carousel-item bg-primary">
        <div class="carousel-caption">
            <div class="container">
                <div class="row g-4 align-items-center text-center">
                    <div class="col-lg-3 animated fadeInLeft">
                        <div class="carousel-img">
                            <img src="img/slide1-left.png" class="img-fluid rounded" alt="Ujian ASN">
                        </div>
                    </div>
                    <div class="col-lg-6 animated fadeInUp">
                        <div>
                            <h4 class="text-white text-uppercase fw-bold mb-3">Portal Resmi Pendaftaran</h4>
                            <h1 class="display-5 text-white mb-4">Ujian Dinas (UDIN) &<br>Ujian Penyesuaian Kenaikan Pangkat (UPKP)</h1>
                            <p class="mb-5 fs-5 text-white">
                                Layanan digital terpadu untuk Aparatur Sipil Negara (ASN) dalam mengikuti ujian
                                peningkatan kompetensi dan kenaikan pangkat secara <b>objektif, transparan, dan akuntabel</b>.
                            </p>
                            <div class="d-flex justify-content-center flex-wrap gap-3">
                                <a class="btn btn-grad rounded-pill py-3 px-5" href="/udinu//modules/auth/register.php">Mulai Pendaftaran</a>
                                <a class="btn btn-outline-light rounded-pill py-3 px-5" href="/udinu/alur.php">Lihat Alur</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 animated fadeInRight">
                        <div class="carousel-img">
                            <img src="img/slide1-right.png" class="img-fluid rounded" alt="Pelayanan Profesional">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 2 -->
    <div class="header-carousel-item bg-dark">
        <div class="carousel-caption">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-5 animated fadeInLeft">
                        <div class="carousel-img">
                            <img src="img/carousel-teacher.png" class="img-fluid w-100" alt="Proses Ujian">
                        </div>
                    </div>
                    <div class="col-lg-7 animated fadeInRight">
                        <div class="text-sm-center text-md-end">
                            <h4 class="text-white text-uppercase fw-bold mb-3">Proses Penyelenggaraan Ujian</h4>
                            <h1 class="display-5 text-white mb-4">Objektif, Transparan, dan Akuntabel</h1>
                            <p class="mb-5 fs-5">
                                Seluruh tahapan ujian dilaksanakan secara daring dengan pengawasan ketat,
                                penilaian otomatis, serta hasil yang dapat dipertanggungjawabkan.
                            </p>
                            <div class="d-flex justify-content-center justify-content-md-end">
                                <a class="btn btn-grad rounded-pill py-3 px-5" href="#tentang">Pelajari Ujian</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Carousel End -->

<!-- Feature Start (FITUR PORTAL) -->
<div id="feature" class="container-fluid feature bg-light py-5">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
            <h1 class="display-4 mb-4">Fitur Portal<br><span class="text-grad">UDIN & UPKP</span></h1>
            <p class="mb-0">
                Portal ini menyediakan berbagai fitur untuk memudahkan ASN dalam proses pendaftaran hingga pengumuman hasil ujian.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.2s">
                <div class="feature-item p-4 pt-0">
                    <div class="feature-icon p-4 mb-4"><i class="fas fa-user-plus fa-3x"></i></div>
                    <h4 class="mb-4">Registrasi Mudah</h4>
                    <p class="mb-4">Daftar akun secara online dengan email aktif dan data diri. Langsung dapat mengakses dashboard pribadi.</p>
                    <a class="btn btn-primary rounded-pill py-2 px-4" href="modules/auth/register.php">Daftar Akun</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.4s">
                <div class="feature-item p-4 pt-0">
                    <div class="feature-icon p-4 mb-4"><i class="fas fa-cloud-upload-alt fa-3x"></i></div>
                    <h4 class="mb-4">Unggah Dokumen</h4>
                    <p class="mb-4">Unggah dokumen persyaratan seperti ijazah, SK pangkat, dan dokumen pendukung langsung dari dashboard.</p>
                    <a class="btn btn-primary rounded-pill py-2 px-4" href="#persyaratan">Lihat Persyaratan</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.6s">
                <div class="feature-item p-4 pt-0">
                    <div class="feature-icon p-4 mb-4"><i class="fas fa-chart-line fa-3x"></i></div>
                    <h4 class="mb-4">Pantau Status</h4>
                    <p class="mb-4">Lacak status pendaftaran, verifikasi berkas, jadwal ujian, dan hasil secara real‑time di dashboard.</p>
                    <a class="btn btn-primary rounded-pill py-2 px-4" href="/udinu//modules/auth/login.php">Cek Status</a>
                </div>
            </div>
            <div class="col-md-6 col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.8s">
                <div class="feature-item p-4 pt-0">
                    <div class="feature-icon p-4 mb-4"><i class="fas fa-bell fa-3x"></i></div>
                    <h4 class="mb-4">Notifikasi</h4>
                    <p class="mb-4">Dapatkan pemberitahuan otomatis setiap ada pembaruan status, pengumuman, atau jadwal penting.</p>
                    <a class="btn btn-primary rounded-pill py-2 px-4" href="/udinu//modules/auth/login.php">Aktifkan Notifikasi</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Feature End -->

<!-- About Start (TENTANG UJIAN) -->
<div id="tentang" class="container-fluid bg-light about py-5">
    <div class="container py-5">
        <div class="text-center mx-auto mb-5 wow fadeInDown" style="max-width: 900px;">
            <h4 class="text-primary">Tentang Ujian</h4>
            <h1 class="display-4 mb-4">Ujian Dinas & Penyesuaian Kenaikan Pangkat</h1>
            <p class="text-muted">
                Ujian Dinas (UDIN) merupakan ujian bagi ASN untuk memenuhi syarat kenaikan pangkat reguler,
                sedangkan Ujian Penyesuaian Kenaikan Pangkat (UPKP) diberikan bagi ASN yang memiliki ijazah/kompetensi baru
                untuk penyesuaian pangkat tanpa menunggu masa kerja. Keduanya diselenggarakan secara objektif, transparan, dan akuntabel
                guna mendukung pengembangan karier aparatur negara yang profesional dan berintegritas.
            </p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4 wow zoomIn" data-wow-delay="0.2s">
                <div class="bg-white p-4 rounded h-100 text-center">
                    <div class="mb-3 text-primary"><i class="fas fa-file-alt fa-2x"></i></div>
                    <h5 class="text-primary mb-3">Ujian Dinas (UDIN)</h5>
                    <p>Ujian bagi ASN untuk kenaikan pangkat reguler. Menguji kompetensi teknis, manajerial, dan sosial kultural sesuai jenjang jabatan yang dituju.</p>
                </div>
            </div>
            <div class="col-md-4 wow fadeInUp" data-wow-delay="0.5s">
                <div class="bg-white p-4 rounded h-100 text-center">
                    <div class="mb-3 text-primary"><i class="fas fa-graduation-cap fa-2x"></i></div>
                    <h5 class="text-primary mb-3">Ujian Penyesuaian Kenaikan Pangkat (UPKP)</h5>
                    <p>Ujian untuk penyesuaian pangkat karena adanya ijazah baru atau peningkatan kualifikasi pendidikan yang dimiliki ASN.</p>
                </div>
            </div>
            <div class="col-md-4 wow slideInRight" data-wow-delay="0.8s">
                <div class="bg-white p-4 rounded h-100 text-center">
                    <div class="mb-3 text-primary"><i class="fas fa-shield-alt fa-2x"></i></div>
                    <h5 class="text-primary mb-3">Berintegritas & Adil</h5>
                    <p>Seluruh proses ujian dilaksanakan dengan pengawasan ketat, penilaian otomatis, dan audit untuk menjamin keadilan serta bebas dari KKN.</p>
                </div>
            </div>
        </div>

        <div class="row g-5 align-items-center">
            <div class="col-lg-6 wow fadeInLeft">
                <div class="bg-white p-3 rounded">
                    <img src="img/map-lokasi.png" class="img-fluid rounded w-100" alt="Peta Peserta Ujian Nasional">
                </div>
            </div>
            <div class="col-lg-6 wow fadeInRight">
                <div class="row g-4">
                    <div class="col-sm-6">
                        <div class="counter-item bg-white rounded p-4 text-center h-100">
                            <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">500</span><span class="fs-2 text-primary">+</span>
                            <h6 class="mt-2">Peserta Terdaftar</h6>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="counter-item bg-white rounded p-4 text-center h-100">
                            <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">34</span>
                            <h6 class="mt-2">Satker Sudah Mengikuti</h6>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="counter-item bg-white rounded p-4 text-center h-100">
                            <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">3</span>
                            <h6 class="mt-2">Jenis Ujian</h6>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="counter-item bg-white rounded p-4 text-center h-100">
                            <span class="text-primary fs-2 fw-bold" data-toggle="counter-up">3</span>
                            <h6 class="mt-2">Tahapan Ujian</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- About End -->

<!-- Service Start (PERSYARATAN PESERTA) -->
<div id="persyaratan" class="container-fluid service py-5">
    <div class="container py-5">
        <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
            <h1 class="display-4 mb-4">Persyaratan Peserta</h1>
            <p class="mb-0">
                Pilih jenis ujian yang sesuai dan lihat persyaratan dokumen yang harus dilengkapi.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Ujian Dinas Tk. I -->
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.2s">
                <div class="service-item persyaratan-card">
                    <div class="service-img">
                        <img src="img/blog-1.png" class="img-fluid rounded-top w-100" alt="Ujian Dinas Tingkat I">
                        <div class="service-icon p-3"><i class="fas fa-file-contract fa-2x"></i></div>
                    </div>
                    <div class="service-content p-4">
                        <div class="service-content-inner">
                            <span class="d-inline-block h4 mb-3">Ujian Dinas Tk. I</span>
                            <p class="mb-4">Untuk kenaikan pangkat dari III/d ke atas atau sesuai ketentuan.</p>
                            <a class="btn btn-grad rounded-pill py-2 px-4" data-bs-toggle="collapse" href="#detail-udin1" role="button" aria-expanded="false" aria-controls="detail-udin1">
                                Detail Persyaratan
                            </a>
                            <div class="collapse mt-3" id="detail-udin1">
                                <div class="card card-body">
                                    <ul class="persyaratan-list">
                                        <li><i class="fas fa-check"></i> Surat usul yang ditandatangani pimpinan unit kerja (Unit utama/PTN/LLDikti)</li>
                                        <li><i class="fas fa-check"></i> SK CPNS</li>
                                        <li><i class="fas fa-check"></i> SK PNS</li>
                                        <li><i class="fas fa-check"></i> SK Jabatan terakhir</li>
                                        <li><i class="fas fa-check"></i> Dokumen Penilaian Prestasi Kerja 2 (dua) tahun terakhir dengan nilai baik</li>
                                        <li><i class="fas fa-check"></i> Surat Keputusan Kenaikan Pangkat II/d terakhir</li>
                                        <li><i class="fas fa-check"></i> Surat Pernyataan Keaslian Dokumen Ditandatangani oleh ybs dan Pimpinan Unit Kerja</li>
                                        <li><i class="fas fa-check"></i> Surat Pernyataan Kebenaran Dokumen Dan Status Kepegawaian</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ujian Dinas Tk. II -->
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.4s">
                <div class="service-item persyaratan-card">
                    <div class="service-img">
                        <img src="img/blog-2.png" class="img-fluid rounded-top w-100" alt="Ujian Dinas Tingkat II">
                        <div class="service-icon p-3"><i class="fas fa-file-signature fa-2x"></i></div>
                    </div>
                    <div class="service-content p-4">
                        <div class="service-content-inner">
                            <span class="d-inline-block h4 mb-3">Ujian Dinas Tk. II</span>
                            <p class="mb-4">Untuk kenaikan pangkat dari III/d ke atas atau eselon III.</p>
                            <a class="btn btn-grad rounded-pill py-2 px-4" data-bs-toggle="collapse" href="#detail-udin2" role="button" aria-expanded="false" aria-controls="detail-udin2">
                                Detail Persyaratan
                            </a>
                            <div class="collapse mt-3" id="detail-udin2">
                                <div class="card card-body">
                                    <ul class="persyaratan-list">
                                        <li><i class="fas fa-check"></i> Surat usul yang ditandatangani pimpinan unit kerja (Unit utama/PTN/LLDikti)</li>
                                        <li><i class="fas fa-check"></i> SK CPNS</li>
                                        <li><i class="fas fa-check"></i> SK PNS</li>
                                        <li><i class="fas fa-check"></i> SK Jabatan terakhir (Tingkat eselon III)</li>
                                        <li><i class="fas fa-check"></i> Dokumen Penilaian Prestasi Kerja 2 (dua) tahun terakhir dengan nilai baik</li>
                                        <li><i class="fas fa-check"></i> Surat Keputusan Kenaikan Pangkat III/d terakhir</li>
                                        <li><i class="fas fa-check"></i> Surat Pernyataan Keaslian Dokumen Ditandatangani oleh ybs dan Pimpinan Unit Kerja</li>
                                        <li><i class="fas fa-check"></i> Surat Pernyataan Kebenaran Dokumen Dan Status Kepegawaian</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UPKP -->
            <div class="col-md-6 col-lg-6 col-xl-4 wow fadeInUp" data-wow-delay="0.6s">
                <div class="service-item persyaratan-card">
                    <div class="service-img">
                        <img src="img/blog-3.png" class="img-fluid rounded-top w-100" alt="UPKP">
                        <div class="service-icon p-3"><i class="fas fa-id-card fa-2x"></i></div>
                    </div>
                    <div class="service-content p-4">
                        <div class="service-content-inner">
                            <span class="d-inline-block h4 mb-3">UPKP</span>
                            <p class="mb-4">Penyesuaian kenaikan pangkat karena ijazah baru atau kualifikasi pendidikan.</p>
                            <a class="btn btn-grad rounded-pill py-2 px-4" data-bs-toggle="collapse" href="#detail-upkp" role="button" aria-expanded="false" aria-controls="detail-upkp">
                                Detail Persyaratan
                            </a>
                            <div class="collapse mt-3" id="detail-upkp">
                                <div class="card card-body">
                                    <ul class="persyaratan-list">
                                        <li><i class="fas fa-check"></i> Surat usul yang ditandatangani pimpinan unit kerja (Unit utama/PTN/LLDikti)</li>
                                        <li><i class="fas fa-check"></i> SK CPNS</li>
                                        <li><i class="fas fa-check"></i> SK PNS</li>
                                        <li><i class="fas fa-check"></i> SK Jabatan terakhir (Tingkat eselon III)</li>
                                        <li><i class="fas fa-check"></i> Surat Keputusan Kenaikan Pangkat terakhir</li>
                                        <li><i class="fas fa-check"></i> Asli ijazah</li>
                                        <li><i class="fas fa-check"></i> Dokumen Penilaian Prestasi Kerja 2 (dua) tahun terakhir dengan nilai baik</li>
                                        <li><i class="fas fa-check"></i> Surat rekomendasi menduduki jabatan yang ditandatangani Pimpinan Unit Kerja</li>
                                        <li><i class="fas fa-check"></i> Surat Pernyataan Keaslian Dokumen Ditandatangani oleh ybs dan Pimpinan Unit Kerja</li>
                                        <li><i class="fas fa-check"></i> Surat Pernyataan Kebenaran Dokumen Dan Status Kepegawaian</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Service End -->

<?php include 'footer.php'; ?>