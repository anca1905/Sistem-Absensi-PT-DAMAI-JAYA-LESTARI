
// FUNGSI TOGGLE SIDEBAR RESPONSIVE
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    // Toggle class 'show' pada kedua elemen
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

const modal = document.getElementById('modalForm');
const modalTitle = document.getElementById('modalTitle');

// Input Fields
const idInput = document.getElementById('id_karyawan');
const namaInput = document.getElementById('nama');
const nikInput = document.getElementById('nik');
const jabatanInput = document.getElementById('jabatan');
const emailInput = document.getElementById('email');

// 1. Fungsi Buka Modal
function openModal(mode) {
    // Reset Form kalau mode tambah
    if (mode === 'add') {
        modalTitle.innerText = "Tambah Karyawan Baru";
        idInput.value = "";
        namaInput.value = "";
        nikInput.value = "";
        jabatanInput.value = "";
        emailInput.value = "";
    }

    // Tampilkan Modal
    modal.classList.add('show');
}

// 2. Fungsi Edit (Terima data JSON dari tombol PHP)
function editData(data) {
    modalTitle.innerText = "Edit Data Karyawan";

    // Isi form dengan data yang dikirim
    idInput.value = data.id;
    namaInput.value = data.name;
    nikInput.value = data.nik;
    jabatanInput.value = data.jabatan;
    emailInput.value = data.email;

    // Tampilkan Modal
    modal.classList.add('show');
}

// 3. Fungsi Tutup Modal
function closeModal() {
    modal.classList.remove('show');
}

// Tutup modal jika klik di area gelap luar box
window.onclick = function (event) {
    if (event.target == modal) {
        closeModal();
    }
}