<?php
function layoutInicio(string $tituloPagina, string $subtitulo = 'Fazenda Paraíso'): void
{
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
        <div class="titulo">
            <h2>SGA Pecuária</h2>
            <p><?= htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>

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
        </main>
    </div>

    <script>
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
