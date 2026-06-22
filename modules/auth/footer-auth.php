        </div> <!-- End of auth-card -->
        
        <!-- Footer Links -->
        <div class="text-center mt-4">
            <p class="text-white mb-3">
                <a href="<?php echo base_url(); ?>" class="text-white text-decoration-none me-3">
                    <i class="fas fa-home me-1"></i>Beranda
                </a>
                <a href="#" class="text-white text-decoration-none me-3">
                    <i class="fas fa-question-circle me-1"></i>Bantuan
                </a>
                <a href="#" class="text-white text-decoration-none">
                    <i class="fas fa-shield-alt me-1"></i>Keamanan
                </a>
            </p>
            <p class="text-white mb-0">
                <small>
                    &copy; <?php echo date('Y'); ?> Sistem Pendaftaran Ujian Dinas dan Ujian Penyesuaian Kenaikan Pangkat. Hak Cipta Dilindungi.<br>
                    Kementerian Pendidikan Tinggi, Sains dan Teknologi
                </small>
            </p>
        </div>
    </div> <!-- End of auth-container -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo base_url('assets/js/main.js'); ?>"></script>
    
    <?php if (!empty($customJS)) : ?>
    <script><?php echo $customJS; ?></script>
    <?php endif; ?>

    <script>
        // Remove spinner when page is loaded
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('spinner').style.display = 'none';
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                var alerts = document.querySelectorAll('.auth-alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Show loading spinner on form submit
        function showFormLoading(formId) {
            document.getElementById('spinner').style.display = 'block';
            var form = document.getElementById(formId);
            var submitBtn = form.querySelector('button[type="submit"]');
            
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                submitBtn.disabled = true;
            }
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            var toast = document.createElement('div');
            toast.className = `position-fixed top-0 end-0 m-3 alert alert-${type} alert-dismissible fade show auth-alert`;
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }
    </script>
</body>
</html>