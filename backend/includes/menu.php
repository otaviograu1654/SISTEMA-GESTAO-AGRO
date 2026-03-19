<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <nav class="menu">
        <div class="menu-title">Principal</div>
        <a href="dashboard.php" class="<?= $paginaAtual === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="animais.php" class="<?= $paginaAtual === 'animais.php' ? 'active' : '' ?>">Animais</a>
        <a href="cadastro_animal.php" class="<?= $paginaAtual === 'cadastro_animal.php' ? 'active' : '' ?>">Cadastrar animal</a>

        <div class="menu-title">Módulos</div>
        <a href="#" class="disabled">Pesagens</a>

        <div class="menu-item">
            <a href="#" class="menu-link" onclick="toggleSubMenu('submenu-financeiro', this); return false;" style="display: flex; justify-content: space-between; align-items: center;">
                Financeiro
                <span class="setinha">▾</span>
            </a>
            <ul id="submenu-financeiro" class="submenu" style="display: block;">
                <li>
                    <a href="contas.php" class="<?= $paginaAtual === 'contas.php' ? 'active' : '' ?>" style="padding-left: 40px; font-size: 14px; opacity: 0.9;">
                        Contas a Pagar
                    </a>
                </li>
            </ul>
        </div>

        <a href="#" class="disabled">Relatórios</a>
    </nav>
</aside>
