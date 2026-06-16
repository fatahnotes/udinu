// modules/admin/js-vacancy-management.js

// Data untuk formasi
const formasiData = {
    'KPS': {
        label: 'Provinsi',
        type: 'provinsi',
        options: [
            'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Jambi', 'Sumatera Selatan', 
            'Bengkulu', 'Lampung', 'Kepulauan Bangka Belitung', 'Kepulauan Riau', 
            'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 
            'Banten', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 
            'Kalimantan Barat', 'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 
            'Kalimantan Utara', 'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan', 
            'Sulawesi Tenggara', 'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara', 
            'Papua Barat', 'Papua'
        ]
    },
    'GP': {
        label: 'Mata Pelajaran',
        type: 'mata_pelajaran',
        options: [
            'Bahasa Indonesia', 'Bahasa Inggris', 'Bahasa Jerman', 'Bahasa Mandarin', 
            'Bahasa Jepang', 'Bahasa Arab', 'Sejarah', 'Sosiologi', 'Ekonomi', 
            'Geografi/Lingkungan dan Masyarakat', 'Biologi', 'Fisika/Desain Teknologi', 
            'Kimia', 'Matematika', 'Seni', 'Informatika/Komputer Sains', 
            'Pendidikan Jasmani Olahraga dan Kesehatan', 'Pendidikan Agama Islam', 
            'Pendidikan Agama Kristen Protestan', 'Pendidikan Agama Katolik', 
            'Pendidikan Agama Hindu', 'Pendidikan Agama Budha', 'Pendidikan Agama Khonghucu',
            'Bimbingan Konseling', 'Pendidikan Pancasila', 'Bahasa Daerah'
        ]
    },
    'TKD': {
        label: 'Jabatan',
        type: 'tenaga_kependidikan',
        options: [
            'Kepala Tata Usaha', 'Tenaga Administrasi', 'Pustakawan', 'Laboran Fisika', 
            'Laboran Kimia', 'Laboran Biologi', 'Laboran Komputer', 'Laboran Bahasa', 
            'Dokter', 'Perawat', 'Ahli Gizi', 'Konselor (BK)', 'Psikolog', 
            'Tenaga Konsumsi (Kantin & Katering)', 'Tenaga Keamanan', 'Tenaga Kebersihan & Pertamanan'
        ]
    }
};

// Delete confirmation
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteTitle').textContent = title;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    });
});

