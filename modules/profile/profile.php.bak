<?php
ob_start(); // Mulai output buffering

// Atur error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

$pageTitle = "Profil Saya";
$activePage = "profile";
$customCSS = "";
$customJS = "";

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';

// Require login
require_login();

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];

// Database connection
$db = get_db_connection();

// GET MODE - Menentukan mode view atau edit
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'view';

// Get profile data
$profile = null;
$profile_complete = false;

// Get mata pelajaran yang sudah dipilih
$user_subjects = [];
try {
    $stmt = $db->prepare("SELECT mata_pelajaran FROM user_subjects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {
    error_log("User subjects fetch error: " . $e->getMessage());
}

try {
    $stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if ($profile) {
        $profile_complete = $profile['is_profile_complete'];
        
        // Calculate profile completion percentage - DIPERBARUI dengan field baru
        // Sekarang termasuk foto dan status_pekerjaan
        $required_fields = ['gender', 'phone', 'address', 'tanggal_lahir', 'last_education', 
                           'major', 'institution', 'foto', 'pekerjaan_saat_ini', 
                           'status_pekerjaan', 'provinsi']; // Ditambahkan status_pekerjaan
        
        $completed_fields = 0;
        
        foreach ($required_fields as $field) {
            if (!empty($profile[$field])) {
                $completed_fields++;
            }
        }
        
        // Cek kondisi khusus untuk pekerjaan
        if (!empty($profile['pekerjaan_saat_ini'])) {
            if ($profile['pekerjaan_saat_ini'] == 'Guru' && !empty($profile['jabatan_guru'])) {
                $completed_fields++; // jabatan_guru
                // Cek mata pelajaran
                if (count($user_subjects) > 0) {
                    $completed_fields++;
                }
            } elseif ($profile['pekerjaan_saat_ini'] == 'Non-Guru' && !empty($profile['jabatan_non_guru'])) {
                $completed_fields++; // jabatan_non_guru
            }
        }
        
        $total_fields = count($required_fields) + 2; // +2 untuk jabatan dan mata_pelajaran
        $completion_percentage = round(($completed_fields / $total_fields) * 100);
        $completion_percentage = min($completion_percentage, 100);
        
        // Jika foto tidak ada, kurangi persentase
        if (empty($profile['foto'])) {
            $completion_percentage = min($completion_percentage, 90);
        }
        
    } else {
        $completion_percentage = 0;
    }
    
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    $completion_percentage = 0;
}

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        // Get form data - DITAMBAHKAN status_pekerjaan
        $gender = sanitize_input($_POST['gender'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $tanggal_lahir = sanitize_input($_POST['tanggal_lahir'] ?? '');
        $last_education = sanitize_input($_POST['last_education'] ?? '');
        $major = sanitize_input($_POST['major'] ?? '');
        $institution = sanitize_input($_POST['institution'] ?? '');
        $pekerjaan_saat_ini = sanitize_input($_POST['pekerjaan_saat_ini'] ?? '');
        $status_pekerjaan = sanitize_input($_POST['status_pekerjaan'] ?? ''); // Field baru
        $jabatan_guru = sanitize_input($_POST['jabatan_guru'] ?? '');
        $jabatan_non_guru = sanitize_input($_POST['jabatan_non_guru'] ?? '');
        $provinsi = sanitize_input($_POST['provinsi'] ?? '');
        $mata_pelajaran = $_POST['mata_pelajaran'] ?? [];
        
        // Validation
        $errors = [];
        
        if (!in_array($gender, ['Laki-laki', 'Perempuan'])) {
            $errors[] = 'Jenis kelamin harus dipilih.';
        }
        
        if (!preg_match('/^08[1-9][0-9]{7,9}$/', $phone)) {
            $errors[] = 'Nomor HP harus dimulai dengan 08 dan terdiri dari 10-12 digit.';
        }
        
        if (empty($address) || strlen($address) < 10) {
            $errors[] = 'Alamat minimal 10 karakter.';
        }
        
        // Validasi tanggal lahir
        if (empty($tanggal_lahir)) {
            $errors[] = 'Tanggal lahir harus diisi.';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $tanggal_lahir);
            if (!$date || $date->format('Y-m-d') !== $tanggal_lahir) {
                $errors[] = 'Format tanggal lahir tidak valid.';
            } else {
                $current_year = date('Y');
                $birth_year = $date->format('Y');
                if ($birth_year < 1900 || $birth_year > $current_year) {
                    $errors[] = "Tahun lahir harus antara 1900 dan $current_year.";
                }
            }
        }
        
        if (empty($last_education)) {
            $errors[] = 'Pendidikan terakhir harus dipilih.';
        }
        
        if (empty($major) || strlen($major) < 2) {
            $errors[] = 'Program studi harus diisi minimal 2 karakter.';
        }
        
        if (empty($institution) || strlen($institution) < 2) {
            $errors[] = 'Instansi asal harus diisi minimal 2 karakter.';
        }
        
        // Validasi foto (jika ada upload) - Wajib untuk kelengkapan
$foto = $profile['foto'] ?? null;

// Cek jika ada file yang diupload
if (isset($_FILES['foto']) && $_FILES['foto']['error'] != UPLOAD_ERR_NO_FILE) {
    $upload_error = $_FILES['foto']['error'];
    
    if ($upload_error === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_name = $_FILES['foto']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['foto']['size']; // PERBAIKAN: 'foto' bukan 'fILES'
        
        if (!in_array($file_ext, $allowed)) {
            $errors[] = 'Format foto tidak didukung. Hanya JPG, JPEG, PNG.';
        } elseif ($file_size > 2 * 1024 * 1024) { // 2MB
            $errors[] = 'Ukuran foto maksimal 2MB.';
        } else {
            // Generate new file name
            $new_file_name = uniqid('foto_', true) . '.' . $file_ext;
            $upload_path = __DIR__ . '/../../storage/uploads/foto/' . $new_file_name;
            
            // Create directory if not exists
            if (!is_dir(dirname($upload_path))) {
                mkdir(dirname($upload_path), 0755, true);
            }
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                // Hapus foto lama jika ada
                if (!empty($profile['foto']) && file_exists(__DIR__ . '/../../' . $profile['foto'])) {
                    unlink(__DIR__ . '/../../' . $profile['foto']);
                }
                $foto = 'storage/uploads/foto/' . $new_file_name;
            } else {
                $errors[] = 'Gagal mengupload foto. Silakan coba lagi.';
            }
        }
    } elseif ($upload_error === UPLOAD_ERR_INI_SIZE || $upload_error === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = 'Ukuran foto terlalu besar. Maksimal 2MB.';
    } elseif ($upload_error === UPLOAD_ERR_PARTIAL) {
        $errors[] = 'Upload foto tidak lengkap. Silakan coba lagi.';
    } elseif ($upload_error === UPLOAD_ERR_NO_TMP_DIR) {
        $errors[] = 'Folder temporary tidak ditemukan. Hubungi administrator.';
    } elseif ($upload_error === UPLOAD_ERR_CANT_WRITE) {
        $errors[] = 'Gagal menulis file. Cek permission folder.';
    } elseif ($upload_error === UPLOAD_ERR_EXTENSION) {
        $errors[] = 'Ekstensi file tidak diizinkan.';
    } else {
        $errors[] = 'Terjadi kesalahan saat upload foto. Error code: ' . $upload_error;
    }
} elseif (empty($foto) && $mode == 'edit') {
    // Jika tidak ada upload baru dan foto belum ada sebelumnya, dan dalam mode edit
    $errors[] = 'Foto wajib diunggah untuk kelengkapan profil.';
}
        
        // Validasi status pekerjaan (field baru)
        if (empty($status_pekerjaan)) {
            $errors[] = 'Status pekerjaan harus dipilih.';
        } elseif (!in_array($status_pekerjaan, ['PNS', 'PPPK', 'Non-ASN'])) {
            $errors[] = 'Status pekerjaan tidak valid.';
        }
        
        if (empty($pekerjaan_saat_ini)) {
            $errors[] = 'Pekerjaan saat ini harus dipilih.';
        } else {
            if ($pekerjaan_saat_ini == 'Guru') {
                if (empty($jabatan_guru)) {
                    $errors[] = 'Jabatan guru harus dipilih.';
                }
                if (count($mata_pelajaran) == 0) {
                    $errors[] = 'Minimal pilih satu mata pelajaran yang pernah diajar.';
                }
            } elseif ($pekerjaan_saat_ini == 'Non-Guru') {
                if (empty($jabatan_non_guru) || strlen($jabatan_non_guru) < 2) {
                    $errors[] = 'Jabatan non guru harus diisi minimal 2 karakter.';
                }
            }
        }
        
        if (empty($provinsi)) {
            $errors[] = 'Provinsi harus dipilih.';
        }
        
        if (empty($errors)) {
            try {
                // Check if profile exists
                $stmt = $db->prepare("SELECT id FROM profiles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $exists = $stmt->fetch();
                
                // Untuk pekerjaan, kita set jabatan_guru atau jabatan_non_guru sesuai pilihan
                $jabatan_guru_value = $pekerjaan_saat_ini == 'Guru' ? $jabatan_guru : null;
                $jabatan_non_guru_value = $pekerjaan_saat_ini == 'Non-Guru' ? $jabatan_non_guru : null;
                
                if ($exists) {
                    // Update existing profile - DITAMBAHKAN status_pekerjaan
                    $stmt = $db->prepare("
                        UPDATE profiles 
                        SET gender = ?, phone = ?, address = ?, tanggal_lahir = ?, 
                            last_education = ?, major = ?, institution = ?, 
                            foto = ?, pekerjaan_saat_ini = ?, status_pekerjaan = ?,
                            jabatan_guru = ?, jabatan_non_guru = ?, provinsi = ?,
                            is_profile_complete = TRUE, updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = ?
                    ");
                    
                    $stmt->execute([
                        $gender, $phone, $address, $tanggal_lahir,
                        $last_education, $major, $institution,
                        $foto, $pekerjaan_saat_ini, $status_pekerjaan,
                        $jabatan_guru_value, $jabatan_non_guru_value, 
                        $provinsi, $user_id
                    ]);
                } else {
                    // Insert new profile - DITAMBAHKAN status_pekerjaan
                    $stmt = $db->prepare("
                        INSERT INTO profiles 
                        (user_id, gender, phone, address, tanggal_lahir, 
                         last_education, major, institution, foto, pekerjaan_saat_ini,
                         status_pekerjaan, jabatan_guru, jabatan_non_guru, provinsi, is_profile_complete)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
                    ");
                    
                    $stmt->execute([
                        $user_id, $gender, $phone, $address, $tanggal_lahir,
                        $last_education, $major, $institution,
                        $foto, $pekerjaan_saat_ini, $status_pekerjaan,
                        $jabatan_guru_value, $jabatan_non_guru_value, $provinsi
                    ]);
                }
                
                // Hapus mata pelajaran lama, lalu simpan yang baru
                $stmt = $db->prepare("DELETE FROM user_subjects WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                foreach ($mata_pelajaran as $mp) {
                    $mp = sanitize_input($mp);
                    if (!empty($mp)) {
                        $stmt = $db->prepare("INSERT INTO user_subjects (user_id, mata_pelajaran) VALUES (?, ?)");
                        $stmt->execute([$user_id, $mp]);
                    }
                }
                
                // Update session
                $_SESSION['profile_complete'] = true;
                
                // Log activity
                log_activity('PROFILE_UPDATE', "Profile updated for user: $user_id", $user_id);
                
                $success = 'Profil berhasil diperbarui!';
                
                // Refresh profile data
                $stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $profile = $stmt->fetch();
                $profile_complete = true;
                $completion_percentage = 100;
                
                // Refresh user subjects
                $stmt = $db->prepare("SELECT mata_pelajaran FROM user_subjects WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // Redirect ke mode view setelah simpan
if (empty($errors)) {
    // Clear output buffer sebelum redirect
    if (ob_get_length()) ob_end_clean();
    
    // Cek apakah headers sudah dikirim
    if (!headers_sent()) {
        header("Location: profile.php?mode=view");
        exit;
    } else {
        // Jika headers sudah dikirim, gunakan JavaScript redirect
        echo '<script>window.location.href = "profile.php?mode=view";</script>';
        exit;
    }
}
                
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $error = 'Terjadi kesalahan saat menyimpan profil. Silakan coba lagi.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// List provinsi Indonesia
$provinsi_list = [
    'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi',
    'Sumatera Selatan', 'Kepulauan Bangka Belitung', 'Bengkulu', 'Lampung',
    'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur',
    'Banten', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur',
    'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur',
    'Kalimantan Utara', 'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan',
    'Sulawesi Tenggara', 'Gorontalo', 'Sulawesi Barat',
    'Maluku', 'Maluku Utara', 'Papua Barat', 'Papua'
];

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<!-- Profile Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card" style="padding: 20px; margin-bottom: 20px;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3" style="font-size: 1.5rem;">Profil Saya</h2>
                    <p class="text-muted mb-0" style="font-size: 0.875rem;">
                        <?php if ($mode == 'view'): ?>
                            Lihat dan kelola data profil Anda
                        <?php else: ?>
                            Lengkapi data profil Anda untuk dapat mengikuti seleksi UDIN & UPKP
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <!-- Tombol Mode -->
                    <?php if ($mode == 'view'): ?>
                        <a href="profile.php?mode=edit" class="btn-dashboard" style="font-size: 0.875rem; padding: 8px 16px;">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    <?php else: ?>
                        <a href="profile.php?mode=view" class="btn-outline-dashboard btn" style="font-size: 0.875rem; padding: 8px 16px;">
                            <i class="fas fa-eye me-2"></i>Lihat Profile
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($error): ?>
    <div class="alert alert-danger dashboard-alert alert-dismissible fade show" role="alert" style="padding: 12px; margin-bottom: 20px; font-size: 0.875rem;">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.75rem;"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success dashboard-alert alert-dismissible fade show" role="alert" style="padding: 12px; margin-bottom: 20px; font-size: 0.875rem;">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.75rem;"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php if ($mode == 'edit'): ?>
    <!-- FORM EDIT MODE -->
    <div class="col-lg-8">
        <div class="dashboard-card" style="padding: 20px;">
            <div class="card-header" style="padding: 12px 0; margin-bottom: 15px;">
                <h4 style="font-size: 1.25rem;"><i class="fas fa-user-edit me-2"></i>Edit Data Pribadi</h4>
            </div>
            
            <form id="profileForm" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="update_profile" value="1">
                
                <!-- FOTO DI ATAS NAMA LENGKAP -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="text-center">
                            <div class="photo-upload-container" style="margin-bottom: 20px;">
                                <!-- Foto Preview -->
                                <div id="fotoPreview" class="mb-3" style="width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 3px solid #dee2e6;">
                                    <?php if (!empty($profile['foto'])): ?>
                                        <img src="<?php echo base_url($profile['foto']); ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user" style="font-size: 60px; color: #6c757d;"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="foto" class="btn-outline-dashboard btn" style="font-size: 0.875rem; padding: 8px 16px; cursor: pointer;">
                                        <i class="fas fa-upload me-2"></i>Pilih Foto
                                    </label>
                                    <input type="file" class="form-control d-none" id="foto" name="foto" accept="image/jpeg,image/png,image/jpg" onchange="previewImage(this)">
                                    <div class="form-text text-center" style="font-size: 0.75rem;">Format: JPG, PNG, maks 2MB</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row" style="margin-bottom: -10px;">
                    <!-- Nama Lengkap -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="full_name" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="full_name" style="font-size: 0.875rem; padding: 6px 12px;"
                                       value="<?php echo htmlspecialchars($user_name); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="email" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Email <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" style="font-size: 0.875rem; padding: 6px 12px;"
                                       value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jenis Kelamin -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="gender" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Jenis Kelamin <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-venus-mars"></i>
                                </span>
                                <select class="form-select" id="gender" name="gender" required style="font-size: 0.875rem; padding: 6px 12px; height: 36px;">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki" <?php echo ($profile && $profile['gender'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="Perempuan" <?php echo ($profile && $profile['gender'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nomor HP -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="phone" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Nomor HP <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="tel" class="form-control" id="phone" name="phone" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;"
                                       placeholder="08xxxxxxxxxx" 
                                       value="<?php echo $profile ? htmlspecialchars($profile['phone']) : ''; ?>" 
                                       required>
                            </div>
                            <div class="form-text" style="font-size: 0.75rem;">Contoh: 081234567890</div>
                        </div>
                    </div>
                    
                    <!-- Tanggal Lahir -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="tanggal_lahir" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Tanggal Lahir <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-birthday-cake"></i>
                                </span>
                                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;"
                                       max="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo $profile && $profile['tanggal_lahir'] ? htmlspecialchars($profile['tanggal_lahir']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pendidikan Terakhir -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="last_education" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Pendidikan Terakhir <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-graduation-cap"></i>
                                </span>
                                <select class="form-select" id="last_education" name="last_education" required style="font-size: 0.875rem; padding: 6px 12px; height: 36px;">
                                    <option value="">Pilih Pendidikan</option>
                                    <option value="SMA/Sederajat" <?php echo ($profile && $profile['last_education'] == 'SMA/Sederajat') ? 'selected' : ''; ?>>SMA/Sederajat</option>
                                    <option value="D3" <?php echo ($profile && $profile['last_education'] == 'D3') ? 'selected' : ''; ?>>D3</option>
                                    <option value="S1/D4" <?php echo ($profile && $profile['last_education'] == 'S1/D4') ? 'selected' : ''; ?>>S1/D4</option>
                                    <option value="S2" <?php echo ($profile && $profile['last_education'] == 'S2') ? 'selected' : ''; ?>>S2</option>
                                    <option value="S3" <?php echo ($profile && $profile['last_education'] == 'S3') ? 'selected' : ''; ?>>S3</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Program Studi -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="major" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Program Studi <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-book"></i>
                                </span>
                                <input type="text" class="form-control" id="major" name="major" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;"
                                       placeholder="Contoh: Pendidikan Matematika"
                                       value="<?php echo $profile ? htmlspecialchars($profile['major']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instansi Asal -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="institution" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Instansi Asal <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-university"></i>
                                </span>
                                <input type="text" class="form-control" id="institution" name="institution" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;"
                                       placeholder="Contoh: Universitas Pendidikan Indonesia"
                                       value="<?php echo $profile ? htmlspecialchars($profile['institution']) : ''; ?>" 
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- STATUS PEKERJAAN - FIELD BARU -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Status Pekerjaan <span class="text-danger">*</span></label>
                            <div class="radio-group" style="display: flex; gap: 15px; margin-top: 5px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status_pekerjaan" id="status_pns" value="PNS" 
                                           <?php echo ($profile && $profile['status_pekerjaan'] == 'PNS') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="status_pns" style="font-size: 0.875rem;">PNS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status_pekerjaan" id="status_pppk" value="PPPK" 
                                           <?php echo ($profile && $profile['status_pekerjaan'] == 'PPPK') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_pppk" style="font-size: 0.875rem;">PPPK</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status_pekerjaan" id="status_non_asn" value="Non-ASN" 
                                           <?php echo ($profile && $profile['status_pekerjaan'] == 'Non-ASN') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status_non_asn" style="font-size: 0.875rem;">Non-ASN</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pekerjaan Saat Ini -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Pekerjaan Saat Ini <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pekerjaan_saat_ini" id="pekerjaan_guru" value="Guru" 
                                           <?php echo ($profile && $profile['pekerjaan_saat_ini'] == 'Guru') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="pekerjaan_guru" style="font-size: 0.875rem;">Guru</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pekerjaan_saat_ini" id="pekerjaan_non_guru" value="Non-Guru" 
                                           <?php echo ($profile && $profile['pekerjaan_saat_ini'] == 'Non-Guru') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="pekerjaan_non_guru" style="font-size: 0.875rem;">Non-Guru</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jabatan Guru (conditional) -->
                    <div class="col-md-6" id="jabatan_guru_container" style="display: none; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="jabatan_guru" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Jabatan Guru <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </span>
                                <select class="form-select" id="jabatan_guru" name="jabatan_guru" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;">
                                    <option value="">Pilih Jabatan</option>
                                    <option value="Guru Pertama" <?php echo ($profile && $profile['jabatan_guru'] == 'Guru Pertama') ? 'selected' : ''; ?>>Guru Pertama</option>
                                    <option value="Guru Muda" <?php echo ($profile && $profile['jabatan_guru'] == 'Guru Muda') ? 'selected' : ''; ?>>Guru Muda</option>
                                    <option value="Guru Madya" <?php echo ($profile && $profile['jabatan_guru'] == 'Guru Madya') ? 'selected' : ''; ?>>Guru Madya</option>
                                    <option value="Guru Utama" <?php echo ($profile && $profile['jabatan_guru'] == 'Guru Utama') ? 'selected' : ''; ?>>Guru Utama</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jabatan Non Guru (conditional) -->
                    <div class="col-md-6" id="jabatan_non_guru_container" style="display: none; margin-bottom: 15px;">
                        <div class="form-group">
                            <label for="jabatan_non_guru" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Jabatan Non-Guru <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-briefcase"></i>
                                </span>
                                <input type="text" class="form-control" id="jabatan_non_guru" name="jabatan_non_guru" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;"
                                       placeholder="Contoh: Staff Administrasi"
                                       value="<?php echo $profile ? htmlspecialchars($profile['jabatan_non_guru']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mata Pelajaran (conditional for Guru) -->
                    <div class="col-12" id="mata_pelajaran_container" style="display: none; margin-bottom: 15px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Mata Pelajaran Yang Pernah Diajar <span class="text-danger">*</span></label>
                            <div class="input-group" style="margin-bottom: 10px;">
                                <select class="form-select" id="mata_pelajaran_select" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;">
                                    <option value="">Pilih Mata Pelajaran</option>
                                    <?php
                                    $mata_pelajaran_options = [
                                        'Bahasa Indonesia', 'Bahasa Inggris', 'Bahasa Jerman', 'Bahasa Mandarin', 
                                        'Bahasa Jepang', 'Bahasa Arab', 'Sejarah', 'Sosiologi', 'Ekonomi', 
                                        'Geografi/Lingkungan dan Masyarakat', 'Biologi', 'Fisika/Desain Teknologi', 
                                        'Kimia', 'Matematika', 'Seni', 'Informatika/Komputer Sains', 
                                        'Pendidikan Jasmani Olahraga dan Kesehatan', 'Pendidikan Agama Islam', 
                                        'Pendidikan Agama Kristen Protestan', 'Pendidikan Agama Katolik', 
                                        'Pendidikan Agama Hindu', 'Pendidikan Agama Budha', 'Pendidikan Agama Khonghucu',
                                        'Bimbingan Konseling', 'Pendidikan Pancasila', 'Bahasa Daerah', 'Lainnya'
                                    ];
                                    foreach ($mata_pelajaran_options as $mp) {
                                        echo '<option value="' . htmlspecialchars($mp) . '">' . $mp . '</option>';
                                    }
                                    ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" id="tambah_mata_pelajaran" style="font-size: 0.875rem; padding: 6px 12px; height: 36px;">
                                    <i class="fas fa-plus"></i> Tambah
                                </button>
                            </div>
                            <div id="daftar_mata_pelajaran" style="margin-top: 10px;">
                                <?php if (!empty($user_subjects)): ?>
                                    <?php foreach ($user_subjects as $mp): ?>
                                        <div class="badge bg-primary me-2 mb-2" style="font-size: 0.75rem; padding: 5px 10px;">
                                            <?php echo htmlspecialchars($mp); ?>
                                            <input type="hidden" name="mata_pelajaran[]" value="<?php echo htmlspecialchars($mp); ?>">
                                            <button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.5rem;" onclick="hapusMataPelajaran(this)"></button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="form-text" style="font-size: 0.75rem;">Klik tambah untuk menambah mata pelajaran. Minimal satu mata pelajaran.</div>
                        </div>
                    </div>
                    
                    <!-- Alamat -->
                    <div class="col-12">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="address" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Alamat Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text align-items-start" style="font-size: 0.875rem; padding: 6px 12px;">
                                    <i class="fas fa-map-marker-alt mt-1"></i>
                                </span>
                                <textarea class="form-control" id="address" name="address" style="font-size: 0.875rem; padding: 6px 12px; min-height: 80px;"
                                          rows="3" placeholder="Jl. Contoh No. 123, Kota, Provinsi"
                                          required><?php echo $profile ? htmlspecialchars($profile['address']) : ''; ?></textarea>
                            </div>
                            <div class="form-text" style="font-size: 0.75rem;">Isi alamat lengkap sesuai KTP</div>
                        </div>
                    </div>
                    
                    <!-- Provinsi -->
                    <div class="col-md-6">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="provinsi" class="form-label" style="font-size: 0.875rem; margin-bottom: 5px;">Provinsi <span class="text-danger">*</span></label>
                            <div class="input-group" style="height: 36px;">
                                <span class="input-group-text" style="font-size: 0.875rem; padding: 0 12px;">
                                    <i class="fas fa-map"></i>
                                </span>
                                <select class="form-select" id="provinsi" name="provinsi" required style="font-size: 0.875rem; padding: 6px 12px; height: 36px;">
                                    <option value="">Pilih Provinsi</option>
                                    <?php foreach ($provinsi_list as $prov): ?>
                                        <option value="<?php echo htmlspecialchars($prov); ?>" <?php echo ($profile && $profile['provinsi'] == $prov) ? 'selected' : ''; ?>>
                                            <?php echo $prov; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="col-12">
                        <div class="d-flex gap-3" style="margin-top: 10px;">
                            <button type="submit" class="btn-dashboard" style="font-size: 0.875rem; padding: 8px 16px;">
                                <i class="fas fa-save me-2"></i>Simpan Profile
                            </button>
                            <a href="profile.php?mode=view" class="btn-outline-dashboard btn" style="font-size: 0.875rem; padding: 8px 16px;">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- MODE VIEW (Setelah Simpan) -->
    <div class="col-lg-8">
        <div class="dashboard-card" style="padding: 20px;">
            <div class="card-header" style="padding: 12px 0; margin-bottom: 15px;">
                <h4 style="font-size: 1.25rem;"><i class="fas fa-eye me-2"></i>Data Profil Anda</h4>
            </div>
            
            <!-- Foto di atas nama lengkap dalam mode view -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="text-center">
                        <div class="photo-view-container" style="margin-bottom: 20px;">
                            <div id="fotoView" style="width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 3px solid #0d6efd;">
                                <?php if (!empty($profile['foto'])): ?>
                                    <img src="<?php echo base_url($profile['foto']); ?>" alt="Foto Profil" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user" style="font-size: 60px; color: #6c757d;"></i>
                                <?php endif; ?>
                            </div>
                            <p style="font-size: 0.875rem; color: #666; margin-top: 10px;">
                                <?php echo !empty($profile['foto']) ? 'Foto profil sudah diunggah' : 'Foto profil belum diunggah'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data dalam bentuk teks (tidak ada form input) -->
            <div class="profile-data-view">
                <!-- Data Pribadi -->
                <div class="data-section mb-4">
                    <h5 class="mb-3" style="font-size: 1.1rem; color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 8px;">
                        <i class="fas fa-id-card me-2"></i>Data Pribadi
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Nama Lengkap</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo htmlspecialchars($user_name); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Email</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo htmlspecialchars($user_email); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Jenis Kelamin</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['gender']) ? htmlspecialchars($profile['gender']) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Nomor HP</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['phone']) ? htmlspecialchars($profile['phone']) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Tanggal Lahir</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['tanggal_lahir']) ? date('d/m/Y', strtotime($profile['tanggal_lahir'])) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Pendidikan -->
                <div class="data-section mb-4">
                    <h5 class="mb-3" style="font-size: 1.1rem; color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 8px;">
                        <i class="fas fa-graduation-cap me-2"></i>Data Pendidikan
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Pendidikan Terakhir</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['last_education']) ? htmlspecialchars($profile['last_education']) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Program Studi</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['major']) ? htmlspecialchars($profile['major']) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Instansi Asal</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['institution']) ? htmlspecialchars($profile['institution']) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Data Pekerjaan -->
                <div class="data-section mb-4">
                    <h5 class="mb-3" style="font-size: 1.1rem; color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 8px;">
                        <i class="fas fa-briefcase me-2"></i>Data Pekerjaan
                    </h5>
                    
                    <div class="row">
                        <!-- STATUS PEKERJAAN dalam mode view -->
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Status Pekerjaan</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php if (!empty($profile['status_pekerjaan'])): ?>
                                        <span class="badge <?php 
                                            echo $profile['status_pekerjaan'] == 'PNS' ? 'bg-success' : 
                                                   ($profile['status_pekerjaan'] == 'PPPK' ? 'bg-info' : 'bg-warning'); 
                                        ?>" style="font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($profile['status_pekerjaan']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">Belum diisi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Pekerjaan Saat Ini</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php if (!empty($profile['pekerjaan_saat_ini'])): ?>
                                        <?php if ($profile['pekerjaan_saat_ini'] == 'Guru'): ?>
                                            <span class="badge bg-primary" style="font-size: 0.875rem;">
                                                Guru <?php echo !empty($profile['jabatan_guru']) ? '(' . htmlspecialchars($profile['jabatan_guru']) . ')' : ''; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" style="font-size: 0.875rem;">
                                                Non-Guru <?php echo !empty($profile['jabatan_non_guru']) ? '(' . htmlspecialchars($profile['jabatan_non_guru']) . ')' : ''; ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Belum diisi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mata Pelajaran (jika Guru) -->
                        <?php if (!empty($profile['pekerjaan_saat_ini']) && $profile['pekerjaan_saat_ini'] == 'Guru' && !empty($user_subjects)): ?>
                        <div class="col-12 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Mata Pelajaran Yang Pernah Diajar</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php foreach ($user_subjects as $mp): ?>
                                        <span class="badge bg-info me-1 mb-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($mp); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Data Alamat -->
                <div class="data-section mb-4">
                    <h5 class="mb-3" style="font-size: 1.1rem; color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 8px;">
                        <i class="fas fa-map-marker-alt me-2"></i>Data Alamat
                    </h5>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Alamat Lengkap</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500; white-space: pre-line;">
                                    <?php echo !empty($profile['address']) ? nl2br(htmlspecialchars($profile['address'])) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="data-field">
                                <label class="data-label" style="font-size: 0.875rem; color: #666; margin-bottom: 4px;">Provinsi</label>
                                <div class="data-value" style="font-size: 1rem; font-weight: 500;">
                                    <?php echo !empty($profile['provinsi']) ? htmlspecialchars($profile['provinsi']) : '<span style="color: #999;">Belum diisi</span>'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tombol Edit -->
                <div class="d-flex gap-3 mt-4">
                    <a href="profile.php?mode=edit" class="btn-dashboard" style="font-size: 0.875rem; padding: 8px 16px;">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </a>
                    <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="btn-outline-dashboard btn" style="font-size: 0.875rem; padding: 8px 16px;">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Profile Status -->
        <div class="dashboard-card mb-4" style="padding: 20px;">
            <div class="card-header" style="padding: 12px 0; margin-bottom: 15px;">
                <h4 style="font-size: 1.25rem;"><i class="fas fa-chart-bar me-2"></i>Status Profil</h4>
            </div>
            
            <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
                    <svg width="120" height="120" viewBox="0 0 120 120" class="circular-chart">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e9ecef" stroke-width="8"/>
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#0d6efd" stroke-width="8" 
                                stroke-dasharray="<?php echo $completion_percentage * 3.4; ?> 340" 
                                stroke-linecap="round" transform="rotate(-90 60 60)"/>
                        <text x="60" y="65" text-anchor="middle" fill="#0d6efd" font-size="24" font-weight="600">
                            <?php echo $completion_percentage; ?>%
                        </text>
                    </svg>
                </div>
                <h5 class="mt-3 mb-2" style="font-size: 1rem;">
                    <?php if ($completion_percentage == 100): ?>
                        <span class="badge bg-success">PROFIL LENGKAP</span>
                    <?php else: ?>
                        <span class="badge bg-warning">BELUM LENGKAP</span>
                    <?php endif; ?>
                </h5>
                <p class="text-muted mb-0" style="font-size: 0.875rem;">
                    <?php if ($completion_percentage == 100): ?>
                        Semua data termasuk foto sudah lengkap.
                    <?php else: ?>
                        Lengkapi semua data termasuk foto untuk mencapai 100%.
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="profile-progress" style="height: 8px; background-color: #e9ecef; border-radius: 4px; overflow: hidden; margin-bottom: 8px;">
                <div class="progress-bar" style="width: <?php echo $completion_percentage; ?>%; height: 100%; background-color: #0d6efd;"></div>
            </div>
            <div class="text-center">
                <small class="text-muted" style="font-size: 0.75rem;"><?php echo $completion_percentage; ?>% Terisi</small>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="dashboard-card" style="padding: 20px;">
            <div class="card-header" style="padding: 12px 0; margin-bottom: 15px;">
                <h4 style="font-size: 1.25rem;"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h4>
            </div>
            
            <div class="list-group list-group-flush" style="font-size: 0.875rem;">
                <a href="<?php echo base_url('modules/dashboard/dashboard.php'); ?>" class="list-group-item list-group-item-action" style="padding: 10px 0;">
                    <i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard Utama
                </a>
                <?php if ($mode == 'view'): ?>
                    <a href="profile.php?mode=edit" class="list-group-item list-group-item-action" style="padding: 10px 0;">
                        <i class="fas fa-edit me-2 text-primary"></i>Edit Profile
                    </a>
                <?php else: ?>
                    <a href="profile.php?mode=view" class="list-group-item list-group-item-action" style="padding: 10px 0;">
                        <i class="fas fa-eye me-2 text-primary"></i>Lihat Profile
                    </a>
                <?php endif; ?>
                <a href="#" class="list-group-item list-group-item-action" style="padding: 10px 0;">
                    <i class="fas fa-download me-2 text-primary"></i>Download Data
                </a>
                <a href="<?php echo base_url('modules/auth/logout.php'); ?>" class="list-group-item list-group-item-action text-danger" style="padding: 10px 0;">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .circular-chart {
        display: block;
        margin: 0 auto;
    }
    
    .circular-chart circle {
        transition: stroke-dasharray 0.3s ease;
    }
    
    .list-group-item {
        border: none;
        color: #495057;
        transition: all 0.3s;
    }
    
    .list-group-item:hover {
        color: #0d6efd;
        background: none;
        padding-left: 10px;
    }
    
    .badge {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 600;
        border-radius: 20px;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }
    
    .input-group-text {
        min-width: 45px;
        justify-content: center;
    }
    
    /* Perkecil ukuran form */
    .form-control, .form-select, .input-group-text {
        font-size: 0.875rem;
        padding: 6px 12px;
        height: 36px;
    }
    
    .form-label {
        font-size: 0.875rem;
        margin-bottom: 5px;
    }
    
    .form-text {
        font-size: 0.75rem;
    }
    
    .dashboard-card {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .card-header {
        padding: 12px 0;
        margin-bottom: 15px;
    }
    
    h4 {
        font-size: 1.25rem;
    }
    
    .alert {
        padding: 12px;
        font-size: 0.875rem;
        margin-bottom: 20px;
    }
    
    .btn-close {
        font-size: 0.75rem;
    }
    
    /* Mode View Styles */
    .data-section {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .data-field {
        margin-bottom: 15px;
    }
    
    .data-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }
    
    .data-value {
        padding: 8px 12px;
        background: white;
        border-radius: 5px;
        border-left: 3px solid #0d6efd;
    }
</style>

<script>
// Tampilkan atau sembunyikan field berdasarkan pilihan pekerjaan
function togglePekerjaanFields() {
    var pekerjaanGuru = document.getElementById('pekerjaan_guru');
    var pekerjaanNonGuru = document.getElementById('pekerjaan_non_guru');
    
    var jabatanGuruContainer = document.getElementById('jabatan_guru_container');
    var jabatanNonGuruContainer = document.getElementById('jabatan_non_guru_container');
    var mataPelajaranContainer = document.getElementById('mata_pelajaran_container');
    
    if (pekerjaanGuru.checked) {
        jabatanGuruContainer.style.display = 'block';
        jabatanNonGuruContainer.style.display = 'none';
        mataPelajaranContainer.style.display = 'block';
        // Set required
        document.getElementById('jabatan_guru').required = true;
        document.getElementById('jabatan_non_guru').required = false;
    } else if (pekerjaanNonGuru.checked) {
        jabatanGuruContainer.style.display = 'none';
        jabatanNonGuruContainer.style.display = 'block';
        mataPelajaranContainer.style.display = 'none';
        // Set required
        document.getElementById('jabatan_guru').required = false;
        document.getElementById('jabatan_non_guru').required = true;
        // Hapus semua mata pelajaran yang sudah dipilih
        var daftar = document.getElementById('daftar_mata_pelajaran');
        daftar.innerHTML = '';
    }
}

// Preview foto
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('fotoPreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview Foto" style="width:100%; height:100%; object-fit:cover;">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Panggil saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    togglePekerjaanFields();
    
    // Tambahkan event listener pada radio button
    var pekerjaanGuru = document.getElementById('pekerjaan_guru');
    var pekerjaanNonGuru = document.getElementById('pekerjaan_non_guru');
    
    if (pekerjaanGuru) pekerjaanGuru.addEventListener('change', togglePekerjaanFields);
    if (pekerjaanNonGuru) pekerjaanNonGuru.addEventListener('change', togglePekerjaanFields);
    
    // Tambahkan mata pelajaran
    var tambahBtn = document.getElementById('tambah_mata_pelajaran');
    if (tambahBtn) {
        tambahBtn.addEventListener('click', function() {
            var select = document.getElementById('mata_pelajaran_select');
            var selectedValue = select.value;
            var selectedText = select.options[select.selectedIndex].text;
            
            if (selectedValue === '') {
                alert('Silakan pilih mata pelajaran terlebih dahulu.');
                return;
            }
            
            // Cek apakah sudah ada
            var existing = document.querySelectorAll('input[name="mata_pelajaran[]"]');
            for (var i = 0; i < existing.length; i++) {
                if (existing[i].value === selectedValue) {
                    alert('Mata pelajaran ini sudah ditambahkan.');
                    return;
                }
            }
            
            // Buat badge
            var badge = document.createElement('div');
            badge.className = 'badge bg-primary me-2 mb-2';
            badge.style.cssText = 'font-size: 0.75rem; padding: 5px 10px; display: inline-block;';
            badge.innerHTML = selectedText +
                '<input type="hidden" name="mata_pelajaran[]" value="' + selectedValue + '">' +
                '<button type="button" class="btn-close btn-close-white ms-1" style="font-size: 0.5rem;" onclick="hapusMataPelajaran(this)"></button>';
            
            document.getElementById('daftar_mata_pelajaran').appendChild(badge);
            
            // Reset select
            select.value = '';
        });
    }
});

// Hapus mata pelajaran
function hapusMataPelajaran(button) {
    var badge = button.parentNode;
    badge.parentNode.removeChild(badge);
}

// Form validation and submission
var profileForm = document.getElementById('profileForm');
if (profileForm) {
    profileForm.addEventListener('submit', function(e) {
        // Phone number validation
        var phone = document.getElementById('phone').value;
        var phoneRegex = /^08[1-9][0-9]{7,9}$/;
        if (!phoneRegex.test(phone)) {
            e.preventDefault();
            showToast('Nomor HP harus dimulai dengan 08 dan terdiri dari 10-12 digit!', 'danger');
            return false;
        }
        
        // Address length validation
        var address = document.getElementById('address').value;
        if (address.length < 10) {
            e.preventDefault();
            showToast('Alamat minimal 10 karakter!', 'danger');
            return false;
        }
        
        // Validasi status pekerjaan
        var statusPekerjaan = document.querySelector('input[name="status_pekerjaan"]:checked');
        if (!statusPekerjaan) {
            e.preventDefault();
            showToast('Status pekerjaan harus dipilih!', 'danger');
            return false;
        }
        
        // Validasi pekerjaan
        var pekerjaanGuru = document.getElementById('pekerjaan_guru');
        var pekerjaanNonGuru = document.getElementById('pekerjaan_non_guru');
        
        if (!pekerjaanGuru.checked && !pekerjaanNonGuru.checked) {
            e.preventDefault();
            showToast('Pekerjaan saat ini harus dipilih!', 'danger');
            return false;
        }
        
        if (pekerjaanGuru.checked) {
            var jabatanGuru = document.getElementById('jabatan_guru').value;
            if (jabatanGuru === '') {
                e.preventDefault();
                showToast('Jabatan guru harus dipilih!', 'danger');
                return false;
            }
            // Cek minimal satu mata pelajaran
            var mataPelajaranCount = document.querySelectorAll('input[name="mata_pelajaran[]"]').length;
            if (mataPelajaranCount === 0) {
                e.preventDefault();
                showToast('Minimal pilih satu mata pelajaran yang pernah diajar!', 'danger');
                return false;
            }
        }
        
        if (pekerjaanNonGuru.checked) {
            var jabatanNonGuru = document.getElementById('jabatan_non_guru').value;
            if (jabatanNonGuru.length < 2) {
                e.preventDefault();
                showToast('Jabatan non guru minimal 2 karakter!', 'danger');
                return false;
            }
        }
        
        // Validasi foto
        var fotoInput = document.getElementById('foto');
        var existingFoto = '<?php echo !empty($profile["foto"]) ? 1 : 0; ?>';
        
        // Jika tidak ada foto yang dipilih dan tidak ada foto yang sudah ada
        if (fotoInput.files.length === 0 && existingFoto == 0) {
            e.preventDefault();
            showToast('Foto wajib diunggah untuk kelengkapan profil!', 'danger');
            return false;
        }
        
        // Show loading
        showLoading();
        
        // Change button text
        var submitBtn = this.querySelector('button[type="submit"]');
        var originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
        submitBtn.disabled = true;
        
        // Restore button after 3 seconds if form doesn't submit
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            hideLoading();
        }, 3000);
    });
}

// Auto-format phone number
var phoneInput = document.getElementById('phone');
if (phoneInput) {
    phoneInput.addEventListener('input', function() {
        var value = this.value.replace(/\D/g, '');
        if (value.length > 12) {
            value = value.substring(0, 12);
        }
        this.value = value;
    });
}

// Fungsi showToast
function showToast(message, type) {
    var toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1050;';
        document.body.appendChild(toastContainer);
    }
    
    var toast = document.createElement('div');
    toast.className = 'alert alert-' + type + ' alert-dismissible fade show';
    toast.style.cssText = 'min-width: 300px; margin-bottom: 10px;';
    toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    toastContainer.appendChild(toast);
    
    // Hapus otomatis setelah 5 detik
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Fungsi showLoading dan hideLoading
function showLoading() {
    var loading = document.getElementById('loading-overlay');
    if (!loading) {
        loading = document.createElement('div');
        loading.id = 'loading-overlay';
        loading.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;';
        loading.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        document.body.appendChild(loading);
    }
    loading.style.display = 'flex';
}

function hideLoading() {
    var loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.style.display = 'none';
    }
}
</script>

<?php
ob_end_flush();
include __DIR__ . '/../dashboard/footer-dashboard.php';
?>