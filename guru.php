<?php
$pageTitle = "Seleksi Guru";
$activePage = "guru";
include 'header.php';
?>

<style>
.container-doc {
    max-width: 1200px;
    margin: auto;
}

.doc-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 40px;
    margin-bottom: 35px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.05);
}

.doc-card h2 {
    font-weight: 800;
    margin-bottom: 20px;
}

.doc-card h3 {
    color: #0d6efd;
    margin-top: 30px;
    margin-bottom: 15px;
}

.doc-card ol li,
.doc-card ul li {
    margin-bottom: 8px;
    line-height: 1.7;
}

.badge-section {
    background: linear-gradient(135deg, #e7f1ff, #f4f8ff);
    color: #0d6efd;
    padding: 8px 18px;
    border-radius: 30px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 15px;
}

.hero-page {
    background: linear-gradient(135deg, #0d6efd, #002d72);
    padding: 90px 0;
    color: #fff;
}

.hero-page h1 {
    font-weight: 800;
}

.table-custom th {
    background: #f0f5ff;
}
</style>

<!-- HERO -->
<section class="hero-page text-center">
    <div class="container">
        <h1 class="display-5">Seleksi Guru SMA Unggul Garuda</h1>
        <p class="lead mt-3">
            Pedoman resmi seleksi, persyaratan, tahapan, dan sistem penilaian calon guru
            dalam program strategis nasional SMA Unggul Garuda.
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
            <li>Warga Negara Indonesia (WNI)</li>
            <li>Bertaqwa kepada Tuhan Yang Maha Esa</li>
            <li>Memiliki Sertifikat Pendidik (PPG)</li>
            <li>Pendidikan minimal S1 linier</li>
            <li>Usia maksimal 35 tahun (reguler), 45 tahun (mutasi)</li>
            <li>Sehat jasmani dan rohani</li>
            <li>Tidak pernah dipidana</li>
            <li>Tidak pernah diberhentikan tidak hormat</li>
            <li>Tidak terikat kontrak tetap instansi lain</li>
            <li>Netral politik</li>
            <li>Bersedia ditempatkan seluruh Indonesia</li>
            <li>Bebas NAPZA</li>
        </ol>
    </div>

    <!-- B -->
    <div class="doc-card">
        <span class="badge-section">B. Persyaratan Khusus</span>
        <ol>
            <li>Sertifikat pendidik</li>
            <li>IPK minimal 3,25</li>
            <li>Bersedia tinggal di asrama</li>
            <li>Mampu pembelajaran berbasis teknologi</li>
            <li>Sertifikat kompetensi tambahan</li>
            <li>Mampu mengimplementasikan STEAM</li>
            <li>IELTS minimal 6.5</li>
        </ol>
    </div>

    <!-- C -->
    <div class="doc-card">
        <span class="badge-section">C. Kriteria Guru</span>
        <ul>
            <li>Penguasaan konten bidang ilmu tinggi</li>
            <li>Kompetensi pedagogik kuat</li>
            <li>Mampu kolaborasi lintas budaya</li>
            <li>Diutamakan S2</li>
            <li>Berjiwa kepemimpinan</li>
            <li>Berjiwa kewirausahaan</li>
            <li>Bahasa Inggris aktif</li>
        </ul>
    </div>

    <!-- D -->
    <div class="doc-card">
        <span class="badge-section">D. Formasi Kebutuhan Guru</span>
        <div class="table-responsive">
            <table class="table table-bordered table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Mata Pelajaran</th>
                        <th>2025</th>
                        <th>2026</th>
                        <th>2027</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Bahasa Indonesia</td><td>1</td><td>1</td><td>1</td></tr>
                    <tr><td>2</td><td>Bahasa Inggris</td><td>2</td><td>2</td><td>2</td></tr>
                    <tr><td>3</td><td>Matematika</td><td>2</td><td>2</td><td>1</td></tr>
                    <tr><td>4</td><td>Fisika</td><td>2</td><td>1</td><td>1</td></tr>
                    <tr><td>5</td><td>Kimia</td><td>2</td><td>1</td><td>1</td></tr>
                    <tr><td>6</td><td>Biologi</td><td>2</td><td>1</td><td>1</td></tr>
                </tbody>
            </table>
            <small class="text-muted">*Disesuaikan bertahap berdasarkan kebutuhan sekolah.</small>
        </div>
    </div>

    <!-- Jadwal -->
    <div class="doc-card">
        <span class="badge-section">Tahapan & Jadwal Seleksi</span>
        <table class="table table-bordered">
            <thead><tr><th>No</th><th>Kegiatan</th><th>Waktu</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>Pengumuman Formasi</td><td>Jul–Agu 2025</td></tr>
                <tr><td>2</td><td>Pendaftaran</td><td>Sep 2025</td></tr>
                <tr><td>3</td><td>Seleksi Administrasi</td><td>Okt 2025</td></tr>
                <tr><td>4</td><td>Tes Tulis & TPA</td><td>Nov 2025</td></tr>
                <tr><td>5</td><td>Microteaching</td><td>Nov 2025</td></tr>
                <tr><td>6</td><td>Wawancara</td><td>Nov 2025</td></tr>
                <tr><td>7</td><td>Pengolahan Nilai</td><td>Des 2025</td></tr>
                <tr><td>8</td><td>Pengumuman</td><td>Jan 2026</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Bobot -->
    <div class="doc-card">
        <span class="badge-section">Bobot Penilaian</span>
        <table class="table table-striped table-bordered">
            <thead><tr><th>No</th><th>Komponen</th><th>Bobot</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>Konten Bidang Studi</td><td>30%</td></tr>
                <tr><td>2</td><td>Pedagogik</td><td>20%</td></tr>
                <tr><td>3</td><td>Microteaching</td><td>20%</td></tr>
                <tr><td>4</td><td>Wawancara</td><td>20%</td></tr>
                <tr><td>5</td><td>Portofolio</td><td>10%</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Penentuan -->
    <div class="doc-card">
        <span class="badge-section">Penentuan Kelulusan</span>
        <p>Kelulusan berdasarkan ranking nilai komposit. Jika nilai sama:</p>
        <ol>
            <li>Nilai konten & pedagogik tertinggi</li>
            <li>Nilai microteaching</li>
            <li>Nilai wawancara</li>
            <li>Nilai psikotes & Inggris</li>
            <li>Usia tertua</li>
        </ol>
    </div>

</div>
</section>

<?php include 'footer.php'; ?>
