// Toggle submenu
function toggleSubMenu(idSubmenu, elementoLink) {
    const submenu = document.getElementById(idSubmenu);
    const setinha = elementoLink.querySelector('.setinha');

    if (!submenu) return;

    if (submenu.style.display === "none" || submenu.style.display === "") {
        submenu.style.display = "block";
        setinha.classList.add("girar");
    } else {
        submenu.style.display = "none";
        setinha.classList.remove("girar");
    }
}

// Espera o DOM carregar (IMPORTANTE)
document.addEventListener('DOMContentLoaded', function () {

    const btnMenu = document.getElementById('btnMenu');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');

    if (btnMenu && sidebar && overlay) {
        btnMenu.addEventListener('click', function () {
            sidebar.classList.toggle('aberto');
            overlay.classList.toggle('ativo');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('aberto');
            overlay.classList.remove('ativo');
        });
    }

});