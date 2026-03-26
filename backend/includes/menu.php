<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);

function estaAtiva(array $paginas, string $paginaAtual): bool
{
    return in_array($paginaAtual, $paginas, true);
}

$paginasCadastros = [
    'animais.php',
    'cadastro_animal.php',
    'produtos.php',
    'tipo_animal.php',
    'raca.php',
    'usuarios.php',
    'plano_contas.php',
    'pastos.php',
    'lotes.php',
    'clientes_fornecedores.php',
    'vacinas.php'
];

$paginasMovimentacao = [
    'compras.php',
    'producao.php',
    'vendas.php',
    'lancamentos_vista.php',
    'reproducao.php',
    'alimentacao.php'
];

$paginasFinanceiro = [
    'contas.php',
    'contas_receber.php',
    'fluxo_caixa.php'
];

$paginasRelatorios = [
    'relatorio_animais.php',
    'relatorio_producao.php',
    'relatorio_estoque.php',
    'relatorio_pesagem.php',
    'dre.php'
];

$cadastrosAberto    = estaAtiva($paginasCadastros, $paginaAtual);
$movimentacaoAberto = estaAtiva($paginasMovimentacao, $paginaAtual);
$financeiroAberto   = estaAtiva($paginasFinanceiro, $paginaAtual);
$relatoriosAberto   = estaAtiva($paginasRelatorios, $paginaAtual);
?>

<aside class="sidebar">
    <nav class="menu">

        <div class="menu-title">Principal</div>

        <a href="dashboard.php" class="<?= $paginaAtual === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <div class="menu-title">Módulos</div>

        <!-- CADASTROS -->
        <div class="menu-item">
            <a href="#" class="menu-link <?= $cadastrosAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-cadastros', this); return false;">
                <span>
                    <i class="fa-solid fa-address-book"></i>
                    Cadastros
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-cadastros" class="submenu" style="display: <?= $cadastrosAberto ? 'block' : 'none' ?>;">
                <li><a href="animal.php" class="<?= $paginaAtual === 'animal.php' ? 'active' : '' ?>">Animais</a></li>
                <li><a href="produtos.php" class="<?= $paginaAtual === 'produtos.php' ? 'active' : '' ?>">Produtos</a></li>
                <li><a href="tipo_animal.php" class="<?= $paginaAtual === 'tipo_animal.php' ? 'active' : '' ?>">Tipo de Animal</a></li>
                <li><a href="raca.php" class="<?= $paginaAtual === 'raca.php' ? 'active' : '' ?>">Raça</a></li>
                <li><a href="usuarios.php" class="<?= $paginaAtual === 'usuarios.php' ? 'active' : '' ?>">Usuários</a></li>
                <li><a href="plano_contas.php" class="<?= $paginaAtual === 'plano_contas.php' ? 'active' : '' ?>">Plano de Contas</a></li>
                <li><a href="pastos.php" class="<?= $paginaAtual === 'pastos.php' ? 'active' : '' ?>">Pastos</a></li>
                <li><a href="lotes.php" class="<?= $paginaAtual === 'lotes.php' ? 'active' : '' ?>">Lotes</a></li>
                <li><a href="clientes_fornecedores.php" class="<?= $paginaAtual === 'clientes_fornecedores.php' ? 'active' : '' ?>">Clientes / Fornecedores</a></li>
                <li><a href="vacinas.php" class="<?= $paginaAtual === 'vacinas.php' ? 'active' : '' ?>">Vacinas</a></li>
                <li><a href="cadastro_animal.php" class="<?= $paginaAtual === 'cadastro_animal.php' ? 'active' : '' ?>">Cadastrar animal</a></li>
            </ul>
        </div>

        <!-- MOVIMENTAÇÃO -->
        <div class="menu-item">
            <a href="#" class="menu-link <?= $movimentacaoAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-movimentacao', this); return false;">
                <span>
                    <i class="fa-solid fa-truck-ramp-box"></i>
                    Movimentação
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-movimentacao" class="submenu" style="display: <?= $movimentacaoAberto ? 'block' : 'none' ?>;">
                <li><a href="compras.php" class="<?= $paginaAtual === 'compras.php' ? 'active' : '' ?>">Compras</a></li>
                <li><a href="producao.php" class="<?= $paginaAtual === 'producao.php' ? 'active' : '' ?>">Produção</a></li>
                <li><a href="vendas.php" class="<?= $paginaAtual === 'vendas.php' ? 'active' : '' ?>">Vendas</a></li>
                <li><a href="lancamentos_vista.php" class="<?= $paginaAtual === 'lancamentos_vista.php' ? 'active' : '' ?>">Lançamentos à Vista</a></li>
                <li><a href="reproducao.php" class="<?= $paginaAtual === 'reproducao.php' ? 'active' : '' ?>">Reprodução</a></li>
                <li><a href="alimentacao.php" class="<?= $paginaAtual === 'alimentacao.php' ? 'active' : '' ?>">Alimentação</a></li>
            </ul>
        </div>

        <!-- FINANCEIRO -->
        <div class="menu-item">
            <a href="#" class="menu-link <?= $financeiroAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-financeiro', this); return false;">
                <span>
                    <i class="fa-solid fa-dollar-sign"></i>
                    Financeiro
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-financeiro" class="submenu" style="display: <?= $financeiroAberto ? 'block' : 'none' ?>;">
                <li><a href="contas.php" class="<?= $paginaAtual === 'contas.php' ? 'active' : '' ?>">Contas a Pagar</a></li>
                <li><a href="contas_receber.php" class="<?= $paginaAtual === 'contas_receber.php' ? 'active' : '' ?>">Contas a Receber</a></li>
                <li><a href="fluxo_caixa.php" class="<?= $paginaAtual === 'fluxo_caixa.php' ? 'active' : '' ?>">Fluxo de Caixa</a></li>
            </ul>
        </div>

        <!-- RELATÓRIOS -->
        <div class="menu-item">
            <a href="#" class="menu-link <?= $relatoriosAberto ? 'active-parent' : '' ?>"
               onclick="toggleSubMenu('submenu-relatorios', this); return false;">
                <span>
                    <i class="fa-solid fa-file-contract"></i>
                    Relatórios
                </span>
                <span class="setinha">▾</span>
            </a>

            <ul id="submenu-relatorios" class="submenu" style="display: <?= $relatoriosAberto ? 'block' : 'none' ?>;">
                <li><a href="relatorio_animais.php" class="<?= $paginaAtual === 'relatorio_animais.php' ? 'active' : '' ?>">Relatório de Animais</a></li>
                <li><a href="relatorio_producao.php" class="<?= $paginaAtual === 'relatorio_producao.php' ? 'active' : '' ?>">Produção</a></li>
                <li><a href="relatorio_estoque.php" class="<?= $paginaAtual === 'relatorio_estoque.php' ? 'active' : '' ?>">Estoque</a></li>
                <li><a href="relatorio_pesagem.php" class="<?= $paginaAtual === 'relatorio_pesagem.php' ? 'active' : '' ?>">Pesagem</a></li>
                <li><a href="dre.php" class="<?= $paginaAtual === 'dre.php' ? 'active' : '' ?>">DRE</a></li>
            </ul>
        </div>

        <a href="configuracoes.php" class="<?= $paginaAtual === 'configuracoes.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Configurações</span>
        </a>

    </nav>
</aside>
