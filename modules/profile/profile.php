<?php
// modules/profile/profile.php — Profil Pengguna (Premium LinkedIn-style)
$pageTitle = "Profil Saya";
$activePage = "profile";
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../auth/functions-auth.php';
require_login();

$db = get_db_connection();
$user_id = $_SESSION['user_id'];
$error = $success = '';
$mode = in_array($_GET['mode'] ?? '', ['view','edit']) ? $_GET['mode'] : 'view';
$tab = $_GET['tab'] ?? 'overview';

// Fetch profile + related data
$stmt = $db->prepare("SELECT * FROM profiles WHERE user_id = ?"); $stmt->execute([$user_id]); $profile = $stmt->fetch();
$stmt = $db->prepare("SELECT mata_pelajaran FROM user_subjects WHERE user_id = ?"); $stmt->execute([$user_id]); $user_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt = $db->prepare("SELECT * FROM user_education WHERE user_id = ? ORDER BY id"); $stmt->execute([$user_id]); $user_education = $stmt->fetchAll();
$stmt = $db->prepare("SELECT * FROM user_training WHERE user_id = ? ORDER BY id"); $stmt->execute([$user_id]); $user_training = $stmt->fetchAll();

// Get reference data
$unit_list = $db->query("SELECT id, kode_satker, nama_satker FROM unit_kerja WHERE is_active = TRUE ORDER BY kode_satker")->fetchAll();
$jabatan_list = $db->query("SELECT id, kode, nama_jabatan, kategori FROM jabatan WHERE is_active = TRUE ORDER BY kategori, nama_jabatan")->fetchAll();
$golongan_list = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e'];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) { $error = 'Token tidak valid.'; }
    else {
        // Data Pribadi
        $gender            = sanitize_input($_POST['gender'] ?? '');
        $nip               = trim($_POST['nip'] ?? '');
        $nik               = trim($_POST['nik'] ?? '');
        $tempat_lahir      = sanitize_input($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir     = $_POST['tanggal_lahir'] ?? '';
        $agama             = $_POST['agama'] ?? '';
        $status_perkawinan = $_POST['status_perkawinan'] ?? '';

        // Data Kepegawaian
        $golongan          = $_POST['golongan'] ?? '';
        $jabatan_id        = !empty($_POST['jabatan_id']) ? intval($_POST['jabatan_id']) : null;
        $unit_kerja_id     = !empty($_POST['unit_kerja_id']) ? intval($_POST['unit_kerja_id']) : null;
        $status_pekerjaan  = $_POST['status_pekerjaan'] ?? '';
        $npwp              = trim($_POST['npwp'] ?? '');
        $no_karpeg         = trim($_POST['no_karpeg'] ?? '');
        $tmt_cpns          = $_POST['tmt_cpns'] ?? '';
        $tmt_pns           = $_POST['tmt_pns'] ?? '';

        // Pendidikan & Keahlian
        $last_education = sanitize_input($_POST['last_education'] ?? '');
        $major          = sanitize_input($_POST['major'] ?? '');
        $skills         = sanitize_input($_POST['skills'] ?? '');
        $sertifikasi    = sanitize_input($_POST['sertifikasi'] ?? '');

        // Kontak & Alamat
        $phone        = sanitize_input($_POST['phone'] ?? '');
        $address      = sanitize_input($_POST['address'] ?? '');
        $provinsi     = sanitize_input($_POST['provinsi'] ?? '');
        $linkedin_url = sanitize_input($_POST['linkedin_url'] ?? '');
        $bio          = sanitize_input($_POST['bio'] ?? '');

        // Mapel
        $mata_pelajaran = $_POST['mata_pelajaran'] ?? [];

        $errors = [];
        // Required validations
        if (!in_array($gender, ['Laki-laki','Perempuan'])) $errors[] = 'Jenis kelamin harus dipilih.';
        if (empty($nip) || !preg_match('/^\d{9,18}$/', $nip)) $errors[] = 'NIP harus diisi (9-18 digit angka).';
        if (!preg_match('/^08[1-9][0-9]{7,9}$/', $phone)) $errors[] = 'Nomor HP harus 10-12 digit dimulai 08.';
        if (empty($tanggal_lahir)) $errors[] = 'Tanggal lahir harus diisi.';
        if (!$unit_kerja_id) $errors[] = 'Instansi / Unit Kerja harus dipilih.';
        if (empty($golongan)) $errors[] = 'Golongan harus dipilih.';
        if (!$jabatan_id) $errors[] = 'Jabatan harus dipilih.';
        if (empty($status_pekerjaan)) $errors[] = 'Status pekerjaan harus dipilih.';
        if (empty($address) || strlen($address) < 10) $errors[] = 'Alamat minimal 10 karakter.';
        if (empty($provinsi)) $errors[] = 'Provinsi harus dipilih.';

        // Optional validations
        if (!empty($nik) && !preg_match('/^\d{16}$/', $nik)) $errors[] = 'NIK harus 16 digit.';
        if (!empty($npwp) && !preg_match('/^\d{15}$/', $npwp)) $errors[] = 'NPWP harus 15 digit.';

        // Foto validation
        $foto = $profile['foto'] ?? null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png'])) $errors[] = 'Foto harus JPG/PNG.';
            elseif ($_FILES['foto']['size'] > 2*1024*1024) $errors[] = 'Foto maksimal 2MB.';
            else {
                $newName = uniqid('foto_', true).'.'.$ext;
                $dest = __DIR__.'/../../storage/uploads/foto/'.$newName;
                if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                    if (!empty($profile['foto']) && file_exists(__DIR__.'/../../'.$profile['foto'])) unlink(__DIR__.'/../../'.$profile['foto']);
                    $foto = 'storage/uploads/foto/'.$newName;
                } else $errors[] = 'Gagal upload foto.';
            }
        } elseif (empty($foto)) $errors[] = 'Foto wajib diunggah.';

        if (empty($errors)) {
            try {
                $exists = $profile ? true : false;
                // Auto-derive last_education & major from first education entry
                $firstEdu = !empty($edu_levels[0]) ? sanitize_input($edu_levels[0]) : '';
                $firstMajor = !empty($edu_majors[0]) ? sanitize_input($edu_majors[0]) : '';
                $cols = "gender=?, phone=?, address=?, tanggal_lahir=?, last_education=?, major=?, foto=?, status_pekerjaan=?, provinsi=?, is_profile_complete=TRUE, updated_at=CURRENT_TIMESTAMP, nip=?, golongan=?, jabatan_id=?, unit_kerja_id=?, nik=?, tempat_lahir=?, agama=?, status_perkawinan=?, npwp=?, no_karpeg=?, tmt_cpns=?, tmt_pns=?, linkedin_url=?, skills=?, sertifikasi=?, bio=?";
                $params = [$gender, $phone, $address, $tanggal_lahir, $firstEdu, $firstMajor, $foto, $status_pekerjaan, $provinsi, $nip, $golongan, $jabatan_id, $unit_kerja_id, $nik, $tempat_lahir, $agama, $status_perkawinan, $npwp, $no_karpeg, $tmt_cpns, $tmt_pns, $linkedin_url, $skills, $sertifikasi, $bio];
                if ($exists) {
                    $db->prepare("UPDATE profiles SET $cols WHERE user_id=?")->execute([...$params, $user_id]);
                } else {
                    $db->prepare("INSERT INTO profiles (user_id,$cols) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$user_id, ...$params]);
                }
                // Save subjects
                $db->prepare("DELETE FROM user_subjects WHERE user_id=?")->execute([$user_id]);
                foreach ($mata_pelajaran as $mp) { $mp = sanitize_input($mp); if (!empty($mp)) $db->prepare("INSERT INTO user_subjects (user_id,mata_pelajaran) VALUES (?,?)")->execute([$user_id, $mp]); }
                // Save education history
                $db->prepare("DELETE FROM user_education WHERE user_id=?")->execute([$user_id]);
                $edu_levels = $_POST['edu_level'] ?? [];
                $edu_majors = $_POST['edu_major'] ?? [];
                $edu_degrees = $_POST['edu_degree'] ?? [];
                $edu_institutions = $_POST['edu_institution'] ?? [];
                foreach ($edu_levels as $i => $lv) {
                    $lv = sanitize_input($lv);
                    if (!empty($lv)) {
                        $mj = sanitize_input($edu_majors[$i] ?? '');
                        $dg = sanitize_input($edu_degrees[$i] ?? '');
                        $in = sanitize_input($edu_institutions[$i] ?? '');
                        $db->prepare("INSERT INTO user_education (user_id,level,major,degree,institution) VALUES (?,?,?,?,?)")->execute([$user_id, $lv, $mj, $dg, $in]);
                    }
                }
                // Save training
                $db->prepare("DELETE FROM user_training WHERE user_id=?")->execute([$user_id]);
                $tr_names = $_POST['tr_name'] ?? [];
                $tr_orgs = $_POST['tr_organizer'] ?? [];
                $tr_years = $_POST['tr_year'] ?? [];
                $tr_certs = $_POST['tr_certificate'] ?? [];
                foreach ($tr_names as $i => $nm) {
                    $nm = sanitize_input($nm);
                    if (!empty($nm)) {
                        $og = sanitize_input($tr_orgs[$i] ?? '');
                        $yr = !empty($tr_years[$i]) ? intval($tr_years[$i]) : null;
                        $ct = sanitize_input($tr_certs[$i] ?? '');
                        $db->prepare("INSERT INTO user_training (user_id,training_name,organizer,training_year,certificate) VALUES (?,?,?,?,?)")->execute([$user_id, $nm, $og, $yr, $ct]);
                    }
                }
                $_SESSION['profile_complete'] = true;
                log_activity('PROFILE_UPDATE', "Profile updated", $user_id);
                if (!headers_sent()) { header("Location: profile.php?mode=view&saved=1"); exit; }
                else echo '<script>window.location="profile.php?mode=view&saved=1";</script>';
            } catch (Exception $e) { $error = 'Gagal menyimpan: '.$e->getMessage(); }
        } else { $error = implode('<br>', $errors); }
    }
}

