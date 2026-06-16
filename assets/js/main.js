/**
 * Main JavaScript for Guru Garuda System
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize popovers
    initPopovers();
    
    // Auto-hide alerts after 5 seconds
    autoHideAlerts();
    
    // Session timeout warning
    initSessionWarning();
    
    // Form handling
    initForms();
});

// Initialize Bootstrap tooltips
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize Bootstrap popovers
function initPopovers() {
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Auto hide alerts
function autoHideAlerts() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

// Session timeout warning
function initSessionTimeout() {
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
        
        // Create warning modal
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
        
        if (!document.getElementById('sessionWarningModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('sessionWarningModal'));
            modal.show();
            
            // Extend session button
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
}

// Initialize forms
function initForms() {
    // Show loading on form submit
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                submitBtn.disabled = true;
                
                // Restore after 5 seconds if form doesn't submit
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    });
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0 position-fixed top-0 end-0 m-3" role="alert">
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
    
    // Remove after hide
    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

// Copy to clipboard
function copyToClipboard(text, successMessage = 'Tersalin ke clipboard!') {
    navigator.clipboard.writeText(text).then(() => {
        showToast(successMessage, 'success');
    }).catch(err => {
        console.error('Failed to copy: ', err);
        showToast('Gagal menyalin', 'danger');
    });
}

// Format date to Indonesian
function formatDateID(dateString) {
    const date = new Date(dateString);
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    return date.toLocaleDateString('id-ID', options);
}

// File upload preview
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}

// Character counter
function setupCharacterCounter(textareaId, counterId, maxLength) {
    const textarea = document.getElementById(textareaId);
    const counter = document.getElementById(counterId);
    
    if (textarea && counter) {
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length}/${maxLength}`;
            counter.classList.toggle('text-danger', length > maxLength);
        });
    }
}

// Password visibility toggle
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Export functions to global scope
window.GuruGaruda = {
    showToast,
    copyToClipboard,
    formatDateID,
    previewImage,
    togglePasswordVisibility,
    setupCharacterCounter
};