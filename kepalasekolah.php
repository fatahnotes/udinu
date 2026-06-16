<?php
$pageTitle = "Seleksi Kepala Sekolah";
$activePage = "kepalasekolah";
include 'header.php';
?>

<style>
.container-doc {
    max-width: 1150px;
    margin: auto;
}

.doc-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 40px;
    margin-bottom: 35px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.05);
}

.doc-card h2 {
    font-weight: 700;
    margin-bottom: 25px;
}

.doc-card h3 {
    color: #0d6efd;
    margin-top: 30px;
    margin-bottom: 15px;
}

.doc-card ol li, 
.doc-card ul li {
    margin-bottom: 10px;
}

.doc-title {
    font-weight: 700;
}

.table-custom th {
    background: #f1f5ff;
    font-weight: 600;
}

.badge-section {
    background: #e8f0ff;
    color: #0d6efd;
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 15px;
}

.hero-page {
    background: linear-gradient(135deg, #ffffff, #8293af);
    padding: 90px 0;
    color: #2b0505;
}

.hero-page h1 {
    font-weight: 800;
}

</style>

<!-- HERO -->
<section class="hero-page text-center">
    <div class="container">
        <h1 class="display-5">Seleksi Kepala Sekolah SMA Unggul Garuda</h1>
        <p class="lead mt-3">
            Pedoman resmi persyaratan, tahapan, metode seleksi, dan sistem penilaian
            calon Kepala Sekolah Program SMA Unggul Garuda
        </p>
    </div>
</section>

<!-- CONTENT -->
<section class="py-5">
    <div class="container-doc">

        <!-- A -->
        <div class="doc-card">
            <span class="badge-section">A. Persyaratan Umum</span>
            <ol>
                <li>Guru Pegawai Negeri Sipil (PNS)</li>
                <li>Usia maksimal 50 tahun</li>
                <li>Memiliki sertifikat pendidik</li>
                <li>Diutamakan S2/S3</li>
                <li>Sertifikat kepemimpinan sekolah</li>
                <li>Pengalaman memimpin minimal 3 tahun</li>
                <li>Menguasai manajemen dan kurikulum</li>
                <li>Mampu menyusun strategi peningkatan mutu</li>
                <li>Sehat jasmani dan rohani</li>
                <li>Tidak pernah diberhentikan tidak hormat</li>
                <li>Netral politik</li>
                <li>Bersedia ditempatkan di seluruh Indonesia</li>
                <li>Bebas NAPZA</li>
            </ol>
        </div>

        <!-- B -->
        <div class="doc-card">
            <span class="badge-section">B. Persyaratan Khusus</span>
            <ul>
                <li>Bahasa Inggris aktif</li>
                <li>Bersedia tinggal di sekolah berasrama</li>
            </ul>

            <h3>Dokumen Wajib</h3>
            <ol>
                <li>Surat rekomendasi PPK</li>
                <li>CV pengalaman kepemimpinan</li>
                <li>SK pangkat dan jabatan</li>
                <li>KTP & Akta</li>
                <li>Ijazah S2/S3</li>
                <li>Sertifikat kepala sekolah (jika ada)</li>
                <li>Bukti pengalaman memimpin</li>
                <li>Sertifikat keahlian tambahan</li>
            </ol>
        </div>

        <!-- Tahapan -->
        <div class="doc-card">
            <span class="badge-section">Tahapan Seleksi</span>

            <table class="table table-bordered table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kegiatan</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Pengumuman formasi</td><td>Nov 2025</td></tr>
                    <tr><td>2</td><td>Pendaftaran</td><td>Nov 2025</td></tr>
                    <tr><td>3</td><td>Seleksi administrasi</td><td>Des 2025</td></tr>
                    <tr><td>4</td><td>Ujian tulis (TPA, bidang, pedagogi, Inggris)</td><td>Feb 2026</td></tr>
                    <tr><td>5</td><td>Praktik mengajar</td><td>Mar 2026</td></tr>
                    <tr><td>6</td><td>Wawancara</td><td>Mar 2026</td></tr>
                    <tr><td>7</td><td>Pengolahan nilai</td><td>Apr 2026</td></tr>
                    <tr><td>8</td><td>Pengumuman hasil</td><td>Apr 2026</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Bobot -->
        <div class="doc-card">
            <span class="badge-section">Komponen & Bobot Penilaian</span>

            <table class="table table-striped table-bordered table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Komponen</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Tes bidang studi</td><td>30%</td></tr>
                    <tr><td>2</td><td>Tes pedagogis</td><td>20%</td></tr>
                    <tr><td>3</td><td>Microteaching</td><td>20%</td></tr>
                    <tr><td>4</td><td>Wawancara</td><td>20%</td></tr>
                    <tr><td>5</td><td>Portofolio</td><td>10%</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Penentuan -->
        <div class="doc-card">
            <span class="badge-section">Penentuan Kelulusan</span>
            <p>
                Kelulusan ditentukan berdasarkan nilai komposit seluruh komponen seleksi.
                Apabila terjadi nilai sama, prioritas:
            </p>
            <ol>
                <li>Nilai Assessment Center tertinggi</li>
                <li>Nilai wawancara tertinggi</li>
                <li>Nilai psikotes tertinggi</li>
                <li>Pengalaman kepemimpinan terlama</li>
            </ol>
        </div>

    </div>
</section>

<?php include 'footer.php'; ?>
