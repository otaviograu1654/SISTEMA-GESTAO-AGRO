<?php
require_once __DIR__ . '/auth.php';

function layoutInicio(string $tituloPagina, string $subtitulo = 'Fazenda Paraíso'): void
{
    exigirLogin();
    exigirPermissaoPaginaAtual();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - <?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <button type="button" class="mobile-menu-toggle" onclick="abrirMenuMobile()" aria-label="Abrir menu">
            <i class="fa-solid fa-bars"></i>
            <span>Menu</span>
        </button>

        <div class="titulo">
            <h2>SGA Pecuária</h2>
            <p>
                <strong class="topbar-fazenda"><?= htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="topbar-separador">·</span>
                <strong class="topbar-usuario"><?= htmlspecialchars(usuarioAtualNome(), ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="topbar-perfil"><?= htmlspecialchars(usuarioAtualPerfil(), ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </div>
    </header>

    <div class="menu-overlay" onclick="fecharMenuMobile()"></div>

    <div class="layout">
        <?php include __DIR__ . '/menu.php'; ?>
        <main class="main">
            <div class="content">
<?php
}

function layoutFim(): void
{
?>
            </div>
            <footer class="app-footer">
                <span>&copy; <?= date('Y') ?> SGA Pecuária.</span>
                <span>Todos os direitos reservados.</span>
            </footer>
        </main>
    </div>

    <script>
        function abrirMenuMobile() {
            document.body.classList.add('menu-mobile-aberto');
        }

        function fecharMenuMobile() {
            document.body.classList.remove('menu-mobile-aberto');
        }

        function toggleSubMenu(idSubmenu, elementoLink) {
            const submenu = document.getElementById(idSubmenu);
            const setinha = elementoLink.querySelector('.setinha');

            if (!submenu) return;

            const aberto = submenu.style.display === 'block';
            submenu.style.display = aberto ? 'none' : 'block';

            if (setinha) {
                setinha.classList.toggle('girar', !aberto);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.sidebar a[href]:not([href="#"])').forEach(function (link) {
                link.addEventListener('click', fecharMenuMobile);
            });

            document.querySelectorAll('.submenu').forEach(function (submenu) {
                const link = submenu.parentElement.querySelector('.menu-link');
                const setinha = link ? link.querySelector('.setinha') : null;

                if (submenu.style.display === 'block' && setinha) {
                    setinha.classList.add('girar');
                }
            });
        });
    </script>
</body>
</html>
<?php
}
