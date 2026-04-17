document.querySelectorAll(".botao-dropdown").forEach(botao => {

botao.addEventListener("click", () => {

const submenu = botao.nextElementSibling;

const seta = botao.querySelector(".seta");

submenu.classList.toggle("submenu-aberto");

seta.classList.toggle("rotacionar");

});

});