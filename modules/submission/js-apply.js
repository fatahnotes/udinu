// modules/submission/js-apply.js — Auto-save + Submit (reliable v2)
(function() {
    var BASE_URL = '';
    function getBaseUrl() {
        if (BASE_URL) return BASE_URL;
        var pathParts = window.location.pathname.split('/');
        var modulesIdx = pathParts.indexOf('modules');
        BASE_URL = window.location.origin + (modulesIdx > 0 ? pathParts.slice(0, modulesIdx).join('/') + '/' : '/');
        if (!BASE_URL.endsWith('/')) BASE_URL += '/';
        return BASE_URL;
    }

    function showStatus(docId, msg, color) {
        var el = document.getElementById('uploadStatus_' + docId);
        if (!el) return;
        el.innerHTML = msg;
        el.style.color = color || '#64748b';
    }

    function markUploaded(docId, uploaded) {
        var card = document.getElementById('docCard_' + docId);
        if (!card) return;
        if (uploaded) card.classList.add('doc-uploaded');
        else card.classList.remove('doc-uploaded');
        var num = card.querySelector('.apply-doc-num');
        if (num) { num.style.background = uploaded ? '#d1fae5' : '#f1f5f9'; num.style.color = uploaded ? '#059669' : '#64748b'; }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function renderUploadDone(docId, data) {
        var zone = document.getElementById('uploadZone_' + docId);
        if (!zone) return;
        var fname = escapeHtml(data.file_name || 'Terunggah');
        var fpath = data.file_path || '#';
        var fid = data.file_id || 0;
        zone.innerHTML =
            '<div class="apply-upload-done">' +
                '<i class="fas fa-check-circle"></i>' +
                '<span class="apply-upload-filename" title="' + fname + '">' + fname + '</span>' +
                '<div class="apply-upload-actions">' +
                    '<a href="' + fpath + '" target="_blank" class="apply-btn-view" title="Lihat dokumen"><i class="fas fa-eye"></i></a>' +
                    '<button type="button" class="apply-btn-delete" title="Hapus" data-file-id="' + fid + '" data-doc-id="' + docId + '" data-file-name="' + fname + '" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"><i class="fas fa-trash"></i></button>' +
                '</div>' +
            '</div>';
        addFileInput(zone, docId);
        // CRITICAL: disable pointer events on file input so action buttons are clickable
        var newInput = zone.querySelector('.apply-file-input');
        if (newInput) {
            newInput.title = data.file_name || '';
            newInput.style.pointerEvents = 'none';
        }
    }

    function renderUploadEmpty(docId) {
        var zone = document.getElementById('uploadZone_' + docId);
        if (!zone) return;
        zone.innerHTML =
            '<div class="apply-upload-empty">' +
                '<i class="fas fa-cloud-upload-alt"></i>' +
                '<span>Pilih file...</span>' +
            '</div>';
        addFileInput(zone, docId);
        // Re-enable pointer events on file input (so user can click to upload)
        var newInput = zone.querySelector('.apply-file-input');
        if (newInput) {
            newInput.style.pointerEvents = 'auto';
        }
    }

    function addFileInput(zone, docId) {
        var card = document.getElementById('docCard_' + docId);
        var existingInput = card ? card.querySelector('.apply-file-input') : null;
        // Fallback to data attributes stored on the card
        var docCode = existingInput ? existingInput.getAttribute('data-doc-code') : (card ? card.getAttribute('data-doc-code') : '');
        var maxSize = existingInput ? existingInput.getAttribute('data-max-size') : (card ? card.getAttribute('data-max-size') : '');
        var subId = existingInput ? existingInput.getAttribute('data-submission-id') : (card ? card.getAttribute('data-submission-id') : '');
        var accept = existingInput ? existingInput.getAttribute('accept') : (card ? card.getAttribute('data-accept') : '*/*');
        var input = document.createElement('input');
        input.type = 'file'; input.name = 'document_' + docId; input.id = 'docFile_' + docId;
        input.className = 'apply-file-input';
        input.setAttribute('accept', accept);
        input.setAttribute('data-doc-id', docId);
        input.setAttribute('data-doc-code', docCode || '');
        input.setAttribute('data-max-size', maxSize || '');
        input.setAttribute('data-submission-id', subId || '');
        zone.appendChild(input);
        bindFileInput(input);
    }

    function bindFileInput(input) {
        if (!input || input._bound) return;
        input._bound = true;
        input.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var docId = this.getAttribute('data-doc-id');
            var docCode = this.getAttribute('data-doc-code');
            var maxSize = parseInt(this.getAttribute('data-max-size'));
            var submissionId = this.getAttribute('data-submission-id');
            var csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
            var zone = document.getElementById('uploadZone_' + docId);
            var docNumberInput = document.getElementById('docNumber_' + docId);
            var docDateInput = document.getElementById('docDate_' + docId);
            var docNumber = docNumberInput ? docNumberInput.value.trim() : '';
            var docDate = docDateInput ? docDateInput.value.trim() : '';

            if (!submissionId || !csrfToken) {
                showStatus(docId, 'Sesi habis, refresh halaman', '#dc2626');
                return;
            }
            if (maxSize && file.size > maxSize) {
                alert('File terlalu besar. Maks: ' + (maxSize / 1048576).toFixed(1) + 'MB');
                this.value = ''; return;
            }

            if (zone) zone.classList.add('uploading');
            showStatus(docId, '<i class="fas fa-spinner fa-spin"></i> Mengunggah...', '#d97706');

            var fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('submission_id', submissionId);
            fd.append('document_id', docId);
            fd.append('document_code', docCode);
            fd.append('document_number', docNumber);
            fd.append('document_date', docDate);
            fd.append('file', file);

            fetch(getBaseUrl() + 'modules/submission/auto-save-file.php', {
                method: 'POST', body: fd, credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (zone) zone.classList.remove('uploading');
                if (d.success) {
                    renderUploadDone(docId, d);
                    markUploaded(docId, true);
                    showStatus(docId, '<i class="fas fa-check-circle"></i> Tersimpan otomatis', '#059669');
                    if (docNumberInput && d.document_number) docNumberInput.value = d.document_number;
                    if (docDateInput && d.document_date) docDateInput.value = d.document_date;
                } else {
                    showStatus(docId, d.message || 'Gagal upload', '#dc2626');
                }
            })
            .catch(function(e) {
                if (zone) zone.classList.remove('uploading');
                showStatus(docId, 'Koneksi gagal', '#dc2626');
                console.error(e);
            });
        });
    }

    function bindMetadataInputs() {
        document.querySelectorAll('.doc-number-input, .doc-date-input').forEach(function(input) {
            if (input._metadataBound) return;
            input._metadataBound = true;
            input.addEventListener('blur', function() {
                var docId = this.id.replace('docNumber_', '').replace('docDate_', '');
                var numInput = document.getElementById('docNumber_' + docId);
                var dateInput = document.getElementById('docDate_' + docId);
                var csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
                var anyInput = document.querySelector('.apply-file-input');
                var submissionId = anyInput ? anyInput.getAttribute('data-submission-id') : '';
                if (!submissionId || !csrfToken) return;
                var fd = new FormData();
                fd.append('csrf_token', csrfToken);
                fd.append('submission_id', submissionId);
                fd.append('action', 'update_metadata');
                fd.append('document_id', docId);
                fd.append('document_number', numInput ? numInput.value.trim() : '');
                fd.append('document_date', dateInput ? dateInput.value.trim() : '');
                fetch(getBaseUrl() + 'modules/submission/auto-save-file.php', {
                    method: 'POST', body: fd, credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(function(e) { console.error('Metadata save failed:', e); });
            });
        });
    }

    // RELIABLE: sets hidden input then form.submit()
    // Uses a dedicated hidden submit button to ensure $_POST['submit_application'] is always sent
    function submitFormAs(actionName) {
        var f = document.getElementById('applyForm');
        if (!f) { console.error('submitFormAs: form #applyForm not found'); return; }
        if (actionName === 'submit_application') {
            // Set the hidden input value
            var hiddenSubmit = document.getElementById('hiddenSubmitApp');
            if (hiddenSubmit) {
                hiddenSubmit.value = '1';
            } else {
                console.error('submitFormAs: #hiddenSubmitApp not found, creating fallback');
                // Fallback: create the hidden input dynamically
                hiddenSubmit = document.createElement('input');
                hiddenSubmit.type = 'hidden';
                hiddenSubmit.name = 'submit_application';
                hiddenSubmit.id = 'hiddenSubmitApp';
                hiddenSubmit.value = '1';
                f.appendChild(hiddenSubmit);
            }
            // Ensure save_draft is NOT in the POST by disabling the save button
            var saveBtn = document.querySelector('button[name="save_draft"]');
            if (saveBtn) saveBtn.disabled = true;
            console.log('submitFormAs: submitting with submit_application=1');
            f.submit();
        }
        if (actionName === 'save_draft') {
            var saveBtn = document.querySelector('button[name="save_draft"]');
            if (saveBtn) { saveBtn.click(); return; }
            f.submit();
        }
    }

    // ============================================================
    // DELETE FILE via AJAX (reliable, no page reload needed)
    // ============================================================
    window._deleteFile = function(fileId, docId, fileName) {
        var csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
        var zone = document.getElementById('uploadZone_' + docId);
        var card = document.getElementById('docCard_' + docId);
        var submissionId = card ? card.getAttribute('data-submission-id') : '';

        if (!fileId || !submissionId || !csrfToken) {
            alert('Gagal menghapus: data tidak lengkap. Refresh halaman.');
            return;
        }

        // Show loading state
        if (zone) zone.classList.add('uploading');
        showStatus(docId, '<i class="fas fa-spinner fa-spin"></i> Menghapus...', '#d97706');

        var fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('action', 'delete_file');
        fd.append('submission_id', submissionId);
        fd.append('file_id', fileId);

        fetch(getBaseUrl() + 'modules/submission/auto-save-file.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (zone) zone.classList.remove('uploading');
            if (d.success) {
                // File deleted successfully — clear the UI
                renderUploadEmpty(docId);
                markUploaded(docId, false);
                showStatus(docId, '<i class="fas fa-check-circle"></i> File terhapus', '#059669');
            } else {
                showStatus(docId, d.message || 'Gagal menghapus file', '#dc2626');
            }
        })
        .catch(function(e) {
            if (zone) zone.classList.remove('uploading');
            showStatus(docId, 'Gagal terhubung ke server', '#dc2626');
            console.error('Delete error:', e);
        });
    };

    function init() {
        document.querySelectorAll('.apply-file-input').forEach(bindFileInput);
        bindMetadataInputs();

        var fs = document.getElementById('formation_id');
        if (fs) { fs.addEventListener('change', function() { submitFormAs('save_draft'); }); }

        var ma = document.getElementById('modalAgreeFinal');
        var cb = document.getElementById('confirmSubmitBtn');
        if (ma && cb) {
            ma.addEventListener('change', function() { cb.disabled = !this.checked; });
            cb.addEventListener('click', function() {
                if (ma.checked) {
                    var modalEl = document.getElementById('confirmSubmitModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                    submitFormAs('submit_application');
                }
            });
        }

        var deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(e) {
                var btn = e.relatedTarget;
                if (!btn) return;
                var fileName = btn.getAttribute('data-file-name') || '-';
                var fileId = btn.getAttribute('data-file-id') || '0';
                var docId = btn.getAttribute('data-doc-id') || '0';
                document.getElementById('deleteFileName').textContent = fileName;
                var confirmBtn = document.getElementById('confirmDeleteBtn');
                if (confirmBtn) {
                    confirmBtn.onclick = function() {
                        // Hide modal first, then delete via AJAX
                        var modal = bootstrap.Modal.getInstance(deleteModal);
                        if (modal) modal.hide();
                        window._deleteFile(parseInt(fileId), parseInt(docId), fileName);
                    };
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