// Build form data — preserve user input on validation errors
$form = $profile ?: [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['gender']            = $_POST['gender'] ?? $form['gender'] ?? '';
    $form['nip']               = $_POST['nip'] ?? $form['nip'] ?? '';
    $form['nik']               = $_POST['nik'] ?? $form['nik'] ?? '';
    $form['tempat_lahir']      = $_POST['tempat_lahir'] ?? $form['tempat_lahir'] ?? '';
    $form['tanggal_lahir']     = $_POST['tanggal_lahir'] ?? $form['tanggal_lahir'] ?? '';
    $form['agama']             = $_POST['agama'] ?? $form['agama'] ?? '';
    $form['status_perkawinan'] = $_POST['status_perkawinan'] ?? $form['status_perkawinan'] ?? '';
    $form['golongan']          = $_POST['golongan'] ?? $form['golongan'] ?? '';
    $form['jabatan_id']        = $_POST['jabatan_id'] ?? $form['jabatan_id'] ?? '';
    $form['unit_kerja_id']     = $_POST['unit_kerja_id'] ?? $form['unit_kerja_id'] ?? '';
    $form['status_pekerjaan']  = $_POST['status_pekerjaan'] ?? $form['status_pekerjaan'] ?? '';
    $form['npwp']              = $_POST['npwp'] ?? $form['npwp'] ?? '';
    $form['no_karpeg']         = $_POST['no_karpeg'] ?? $form['no_karpeg'] ?? '';
    $form['tmt_cpns']          = $_POST['tmt_cpns'] ?? $form['tmt_cpns'] ?? '';
    $form['tmt_pns']           = $_POST['tmt_pns'] ?? $form['tmt_pns'] ?? '';
    $form['last_education']    = $_POST['last_education'] ?? $form['last_education'] ?? '';
    $form['major']             = $_POST['major'] ?? $form['major'] ?? '';
    $form['skills']            = $_POST['skills'] ?? $form['skills'] ?? '';
    $form['sertifikasi']       = $_POST['sertifikasi'] ?? $form['sertifikasi'] ?? '';
    $form['phone']             = $_POST['phone'] ?? $form['phone'] ?? '';
    $form['address']           = $_POST['address'] ?? $form['address'] ?? '';
    $form['provinsi']          = $_POST['provinsi'] ?? $form['provinsi'] ?? '';
    $form['linkedin_url']      = $_POST['linkedin_url'] ?? $form['linkedin_url'] ?? '';
    $form['bio']               = $_POST['bio'] ?? $form['bio'] ?? '';
    if (isset($_POST['mata_pelajaran'])) {
        $user_subjects = array_map('sanitize_input', $_POST['mata_pelajaran']);
    }
    // Preserve education & training from POST
    if (isset($_POST['edu_level'])) {
        $user_education = [];
        $lvls = $_POST['edu_level'] ?? [];
        $mjrs = $_POST['edu_major'] ?? [];
        $dgrs = $_POST['edu_degree'] ?? [];
        $inst = $_POST['edu_institution'] ?? [];
        foreach ($lvls as $i => $lv) {
            if (!empty(trim($lv))) {
                $user_education[] = ['level'=>sanitize_input($lv), 'major'=>sanitize_input($mjrs[$i]??''), 'degree'=>sanitize_input($dgrs[$i]??''), 'institution'=>sanitize_input($inst[$i]??'')];
            }
        }
    }
    if (isset($_POST['tr_name'])) {
        $user_training = [];
        $nms = $_POST['tr_name'] ?? [];
        $ogs = $_POST['tr_organizer'] ?? [];
        $yrs = $_POST['tr_year'] ?? [];
        $cts = $_POST['tr_certificate'] ?? [];
        foreach ($nms as $i => $nm) {
            if (!empty(trim($nm))) {
                $user_training[] = ['training_name'=>sanitize_input($nm), 'organizer'=>sanitize_input($ogs[$i]??''), 'training_year'=>!empty($yrs[$i])?intval($yrs[$i]):null, 'certificate'=>sanitize_input($cts[$i]??'')];
            }
        }
    }
}

// Check profile completeness (only required fields)
$is_complete = $profile && $profile['is_profile_complete'] && !empty($profile['nip']) && !empty($profile['golongan']) && $profile['unit_kerja_id'] && $profile['jabatan_id'] && !empty($profile['foto']);
if (!$is_complete && ($_SESSION['user_role'] ?? '') !== 'SUPERADMIN' && $mode !== 'edit') {
    $mode = 'edit';
}

