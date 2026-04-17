<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);

function estaAtiva(array $paginas, string $paginaAtual): bool
{
    return in_array($paginaAtual, $paginas, true);
}

$paginasAnimais = [
    'animais.php',
    'animal.php',
    'cadastro_animal.php',
    'editar_animal.php',
];

$paginasMovimentacao = [
    'pesagens.php',
    'vacinacao.php',
];

$paginasFinanceiro = [
    'plano_contas.php',
    'compras.php',
    'vendas.php',
    'lancamentos_vista.php',
    'contas_a_pagar.php',
    'fluxo_caixa.php',
];

$paginasConfiguracoes = [
    'usuarios.php',
];

$movimentacaoAberto = estaAtiva($paginasMovimentacao, $paginaAtual);
$financeiroAberto = estaAtiva($paginasFinanceiro, $paginaAtual);
$configuracoesAberto = estaAtiva($paginasConfiguracoes, $paginaAtual);
?>

<aside class="sidebar">
    <nav class="menu">
        <div class="menu-title">Principal</div>

        <a href="animais.php" class="<?= estaAtiva($paginasAnimais, $paginaAtual) ? 'active' : '' ?>">
            <i class="fa-solid fa-cow"></i>
            <span>Animais</span>
        </a>

        <a href="dashboard.php" class="<?= $paginaAtual === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <a href="estoque.php" class="<?= $paginaAtual === 'estoque.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Estoque</span>
        </a>

        <a href="suporte.php" class="<?= $paginaAtual === 'suporte.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-life-ring"></i>
            <span>Suporte</span>
        </a>

        <div class="menu-title">Movimentação</div>

        <div class="menu-item">
            <a href="#"
               class="menu-link <?= $movimentacaoAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-movimentacao', this); return false;">
                <span>
                    <i class="fa-solid fa-truck-ramp-box"></i>
                    Movimentação
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-movimentacao" class="submenu" style="display: <?= $movimentacaoAberto ? 'block' : 'none' ?>;">
                <li><a href="pesagens.php" class="<?= $paginaAtual === 'pesagens.php' ? 'active' : '' ?>">Pesagens</a></li>
                <li><a href="vacinacao.php" class="<?= $paginaAtual === 'vacinacao.php' ? 'active' : '' ?>">Vacinação</a></li>
            </ul>
        </div>

        <div class="menu-title">Financeiro</div>

        <div class="menu-item">
            <a href="#"
               class="menu-link <?= $financeiroAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-financeiro', this); return false;">
                <span>
                    <i class="fa-solid fa-dollar-sign"></i>
                    Financeiro
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-financeiro" class="submenu" style="display: <?= $financeiroAberto ? 'block' : 'none' ?>;">
                <li><a href="plano_contas.php" class="<?= $paginaAtual === 'plano_contas.php' ? 'active' : '' ?>">Plano de contas</a></li>
                <li><a href="compras.php" class="<?= $paginaAtual === 'compras.php' ? 'active' : '' ?>">Compras</a></li>
                <li><a href="vendas.php" class="<?= $paginaAtual === 'vendas.php' ? 'active' : '' ?>">Vendas</a></li>
                <li><a href="lancamentos_vista.php" class="<?= $paginaAtual === 'lancamentos_vista.php' ? 'active' : '' ?>">Lançamentos à vista</a></li>
                <li><a href="contas_a_pagar.php" class="<?= $paginaAtual === 'contas_a_pagar.php' ? 'active' : '' ?>">Contas a pagar</a></li>
                <li><a href="fluxo_caixa.php" class="<?= $paginaAtual === 'fluxo_caixa.php' ? 'active' : '' ?>">Fluxo de caixa</a></li>
            </ul>
        </div>

        <div class="menu-title">Configurações</div>

        <div class="menu-item">
            <a href="#"
               class="menu-link <?= $configuracoesAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-configuracoes', this); return false;">
                <span>
                    <i class="fa-solid fa-gear"></i>
                    Configurações
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-configuracoes" class="submenu" style="display: <?= $configuracoesAberto ? 'block' : 'none' ?>;">
                <li><a href="usuarios.php" class="<?= $paginaAtual === 'usuarios.php' ? 'active' : '' ?>">Usuários</a></li>
            </ul>
        </div>
    </nav>
</aside>
