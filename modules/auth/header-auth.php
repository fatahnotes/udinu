<?php
require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistem Pendaftaran Guru Garuda</title>
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Inter:slnt,wght@-10..0,100..900&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo base_url('assets/css/style.css'); ?>" rel="stylesheet">
    
    <?php if (!empty($customCSS)) : ?>
    <style><?php echo $customCSS; ?></style>
    <?php endif; ?>

    <style>
        /* Auth Page Styles */
        .auth-page {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            min-height: 100vh;
            padding: 40px 0;
            display: flex;
            align-items: center;
        }

        .auth-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .auth-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            margin: 20px;
        }

        .auth-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .auth-body {
            padding: 40px;
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-logo img {
            height: 60px;
        }

        .auth-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #0d6efd;
        }

        .auth-subtitle {
            color: #6c757d;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .input-group {
            border-radius: 10px;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            color: #6c757d;
            padding: 12px 15px;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group .form-control:focus {
            border-color: #e9ecef;
        }

        .input-group .form-control:focus + .input-group-text {
            border-color: #0d6efd;
        }

        /* Password Strength */
        .password-strength {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #fd7e14; width: 50%; }
        .strength-good { background-color: #ffc107; width: 75%; }
        .strength-strong { background-color: #28a745; width: 100%; }

        /* Button Styles */
        .btn-auth {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            border: none;
            color: white;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-auth:hover {
            background: linear-gradient(135deg, #0b5ed7 0%, #0bb8d9 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }

        .btn-auth-secondary {
            background: white;
            border: 2px solid #0d6efd;
            color: #0d6efd;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-auth-secondary:hover {
            background: #0d6efd;
            color: white;
            transform: translateY(-2px);
        }

        /* Alert Styles */
        .auth-alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        /* Demo Accounts Table */
        .demo-table {
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
        }

        .demo-table th {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 12px 15px;
        }

        .demo-table td {
            padding: 10px 15px;
            border-color: #e9ecef;
        }

        .badge-role {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-header {
                padding: 30px 20px;
            }
            
            .auth-body {
                padding: 30px 20px;
            }
            
            .auth-title {
                font-size: 2rem;
            }
            
            .btn-auth, .btn-auth-secondary {
                padding: 12px 20px;
                font-size: 15px;
            }
        }
    </style>
</head>

<body class="auth-page">
    <!-- Spinner -->
    <div id="spinner" class="spinner-border text-primary position-fixed" style="width: 3rem; height: 3rem; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none;">
        <span class="visually-hidden">Loading...</span>
    </div>

    <div class="auth-container">
        <!-- Logo Header -->
        <div class="auth-logo">
            <img src="<?php echo base_url('img/logo-dikti.svg'); ?>" alt="Logo Kemdiktisaintek" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjYwIiB2aWV3Qm94PSIwIDAgMjAwIDYwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iNjAiIGZpbGw9IiMwZDZlZmQiIHJ4PSIxMCIvPjx0ZXh0IHg9IjEwMCIgeT0iMzUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkdVUlUgR0FSVURBPC90ZXh0Pjwvc3ZnPg=='">
        </div>

        <!-- Auth Card -->
        <div class="auth-card">