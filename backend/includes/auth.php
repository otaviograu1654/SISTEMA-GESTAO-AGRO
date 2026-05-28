<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function encerrarSessaoPorInatividade(): void
{
    $limiteSegundos = 8 * 60 * 60;
    $ultimoAcesso = (int) ($_SESSION['ultimo_acesso'] ?? time());

    if (time() - $ultimoAcesso > $limiteSegundos) {
        $_SESSION = [];
        session_destroy();
        return;
    }

    $_SESSION['ultimo_acesso'] = time();
}

function usuarioLogado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function exigirLogin(): void
{
    encerrarSessaoPorInatividade();

    if (usuarioLogado()) {
        return;
    }

    $destino = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
    header('Location: login.php?redirect=' . urlencode($destino));
    exit;
}

function usuarioAtualId(): int
{
    return (int) ($_SESSION['usuario_id'] ?? 0);
}

function usuarioAtualNome(): string
{
    return (string) ($_SESSION['usuario_nome'] ?? 'Usuario');
}

function usuarioAtualPerfil(): string
{
    $perfil = (string) ($_SESSION['usuario_perfil'] ?? '');

    return $perfil === 'Administrador' ? 'Desenvolvedor' : $perfil;
}

function usuarioEhDesenvolvedor(): bool
{
    return in_array(usuarioAtualPerfil(), ['Desenvolvedor', 'Administrador'], true);
}

function usuarioEhFazendeiro(): bool
{
    return in_array(usuarioAtualPerfil(), ['Fazendeiro', 'Gestor'], true);
}

function usuarioPodeGerenciarUsuarios(): bool
{
    return usuarioEhDesenvolvedor() || usuarioEhFazendeiro();
}

function modulosSistema(): array
{
    return [
        'cadastros' => 'Cadastros',
        'movimentacao' => 'Movimentacao',
        'estoque' => 'Estoque',
        'financeiro' => 'Financeiro',
    ];
}

function moduloDaPagina(string $pagina): ?string
{
    $mapa = [
        'animais.php' => 'cadastros',
        'animal.php' => 'cadastros',
        'cadastro_animal.php' => 'cadastros',
        'editar_animal.php' => 'cadastros',
        'racas.php' => 'cadastros',
        'lotes.php' => 'cadastros',
        'parceiros.php' => 'cadastros',
        'pesagens.php' => 'movimentacao',
        'vacinacao.php' => 'movimentacao',
        'producao_leite.php' => 'movimentacao',
        'estoque.php' => 'estoque',
        'plano_contas.php' => 'financeiro',
        'compras.php' => 'financeiro',
        'vendas.php' => 'financeiro',
        'lancamentos_vista.php' => 'financeiro',
        'contas_a_pagar.php' => 'financeiro',
        'fluxo_caixa.php' => 'financeiro',
    ];

    return $mapa[$pagina] ?? null;
}

function obterPdoAuth(?PDO $pdo = null): ?PDO
{
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    return isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO ? $GLOBALS['pdo'] : null;
}

function usuarioTemPermissaoModulo(string $modulo, ?PDO $pdo = null): bool
{
    if (!usuarioLogado()) {
        return false;
    }

    if (usuarioEhDesenvolvedor() || usuarioEhFazendeiro()) {
        return true;
    }

    if (!array_key_exists($modulo, modulosSistema())) {
        return false;
    }

    $pdoAuth = obterPdoAuth($pdo);

    if (!$pdoAuth instanceof PDO) {
        return false;
    }

    try {
        $stmt = $pdoAuth->prepare("
            SELECT permitido
            FROM usuario_permissoes
            WHERE usuario_id = :usuario_id
              AND modulo = :modulo
            LIMIT 1
        ");
        $stmt->execute([
            ':usuario_id' => usuarioAtualId(),
            ':modulo' => $modulo,
        ]);

        return (int) $stmt->fetchColumn() === 1;
    } catch (PDOException $e) {
        return false;
    }
}

function exigirPermissaoModulo(string $modulo): void
{
    exigirLogin();

    if (usuarioTemPermissaoModulo($modulo)) {
        return;
    }

    http_response_code(403);
    echo 'Acesso negado. Seu usuario nao tem permissao para acessar este modulo.';
    exit;
}

function exigirPermissaoPaginaAtual(): void
{
    $pagina = basename($_SERVER['PHP_SELF'] ?? '');

    if ($pagina === 'usuarios.php') {
        exigirGerenciarUsuarios();
        return;
    }

    $modulo = moduloDaPagina($pagina);

    if ($modulo !== null) {
        exigirPermissaoModulo($modulo);
    }
}

function exigirGerenciarUsuarios(): void
{
    exigirLogin();

    if (usuarioPodeGerenciarUsuarios()) {
        return;
    }

    http_response_code(403);
    echo 'Acesso negado. Seu usuario nao tem permissao para executar esta acao.';
    exit;
}
