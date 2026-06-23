<?php
// config/unit-kerja-helpers.php — Helper functions untuk Unit Kerja & Verifikator Scoping

/**
 * Ambil daftar unit_kerja_id yang di-scope ke user
 * Untuk ADMIN_SATKER: otomatis dari profile.unit_kerja_id
 * Untuk ADMIN_PUSAT: dari unit_kerja_verifikator mapping
 * Untuk SUPERADMIN: null (ALL)
 * Returns array atau null
 */
function get_user_unit_scope($user_id = null, $role = null) {
    if ($user_id === null) $user_id = $_SESSION['user_id'] ?? 0;
    if ($role === null) $role = $_SESSION['user_role'] ?? '';
    
    // SUPERADMIN bisa akses semua
    if ($role === 'SUPERADMIN') return null;
    
    $db = get_db_connection();
    
    // ADMIN_SATKER — otomatis dari profile
    if ($role === 'ADMIN_SATKER') {
        $stmt = $db->prepare("SELECT unit_kerja_id FROM profiles WHERE user_id = ? AND unit_kerja_id IS NOT NULL");
        $stmt->execute([$user_id]);
        $uk = $stmt->fetch();
        return $uk ? [(int)$uk['unit_kerja_id']] : [];
    }
    
    // ADMIN_PUSAT — dari mapping unit_kerja_verifikator
    if ($role === 'ADMIN_PUSAT') {
        $stmt = $db->prepare("SELECT unit_kerja_id FROM unit_kerja_verifikator WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
    
    return [];
}

/**
 * Ambil daftar unit_kerja_id yang di-assign ke verifikator saat ini
 * @deprecated — gunakan get_user_unit_scope()
 */
function get_verifikator_unit_ids($user_id = null) {
    return get_user_unit_scope($user_id);
}

/**
 * Filter query submissions/pendaftar berdasarkan unit_kerja verifikator
 * Mengembalikan WHERE clause dan params untuk ditambahkan ke query
 */
function get_verifikator_scope_filter($user_id = null) {
    $role = $user_id ? null : ($_SESSION['user_role'] ?? '');
    $unit_ids = get_user_unit_scope($user_id, $role);
    
    if ($unit_ids === null) {
        // SUPERADMIN — no filter
        return ['where' => '', 'params' => []];
    }
    
    if (empty($unit_ids)) {
        // Verifikator tanpa unit — tidak bisa lihat apa-apa
        return ['where' => ' AND 1=0', 'params' => []];
    }
    
    $placeholders = implode(',', array_fill(0, count($unit_ids), '?'));
    return [
        'where' => " AND p.unit_kerja_id IN ({$placeholders})",
        'params' => $unit_ids
    ];
}

/**
 * Cek apakah verifikator memiliki akses ke pendaftar berdasarkan user_id
 */
function verifikator_can_access_pendaftar($pendaftar_user_id) {
    $role = $_SESSION['user_role'] ?? '';
    if ($role === 'SUPERADMIN') return true;
    
    $db = get_db_connection();
    $verifikator_id = $_SESSION['user_id'] ?? 0;
    
    // Ambil unit_kerja dari pendaftar
    $stmt = $db->prepare("SELECT unit_kerja_id FROM profiles WHERE user_id = ?");
    $stmt->execute([$pendaftar_user_id]);
    $uk = $stmt->fetch();
    if (!$uk || !$uk['unit_kerja_id']) return false;
    
    // ADMIN_SATKER — cek unit_kerja dari profile sendiri
    if ($role === 'ADMIN_SATKER') {
        $stmt = $db->prepare("SELECT unit_kerja_id FROM profiles WHERE user_id = ? AND unit_kerja_id IS NOT NULL");
        $stmt->execute([$verifikator_id]);
        $my_uk = $stmt->fetch();
        return $my_uk && (int)$my_uk['unit_kerja_id'] === (int)$uk['unit_kerja_id'];
    }
    
    // ADMIN_PUSAT — cek mapping
    $stmt = $db->prepare("SELECT 1 FROM unit_kerja_verifikator WHERE user_id = ? AND unit_kerja_id = ?");
    $stmt->execute([$verifikator_id, $uk['unit_kerja_id']]);
    return (bool)$stmt->fetch();
}
?>