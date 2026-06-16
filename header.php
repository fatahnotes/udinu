<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?> - OSDM Kemdiktisaintek</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

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
/* === FIX KHUSUS SECTION PRINSIP === */
.principle-card {
    background: #fff;
    border-radius: 10px;
    padding: 30px 25px;
    height: 100%;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    overflow: visible !important;
    transition: all 0.3s ease;
}

.principle-card:hover {
    transform: translateY(-5px);
}

.principle-icon {
    width: 70px;
    height: 70px;
    background: #0d6efd;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
    font-size: 26px;
}

.principle-card h5 {
    font-weight: 700;
    margin-bottom: 15px;
}

.principle-card p {
    font-size: 15px;
    line-height: 1.7;
}

/* Pastikan animasi tidak memotong */
.wow, .row, .col-lg-3 {
    overflow: visible !important;
}

/* Custom styles for auth pages */
.auth-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 0;
}

.auth-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.auth-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.auth-body {
    padding: 30px;
}

.form-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #667eea;
}

.form-control {
    padding-left: 45px;
    height: 45px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
    background-color: #667eea;
    color: white;
}

.tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 8px 8px;
    padding: 20px;
    background: white;
}
</style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Topbar Start -->
    <div class="container-fluid topbar px-0 px-lg-4 bg-light py-2 d-none d-lg-block">
        <div class="container">
            <div class="row gx-0 align-items-center">
                <div class="col-lg-8 text-center text-lg-start mb-lg-0">
                    <div class="d-flex flex-wrap">
                        <div class="border-end border-primary pe-3">
                            <a href="#" class="text-muted small" aria-label="Lokasi Kami"><i class="fas fa-map-marker-alt text-primary me-2"></i>Lokasi Kami</a>
                        </div>
                        <div class="ps-3">
                            <a href="mailto:osdm@kemdiktisaintek.go.id" class="text-muted small"><i class="fas fa-envelope text-primary me-2"></i>osdm@kemdiktisaintek.go.id</a>
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
                <a href="index.php" class="navbar-brand p-0" aria-label="OSDM Kemdiktisaintek">
                    <img src="img/logo-dikti.svg" alt="Logo OSDM Kemdiktisaintek">
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
                                <li><a class="dropdown-item" href="/gurugaruda/modules/dashboard/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="/gurugaruda/modules/profile/profile.php"><i class="fas fa-user-circle me-2"></i>Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/gurugaruda/modules/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
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
                    <a href="tel:+622157111144" class="btn btn-light btn-lg-square rounded-circle position-relative wow tada" data-wow-delay=".9s" aria-label="Hubungi Kami">
                        <i class="fa fa-phone-alt fa-2x"></i>
                        <div class="position-absolute" style="top: 7px; right: 12px;">
                            <span><i class="fa fa-comment-dots text-secondary"></i></span>
                        </div>
                    </a>
                    <div class="d-flex flex-column ms-3">
                        <span>Hubungi Segera Kami</span>
                        <a href="tel:+622157111144"><span class="text-dark">Telp: +62 21 57111144</span></a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar & Hero End -->