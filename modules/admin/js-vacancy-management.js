// modules/admin/js-vacancy-management.js
// Manajemen Ujian - JavaScript helpers

function confirmDelete(id, title) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.addEventListener('DOMContentLoaded', function() {
    var activeSwitch = document.getElementById('isActiveSwitch');
    if (activeSwitch) {
        activeSwitch.addEventListener('change', function() {
            var label = document.getElementById('isActiveLabel');
            if (label) {
                label.textContent = this.checked 
                    ? 'Aktif - Peserta dapat mendaftar' 
                    : 'Nonaktif - Tersembunyi dari peserta';
            }
        });
    }
    
    var openDate = document.querySelector('input[name="open_date"]');
    var closeDate = document.querySelector('input[name="close_date"]');
    if (openDate && closeDate) {
        openDate.min = new Date().toISOString().split('T')[0];
        openDate.addEventListener('change', function() {
            closeDate.min = this.value;
            if (closeDate.value < this.value) closeDate.value = this.value;
        });
        closeDate.addEventListener('change', function() {
            if (openDate.value && this.value < openDate.value) {
                alert('Tanggal tutup harus setelah tanggal buka!');
                this.value = openDate.value;
            }
        });
    }
});
