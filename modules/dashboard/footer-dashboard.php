            </div> <!-- End of content-area -->
        </div> <!-- End of main-content -->
    </div> <!-- End of dashboard-wrapper -->

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
            
            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    if (window.innerWidth <= 768) {
                        if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                            sidebar.classList.remove('active');
                        }
                    }
                });
            }
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                var alerts = document.querySelectorAll('.dashboard-alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Session timeout warning
            let lastActivity = Date.now();
            const SESSION_TIMEOUT = 3600 * 1000; // 1 hour
            const WARNING_TIME = 5 * 60 * 1000; // 5 minutes
            
            function checkSession() {
                const now = Date.now();
                const timeSinceLastActivity = now - lastActivity;
                
                if (timeSinceLastActivity > SESSION_TIMEOUT - WARNING_TIME) {
                    showSessionWarning(SESSION_TIMEOUT - timeSinceLastActivity);
                }
            }
            
            function showSessionWarning(timeLeft) {
                const minutes = Math.floor(timeLeft / 60000);
                const seconds = Math.floor((timeLeft % 60000) / 1000);
                
                if (!document.getElementById('sessionWarningModal')) {
                    const modalHtml = `
                        <div class="modal fade" id="sessionWarningModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-white">
                                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Peringatan Session</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Session Anda akan segera berakhir dalam <strong>${minutes} menit ${seconds} detik</strong>.</p>
                                        <p>Silakan simpan pekerjaan Anda atau klik "Perpanjang Session" untuk melanjutkan.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        <button type="button" class="btn btn-primary" id="extendSessionBtn">Perpanjang Session</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    const modal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
                    modal.show();
                    
                    document.getElementById('extendSessionBtn').addEventListener('click', function() {
                        lastActivity = Date.now();
                        modal.hide();
                        showToast('Session diperpanjang!', 'success');
                    });
                }
            }
            
            // Update last activity on user interaction
            ['click', 'keypress', 'scroll', 'mousemove'].forEach(event => {
                document.addEventListener(event, () => {
                    lastActivity = Date.now();
                });
            });
            
            // Check session every minute
            setInterval(checkSession, 60000);
        });
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl);
            toast.show();
            
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }
        
        // Show loading spinner
        function showLoading() {
            document.getElementById('spinner').style.display = 'block';
        }
        
        // Hide loading spinner
        function hideLoading() {
            document.getElementById('spinner').style.display = 'none';
        }
    </script>
</body>
</html>