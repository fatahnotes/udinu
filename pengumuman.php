<?php
$pageTitle = "Pengumuman Ujian Dinas & UPKP";
$activePage = "pengumuman";

include 'header.php';

/* ===== DATA SEMENTARA (NANTI BISA GANTI DATABASE) ===== */
$pengumuman = [
    1 => [
        'judul' => 'Pembukaan Pendaftaran Ujian Dinas (UDIN) dan UPKP',
        'tanggal' => '5 Juni 2026',
        'isi' => '
        <p>Panitia Penyelenggara Ujian Dinas (UDIN) dan Ujian Penyesuaian Kenaikan Pangkat (UPKP) mengumumkan bahwa pendaftaran telah resmi dibuka.</p>
        <p>ASN yang memenuhi syarat dapat segera melakukan pendaftaran melalui portal ini dengan melengkapi dokumen yang dipersyaratkan.</p>
        <ul>
            <li>Periode pendaftaran: <strong>5 – 25 Juni 2026</strong></li>
            <li>Jenis ujian yang dibuka: UDIN dan UPKP</li>
            <li>Pendaftaran dilakukan secara online melalui akun masing-masing</li>
        </ul>
        <p>Pastikan Anda membaca pedoman dan persyaratan sebelum mendaftar.</p>'
    ],
    2 => [
        'judul' => 'Hasil Verifikasi Administrasi Pendaftaran',
        'tanggal' => '2 Juli 2026',
        'isi' => '
        <p>Panitia telah menyelesaikan proses verifikasi dokumen administrasi seluruh pendaftar.</p>
        <p>Peserta dapat melihat status kelulusan administrasi melalui dashboard masing-masing dengan ketentuan:</p>
        <ul>
            <li><strong>Memenuhi Syarat (MS)</strong> – berhak mengikuti ujian</li>
            <li><strong>Tidak Memenuhi Syarat (TMS)</strong> – dapat melihat alasan pada catatan verifikator</li>
        </ul>
        <p>Bagi peserta yang dinyatakan TMS, masa sanggah dibuka hingga 5 Juli 2026.</p>'
    ],
    3 => [
        'judul' => 'Jadwal Pelaksanaan Ujian Dinas & UPKP',
        'tanggal' => '8 Juli 2026',
        'isi' => '
        <p>Ujian akan dilaksanakan sesuai jadwal berikut:</p>
        <ul>
            <li><strong>Ujian Dinas (UDIN):</strong> 15 Juli 2026 (sesi pagi & siang)</li>
            <li><strong>Ujian Penyesuaian Kenaikan Pangkat (UPKP):</strong> 16 Juli 2026 (sesi pagi & siang)</li>
        </ul>
        <p>Peserta wajib mencetak kartu ujian melalui dashboard dan hadir 30 menit sebelum ujian dimulai. Lokasi ujian akan tertera pada kartu peserta.</p>'
    ],
    4 => [
        'judul' => 'Pengumuman Hasil Ujian',
        'tanggal' => '25 Juli 2026',
        'isi' => '
        <p>Panitia telah menyelesaikan penilaian ujian. Hasil ujian dapat dilihat melalui dashboard masing-masing peserta.</p>
        <ul>
            <li><strong>Lulus</strong> – sertifikat kelulusan dapat diunduh di dashboard</li>
            <li><strong>Tidak Lulus</strong> – peserta dapat mengulang pada periode berikutnya sesuai ketentuan</li>
        </ul>
        <p>Selamat kepada peserta yang dinyatakan lulus. Semoga pencapaian ini mendukung pengembangan karier Anda sebagai ASN yang profesional.</p>'
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
    <h1 class="display-5">Pengumuman Ujian Dinas & UPKP</h1>
    <p class="text-muted">
        Informasi resmi pendaftaran, verifikasi, jadwal, dan hasil Ujian Dinas (UDIN) serta Ujian Penyesuaian Kenaikan Pangkat (UPKP)
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