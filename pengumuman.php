<?php
$pageTitle = "Pengumuman Seleksi";
$activePage = "pengumuman";

include 'header.php';

/* ===== DATA SEMENTARA (NANTI BISA GANTI DATABASE) ===== */
$pengumuman = [
    1 => [
        'judul' => 'Pengumuman Hasil Seleksi Administrasi Tahap I',
        'tanggal' => '10 Juni 2026',
        'isi' => '
        <p>Panitia Seleksi Guru dan Tenaga Kependidikan SMA Unggul Garuda mengumumkan bahwa proses seleksi administrasi tahap I telah selesai dilaksanakan.</p>
        <p>Peserta dapat melihat status kelulusan administrasi melalui akun masing-masing pada dashboard portal seleksi.</p>
        <ul>
            <li>Peserta dengan status <strong>Lolos Administrasi</strong> berhak mengikuti tahapan Tes Kompetensi.</li>
            <li>Peserta dengan status <strong>Tidak Lolos</strong> dapat melihat alasan verifikasi pada akun masing-masing.</li>
        </ul>
        <p>Demikian pengumuman ini disampaikan untuk menjadi perhatian.</p>'
    ],
    2 => [
        'judul' => 'Jadwal Pelaksanaan Tes Kompetensi Seleksi Guru Garuda',
        'tanggal' => '18 Juni 2026',
        'isi' => '
        <p>Tes kompetensi akan dilaksanakan secara daring dan luring sesuai dengan jadwal berikut:</p>
        <ul>
            <li>Guru Matematika dan IPA: 20 Juni 2026</li>
            <li>Guru Bahasa dan Sosial: 21 Juni 2026</li>
            <li>Tenaga Kependidikan: 22 Juni 2026</li>
        </ul>
        <p>Peserta wajib mencetak kartu ujian melalui dashboard masing-masing.</p>'
    ],
    3 => [
        'judul' => 'Pengumuman Tahapan Asesmen Center',
        'tanggal' => '30 Juni 2026',
        'isi' => '
        <p>Peserta yang dinyatakan lolos Tes Kompetensi akan mengikuti tahapan Asesmen Center.</p>
        <p>Asesmen ini bertujuan untuk menilai aspek:</p>
        <ul>
            <li>Integritas dan etika profesional</li>
            <li>Kemampuan problem solving</li>
            <li>Komunikasi dan kolaborasi</li>
            <li>Kepemimpinan (untuk formasi tertentu)</li>
        </ul>'
    ]
];

/* ===== LOGIKA LIST / DETAIL ===== */
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>

<div class="container py-5">

    <?php if ($id && isset($pengumuman[$id])): ?>

        <!-- DETAIL PENGUMUMAN -->
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <h2 class="mb-3"><?= htmlspecialchars($pengumuman[$id]['judul']) ?></h2>
                <p class="text-muted mb-4">
                    <i class="fa fa-calendar-alt me-2"></i><?= $pengumuman[$id]['tanggal'] ?>
                </p>

                <div class="content">
                    <?= $pengumuman[$id]['isi'] ?>
                </div>

                <a href="pengumuman.php" class="btn btn-secondary mt-4">
                    ← Kembali ke Daftar Pengumuman
                </a>
            </div>
        </div>

   <?php else: ?>

<style>
.pengumuman-list {
    max-width: 900px;
    margin: 0 auto;
}

.pengumuman-item {
    display: flex;
    gap: 20px;
    background: #fff;
    border-radius: 10px;
    padding: 20px 25px;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.pengumuman-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}

.pengumuman-left {
    min-width: 90px;
    text-align: center;
    border-right: 2px solid #f0f0f0;
    padding-right: 15px;
}

.pengumuman-nomor {
    font-size: 26px;
    font-weight: bold;
    color: #0d6efd;
}

.pengumuman-tanggal {
    font-size: 13px;
    color: #6c757d;
}

.pengumuman-content h5 {
    margin-bottom: 8px;
}

.pengumuman-content p {
    color: #6c757d;
}

.pengumuman-content a {
    text-decoration: none;
}
</style>

<div class="text-center mb-5">
    <h1 class="display-5">Pengumuman Seleksi</h1>
    <p class="text-muted">
        Informasi resmi tahapan seleksi Guru dan Tenaga Kependidikan SMA Unggul Garuda
    </p>
</div>

<div class="pengumuman-list">

<?php 
$no = 1;
foreach ($pengumuman as $pid => $item): 
?>

    <div class="pengumuman-item">
        
        <!-- KIRI: NOMOR & TANGGAL -->
        <div class="pengumuman-left">
            <div class="pengumuman-nomor">
                <?= str_pad($no, 2, '0', STR_PAD_LEFT); ?>
            </div>
            <div class="pengumuman-tanggal">
                <?= $item['tanggal']; ?>
            </div>
        </div>

        <!-- KANAN: ISI -->
        <div class="pengumuman-content">
            <h5>
                <a href="pengumuman.php?id=<?= $pid ?>">
                    <?= htmlspecialchars($item['judul']); ?>
                </a>
            </h5>

            <p>
                <?= strip_tags(substr($item['isi'], 0, 160)); ?>...
            </p>

            <a href="pengumuman.php?id=<?= $pid ?>" class="text-primary fw-semibold">
                Baca selengkapnya →
            </a>
        </div>

    </div>

<?php 
$no++;
endforeach; 
?>

</div>

<?php endif; ?>


</div>

<?php include 'footer.php'; ?>
