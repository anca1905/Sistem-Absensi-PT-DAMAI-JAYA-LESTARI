<?php
// config/alert_helper.php
// Helper untuk menampilkan notifikasi SweetAlert2 dari PHP

/**
 * Tampilkan SweetAlert, lalu redirect ke URL
 * @param string $pesan   Isi pesan
 * @param string $url     URL tujuan redirect
 * @param string $tipe    'success', 'error', 'warning', 'info'
 * @param string $judul   Judul popup (opsional)
 */
function swalRedirect($pesan, $url, $tipe = 'success', $judul = '') {
    if ($judul === '') {
        $judul = ($tipe === 'success') ? 'Berhasil!' : (($tipe === 'error') ? 'Oops!' : 'Perhatian!');
    }
    echo "<script>
        Swal.fire({
            icon: '{$tipe}',
            title: '{$judul}',
            text: '{$pesan}',
            confirmButtonText: 'OK',
            confirmButtonColor: '#4258FF',
            allowOutsideClick: false,
            allowEscapeKey: false,
        }).then(function() {
            window.location.href = '{$url}';
        });
    </script>";
}

/**
 * Tampilkan SweetAlert tanpa redirect
 * @param string $pesan   Isi pesan
 * @param string $tipe    'success', 'error', 'warning', 'info'
 * @param string $judul   Judul popup (opsional)
 */
function swalAlert($pesan, $tipe = 'info', $judul = '') {
    if ($judul === '') {
        $judul = ($tipe === 'success') ? 'Berhasil!' : (($tipe === 'error') ? 'Oops!' : 'Perhatian!');
    }
    echo "<script>
        Swal.fire({
            icon: '{$tipe}',
            title: '{$judul}',
            text: '{$pesan}',
            confirmButtonText: 'OK',
            confirmButtonColor: '#4258FF',
        });
    </script>";
}

/**
 * Tampilkan konfirmasi SweetAlert (ganti confirm() browser)
 * Gunakan di tempat yg memerlukan konfirmasi delete dengan link href
 */
function swalConfirmLink($pesan, $url, $judul = 'Yakin?', $btnLabel = 'Ya, Hapus!') {
    // Ini helper untuk referensi — implementasi dilakukan di JS
}
?>