// Helper: get display name for lookup values
function get_instance_name($db, $id) { if (!$id) return '-'; $s=$db->prepare("SELECT nama_satker FROM unit_kerja WHERE id=?"); $s->execute([$id]); $r=$s->fetch(); return $r['nama_satker']??'-'; }
function get_jabatan_name($db, $id) { if (!$id) return '-'; $s=$db->prepare("SELECT nama_jabatan, kategori FROM jabatan WHERE id=?"); $s->execute([$id]); $r=$s->fetch(); return $r ? $r['nama_jabatan'].' ('.ucfirst($r['kategori']).')' : '-'; }

// Data lists
$provinsi_list = ['Aceh','Sumatera Utara','Sumatera Barat','Riau','Kepulauan Riau','Jambi','Sumatera Selatan','Kepulauan Bangka Belitung','Bengkulu','Lampung','DKI Jakarta','Jawa Barat','Jawa Tengah','DI Yogyakarta','Jawa Timur','Banten','Bali','Nusa Tenggara Barat','Nusa Tenggara Timur','Kalimantan Barat','Kalimantan Tengah','Kalimantan Selatan','Kalimantan Timur','Kalimantan Utara','Sulawesi Utara','Sulawesi Tengah','Sulawesi Selatan','Sulawesi Tenggara','Gorontalo','Sulawesi Barat','Maluku','Maluku Utara','Papua Barat','Papua'];
$agama_list = ['Islam','Kristen Protestan','Katolik','Hindu','Budha','Konghucu','Lainnya'];
$status_kawin_list = ['Belum Menikah','Menikah','Cerai Hidup','Cerai Mati'];
$education_list = ['SMA/Sederajat','D1','D2','D3','S1/D4','S2','S3'];
$mata_pelajaran_options = ['Bahasa Indonesia','Bahasa Inggris','Matematika','Fisika','Kimia','Biologi','Sejarah','Ekonomi','Geografi','Sosiologi','Pendidikan Agama Islam','Pendidikan Pancasila','Informatika','Seni','Pendidikan Jasmani','Bimbingan Konseling','Bahasa Daerah','Bahasa Mandarin','Bahasa Jepang','Bahasa Jerman','Bahasa Arab','Lainnya'];

include __DIR__ . '/../dashboard/header-dashboard.php';
?>

<?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm mb-3"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div><?php endif; ?>
<?php if ($success || isset($_GET['saved'])): ?><div class="alert alert-success border-0 shadow-sm mb-3"><i class="fas fa-check-circle me-2"></i>Profil berhasil disimpan!</div><?php endif; ?>

<!-- ============================================================ -->
<!-- PROFILE HEADER BANNER -->
<!-- ============================================================ -->
<div class="profile-header-banner mb-4">
    <div class="profile-header-bg"></div>
    <div class="profile-header-content">
        <div class="profile-avatar-wrapper">
            <div class="profile-avatar">
                <?php if (!empty($form['foto'])): ?>
                    <img src="<?php echo base_url($form['foto']); ?>" alt="Foto Profil">
                <?php else: ?>
                    <div class="avatar-placeholder"><i class="fas fa-user"></i></div>
                <?php endif; ?>
            </div>
            <?php if ($is_complete): ?>
                <span class="profile-verified-badge" title="Profil Terverifikasi"><i class="fas fa-check-circle"></i></span>
            <?php endif; ?>
        </div>
        <div class="profile-header-info">
            <h2 class="profile-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
            <?php if (!empty($form['nip'])): ?>
                <div class="profile-nip">NIP. <?php echo htmlspecialchars($form['nip']); ?></div>
            <?php endif; ?>
            <div class="profile-meta">
                <?php
                    $jabatan_display = get_jabatan_name($db, $form['jabatan_id'] ?? 0);
                    $instansi_display = get_instance_name($db, $form['unit_kerja_id'] ?? 0);
                ?>
                <?php if ($jabatan_display !== '-'): ?><span class="profile-meta-item"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($jabatan_display); ?></span><?php endif; ?>
                <?php if ($instansi_display !== '-'): ?><span class="profile-meta-item"><i class="fas fa-building"></i> <?php echo htmlspecialchars($instansi_display); ?></span><?php endif; ?>
                <?php if (!empty($form['golongan'])): ?><span class="profile-meta-item"><i class="fas fa-star"></i> Gol. <?php echo htmlspecialchars($form['golongan']); ?></span><?php endif; ?>
                <?php if (!empty($form['provinsi'])): ?><span class="profile-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($form['provinsi']); ?></span><?php endif; ?>
            </div>
        </div>
        <div class="profile-header-actions">
            <?php if ($mode === 'view'): ?>
                <a href="?mode=edit" class="btn btn-primary btn-lg rounded-pill px-4 shadow-sm fw-semibold"><i class="fas fa-edit me-2"></i>Edit Profil</a>
            <?php else: ?>
                <a href="?mode=view" class="btn btn-outline-primary btn-lg rounded-pill px-4 fw-semibold"><i class="fas fa-eye me-2"></i>Lihat Profil</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($mode === 'edit'): ?>
