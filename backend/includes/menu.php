<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);

function estaAtiva(array $paginas, string $paginaAtual): bool
{
    return in_array($paginaAtual, $paginas, true);
}

$paginasCadastros = [
    'animais.php',
    'animal.php',
    'cadastro_animal.php',
    'editar_animal.php',
    'racas.php',
    'lotes.php',
    'parceiros.php',
    'usuarios.php',
];

$paginasAnimais = [
    'animais.php',
    'animal.php',
    'cadastro_animal.php',
    'editar_animal.php',
];

$paginasMovimentacao = [
    'pesagens.php',
    'vacinacao.php',
    'producao_leite.php',
    'estoque.php',
];

$paginasFinanceiro = [
    'plano_contas.php',
    'compras.php',
    'vendas.php',
    'lancamentos_vista.php',
    'contas_a_pagar.php',
    'fluxo_caixa.php',
];

$cadastrosAberto = estaAtiva($paginasCadastros, $paginaAtual);
$movimentacaoAberto = estaAtiva($paginasMovimentacao, $paginaAtual);
$financeiroAberto = estaAtiva($paginasFinanceiro, $paginaAtual);
$podeCadastros = usuarioTemPermissaoModulo('cadastros');
$podeMovimentacao = usuarioTemPermissaoModulo('movimentacao');
$podeEstoque = usuarioTemPermissaoModulo('estoque');
$podeFinanceiro = usuarioTemPermissaoModulo('financeiro');
?>

<aside class="sidebar">
    <nav class="menu">
        <div class="menu-title">Principal</div>

        <a href="dashboard.php" class="<?= $paginaAtual === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <a href="suporte.php" class="<?= $paginaAtual === 'suporte.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-life-ring"></i>
            <span>Suporte</span>
        </a>

        <?php if ($podeCadastros || usuarioPodeGerenciarUsuarios()): ?>
            <div class="menu-title">Cadastros</div>

            <div class="menu-item">
                <a href="#"
                   class="menu-link <?= $cadastrosAberto ? 'active-parent' : '' ?>"
                   onclick="toggleSubMenu('submenu-cadastros', this); return false;">
                    <span>
                        <i class="fa-solid fa-address-book"></i>
                        Cadastros
                    </span>
                    <span class="setinha">v</span>
                </a>

                <ul id="submenu-cadastros" class="submenu" style="display: <?= $cadastrosAberto ? 'block' : 'none' ?>;">
                    <?php if ($podeCadastros): ?>
                        <li><a href="animais.php" class="<?= estaAtiva($paginasAnimais, $paginaAtual) ? 'active' : '' ?>">Animais</a></li>
                        <li><a href="racas.php" class="<?= $paginaAtual === 'racas.php' ? 'active' : '' ?>">Raças</a></li>
                        <li><a href="lotes.php" class="<?= $paginaAtual === 'lotes.php' ? 'active' : '' ?>">Lotes</a></li>
                        <li><a href="parceiros.php" class="<?= $paginaAtual === 'parceiros.php' ? 'active' : '' ?>">Parceiros</a></li>
                    <?php endif; ?>
                    <?php if (usuarioPodeGerenciarUsuarios()): ?>
                        <li><a href="usuarios.php" class="<?= $paginaAtual === 'usuarios.php' ? 'active' : '' ?>">Usuarios</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($podeMovimentacao || $podeEstoque): ?>
            <div class="menu-title">Movimentação</div>

            <div class="menu-item">
                <a href="#"
                   class="menu-link <?= $movimentacaoAberto ? 'active-parent' : '' ?>"
                   onclick="toggleSubMenu('submenu-movimentacao', this); return false;">
                    <span>
                        <i class="fa-solid fa-truck-ramp-box"></i>
                        Movimentação
                    </span>
                    <span class="setinha">v</span>
                </a>

                <ul id="submenu-movimentacao" class="submenu" style="display: <?= $movimentacaoAberto ? 'block' : 'none' ?>;">
                    <?php if ($podeMovimentacao): ?>
                        <li><a href="pesagens.php" class="<?= $paginaAtual === 'pesagens.php' ? 'active' : '' ?>">Pesagens</a></li>
                        <li><a href="vacinacao.php" class="<?= $paginaAtual === 'vacinacao.php' ? 'active' : '' ?>">Vacinação</a></li>
                        <li><a href="producao_leite.php" class="<?= $paginaAtual === 'producao_leite.php' ? 'active' : '' ?>">Produção de leite</a></li>
                    <?php endif; ?>
                    <?php if ($podeEstoque): ?>
                        <li><a href="estoque.php" class="<?= $paginaAtual === 'estoque.php' ? 'active' : '' ?>">Estoque</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($podeFinanceiro): ?>
            <div class="menu-title">Financeiro</div>

            <div class="menu-item">
                <a href="#"
                   class="menu-link <?= $financeiroAberto ? 'active-parent' : '' ?>"
                   onclick="toggleSubMenu('submenu-financeiro', this); return false;">
                    <span>
                        <i class="fa-solid fa-dollar-sign"></i>
                        Financeiro
                    </span>
                    <span class="setinha">v</span>
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
        <?php endif; ?>

        <div class="menu-title">Conta</div>

        <a href="logout.php" class="menu-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Sair da conta</span>
        </a>
    </nav>
</aside>
