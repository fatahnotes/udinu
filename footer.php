<!-- Footer Start -->
<style>
    /* Override footer styles with gradient accents */
    .footer {
        background: #1a1a2e;
    }
    .footer h3, .footer h4 {
        background: linear-gradient(135deg, #F9DA00, #FF9133);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }
    .footer .btn-md-square {
        background: linear-gradient(135deg, #F9DA00, #FF9133);
        color: #000;
        border: none;
    }
    .footer .btn-md-square:hover {
        background: linear-gradient(135deg, #FF9133, #F9DA00);
        color: #000;
    }
    .footer a:not(.btn) {
        color: #FF9133;
        transition: 0.3s;
    }
    .footer a:not(.btn):hover {
        color: #F9DA00;
    }
    .footer .btn-primary {
        background: linear-gradient(135deg, #F9DA00, #FF9133);
        border: none;
        color: #000 !important;
        font-weight: 600;
    }
    .footer .btn-primary:hover {
        background: linear-gradient(135deg, #FF9133, #F9DA00);
    }
    .copyright {
        background: #16213e;
    }
    .copyright a {
        color: #FF9133 !important;
    }
    .back-to-top {
        background: linear-gradient(135deg, #F9DA00, #FF9133) !important;
        color: #000 !important;
        border: none;
    }
</style>

<div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-xl-9">
                <div class="mb-5">
                    <div class="row g-4">
                        <!-- Brand & Deskripsi -->
                        <div class="col-md-6 col-lg-6 col-xl-5">
                            <div class="footer-item">
                                <a href="index.php" class="p-0">
                                    <h3 class="text-white"><i class="fab fa-slack me-3"></i> UDIN & UPKP</h3>
                                </a>
                                <p class="text-white mb-4">
                                    Portal resmi pendaftaran Ujian Dinas (UDIN) dan Ujian Penyesuaian Kenaikan Pangkat (UPKP)
                                    bagi Aparatur Sipil Negara.
                                </p>
                                <div class="footer-btn d-flex">
                                    <a class="btn btn-md-square rounded-circle me-3" href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                                    <a class="btn btn-md-square rounded-circle me-3" href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                                    <a class="btn btn-md-square rounded-circle me-3" href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                                    <a class="btn btn-md-square rounded-circle me-0" href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                                </div>
                            </div>
                        </div>

                        <!-- Tautan Penting -->
                        <div class="col-md-6 col-lg-6 col-xl-3">
                            <div class="footer-item">
                                <h4 class="text-white mb-4">Tautan Penting</h4>
                                <a href="index.php"><i class="fas fa-angle-right me-2"></i> Beranda</a>
                                <a href="pengumuman.php"><i class="fas fa-angle-right me-2"></i> Pengumuman</a>
                                <a href="alur.php"><i class="fas fa-angle-right me-2"></i> Alur Pendaftaran</a>
                            </div>
                        </div>

                        <!-- Kontak -->
                        <div class="col-md-6 col-lg-6 col-xl-4">
                            <div class="footer-item">
                                <h4 class="mb-4 text-white">Kantor Penyelenggara</h4>
                                <p class="text-white mb-0"><i class="fas fa-map-marker-alt me-2"></i> Gedung D Lt. 8 Kemdiktisaintek</p>
                                <p class="text-white mb-0"><i class="fas fa-map-marker-alt me-2"></i> Jl. Jenderal Sudirman, Senayan</p>
                                <p class="text-white mb-1"><i class="fas fa-phone-alt me-2"></i> +62 21 1500-123</p>
                                <p class="text-white mb-0"><i class="fas fa-envelope me-2"></i> info@kemdiktisaintek.go.id</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Berlangganan Info -->
            <div class="col-xl-3">
                <div class="footer-item">
                    <h4 class="text-white mb-4">Berlangganan Info</h4>
                    <p class="text-white mb-3">Dapatkan update terbaru jadwal ujian, pengumuman, dan informasi penting lainnya.</p>
                    <div class="position-relative rounded-pill mb-4">
                        <input class="form-control rounded-pill w-100 py-3 ps-4 pe-5" type="email" placeholder="Email Anda" aria-label="Email Anda">
                        <button type="button" class="btn btn-primary rounded-pill position-absolute top-0 end-0 py-2 mt-2 me-2">Daftar</button>
                    </div>
                    <div class="d-flex flex-shrink-0">
                        <div class="footer-btn">
                            <a href="tel:+62211500123" class="btn btn-lg-square rounded-circle position-relative wow tada" data-wow-delay=".9s" aria-label="Pusat Bantuan">
                                <i class="fa fa-phone-alt fa-2x"></i>
                                <div class="position-absolute" style="top: 2px; right: 12px;">
                                    <span><i class="fa fa-comment-dots text-secondary"></i></span>
                                </div>
                            </a>
                        </div>
                        <div class="d-flex flex-column ms-3 flex-shrink-0">
                            <span class="text-white">Pusat Bantuan</span>
                            <a href="tel:+62211500123"><span class="text-white">+62 21 1500-123</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Footer End -->

<!-- Copyright Start -->
<div class="container-fluid copyright py-4">
    <div class="container text-center">
        <span class="text-white"><a href="#" class="border-bottom text-white">Kemdiktisaintek</a>, © 2026. Hak Cipta Dilindungi.</span>
    </div>
</div>
<!-- Copyright End -->

<!-- Back to Top -->
<a href="#" class="btn btn-primary btn-lg-square rounded-circle back-to-top" aria-label="Kembali ke atas"><i class="fa fa-arrow-up"></i></a>

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/wow/wow.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/counterup/counterup.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>

<!-- Template Javascript -->
<script src="js/main.js"></script>

<?php if (!empty($customJS)) : ?>
<!-- Page Specific JS -->
<script><?php echo $customJS; ?></script>
<?php endif; ?>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
    var Tawk_API = Tawk_API || {},
        Tawk_LoadStart = new Date();
    (function() {
        var s1 = document.createElement("script"),
            s0 = document.getElementsByTagName("script")[0];
        s1.async = true;
        s1.src = 'https://embed.tawk.to/68679b70e92a60190f98eb78/1ivabecjm';
        s1.charset = 'UTF-8';
        s1.setAttribute('crossorigin', '*');
        s0.parentNode.insertBefore(s1, s0);
    })();
</script>
<!--End of Tawk.to Script-->
</body>
</html>