<!-- ============================================================ -->
<!-- EDIT MODE -->
<!-- ============================================================ -->
<form method="POST" enctype="multipart/form-data" id="profileForm" novalidate>
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
<input type="hidden" name="update_profile" value="1">

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">

        <!-- Section: Data Pribadi -->
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="section-icon" style="background:#eef2ff;color:#4f46e5"><i class="fas fa-user"></i></div>
                <div><h5 class="mb-0">Data Pribadi</h5><small class="text-muted">Informasi identitas diri</small></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label profile-label">Nama Lengkap</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">NIP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nip" value="<?php echo htmlspecialchars($form['nip']??''); ?>" placeholder="18 digit NIP" maxlength="18" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">NIK <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="text" class="form-control" name="nik" value="<?php echo htmlspecialchars($form['nik']??''); ?>" placeholder="16 digit NIK KTP" maxlength="16">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Tempat Lahir <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="text" class="form-control" name="tempat_lahir" value="<?php echo htmlspecialchars($form['tempat_lahir']??''); ?>" placeholder="Kota kelahiran">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="tanggal_lahir" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form['tanggal_lahir']??''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select class="form-select" name="gender" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki" <?php echo ($form['gender']??'')==='Laki-laki'?'selected':''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo ($form['gender']??'')==='Perempuan'?'selected':''; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Agama <span class="text-muted fw-normal">(opsional)</span></label>
                    <select class="form-select" name="agama">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($agama_list as $a): ?>
                        <option value="<?php echo $a; ?>" <?php echo ($form['agama']??'')===$a?'selected':''; ?>><?php echo $a; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Status Perkawinan <span class="text-muted fw-normal">(opsional)</span></label>
                    <select class="form-select" name="status_perkawinan">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($status_kawin_list as $sk): ?>
                        <option value="<?php echo $sk; ?>" <?php echo ($form['status_perkawinan']??'')===$sk?'selected':''; ?>><?php echo $sk; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section: Data Kepegawaian -->
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="section-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-building"></i></div>
                <div><h5 class="mb-0">Data Kepegawaian</h5><small class="text-muted">Informasi status ASN & instansi</small></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label profile-label">Golongan <span class="text-danger">*</span></label>
                    <select class="form-select" name="golongan" required>
                        <option value="">-- Pilih Golongan --</option>
                        <?php foreach ($golongan_list as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo ($form['golongan']??'')===$g?'selected':''; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Jabatan Saat Ini <span class="text-danger">*</span></label>
                    <select class="form-select" name="jabatan_id" id="jabatanSelect" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <?php foreach ($jabatan_list as $j): ?>
                        <option value="<?php echo $j['id']; ?>" <?php echo ($form['jabatan_id']??0)==$j['id']?'selected':''; ?>><?php echo htmlspecialchars($j['nama_jabatan'].' ('.ucfirst($j['kategori']).')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Instansi / Unit Kerja <span class="text-danger">*</span></label>
                    <select class="form-select" name="unit_kerja_id" required>
                        <option value="">-- Pilih Unit Kerja --</option>
                        <?php foreach ($unit_list as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo ($form['unit_kerja_id']??0)==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['kode_satker'].' - '.$u['nama_satker']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Status Kepegawaian <span class="text-danger">*</span></label>
                    <select class="form-select" name="status_pekerjaan" required>
                        <option value="">-- Pilih --</option>
                        <option value="PNS" <?php echo ($form['status_pekerjaan']??'')==='PNS'?'selected':''; ?>>PNS</option>
                        <option value="PPPK" <?php echo ($form['status_pekerjaan']??'')==='PPPK'?'selected':''; ?>>PPPK</option>
                        <option value="Non-ASN" <?php echo ($form['status_pekerjaan']??'')==='Non-ASN'?'selected':''; ?>>Non-ASN</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">NPWP <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="text" class="form-control" name="npwp" value="<?php echo htmlspecialchars($form['npwp']??''); ?>" placeholder="15 digit NPWP" maxlength="15">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">No. Karpeg <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="text" class="form-control" name="no_karpeg" value="<?php echo htmlspecialchars($form['no_karpeg']??''); ?>" placeholder="Nomor Kartu Pegawai">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">TMT CPNS <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="date" class="form-control" name="tmt_cpns" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form['tmt_cpns']??''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">TMT PNS <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="date" class="form-control" name="tmt_pns" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($form['tmt_pns']??''); ?>">
                </div>
            </div>
        </div>

        <!-- Section: Pendidikan -->
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="section-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-graduation-cap"></i></div>
                <div><h5 class="mb-0">Riwayat Pendidikan</h5><small class="text-muted">Tambahkan semua jenjang pendidikan Anda</small></div>
            </div>
            <div id="educationContainer">
                <?php
                $eduEntries = !empty($user_education) ? $user_education : [['level'=>'','institution'=>'','major'=>'','degree'=>'']];
                foreach ($eduEntries as $i => $edu):
                    $edu = (array)$edu;
                ?>
                <div class="edu-row mb-3 pb-3 <?php echo $i < count($eduEntries)-1 ? 'border-bottom border-light' : ''; ?>">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label profile-label small">Level Pendidikan</label>
                            <select class="form-select form-select-sm" name="edu_level[]">
                                <option value="">-- Pilih --</option>
                                <?php foreach ($education_list as $ed): ?>
                                <option value="<?php echo $ed; ?>" <?php echo ($edu['level']??'')===$ed?'selected':''; ?>><?php echo $ed; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label profile-label small">Nama Sekolah / Universitas <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" class="form-control form-control-sm" name="edu_institution[]" value="<?php echo htmlspecialchars($edu['institution']??''); ?>" placeholder="Contoh: Universitas Indonesia">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label profile-label small">Gelar <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" class="form-control form-control-sm" name="edu_degree[]" value="<?php echo htmlspecialchars($edu['degree']??''); ?>" placeholder="Contoh: S.Pd., M.Pd.">
                        </div>
                        <div class="col-md-2">
                            <?php if ($i === 0): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 w-100" onclick="addEduRow()" title="Tambah Pendidikan"><i class="fas fa-plus me-1"></i>Tambah</button>
                            <?php else: ?>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 w-100" onclick="this.closest('.edu-row').remove()" title="Hapus"><i class="fas fa-trash me-1"></i>Hapus</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-11">
                            <label class="form-label profile-label small">Program Studi / Jurusan <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="text" class="form-control form-control-sm" name="edu_major[]" value="<?php echo htmlspecialchars($edu['major']??''); ?>" placeholder="Contoh: Pendidikan Matematika">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-12">
                    <label class="form-label profile-label">Keahlian / Skills <span class="text-muted fw-normal">(opsional, pisahkan dengan koma)</span></label>
                    <input type="text" class="form-control" name="skills" id="skillsInput" value="<?php echo htmlspecialchars($form['skills']??''); ?>" placeholder="Contoh: Manajemen Proyek, Analisis Data, Pengembangan Kurikulum">
                    <div id="skillsPreview" class="d-flex flex-wrap gap-1 mt-2"></div>
                </div>
                <div class="col-12">
                    <label class="form-label profile-label">Sertifikasi <span class="text-muted fw-normal">(opsional, pisahkan dengan koma)</span></label>
                    <input type="text" class="form-control" name="sertifikasi" value="<?php echo htmlspecialchars($form['sertifikasi']??''); ?>" placeholder="Contoh: Sertifikasi Pengadaan Barang/Jasa, Diklat PIM III">
                </div>
                <div class="col-12" id="mapelSection" style="display:none">
                    <label class="form-label profile-label">Mata Pelajaran Yang Pernah Diajar</label>
                    <div class="input-group mb-2">
                        <select class="form-select" id="mapelSelect"><option value="">Pilih Mata Pelajaran</option><?php foreach ($mata_pelajaran_options as $mp): ?><option value="<?php echo $mp; ?>"><?php echo $mp; ?></option><?php endforeach; ?></select>
                        <button type="button" class="btn btn-outline-secondary" onclick="addMapel()"><i class="fas fa-plus"></i></button>
                    </div>
                    <div id="mapelTags" class="d-flex flex-wrap gap-1">
                        <?php foreach ($user_subjects as $mp): ?>
                        <span class="badge bg-info-subtle text-info-emphasis px-2 py-1"><?php echo htmlspecialchars($mp); ?><input type="hidden" name="mata_pelajaran[]" value="<?php echo htmlspecialchars($mp); ?>"><button type="button" class="btn-close ms-1" style="font-size:0.45rem" onclick="this.parentElement.remove()"></button></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Pelatihan / Training -->
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="section-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-certificate"></i></div>
                <div><h5 class="mb-0">Pelatihan / Training <span class="text-muted fw-normal fs-6">(opsional)</span></h5><small class="text-muted">Diklat, workshop, seminar yang pernah diikuti</small></div>
            </div>
            <div id="trainingContainer">
                <?php
                $trEntries = !empty($user_training) ? $user_training : [['training_name'=>'','organizer'=>'','training_year'=>'','certificate'=>'']];
                foreach ($trEntries as $i => $tr):
                    $tr = (array)$tr;
                ?>
                <div class="tr-row row g-2 mb-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label profile-label small">Nama Pelatihan</label>
                        <input type="text" class="form-control form-control-sm" name="tr_name[]" value="<?php echo htmlspecialchars($tr['training_name']??''); ?>" placeholder="Contoh: Diklat PIM III">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label profile-label small">Penyelenggara <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" class="form-control form-control-sm" name="tr_organizer[]" value="<?php echo htmlspecialchars($tr['organizer']??''); ?>" placeholder="Contoh: BPSDM">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label profile-label small">Tahun <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="number" class="form-control form-control-sm" name="tr_year[]" value="<?php echo htmlspecialchars($tr['training_year']??''); ?>" placeholder="2023" min="1990" max="<?php echo date('Y'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label profile-label small">Sertifikat <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" class="form-control form-control-sm" name="tr_certificate[]" value="<?php echo htmlspecialchars($tr['certificate']??''); ?>" placeholder="No. Sertifikat">
                    </div>
                    <div class="col-md-1">
                        <?php if ($i === 0): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle" onclick="addTrRow()" title="Tambah Pelatihan"><i class="fas fa-plus"></i></button>
                        <?php else: ?>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" onclick="this.closest('.tr-row').remove()" title="Hapus"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Section: Kontak & Alamat -->
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="section-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-address-book"></i></div>
                <div><h5 class="mb-0">Kontak & Alamat</h5><small class="text-muted">Informasi kontak dan domisili</small></div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label profile-label">Nomor HP <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" name="phone" placeholder="08xxxxxxxxxx" value="<?php echo htmlspecialchars($form['phone']??''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">LinkedIn <span class="text-muted fw-normal">(opsional)</span></label>
                    <input type="url" class="form-control" name="linkedin_url" value="<?php echo htmlspecialchars($form['linkedin_url']??''); ?>" placeholder="https://linkedin.com/in/username">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label">Provinsi <span class="text-danger">*</span></label>
                    <select class="form-select" name="provinsi" required>
                        <option value="">-- Pilih Provinsi --</option>
                        <?php foreach ($provinsi_list as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo ($form['provinsi']??'')===$p?'selected':''; ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label profile-label">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="address" rows="2" placeholder="Jl. Contoh No. 123, Kelurahan, Kecamatan, Kota/Kabupaten" required><?php echo htmlspecialchars($form['address']??''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Section: Foto & Bio -->
        <div class="profile-section-card">
            <div class="profile-section-header">
                <div class="section-icon" style="background:#fce7f3;color:#db2777"><i class="fas fa-camera"></i></div>
                <div><h5 class="mb-0">Foto Profil & Bio</h5><small class="text-muted">Tampilkan citra profesional Anda</small></div>
            </div>
            <div class="row g-3 align-items-center">
                <div class="col-md-auto text-center">
                    <div class="edit-photo-preview" id="fotoPreview">
                        <?php if (!empty($form['foto'])): ?><img src="<?php echo base_url($form['foto']); ?>"><?php else: ?><i class="fas fa-user" style="font-size:60px;color:#94a3b8"></i><?php endif; ?>
                    </div>
                    <label class="btn btn-outline-secondary btn-sm mt-2 rounded-pill" style="cursor:pointer"><i class="fas fa-camera me-1"></i>Upload Foto<input type="file" name="foto" accept="image/*" class="d-none" onchange="previewImage(this)"></label>
                    <div class="form-text">Format JPG/PNG, maks 2MB</div>
                </div>
                <div class="col-md">
                    <label class="form-label profile-label">Bio Singkat <span class="text-muted fw-normal">(opsional)</span></label>
                    <textarea class="form-control" name="bio" id="bioField" rows="4" placeholder="Ceritakan sedikit tentang diri Anda, pengalaman, dan aspirasi profesional..."><?php echo htmlspecialchars($form['bio']??''); ?></textarea>
                    <div class="form-text"><span id="bioCount">0</span>/500 karakter</div>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm"><i class="fas fa-save me-2"></i>Simpan Profil</button>
            <a href="?mode=view" class="btn btn-outline-secondary btn-lg rounded-pill px-4">Batal</a>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4">
        <div class="profile-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-clipboard-check me-2 text-success"></i>Kelengkapan Profil</h6>
            <?php
            $mandatory = ['NIP','NIK (opsional)','Nomor HP','Tgl Lahir','Golongan','Jabatan','Instansi','Pendidikan','Prodi','Alamat','Provinsi','Foto'];
            $checks = [
                $form['nip']??null, $form['nik']??null, $form['phone']??null, $form['tanggal_lahir']??null,
                $form['golongan']??null, ($form['jabatan_id']??null)?'OK':null, ($form['unit_kerja_id']??null)?'OK':null,
                $form['last_education']??null, $form['major']??null, $form['address']??null, $form['provinsi']??null, $form['foto']??null
            ];
            $filled = 0; foreach ($checks as $c) if (!empty($c)) $filled++;
            $pct = round($filled/count($checks)*100);
            ?>
            <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-success rounded-pill" style="width:<?php echo $pct; ?>%"></div></div>
            <small class="text-muted"><?php echo $filled; ?>/<?php echo count($checks); ?> field terisi (<?php echo $pct; ?>%)</small>
            <ul class="list-unstyled mt-3 small">
                <?php foreach ($mandatory as $i => $label): ?>
                <li class="mb-2 d-flex align-items-center gap-2">
                    <i class="fas fa-<?php echo !empty($checks[$i])?'check-circle text-success':'circle text-muted'; ?>"></i>
                    <span class="<?php echo !empty($checks[$i])?'':'text-muted'; ?>"><?php echo $label; ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="profile-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-lightbulb me-2 text-warning"></i>Tips</h6>
            <ul class="list-unstyled small text-muted mb-0">
                <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> Lengkapi data agar dapat mendaftar ujian</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> Foto profesional meningkatkan kredibilitas</li>
                <li class="mb-2"><i class="fas fa-check-circle text-success me-1"></i> Tulis bio singkat yang menggambarkan Anda</li>
                <li><i class="fas fa-check-circle text-success me-1"></i> Update profil secara berkala</li>
            </ul>
        </div>
    </div>
</div>
</form>

<?php else: ?>
<!-- ============================================================ -->
<!-- VIEW MODE — Compact single-screen tabs -->
<!-- ============================================================ -->
<div class="row">
    <div class="col-lg-8">

        <!-- Tabs -->
        <div class="profile-tabs mb-3">
            <a href="?tab=overview" class="profile-tab <?php echo $tab==='overview'?'active':''; ?>"><i class="fas fa-user me-1"></i> Ringkasan</a>
            <a href="?tab=kepegawaian" class="profile-tab <?php echo $tab==='kepegawaian'?'active':''; ?>"><i class="fas fa-building me-1"></i> Kepegawaian</a>
            <a href="?tab=pendidikan" class="profile-tab <?php echo $tab==='pendidikan'?'active':''; ?>"><i class="fas fa-graduation-cap me-1"></i> Pendidikan</a>
            <a href="?tab=kontak" class="profile-tab <?php echo $tab==='kontak'?'active':''; ?>"><i class="fas fa-address-card me-1"></i> Kontak</a>
        </div>

        <?php $activeTab = in_array($tab, ['overview','kepegawaian','pendidikan','kontak']) ? $tab : 'overview'; ?>

        <!-- ============ TAB: Ringkasan ============ -->
        <?php if ($activeTab === 'overview'): ?>
        <?php if (!empty($form['bio'])): ?>
        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-quote-right text-pink me-2"></i>Tentang Saya</div>
            <p class="mb-0" style="line-height:1.6;font-size:0.9rem"><?php echo nl2br(htmlspecialchars($form['bio'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-info-circle text-indigo me-2"></i>Informasi Pribadi</div>
            <div class="profile-grid-3">
                <?php
                $quickFacts = [
                    ['NIP', $form['nip']??null],
                    ['NIK', $form['nik']??null],
                    ['Tempat / Tgl Lahir', (!empty($form['tempat_lahir'])?$form['tempat_lahir'].', ':'').(!empty($form['tanggal_lahir'])?date('d M Y',strtotime($form['tanggal_lahir'])):'')],
                    ['Jenis Kelamin', $form['gender']??null],
                    ['Agama', $form['agama']??null],
                    ['Status', $form['status_perkawinan']??null],
                ];
                foreach ($quickFacts as $qf): if (empty($qf[1])) continue; ?>
                <div class="profile-kv"><span class="profile-kv-label"><?php echo $qf[0]; ?></span><span class="profile-kv-value"><?php echo htmlspecialchars($qf[1]); ?></span></div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($form['skills'])): ?>
            <div class="profile-tags-row"><span class="profile-tags-label">Keahlian:</span>
                <?php foreach (array_map('trim', explode(',', $form['skills'])) as $s): if(empty($s)) continue; ?>
                <span class="skill-badge"><?php echo htmlspecialchars($s); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($form['sertifikasi'])): ?>
            <div class="profile-tags-row"><span class="profile-tags-label">Sertifikasi:</span>
                <?php foreach (array_map('trim', explode(',', $form['sertifikasi'])) as $s): if(empty($s)) continue; ?>
                <span class="skill-badge skill-badge-alt"><?php echo htmlspecialchars($s); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($user_subjects)): ?>
            <div class="profile-tags-row"><span class="profile-tags-label">Mapel:</span>
                <?php foreach ($user_subjects as $mp): ?>
                <span class="skill-badge skill-badge-green"><?php echo htmlspecialchars($mp); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ============ TAB: Kepegawaian ============ -->
        <?php if ($activeTab === 'kepegawaian'): ?>
        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-building text-amber me-2"></i>Data Kepegawaian</div>
            <div class="profile-grid-3">
                <?php
                $empFacts = [
                    ['Golongan', $form['golongan']??null],
                    ['Jabatan', get_jabatan_name($db, $form['jabatan_id']??0)],
                    ['Instansi', get_instance_name($db, $form['unit_kerja_id']??0)],
                    ['Status Kepegawaian', $form['status_pekerjaan']??null],
                    ['NPWP', $form['npwp']??null],
                    ['No. Karpeg', $form['no_karpeg']??null],
                    ['TMT CPNS', !empty($form['tmt_cpns'])?date('d M Y',strtotime($form['tmt_cpns'])):null],
                    ['TMT PNS', !empty($form['tmt_pns'])?date('d M Y',strtotime($form['tmt_pns'])):null],
                ];
                foreach ($empFacts as $ef): if (empty($ef[1]) || $ef[1]==='-') continue; ?>
                <div class="profile-kv"><span class="profile-kv-label"><?php echo $ef[0]; ?></span><span class="profile-kv-value"><?php echo htmlspecialchars($ef[1]); ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============ TAB: Pendidikan ============ -->
        <?php if ($activeTab === 'pendidikan'): ?>
        <?php
        $stmt = $db->prepare("SELECT * FROM user_education WHERE user_id = ? ORDER BY id"); $stmt->execute([$user_id]); $viewEdu = $stmt->fetchAll();
        $stmt = $db->prepare("SELECT * FROM user_training WHERE user_id = ? ORDER BY id"); $stmt->execute([$user_id]); $viewTr = $stmt->fetchAll();
        ?>

        <!-- Riwayat Pendidikan — Timeline Style -->
        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-graduation-cap text-blue me-2"></i>Riwayat Pendidikan</div>
            <?php if (!empty($viewEdu)): ?>
            <div class="edu-timeline">
                <?php foreach ($viewEdu as $i => $edu): $edu = (array)$edu; ?>
                <div class="edu-timeline-item">
                    <div class="edu-timeline-marker">
                        <div class="edu-timeline-dot"></div>
                        <?php if ($i < count($viewEdu)-1): ?><div class="edu-timeline-line"></div><?php endif; ?>
                    </div>
                    <div class="edu-timeline-content">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="edu-level-badge"><?php echo htmlspecialchars($edu['level']); ?></span>
                            <?php if (!empty($edu['degree'])): ?>
                            <span class="edu-degree-badge"><?php echo htmlspecialchars($edu['degree']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($edu['institution'])): ?>
                        <div class="fw-semibold mb-1" style="font-size:0.95rem;color:#1e293b"><?php echo htmlspecialchars($edu['institution']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($edu['major'])): ?>
                        <div style="font-size:0.82rem;color:#64748b"><i class="fas fa-book-open me-1" style="font-size:0.7rem"></i><?php echo htmlspecialchars($edu['major']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-3"><i class="fas fa-graduation-cap fa-2x text-muted mb-2 d-block"></i><p class="text-muted small mb-0">Belum ada riwayat pendidikan.</p></div>
            <?php endif; ?>
        </div>

        <!-- Pelatihan — Timeline Style -->
        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-certificate text-pink me-2"></i>Pelatihan / Training</div>
            <?php if (!empty($viewTr)): ?>
            <div class="edu-timeline">
                <?php foreach ($viewTr as $i => $tr): $tr = (array)$tr; ?>
                <div class="edu-timeline-item">
                    <div class="edu-timeline-marker">
                        <div class="edu-timeline-dot tr-dot"></div>
                        <?php if ($i < count($viewTr)-1): ?><div class="edu-timeline-line"></div><?php endif; ?>
                    </div>
                    <div class="edu-timeline-content">
                        <div class="fw-semibold mb-1" style="font-size:0.92rem;color:#1e293b"><?php echo htmlspecialchars($tr['training_name']); ?></div>
                        <div style="font-size:0.8rem;color:#64748b">
                            <?php if (!empty($tr['organizer'])): ?><span><i class="fas fa-building me-1" style="font-size:0.65rem"></i><?php echo htmlspecialchars($tr['organizer']); ?></span><?php endif; ?>
                            <?php if (!empty($tr['training_year'])): ?><span class="ms-3"><i class="fas fa-calendar-alt me-1" style="font-size:0.65rem"></i><?php echo htmlspecialchars($tr['training_year']); ?></span><?php endif; ?>
                            <?php if (!empty($tr['certificate'])): ?><span class="ms-3"><i class="fas fa-stamp me-1" style="font-size:0.65rem"></i><?php echo htmlspecialchars($tr['certificate']); ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-3"><i class="fas fa-certificate fa-2x text-muted mb-2 d-block"></i><p class="text-muted small mb-0">Belum ada riwayat pelatihan.</p></div>
            <?php endif; ?>
        </div>

        <!-- Skills & Sertifikasi -->
        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-tools text-indigo me-2"></i>Keahlian & Sertifikasi</div>
            <?php if (!empty($form['skills'])): ?>
            <div class="profile-tags-row" style="border-top:0;margin-top:0;padding-top:0"><span class="profile-tags-label">Keahlian:</span>
                <?php foreach (array_map('trim', explode(',', $form['skills'])) as $s): if(empty($s)) continue; ?>
                <span class="skill-badge"><?php echo htmlspecialchars($s); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($form['sertifikasi'])): ?>
            <div class="profile-tags-row"><span class="profile-tags-label">Sertifikasi:</span>
                <?php foreach (array_map('trim', explode(',', $form['sertifikasi'])) as $s): if(empty($s)) continue; ?>
                <span class="skill-badge skill-badge-alt"><?php echo htmlspecialchars($s); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (empty($form['skills']) && empty($form['sertifikasi'])): ?>
            <div class="text-center py-2"><p class="text-muted small mb-0">Belum ada keahlian atau sertifikasi.</p></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ============ TAB: Kontak ============ -->
        <?php if ($activeTab === 'kontak'): ?>
        <div class="profile-section-card profile-section-compact">
            <div class="profile-section-header-compact"><i class="fas fa-address-book text-green me-2"></i>Kontak & Alamat</div>
            <div class="profile-grid-2">
                <?php
                foreach ([
                    ['Email',$_SESSION['user_email']],
                    ['Nomor HP',$form['phone']??null],
                    ['Provinsi',$form['provinsi']??null],
                    ['Alamat',$form['address']??null],
                ] as $cf): if (empty($cf[1])) continue; ?>
                <div class="profile-kv"><span class="profile-kv-label"><?php echo $cf[0]; ?></span><span class="profile-kv-value"><?php echo htmlspecialchars($cf[1]); ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($form['linkedin_url'])): ?>
            <div class="mt-2 pt-2 border-top">
                <a href="<?php echo htmlspecialchars($form['linkedin_url']); ?>" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm rounded-pill px-3"><i class="fab fa-linkedin me-1"></i>Profil LinkedIn</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right Sidebar (View Mode) -->
    <div class="col-lg-4">
        <?php if ($is_complete): ?>
        <div class="profile-sidebar-card text-center">
            <span class="badge bg-success rounded-pill px-3 py-2 mb-2"><i class="fas fa-check-circle me-1"></i>PROFIL LENGKAP</span>
            <p class="text-muted small mb-0">Siap mendaftar ujian.</p>
        </div>
        <?php else: ?>
        <div class="profile-sidebar-card text-center" style="background:linear-gradient(135deg,#fef3c7,#fff7ed)">
            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 mb-2"><i class="fas fa-exclamation-triangle me-1"></i>PROFIL BELUM LENGKAP</span>
            <p class="text-muted small mb-2">Lengkapi data untuk mendaftar ujian.</p>
            <a href="?mode=edit" class="btn btn-warning btn-sm rounded-pill px-4 fw-semibold"><i class="fas fa-edit me-1"></i>Lengkapi</a>
        </div>
        <?php endif; ?>

        <div class="profile-sidebar-card">
            <h6 class="text-uppercase small fw-bold text-muted mb-3"><i class="fas fa-chart-pie me-2"></i>Statistik Pendaftaran</h6>
            <?php
            $stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(*) FILTER (WHERE status='submitted') as submitted, COUNT(*) FILTER (WHERE status='accepted') as accepted FROM submissions WHERE user_id=?");
            $stmt->execute([$user_id]); $stats = $stmt->fetch();
            ?>
            <div class="d-flex justify-content-around text-center">
                <div><div class="fw-bold fs-5 text-primary"><?php echo (int)($stats['total']??0); ?></div><small class="text-muted">Total</small></div>
                <div><div class="fw-bold fs-5 text-warning"><?php echo (int)($stats['submitted']??0); ?></div><small class="text-muted">Terkirim</small></div>
                <div><div class="fw-bold fs-5 text-success"><?php echo (int)($stats['accepted']??0); ?></div><small class="text-muted">Lulus</small></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- STYLES -->
<!-- ============================================================ -->
<style>
/* === Profile Header Banner === */
.profile-header-banner {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    background: #fff;
}
.profile-header-bg {
    height: 160px;
    background: linear-gradient(135deg, #0a2463 0%, #123499 25%, #1a56db 50%, #2563eb 75%, #3b82f6 100%);
    position: relative;
}
.profile-header-bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.profile-header-content {
    position: relative;
    padding: 0 28px 24px;
    margin-top: -65px;
    display: flex;
    align-items: flex-end;
    gap: 20px;
    flex-wrap: wrap;
    background: #fff;
}
.profile-avatar-wrapper { position: relative; flex-shrink: 0; }
.profile-avatar {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: 5px solid #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    overflow: hidden;
    background: #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
.avatar-placeholder { font-size: 56px; color: #94a3b8; }
.profile-verified-badge {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: #059669;
    color: #fff;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.profile-header-info { flex: 1; min-width: 200px; padding-bottom: 4px; }
.profile-name { color: #1e293b; font-size: 1.5rem; font-weight: 700; margin-bottom: 2px; }
.profile-nip { color: #64748b; font-size: 0.85rem; margin-bottom: 8px; }
.profile-meta { display: flex; flex-wrap: wrap; gap: 8px; }
.profile-meta-item {
    background: #f1f5f9;
    color: #475569;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.profile-meta-item i { color: #0d6efd; font-size: 0.7rem; }
.profile-header-actions { padding-bottom: 4px; flex-shrink: 0; }

/* === Tabs === */
.profile-tabs {
    display: flex;
    gap: 4px;
    background: #fff;
    border-radius: 12px;
    padding: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.profile-tab {
    flex: 1;
    text-align: center;
    padding: 10px 16px;
    border-radius: 10px;
    color: #64748b;
    font-weight: 500;
    font-size: 0.88rem;
    text-decoration: none;
    transition: all 0.2s;
    white-space: nowrap;
}
.profile-tab:hover { background: #f1f5f9; color: #334155; text-decoration: none; }
.profile-tab.active {
    background: #1a3a5c;
    color: #fff;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(26,58,92,0.25);
}

/* === Section Cards === */
.profile-section-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}
.profile-section-compact { padding: 18px 20px; margin-bottom: 14px; }
.profile-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}
.profile-section-header-compact {
    font-size: 0.85rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
}
.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

/* === Compact Grid Layouts === */
.profile-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 24px; }
.profile-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px 20px; }
.profile-kv { padding: 6px 0; }
.profile-kv-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 1px;
}
.profile-kv-value {
    font-size: 0.85rem;
    font-weight: 500;
    color: #1e293b;
    word-break: break-word;
}

/* === Tags Row (Skills, Sertifikasi, Mapel) === */
.profile-tags-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}
.profile-tags-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    margin-right: 4px;
}

/* === Sidebar === */
.profile-sidebar-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

/* === Form Labels === */
.profile-label {
    font-size: 0.82rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #334155;
}

/* === View Fields === */
.view-field { padding: 10px 0; }
.view-field label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}
.view-field span {
    font-size: 0.92rem;
    font-weight: 500;
    color: #1e293b;
}

/* === Skills Badge === */
.skill-badge {
    background: #eef2ff;
    color: #4f46e5;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 500;
    display: inline-block;
}
.skill-badge-alt { background: #fef3c7; color: #92400e; }
.skill-badge-green { background: #d1fae5; color: #065f46; }

/* === Education Timeline === */
.edu-timeline { padding-left: 4px; }
.edu-timeline-item { display: flex; gap: 14px; padding-bottom: 0; }
.edu-timeline-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 20px;
    padding-top: 4px;
}
.edu-timeline-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #2563eb;
    border: 3px solid #dbeafe;
    flex-shrink: 0;
}
.tr-dot { background: #db2777; border-color: #fce7f3; }
.edu-timeline-line {
    width: 2px;
    flex: 1;
    min-height: 24px;
    background: #e2e8f0;
    margin: 4px 0;
}
.edu-timeline-content { flex: 1; padding-bottom: 16px; }
.edu-timeline-item:last-child .edu-timeline-content { padding-bottom: 0; }
.edu-level-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.78rem;
    font-weight: 600;
}
.edu-degree-badge {
    background: #eef2ff;
    color: #4f46e5;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* === Color Helpers === */
.text-pink { color: #db2777 !important; }
.text-indigo { color: #4f46e5 !important; }
.text-amber { color: #d97706 !important; }
.text-blue { color: #2563eb !important; }
.text-green { color: #059669 !important; }

/* === Edit Photo Preview === */
.edit-photo-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px dashed #cbd5e1;
    margin: 0 auto;
}
.edit-photo-preview img { width: 100%; height: 100%; object-fit: cover; }

/* === Responsive === */
@media (max-width: 768px) {
    .profile-header-content { flex-direction: column; align-items: center; text-align: center; margin-top: -45px; }
    .profile-avatar { width: 100px; height: 100px; }
    .profile-name { font-size: 1.2rem; }
    .profile-meta { justify-content: center; }
    .profile-header-actions { width: 100%; text-align: center; }
    .profile-tabs { overflow-x: auto; }
    .profile-tab { font-size: 0.78rem; padding: 8px 10px; }
    .profile-grid-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .profile-grid-2, .profile-grid-3 { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Image Preview ===
    var fotoInput = document.querySelector('input[name="foto"]');
    if (fotoInput) {
        fotoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var preview = document.getElementById('fotoPreview');
                    preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover">';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }

    // === Bio Character Counter ===
    var bioField = document.getElementById('bioField');
    var bioCount = document.getElementById('bioCount');
    if (bioField && bioCount) {
        bioCount.textContent = bioField.value.length;
        bioField.addEventListener('input', function() { bioCount.textContent = this.value.length; });
    }

    // === Skills Live Preview ===
    var skillsInput = document.getElementById('skillsInput');
    var skillsPreview = document.getElementById('skillsPreview');
    if (skillsInput && skillsPreview) {
        skillsInput.addEventListener('input', function() {
            skillsPreview.innerHTML = '';
            this.value.split(',').forEach(function(s) {
                s = s.trim();
                if (s) {
                    var span = document.createElement('span');
                    span.className = 'skill-badge';
                    span.textContent = s;
                    skillsPreview.appendChild(span);
                }
            });
        });
        skillsInput.dispatchEvent(new Event('input'));
    }

    // === Show Mapel if Jabatan has 'guru' ===
    var jabSel = document.getElementById('jabatanSelect');
    if (jabSel) {
        jabSel.addEventListener('change', toggleMapel);
        toggleMapel();
    }
    function toggleMapel() {
        var sel = document.getElementById('jabatanSelect');
        var mapel = document.getElementById('mapelSection');
        if (sel && mapel) {
            var txt = sel.options[sel.selectedIndex].text.toLowerCase();
            mapel.style.display = txt.includes('guru') ? 'block' : 'none';
        }
    }
});

// === Add Mapel Function ===
function addMapel() {
    var sel = document.getElementById('mapelSelect');
    if (!sel || !sel.value) return;
    var tags = document.getElementById('mapelTags');
    var escaped = sel.value.replace(/"/g, '&quot;');
    if (tags.querySelector('input[value="' + escaped + '"]')) { alert('Mata pelajaran sudah ada!'); return; }
    var span = document.createElement('span');
    span.className = 'badge bg-info-subtle text-info-emphasis px-2 py-1';
    span.innerHTML = sel.value + '<input type="hidden" name="mata_pelajaran[]" value="' + escaped + '"><button type="button" class="btn-close ms-1" style="font-size:0.45rem" onclick="this.parentElement.remove()"></button>';
    tags.appendChild(span);
    sel.value = '';
}

// === Add Education Row ===
function addEduRow() {
    var container = document.getElementById('educationContainer');
    var wrapper = document.createElement('div');
    wrapper.className = 'edu-row mb-3 pb-3 border-bottom border-light';
    wrapper.innerHTML =
        '<div class="row g-2 align-items-end">' +
            '<div class="col-md-3"><label class="form-label profile-label small">Level Pendidikan</label><select class="form-select form-select-sm" name="edu_level[]"><option value="">-- Pilih --</option><?php foreach ($education_list as $ed): ?><option value="<?php echo $ed; ?>"><?php echo $ed; ?></option><?php endforeach; ?></select></div>' +
            '<div class="col-md-4"><label class="form-label profile-label small">Nama Sekolah / Universitas <span class="text-muted fw-normal">(opsional)</span></label><input type="text" class="form-control form-control-sm" name="edu_institution[]" placeholder="Contoh: Universitas Indonesia"></div>' +
            '<div class="col-md-3"><label class="form-label profile-label small">Gelar <span class="text-muted fw-normal">(opsional)</span></label><input type="text" class="form-control form-control-sm" name="edu_degree[]" placeholder="Contoh: S.Pd., M.Pd."></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 w-100" onclick="this.closest(\'.edu-row\').remove()"><i class="fas fa-trash me-1"></i>Hapus</button></div>' +
        '</div>' +
        '<div class="row g-2 mt-1">' +
            '<div class="col-md-11"><label class="form-label profile-label small">Program Studi / Jurusan <span class="text-muted fw-normal">(opsional)</span></label><input type="text" class="form-control form-control-sm" name="edu_major[]" placeholder="Contoh: Pendidikan Matematika"></div>' +
        '</div>';
    container.appendChild(wrapper);
}

// === Add Training Row ===
function addTrRow() {
    var container = document.getElementById('trainingContainer');
    var row = document.createElement('div');
    row.className = 'tr-row row g-2 mb-2 align-items-end';
    row.innerHTML = '<div class="col-md-4"><label class="form-label profile-label small">Nama Pelatihan</label><input type="text" class="form-control form-control-sm" name="tr_name[]" placeholder="Contoh: Diklat PIM III"></div>' +
        '<div class="col-md-3"><label class="form-label profile-label small">Penyelenggara <span class="text-muted fw-normal">(opsional)</span></label><input type="text" class="form-control form-control-sm" name="tr_organizer[]" placeholder="Contoh: BPSDM"></div>' +
        '<div class="col-md-2"><label class="form-label profile-label small">Tahun <span class="text-muted fw-normal">(opsional)</span></label><input type="number" class="form-control form-control-sm" name="tr_year[]" placeholder="2023" min="1990" max="<?php echo date('Y'); ?>"></div>' +
        '<div class="col-md-2"><label class="form-label profile-label small">Sertifikat <span class="text-muted fw-normal">(opsional)</span></label><input type="text" class="form-control form-control-sm" name="tr_certificate[]" placeholder="No. Sertifikat"></div>' +
        '<div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm rounded-circle" onclick="this.closest(\'.tr-row\').remove()" title="Hapus"><i class="fas fa-times"></i></button></div>';
    container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../dashboard/footer-dashboard.php'; ?>
