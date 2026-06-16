// modules/submission/js-apply.js
document.addEventListener('DOMContentLoaded', function() {
    console.log("Apply page loaded");
    
    // Ensure BASE_URL is defined
    if (typeof BASE_URL === 'undefined') {
        console.warn("BASE_URL is undefined, using fallback");
        const baseTag = document.querySelector('base');
        if (baseTag && baseTag.href) {
            BASE_URL = baseTag.href;
        } else {
            const currentPath = window.location.pathname;
            const pathParts = currentPath.split('/');
            const modulesIndex = pathParts.indexOf('modules');
            if (modulesIndex > 0) {
                BASE_URL = window.location.origin + pathParts.slice(0, modulesIndex).join('/') + '/';
            } else {
                BASE_URL = window.location.origin + '/';
            }
        }
        
        if (!BASE_URL.endsWith('/')) {
            BASE_URL += '/';
        }
    }
    
    console.log("Using BASE_URL:", BASE_URL);
    
    // File upload with auto-save - FIXED VERSION
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) {
                console.log("No file selected");
                return;
            }
            
            const docId = this.dataset.documentId || this.id.split('_')[1];
            const documentCode = this.dataset.documentCode;
            const uploadArea = document.getElementById(`uploadArea_${docId}`);
            const preview = document.getElementById(`preview_${docId}`);
            const maxSize = parseInt(this.dataset.maxSize);
            const submissionId = document.querySelector('input[name="submission_id"]')?.value;
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
            
            console.log("Uploading file:", {
                name: file.name,
                size: file.size,
                maxSize: maxSize,
                docId: docId,
                documentCode: documentCode,
                submissionId: submissionId
            });
            
            // Validate required fields
            if (!submissionId || !csrfToken) {
                console.error("Missing required fields: submissionId or csrfToken");
                showToast('Error: Data tidak lengkap. Silakan refresh halaman.', 'error');
                return;
            }
            
            // Validate file size
            if (file.size > maxSize) {
                alert(`Ukuran file terlalu besar. Maksimal: ${formatBytes(maxSize)}`);
                this.value = '';
                return;
            }
            
            // Show uploading state
            uploadArea.classList.add('uploading');
            uploadArea.style.borderColor = '#0d6efd';
            uploadArea.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            
            const placeholder = uploadArea.querySelector('.file-upload-placeholder');
            if (placeholder) {
                placeholder.innerHTML = `
                    <i class="fas fa-spinner fa-spin text-primary mb-1"></i>
                    <p class="mb-0 small text-primary">Mengunggah...</p>
                `;
            }
            
            // Create preview immediately
            preview.innerHTML = createFilePreview(file, documentCode);
            preview.classList.add('show');
            
            // Auto-save via AJAX
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('submission_id', submissionId);
            formData.append('document_id', docId);
            formData.append('document_code', documentCode);
            formData.append('file', file);
            
            const url = BASE_URL + 'modules/submission/auto-save-file.php';
            console.log("Sending auto-save request to:", url);
            
            // Add loading state to submit button
            const submitBtn = document.querySelector('button[name="submit_application"]');
            if (submitBtn && !submitBtn.classList.contains('btn-loading')) {
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
            }
            
            fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin', // Important for sending session cookies
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log("Response status:", response.status, response.statusText);
                
                // First check if response is OK
                if (!response.ok) {
                    // Try to parse error response
                    return response.text().then(text => {
                        try {
                            const data = JSON.parse(text);
                            throw new Error(data.message || `HTTP error! status: ${response.status}`);
                        } catch (e) {
                            throw new Error(`Server error: ${response.status} - ${text}`);
                        }
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log("Auto-save response:", data);
                
                if (data.success) {
                    // Update upload area
                    if (placeholder) {
                        placeholder.innerHTML = `
                            <i class="fas fa-check-circle text-success mb-1"></i>
                            <p class="mb-0 small text-success">Tersimpan</p>
                        `;
                    }
                    
                    // Update preview with view button
                    const previewItem = preview.querySelector('.preview-item');
                    if (previewItem) {
                        // Remove existing view button if exists
                        const existingViewBtn = previewItem.querySelector('.view-file-btn');
                        if (existingViewBtn) {
                            existingViewBtn.remove();
                        }
                        
                        // Add new view button
                        const viewBtn = document.createElement('a');
                        viewBtn.href = data.file_path;
                        viewBtn.target = '_blank';
                        viewBtn.className = 'btn btn-sm btn-outline-success view-file-btn me-1';
                        viewBtn.innerHTML = '<i class="fas fa-eye me-1"></i>Lihat';
                        viewBtn.title = 'Lihat Dokumen';
                        
                        const buttonGroup = previewItem.querySelector('.btn-group');
                        if (buttonGroup) {
                            buttonGroup.prepend(viewBtn);
                        } else {
                            const container = document.createElement('div');
                            container.className = 'btn-group btn-group-sm';
                            container.appendChild(viewBtn);
                            previewItem.appendChild(container);
                        }
                    }
                    
                    // Update file info section
                    const documentItem = uploadArea.closest('.document-item');
                    const fileInfo = documentItem.querySelector('.file-info');
                    
                    if (fileInfo) {
                        // Update or add view link
                        let viewLink = fileInfo.querySelector('.view-file-link');
                        if (!viewLink) {
                            viewLink = document.createElement('div');
                            viewLink.className = 'info-item view-file-link';
                            fileInfo.appendChild(viewLink);
                        }
                        
                        viewLink.innerHTML = `
                            <i class="fas fa-eye me-2 text-success"></i>
                            <small><a href="${data.file_path}" target="_blank" class="text-success">Lihat Dokumen</a></small>
                        `;
                    }
                    
                    // Update existing files section if visible
                    const existingFilesSection = document.querySelector('.existing-files');
                    if (existingFilesSection) {
                        // Add new file to existing files list
                        const fileItem = document.createElement('div');
                        fileItem.className = 'existing-file-item d-flex justify-content-between align-items-center mb-2 p-2 border rounded';
                        fileItem.innerHTML = `
                            <div class="small">
                                <i class="fas fa-file-${data.file_path.includes('.pdf') ? 'pdf' : 'image'} text-primary me-2"></i>
                                ${data.file_name}
                            </div>
                            <div class="d-flex gap-1">
                                <a href="${data.file_path}" target="_blank" class="btn btn-sm btn-outline-success" title="Lihat Dokumen">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-file-btn" 
                                        data-file-id="${data.file_id}"
                                        data-file-name="${data.file_name}"
                                        title="Hapus Dokumen">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        `;
                        
                        existingFilesSection.appendChild(fileItem);
                        
                        // Add event listener to new delete button
                        fileItem.querySelector('.delete-file-btn').addEventListener('click', function() {
                            const fileId = this.dataset.fileId;
                            const fileName = this.dataset.fileName;
                            
                            document.getElementById('fileName').textContent = fileName;
                            document.getElementById('fileToDelete').value = fileId;
                            
                            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                            modal.show();
                        });
                    }
                    
                    // Show success message
                    showToast('File berhasil disimpan', 'success');
                    
                } else {
                    throw new Error(data.message || 'Gagal menyimpan file');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Check for specific error types
                let errorMessage = error.message;
                if (errorMessage.includes('Unauthorized') || errorMessage.includes('401')) {
                    errorMessage = 'Sesi Anda telah berakhir. Silakan login ulang.';
                    // Redirect to login after delay
                    setTimeout(() => {
                        window.location.href = BASE_URL + 'modules/auth/login.php';
                    }, 2000);
                } else if (errorMessage.includes('Forbidden') || errorMessage.includes('403')) {
                    errorMessage = 'Akses ditolak. Pastikan Anda memiliki izin yang sesuai.';
                } else if (errorMessage.includes('500')) {
                    errorMessage = 'Terjadi kesalahan server. Silakan coba lagi.';
                }
                
                showToast('Gagal menyimpan file: ' + errorMessage, 'error');
                
                // Reset file input
                if (placeholder) {
                    placeholder.innerHTML = `
                        <i class="fas fa-cloud-upload-alt mb-1"></i>
                        <p class="mb-0 small">Klik untuk upload</p>
                    `;
                }
                preview.classList.remove('show');
                preview.innerHTML = '';
                input.value = '';
                uploadArea.style.borderColor = '#adb5bd';
                uploadArea.style.backgroundColor = '';
            })
            .finally(() => {
                // Remove loading state
                uploadArea.classList.remove('uploading');
                
                if (submitBtn) {
                    submitBtn.classList.remove('btn-loading');
                    submitBtn.disabled = false;
                }
            });
        });
    });
    
    // Delete file functionality - FIXED
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-file-btn')) {
            const btn = e.target.closest('.delete-file-btn');
            const fileId = btn.dataset.fileId;
            const fileName = btn.dataset.fileName;
            
            document.getElementById('fileName').textContent = fileName;
            document.getElementById('fileToDelete').value = fileId;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            modal.show();
        }
    });
    
    // Handle delete confirmation
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
        const fileId = document.getElementById('fileToDelete').value;
        const deleteFilesInput = document.getElementById('deleteFiles');
        let deleteFiles = deleteFilesInput.value ? deleteFilesInput.value.split(',').filter(id => id) : [];
        
        if (!deleteFiles.includes(fileId)) {
            deleteFiles.push(fileId);
            deleteFilesInput.value = deleteFiles.join(',');
        }
        
        // Submit form to delete file
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
        const submissionId = document.querySelector('input[name="submission_id"]')?.value;
        
        if (!csrfToken || !submissionId) {
            showToast('Error: Data tidak lengkap', 'error');
            return;
        }
        
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <input type="hidden" name="submission_id" value="${submissionId}">
            <input type="hidden" name="file_id" value="${fileId}">
            <input type="hidden" name="delete_file" value="1">
        `;
        
        document.body.appendChild(form);
        form.submit();
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert:not(.alert-danger)').forEach(alert => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                try {
                    bsAlert.close();
                } catch (e) {
                    console.log("Error closing alert:", e);
                }
            }
        });
    }, 5000);
    
    // Format file size
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    // Create file preview element
    function createFilePreview(file, documentCode) {
        const extension = file.name.split('.').pop().toLowerCase();
        const icon = getFileIcon(extension);
        const size = formatBytes(file.size);
        
        return `
            <div class="preview-item">
                <div class="preview-info">
                    <div class="preview-icon">
                        <i class="fas fa-${icon}"></i>
                    </div>
                    <div>
                        <p class="mb-0"><strong>${file.name}</strong></p>
                        <p class="text-muted mb-0">${size} • ${extension.toUpperCase()}</p>
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-danger remove-file" title="Hapus">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    }
    
    // Get appropriate icon for file type
    function getFileIcon(extension) {
        const icons = {
            'pdf': 'file-pdf',
            'jpg': 'file-image',
            'jpeg': 'file-image',
            'png': 'file-image',
            'doc': 'file-word',
            'docx': 'file-word'
        };
        return icons[extension] || 'file';
    }
    
    // Remove file from preview
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-file')) {
            const previewItem = e.target.closest('.preview-item');
            const preview = previewItem.closest('.file-preview');
            const uploadArea = preview.previousElementSibling;
            const input = uploadArea.querySelector('.file-input');
            
            // Reset input
            if (input) {
                input.value = '';
                
                // Reset upload area
                uploadArea.style.borderColor = '#adb5bd';
                uploadArea.style.backgroundColor = '';
                
                const placeholder = uploadArea.querySelector('.file-upload-placeholder');
                if (placeholder) {
                    placeholder.innerHTML = `
                        <i class="fas fa-cloud-upload-alt mb-1"></i>
                        <p class="mb-0 small">Klik untuk upload</p>
                    `;
                }
                
                // Hide preview
                preview.classList.remove('show');
                preview.innerHTML = '';
                
                showToast('File dihapus dari daftar unggahan', 'info');
            }
        }
    });
    
    // Show toast notification
    function showToast(message, type = 'info') {
        // Create toast container if not exists
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '20px';
            toastContainer.style.right = '20px';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'info')} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : (type === 'success' ? 'check-circle' : 'info-circle')} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast, { 
            delay: type === 'error' ? 5000 : 3000,
            autohide: true 
        });
        bsToast.show();
        
        // Remove toast after hiding
        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
            
            // Remove container if empty
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        });
    }
    
    // Form validation
    const form = document.getElementById('applyForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Only validate for actual submission, not for save draft
            if (!e.submitter || e.submitter.name !== 'submit_application') {
                return;
            }
            
            // Validate checkboxes
            const requiredCheckboxes = form.querySelectorAll('input[type="checkbox"][required]');
            let allChecked = true;
            
            requiredCheckboxes.forEach(cb => {
                if (!cb.checked) {
                    allChecked = false;
                    cb.closest('.form-check').classList.add('is-invalid');
                } else {
                    cb.closest('.form-check').classList.remove('is-invalid');
                }
            });
            
            if (!allChecked) {
                e.preventDefault();
                showToast('Harap setujui semua syarat dan ketentuan', 'error');
                window.scrollTo({
                    top: form.querySelector('.form-check.is-invalid').offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    }
    
    // Add confirmation for submit button
    const submitBtn = document.querySelector('button[name="submit_application"]');
    if (submitBtn && !document.querySelector('#confirmSubmitBtn')) {
        submitBtn.addEventListener('click', function(e) {
            if (this.form && this.name === 'submit_application') {
                // Check if form is valid first
                const form = this.form;
                const requiredCheckboxes = form.querySelectorAll('input[type="checkbox"][required]');
                let allChecked = true;
                
                requiredCheckboxes.forEach(cb => {
                    if (!cb.checked) {
                        allChecked = false;
                    }
                });
                
                if (!allChecked) {
                    e.preventDefault();
                    showToast('Harap setujui semua syarat dan ketentuan terlebih dahulu', 'error');
                    return;
                }
                
                // Show confirmation modal
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('confirmSubmitModal'));
                modal.show();
            }
        });
    }
    
    // Handle confirmation modal submit
    document.getElementById('confirmSubmitBtn')?.addEventListener('click', function() {
        const form = document.getElementById('applyForm');
        if (form) {
            // Create a hidden input to indicate confirmation
            const confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirmed_submit';
            confirmInput.value = '1';
            form.appendChild(confirmInput);
            
            // Submit the form
            form.submit();
        }
    });
});