// Fungsi untuk menambahkan baris formasi
function addFormasiRow(data = null) {
    const vacancyTypeSelect = document.getElementById('vacancy_type_id');
    const selectedOption = vacancyTypeSelect.options[vacancyTypeSelect.selectedIndex];
    const typeCode = selectedOption.getAttribute('data-type-code');
    
    if (!typeCode || !formasiData[typeCode]) return;
    
    const container = document.getElementById('formasi-container');
    const rowId = 'formasi-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    
    const formasi = formasiData[typeCode];
    const isEdit = data !== null;
    
    const row = document.createElement('div');
    row.className = 'formasi-row';
    
    // Buat options untuk select
    let optionsHtml = '<option value="">Pilih ' + formasi.label + '</option>';
    formasi.options.forEach(opt => {
        const selected = (isEdit && data.formation_name === opt) ? 'selected' : '';
        optionsHtml += '<option value="' + opt + '" ' + selected + '>' + opt + '</option>';
    });
    
    row.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">${formasi.label} <span class="text-danger">*</span></label>
                    <select class="form-select formasi-select" name="formations[${rowId}][name]" required>
                        ${optionsHtml}
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="formations[${rowId}][jumlah]" 
                           min="1" value="${isEdit ? data.jumlah : ''}" required>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-danger" onclick="removeFormasiRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <input type="hidden" name="formations[${rowId}][type]" value="${formasi.type}">
    `;
    
    container.appendChild(row);
}

// Fungsi untuk menghapus baris formasi
function removeFormasiRow(button) {
    const row = button.closest('.formasi-row');
    if (row) {
        row.remove();
    }
}

// Fungsi untuk memperbarui tampilan formasi berdasarkan jenis lowongan
function updateFormasiDisplay() {
    const vacancyTypeSelect = document.getElementById('vacancy_type_id');
    const selectedOption = vacancyTypeSelect.options[vacancyTypeSelect.selectedIndex];
    const typeCode = selectedOption.getAttribute('data-type-code');
    const formasiSection = document.getElementById('formasi-section');
    const container = document.getElementById('formasi-container');
    
    if (typeCode && formasiData[typeCode]) {
        formasiSection.style.display = 'block';
        container.innerHTML = '';
        
        // Tambahkan baris formasi yang sudah ada (untuk edit mode)
        if (window.existingFormations && window.existingFormations.length > 0) {
            window.existingFormations.forEach(formation => {
                addFormasiRow(formation);
            });
        } else {
            // Tambahkan satu baris kosong untuk mode add
            addFormasiRow();
        }
    } else {
        formasiSection.style.display = 'none';
        container.innerHTML = '';
    }
}

// Event listener untuk jenis lowongan
document.getElementById('vacancy_type_id')?.addEventListener('change', function() {
    updateFormasiDisplay();
});

// Event listener untuk tombol tambah formasi
document.getElementById('add-formasi-btn')?.addEventListener('click', function() {
    addFormasiRow();
});

// Date validation
document.addEventListener('DOMContentLoaded', function() {
    const openDateInput = document.getElementById('open_date');
    const closeDateInput = document.getElementById('close_date');
    
    if (openDateInput && closeDateInput) {
        openDateInput.addEventListener('change', function() {
            closeDateInput.min = this.value;
            if (closeDateInput.value && closeDateInput.value < this.value) {
                closeDateInput.value = this.value;
            }
        });
        
        closeDateInput.addEventListener('change', function() {
            if (this.value < openDateInput.value) {
                alert('Tanggal tutup harus setelah tanggal buka');
                this.value = openDateInput.value;
            }
        });
    }
    
    // Set min date to today for open_date
    if (openDateInput) {
        openDateInput.min = new Date().toISOString().split('T')[0];
    }
    
    // Inisialisasi formasi jika jenis lowongan sudah dipilih
    const vacancyTypeSelect = document.getElementById('vacancy_type_id');
    if (vacancyTypeSelect && vacancyTypeSelect.value) {
        // Tunggu sebentar untuk memastikan DOM sudah siap
        setTimeout(() => {
            updateFormasiDisplay();
        }, 100);
    }
});

// Validasi form sebelum submit
const vacancyForm = document.getElementById('vacancyForm');
if (vacancyForm) {
    vacancyForm.addEventListener('submit', function(e) {
        const vacancyTypeSelect = document.getElementById('vacancy_type_id');
        const selectedOption = vacancyTypeSelect.options[vacancyTypeSelect.selectedIndex];
        const typeCode = selectedOption.getAttribute('data-type-code');
        const formasiSection = document.getElementById('formasi-section');
        
        // Jika jenis lowongan membutuhkan formasi, pastikan ada minimal satu formasi
        if (typeCode && formasiData[typeCode] && formasiSection.style.display === 'block') {
            const formasiRows = document.querySelectorAll('.formasi-row');
            if (formasiRows.length === 0) {
                e.preventDefault();
                alert('Silakan tambahkan minimal satu formasi untuk jenis lowongan ini.');
                return;
            }
            
            // Validasi setiap formasi
            let isValid = true;
            formasiRows.forEach(row => {
                const select = row.querySelector('.formasi-select');
                const jumlah = row.querySelector('input[name$="[jumlah]"]');
                
                if (!select.value || !jumlah.value || parseInt(jumlah.value) < 1) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Silakan lengkapi semua formasi (pilih nama dan isi jumlah).');
            }
        }
    });
}