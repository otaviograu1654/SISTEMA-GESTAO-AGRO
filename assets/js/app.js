
// Toggle submenu
window.toggleSubMenu = function(idSubmenu, elementoLink) {
    const submenu = document.getElementById(idSubmenu);
    const setinha = elementoLink.querySelector('.setinha');

    if (!submenu) return;

    if (submenu.style.display === "none" || submenu.style.display === "") {
        submenu.style.display = "block";
        if (setinha) setinha.classList.add("girar");
    } else {
        submenu.style.display = "none";
        if  (setinha) setinha.classList.remove("girar");
    }
};

