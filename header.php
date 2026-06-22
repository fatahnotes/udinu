<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?> - Portal UDIN & UPKP</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="UDIN, UPKP, ujian dinas, kenaikan pangkat, ASN, pendaftaran ujian" name="keywords">
    <meta content="Portal resmi pendaftaran Ujian Dinas (UDIN) dan Ujian Penyesuaian Kenaikan Pangkat (UPKP) bagi Aparatur Sipil Negara secara online." name="description">

    <!-- Favicon -->
    <link rel="icon" href="img/icon_diktisaintek_new.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="img/icon_diktisaintek_new.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/icon_diktisaintek_new.svg">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:slnt,wght@-10..0,100..900&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link rel="stylesheet" href="lib/animate/animate.min.css" />
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    
    <?php if (!empty($customCSS)) : ?>
    <!-- Page Specific CSS -->
    <style><?php echo $customCSS; ?></style>
    <?php endif; ?>

    <style>
/* === TAMBAHAN GRADASI KUNING-ORANYE === */
.btn-grad {
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    border: none;
    color: #000 !important;
    font-weight: 600;
    transition: all 0.3s ease;
}
.btn-grad:hover {
    background: linear-gradient(135deg, #FF9133, #F9DA00);
    color: #000 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255,145,51,0.4);
}

/* Auth page latar gradasi */
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    padding: 40px 0;
}
.auth-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}
.auth-header {
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    color: #000;
    padding: 30px;
    text-align: center;
    font-weight: 700;
}
.auth-body {
    padding: 30px;
}

.form-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #FF9133; /* ikon form warna oranye */
}
.form-control {
    padding-left: 45px;
    height: 45px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}
.form-control:focus {
    border-color: #FF9133;
    box-shadow: 0 0 0 0.2rem rgba(255,145,51,0.25);
}

.password-strength {
    height: 5px;
    margin-top: 5px;
    border-radius: 2px;
    background-color: #e9ecef;
    overflow: hidden;
}
.password-strength-bar {
    height: 100%;
    width: 0%;
    transition: width 0.3s ease, background-color 0.3s ease;
}
.strength-weak { background-color: #dc3545; width: 25%; }
.strength-fair { background-color: #fd7e14; width: 50%; }
.strength-good { background-color: #ffc107; width: 75%; }
.strength-strong { background-color: #28a745; width: 100%; }

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 10px 20px;
    border-radius: 8px 8px 0 0;
}
.nav-tabs .nav-link.active {
    background-color: #F9DA00;
    color: #000;
    font-weight: 600;
}
.tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 8px 8px;
    padding: 20px;
    background: white;
}

/* Ikon kotak prinsip (jika digunakan) */
.principle-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    color: #000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
    font-size: 26px;
}

/* Navbar & topbar aksen */
.topbar {
    border-bottom: 3px solid #FF9133;
}
.topbar .text-primary,
.topbar .text-primary i {
    color: #FF9133 !important;
}
.topbar .border-primary {
    border-color: #FF9133 !important;
}
.navbar .btn-primary {
    background: linear-gradient(135deg, #F9DA00, #FF9133);
    border: none;
    color: #000 !important;
    font-weight: 600;
}
.navbar .btn-primary:hover {
    background: linear-gradient(135deg, #FF9133, #F9DA00);
}
    </style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-warning" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Topbar Start -->
    <div class="container-fluid topbar px-0 px-lg-4 bg-white py-2 d-none d-lg-block">
        <div class="container">
            <div class="row gx-0 align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-lg-0">
                    <div class="d-flex flex-wrap">
                        <div class="border-end border-primary pe-3">
                            <a href="#" class="text-muted small" aria-label="Lokasi Kami"><i class="fas fa-map-marker-alt text-primary me-2"></i>Kantor Pusat Ujian</a>
                        </div>
                        <div class="ps-3">
                            <a href="mailto:info@kemdiktisaintek.go.id" class="text-muted small"><i class="fas fa-envelope text-primary me-2"></i>info@kemdiktisaintek.go.id</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center text-lg-end">
                    <div class="d-flex justify-content-end">
                        <div class="d-flex border-end border-primary pe-3">
                            <a class="btn p-0 text-primary me-3" href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a class="btn p-0 text-primary me-3" href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a class="btn p-0 text-primary me-3" href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a class="btn p-0 text-primary me-0" href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                        <div class="dropdown ms-3">
                            <a href="#" class="dropdown-toggle text-dark" data-bs-toggle="dropdown" aria-label="Pilih Bahasa"><small><i class="fas fa-globe-europe text-primary me-2"></i> Indonesia</small></a>
                            <div class="dropdown-menu rounded">
                                <a href="#" class="dropdown-item">Indonesia</a>
                                <a href="#" class="dropdown-item">English</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar px-0 px-lg-4 py-lg-0">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a href="index.php" class="navbar-brand p-0" aria-label="Portal UDIN & UPKP">
                    <img src="img/logo-dikti.svg" alt="UDIN & UPKP">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-label="Toggle navigation">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav mx-0 mx-lg-auto">
                        <a href="index.php" class="nav-item nav-link <?= ($activePage == 'home') ? 'active' : '' ?>">Beranda</a>
                        <a href="pengumuman.php" class="nav-item nav-link <?= ($activePage == 'pengumuman') ? 'active' : '' ?>">Pengumuman</a>
                        <a href="alur.php" class="nav-item nav-link <?= ($activePage == 'alur') ? 'active' : '' ?>">Alur</a>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/udinu/modules/dashboard/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="/udinu/modules/profile/profile.php"><i class="fas fa-user-circle me-2"></i>Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/udinu/modules/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="nav-btn px-3">
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="/udinu/modules/auth/register.php" class="btn btn-primary rounded-pill py-2 px-4 ms-3 flex-shrink-0">Registrasi</a>
                            <?php endif; ?>
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="/udinu/modules/auth/login.php" class="btn btn-primary rounded-pill py-2 px-4 ms-3 flex-shrink-0">Login</a>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                </div>
                <div class="d-none d-xl-flex flex-shrink-0 ps-4">
                    <a href="tel:+62211500123" class="btn btn-light btn-lg-square rounded-circle position-relative wow tada" data-wow-delay=".9s" aria-label="Hubungi Helpdesk">
                        <i class="fa fa-phone-alt fa-2x text-warning"></i>
                        <div class="position-absolute" style="top: 7px; right: 12px;">
                            <span><i class="fa fa-comment-dots text-secondary"></i></span>
                        </div>
                    </a>
                    <div class="d-flex flex-column ms-3">
                        <span>Bantuan Pendaftaran</span>
                        <a href="tel:+62211500123"><span class="text-dark">Telp: 021-1500-123</span></a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar & Hero End -->