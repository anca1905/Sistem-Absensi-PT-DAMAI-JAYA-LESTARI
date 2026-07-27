
// FUNGSI TOGGLE SIDEBAR RESPONSIVE
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    // Toggle class 'show' pada kedua elemen
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}
