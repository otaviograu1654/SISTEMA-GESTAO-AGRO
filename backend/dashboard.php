<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

function buscarResumo(PDO $pdo, string $sql, array $padrao, array &$erros, string $contexto): array
{
    try {
        $resultado = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

        if (!is_array($resultado)) {
            return $padrao;
        }

        return array_merge($padrao, $resultado);
    } catch (PDOException $e) {
        $erros[] = $contexto;
        return $padrao;
    }
}

function buscarLista(PDO $pdo, string $sql, array &$erros, string $contexto): array
{
    try {
        $resultado = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return is_array($resultado) ? $resultado : [];
    } catch (PDOException $e) {
        $erros[] = $contexto;
        return [];
    }
}

function formatarMoeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData(?string $data): string
{
    if (!$data) {
        return '--';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return $data;
    }

    return date('d/m/Y', $timestamp);
}

function badgeConta(string $status, string $vencimento): string
{
    if ($status !== 'pendente') {
        return 'badge-sucesso';
    }

    if ($vencimento < date('Y-m-d')) {
        return 'badge-erro';
    }

    return 'badge-alerta';
}

function badgeSanitario(string $status, ?string $proximaData): string
{
    $statusNormalizado = mb_strtolower(trim($status), 'UTF-8');

    if ($statusNormalizado === 'realizado') {
        return 'badge-sucesso';
    }

    if ($proximaData && $proximaData < date('Y-m-d')) {
        return 'badge-erro';
    }

    return 'badge-alerta';
}

$errosDashboard = [];
$podeCadastros = usuarioTemPermissaoModulo('cadastros');
$podeMovimentacao = usuarioTemPermissaoModulo('movimentacao');
$podeEstoque = usuarioTemPermissaoModulo('estoque');
$podeFinanceiro = usuarioTemPermissaoModulo('financeiro');
$podeUsuarios = usuarioPodeGerenciarUsuarios();
$perfilFuncionario = usuarioAtualPerfil() === 'Funcionario';
$dashboardGestor = !$perfilFuncionario;

$rebanho = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_animais,
            SUM(CASE WHEN LOWER(sexo) = 'macho' THEN 1 ELSE 0 END) AS machos,
            SUM(CASE WHEN LOWER(sexo) IN ('femea', 'fêmea') THEN 1 ELSE 0 END) AS femeas,
            SUM(CASE WHEN prenha = 1 THEN 1 ELSE 0 END) AS prenhas,
            SUM(CASE WHEN status = 'Ativo' THEN 1 ELSE 0 END) AS ativos,
            SUM(CASE WHEN status = 'Vendido' THEN 1 ELSE 0 END) AS vendidos,
            SUM(CASE WHEN status = 'Óbito' OR status = 'Ã“bito' THEN 1 ELSE 0 END) AS obitos
        FROM animais
    ",
    [
        'total_animais' => 0,
        'machos' => 0,
        'femeas' => 0,
        'prenhas' => 0,
        'ativos' => 0,
        'vendidos' => 0,
        'obitos' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar o resumo do rebanho.'
);

$producaoLeite = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_registros,
            SUM(litros) AS total_litros,
            SUM(CASE WHEN data_producao = CURDATE() THEN litros ELSE 0 END) AS litros_hoje,
            AVG(litros) AS media_litros
        FROM producao_leite
    ",
    [
        'total_registros' => 0,
        'total_litros' => 0,
        'litros_hoje' => 0,
        'media_litros' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar a produção de leite.'
);

$pesagens = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_pesagens,
            SUM(CASE WHEN data_pesagem = CURDATE() THEN 1 ELSE 0 END) AS pesagens_hoje,
            AVG(peso_kg) AS peso_medio
        FROM pesagens
    ",
    [
        'total_pesagens' => 0,
        'pesagens_hoje' => 0,
        'peso_medio' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar o resumo de pesagens.'
);

$ultimaPesagem = buscarResumo(
    $pdo,
    "
        SELECT
            a.nome_apelido,
            a.brinco,
            p.data_pesagem,
            p.peso_kg
        FROM pesagens p
        INNER JOIN animais a ON a.id = p.animal_id
        ORDER BY p.data_pesagem DESC, p.id DESC
        LIMIT 1
    ",
    [
        'nome_apelido' => '',
        'brinco' => '',
        'data_pesagem' => null,
        'peso_kg' => null,
    ],
    $errosDashboard,
    'Não foi possível carregar a última pesagem.'
);

$financeiro = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_lancamentos,
            SUM(CASE WHEN tipo = 'Receita' THEN valor ELSE 0 END) AS total_receitas,
            SUM(CASE WHEN tipo = 'Despesa' THEN valor ELSE 0 END) AS total_despesas,
            SUM(CASE WHEN tipo = 'Receita' AND data_lancamento = CURDATE() THEN valor ELSE 0 END) AS receitas_hoje,
            SUM(CASE WHEN tipo = 'Despesa' AND data_lancamento = CURDATE() THEN valor ELSE 0 END) AS despesas_hoje,
            SUM(
                CASE
                    WHEN tipo = 'Despesa'
                     AND (
                        LOWER(COALESCE(categoria, '')) LIKE '%compra%'
                        OR LOWER(COALESCE(descricao, '')) LIKE '%fornecedor:%'
                     )
                    THEN 1 ELSE 0
                END
            ) AS total_compras,
            SUM(
                CASE
                    WHEN tipo = 'Despesa'
                     AND (
                        LOWER(COALESCE(categoria, '')) LIKE '%compra%'
                        OR LOWER(COALESCE(descricao, '')) LIKE '%fornecedor:%'
                     )
                    THEN valor ELSE 0
                END
            ) AS valor_compras,
            SUM(
                CASE
                    WHEN tipo = 'Receita'
                     AND (
                        LOWER(COALESCE(categoria, '')) LIKE '%venda%'
                        OR LOWER(COALESCE(descricao, '')) LIKE '%venda%'
                     )
                    THEN 1 ELSE 0
                END
            ) AS total_vendas,
            SUM(
                CASE
                    WHEN tipo = 'Receita'
                     AND (
                        LOWER(COALESCE(categoria, '')) LIKE '%venda%'
                        OR LOWER(COALESCE(descricao, '')) LIKE '%venda%'
                     )
                    THEN valor ELSE 0
                END
            ) AS valor_vendas,
            COUNT(DISTINCT CASE WHEN categoria IS NOT NULL AND categoria <> '' THEN categoria END) AS total_categorias
        FROM financeiro
    ",
    [
        'total_lancamentos' => 0,
        'total_receitas' => 0,
        'total_despesas' => 0,
        'receitas_hoje' => 0,
        'despesas_hoje' => 0,
        'total_compras' => 0,
        'valor_compras' => 0,
        'total_vendas' => 0,
        'valor_vendas' => 0,
        'total_categorias' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar o resumo financeiro.'
);

$contas = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_contas,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS contas_pendentes,
            SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END) AS valor_pendente,
            SUM(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN 1 ELSE 0 END) AS contas_atrasadas,
            SUM(
                CASE
                    WHEN status = 'pendente'
                     AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    THEN 1 ELSE 0
                END
            ) AS contas_semana
        FROM tabelacontas
    ",
    [
        'total_contas' => 0,
        'contas_pendentes' => 0,
        'valor_pendente' => 0,
        'contas_atrasadas' => 0,
        'contas_semana' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar as contas a pagar.'
);

$estoqueResumo = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_produtos,
            SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS produtos_ativos,
            SUM(CASE WHEN quantidade_atual <= 0 THEN 1 ELSE 0 END) AS produtos_zerados,
            SUM(
                CASE
                    WHEN validade IS NOT NULL
                     AND validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    THEN 1 ELSE 0
                END
            ) AS vencendo_30_dias
        FROM estoque_produtos
    ",
    [
        'total_produtos' => 0,
        'produtos_ativos' => 0,
        'produtos_zerados' => 0,
        'vencendo_30_dias' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar o resumo de estoque.'
);

$sanitario = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_manejos,
            SUM(CASE WHEN LOWER(tipo) IN ('vacinacao', 'vacinação') THEN 1 ELSE 0 END) AS total_vacinacoes,
            SUM(
                CASE
                    WHEN LOWER(tipo) IN ('vacinacao', 'vacinação')
                     AND data_evento = CURDATE()
                     AND LOWER(COALESCE(status, '')) = 'realizado'
                    THEN 1 ELSE 0
                END
            ) AS vacinacoes_hoje,
            SUM(
                CASE
                    WHEN LOWER(tipo) IN ('vacinacao', 'vacinação')
                     AND proxima_data >= CURDATE()
                    THEN 1 ELSE 0
                END
            ) AS vacinacoes_proximas,
            SUM(
                CASE
                    WHEN LOWER(tipo) IN ('vacinacao', 'vacinação')
                     AND proxima_data < CURDATE()
                     AND LOWER(COALESCE(status, '')) <> 'realizado'
                    THEN 1 ELSE 0
                END
            ) AS vacinacoes_atrasadas
        FROM manejos_sanitarios
    ",
    [
        'total_manejos' => 0,
        'total_vacinacoes' => 0,
        'vacinacoes_hoje' => 0,
        'vacinacoes_proximas' => 0,
        'vacinacoes_atrasadas' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar o resumo sanitário.'
);

$usuarios = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_usuarios,
            SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) AS usuarios_ativos,
            SUM(CASE WHEN perfil IN ('Desenvolvedor', 'Administrador') THEN 1 ELSE 0 END) AS administradores
        FROM usuarios
    ",
    [
        'total_usuarios' => 0,
        'usuarios_ativos' => 0,
        'administradores' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar os usuários.'
);

$suporte = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_chamados,
            SUM(CASE WHEN status = 'Aberto' THEN 1 ELSE 0 END) AS chamados_abertos,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS chamados_hoje
        FROM suporte_chamados
    ",
    [
        'total_chamados' => 0,
        'chamados_abertos' => 0,
        'chamados_hoje' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar os chamados de suporte.'
);

$animaisRecentes = buscarLista(
    $pdo,
    "
        SELECT id, brinco, nome_apelido, raca, sexo, lote, created_at
        FROM animais
        ORDER BY id DESC
        LIMIT 6
    ",
    $errosDashboard,
    'Não foi possível carregar os animais recentes.'
);

$lancamentosRecentes = buscarLista(
    $pdo,
    "
        SELECT tipo, origem, categoria, descricao, valor, data_lancamento
        FROM financeiro
        ORDER BY data_lancamento DESC, id DESC
        LIMIT 6
    ",
    $errosDashboard,
    'Não foi possível carregar os lançamentos recentes.'
);

$contasProximas = buscarLista(
    $pdo,
    "
        SELECT descricao, natureza, valor, data_vencimento, prioridade, status
        FROM tabelacontas
        ORDER BY
            CASE WHEN status = 'pendente' THEN 0 ELSE 1 END,
            data_vencimento ASC,
            id DESC
        LIMIT 6
    ",
    $errosDashboard,
    'Não foi possível carregar as próximas contas.'
);

$agendaSanitaria = buscarLista(
    $pdo,
    "
        SELECT a.nome_apelido, a.brinco, ms.tipo, ms.descricao, ms.data_evento, ms.proxima_data, ms.status
        FROM manejos_sanitarios ms
        INNER JOIN animais a ON a.id = ms.animal_id
        ORDER BY
            CASE WHEN ms.proxima_data IS NULL THEN 1 ELSE 0 END,
            ms.proxima_data ASC,
            ms.data_evento DESC,
            ms.id DESC
        LIMIT 6
    ",
    $errosDashboard,
    'Não foi possível carregar a agenda sanitária.'
);

$produtosAtencao = buscarLista(
    $pdo,
    "
        SELECT nome, categoria, quantidade_atual, unidade, validade
        FROM estoque_produtos
        WHERE quantidade_atual <= 0
           OR (
                validade IS NOT NULL
                AND validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
           )
        ORDER BY
            CASE WHEN quantidade_atual <= 0 THEN 0 ELSE 1 END,
            validade ASC,
            nome ASC
        LIMIT 6
    ",
    $errosDashboard,
    'Não foi possível carregar os produtos em atenção.'
);

$saldoFinanceiro = (float) $financeiro['total_receitas'] - (float) $financeiro['total_despesas'];
$totalAnimais = (int) $rebanho['total_animais'];
$animaisAtivos = (int) $rebanho['ativos'];
$percentualAtivos = $totalAnimais > 0 ? ($animaisAtivos / $totalAnimais) * 100 : 0;
$resultadoOperacional = $saldoFinanceiro >= 0 ? 'Superávit' : 'Déficit';
$alertasGestor = [];
$alertasInteligentes = [];

if ((int) $contas['contas_atrasadas'] > 0) {
    $alertasGestor[] = [
        'titulo' => 'Contas atrasadas',
        'descricao' => (int) $contas['contas_atrasadas'] . ' conta(s) vencida(s) precisam de atenção.',
        'link' => 'contas_a_pagar.php',
        'modulo' => 'financeiro',
    ];
}

if ((int) $sanitario['vacinacoes_atrasadas'] > 0) {
    $alertasGestor[] = [
        'titulo' => 'Vacinações atrasadas',
        'descricao' => (int) $sanitario['vacinacoes_atrasadas'] . ' pendência(s) sanitária(s) no rebanho.',
        'link' => 'vacinacao.php',
        'modulo' => 'movimentacao',
    ];
}

if ((int) $estoqueResumo['produtos_zerados'] > 0) {
    $alertasGestor[] = [
        'titulo' => 'Estoque zerado',
        'descricao' => (int) $estoqueResumo['produtos_zerados'] . ' produto(s) sem saldo atual.',
        'link' => 'estoque.php',
        'modulo' => 'estoque',
    ];
}

if ((int) $estoqueResumo['vencendo_30_dias'] > 0) {
    $alertasGestor[] = [
        'titulo' => 'Validade próxima',
        'descricao' => (int) $estoqueResumo['vencendo_30_dias'] . ' produto(s) vencem nos próximos 30 dias.',
        'link' => 'estoque.php',
        'modulo' => 'estoque',
    ];
}

if ($podeFinanceiro && (int) $contas['contas_atrasadas'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Urgente',
        'titulo' => 'Contas atrasadas',
        'descricao' => (int) $contas['contas_atrasadas'] . ' conta(s) vencida(s) precisam de atenção.',
        'link' => 'contas_a_pagar.php',
    ];
}

if ($podeFinanceiro && (int) $contas['contas_semana'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Atenção',
        'titulo' => 'Contas vencendo',
        'descricao' => (int) $contas['contas_semana'] . ' conta(s) vencem nos próximos 7 dias.',
        'link' => 'contas_a_pagar.php',
    ];
}

if ($podeMovimentacao && (int) $sanitario['vacinacoes_atrasadas'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Urgente',
        'titulo' => 'Vacinações atrasadas',
        'descricao' => (int) $sanitario['vacinacoes_atrasadas'] . ' pendência(s) sanitária(s) no rebanho.',
        'link' => 'vacinacao.php',
    ];
}

if ($podeMovimentacao && (int) $sanitario['vacinacoes_proximas'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Atenção',
        'titulo' => 'Vacinações próximas',
        'descricao' => (int) $sanitario['vacinacoes_proximas'] . ' vacinação(ões) futuras para acompanhar.',
        'link' => 'vacinacao.php',
    ];
}

if ($podeEstoque && (int) $estoqueResumo['produtos_zerados'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Urgente',
        'titulo' => 'Estoque zerado',
        'descricao' => (int) $estoqueResumo['produtos_zerados'] . ' produto(s) sem saldo atual.',
        'link' => 'estoque.php',
    ];
}

if ($podeEstoque && (int) $estoqueResumo['vencendo_30_dias'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Atenção',
        'titulo' => 'Validade próxima',
        'descricao' => (int) $estoqueResumo['vencendo_30_dias'] . ' produto(s) vencem nos próximos 30 dias.',
        'link' => 'estoque.php',
    ];
}

if (usuarioEhDesenvolvedor() && (int) $suporte['chamados_abertos'] > 0) {
    $alertasInteligentes[] = [
        'nivel' => 'Atenção',
        'titulo' => 'Chamados abertos',
        'descricao' => (int) $suporte['chamados_abertos'] . ' chamado(s) aguardam atendimento.',
        'link' => 'suporte.php',
    ];
}

layoutInicio('Dashboard');
?>

<div class="page-header dashboard-header">
    <div>
        <h1><?= $perfilFuncionario ? 'Dashboard do funcionário' : 'Dashboard geral' ?></h1>
        <p><?= $perfilFuncionario ? 'Resumo dos módulos liberados para sua rotina no sistema.' : 'Resumo consolidado dos módulos do backend com indicadores operacionais, financeiros e de cadastro.' ?></p>
    </div>

    <div class="top-actions">
        <?php if ($podeCadastros): ?>
            <a class="btn-link" href="animais.php">Ver animais</a>
        <?php endif; ?>
        <?php if ($podeMovimentacao): ?>
            <a class="btn-link" href="pesagens.php">Registrar pesagem</a>
            <a class="btn-link" href="vacinacao.php">Registrar vacinação</a>
            <a class="btn-link" href="producao_leite.php">Registrar leite</a>
        <?php endif; ?>
        <?php if ($podeEstoque): ?>
            <a class="btn-link" href="estoque.php">Ver estoque</a>
        <?php endif; ?>
        <?php if ($podeFinanceiro): ?>
            <a class="btn-link" href="compras.php">Lançar compra</a>
            <a class="btn-link" href="vendas.php">Lançar venda</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$podeCadastros && !$podeMovimentacao && !$podeEstoque && !$podeFinanceiro): ?>
    <div class="mensagem erro mensagem-bloco">
        Nenhum módulo foi liberado para este usuário. Solicite acesso ao gestor da fazenda.
    </div>
<?php endif; ?>

<?php if (!empty($errosDashboard)): ?>
    <div class="mensagem erro mensagem-bloco">
        Algumas informações do dashboard não puderam ser carregadas.
    </div>
<?php endif; ?>

<?php if ($dashboardGestor): ?>
<section class="dashboard-section">
    <div class="section-title">
        <h2>Visão executiva da fazenda</h2>
        <p>Indicadores diretos para acompanhar operação, caixa e pontos de atenção do dia.</p>
    </div>

    <div class="cards dashboard-cards">
        <?php if ($podeCadastros): ?>
            <div class="card metric-card">
                <h3>Rebanho ativo</h3>
                <div class="value"><?= number_format($percentualAtivos, 0, ',', '.') ?>%</div>
                <div class="metric-meta"><?= $animaisAtivos ?> de <?= $totalAnimais ?> animais ativos</div>
            </div>
        <?php endif; ?>

        <?php if ($podeFinanceiro): ?>
            <div class="card metric-card">
                <h3>Resultado financeiro</h3>
                <div class="value value-money <?= $saldoFinanceiro >= 0 ? 'value-positive' : 'value-negative' ?>"><?= formatarMoeda($saldoFinanceiro) ?></div>
                <div class="metric-meta"><?= $resultadoOperacional ?> acumulado no financeiro</div>
            </div>
        <?php endif; ?>

        <?php if ($podeMovimentacao): ?>
            <div class="card metric-card">
                <h3>Produção de leite hoje</h3>
                <div class="value"><?= number_format((float) $producaoLeite['litros_hoje'], 1, ',', '.') ?> L</div>
                <div class="metric-meta"><?= number_format((float) $producaoLeite['total_litros'], 1, ',', '.') ?> L registrados no total</div>
            </div>
        <?php endif; ?>

        <div class="card metric-card">
            <h3>Alertas prioritários</h3>
            <div class="value"><?= count($alertasInteligentes) ?></div>
            <div class="metric-meta"><?= empty($alertasInteligentes) ? 'Nenhuma pendência crítica no momento' : 'Pendências para revisar' ?></div>
        </div>
    </div>
</section>

<?php if (!empty($alertasInteligentes)): ?>
<section class="panel panel-spaced">
    <h2>Alertas inteligentes</h2>
    <p>Itens priorizados conforme perfil e permissões do usuário.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Alerta</th>
                    <th>Resumo</th>
                    <th>Nível</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertasInteligentes as $alerta): ?>
                    <tr>
                        <td><span class="badge badge-alerta"><?= htmlspecialchars($alerta['titulo'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($alerta['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $alerta['nivel'] === 'Urgente' ? 'badge-erro' : 'badge-alerta' ?>">
                                <?= htmlspecialchars($alerta['nivel'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn-link" href="<?= htmlspecialchars($alerta['link'], ENT_QUOTES, 'UTF-8') ?>">Abrir módulo</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>

<?php if (!$dashboardGestor && !empty($alertasInteligentes)): ?>
<section class="panel panel-spaced">
    <h2>Alertas inteligentes</h2>
    <p>Itens priorizados conforme seus módulos liberados.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Alerta</th>
                    <th>Resumo</th>
                    <th>Nível</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertasInteligentes as $alerta): ?>
                    <tr>
                        <td><span class="badge badge-alerta"><?= htmlspecialchars($alerta['titulo'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td><?= htmlspecialchars($alerta['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $alerta['nivel'] === 'Urgente' ? 'badge-erro' : 'badge-alerta' ?>">
                                <?= htmlspecialchars($alerta['nivel'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn-link" href="<?= htmlspecialchars($alerta['link'], ENT_QUOTES, 'UTF-8') ?>">Abrir módulo</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if ($podeCadastros || $podeMovimentacao): ?>
<section class="dashboard-section">
    <div class="section-title">
        <h2>Rebanho e produção</h2>
        <p>Indicadores principais de cadastro animal e acompanhamento de peso.</p>
    </div>

    <div class="cards dashboard-cards">
        <?php if ($podeCadastros): ?>
        <div class="card metric-card">
            <h3>Total de animais</h3>
            <div class="value"><?= (int) $rebanho['total_animais'] ?></div>
            <div class="metric-meta">Rebanho cadastrado no sistema</div>
        </div>
        <div class="card metric-card">
            <h3>Machos</h3>
            <div class="value"><?= (int) $rebanho['machos'] ?></div>
            <div class="metric-meta">Animais classificados como macho</div>
        </div>
        <div class="card metric-card">
            <h3>Fêmeas</h3>
            <div class="value"><?= (int) $rebanho['femeas'] ?></div>
            <div class="metric-meta">Animais classificados como fêmea</div>
        </div>
        <div class="card metric-card">
            <h3>Prenhas</h3>
            <div class="value"><?= (int) $rebanho['prenhas'] ?></div>
            <div class="metric-meta">Matrizes com prenhez marcada</div>
        </div>
        <div class="card metric-card">
            <h3>Ativos</h3>
            <div class="value"><?= (int) $rebanho['ativos'] ?></div>
            <div class="metric-meta">Animais atualmente no rebanho</div>
        </div>
        <div class="card metric-card">
            <h3>Vendidos</h3>
            <div class="value"><?= (int) $rebanho['vendidos'] ?></div>
            <div class="metric-meta">Baixas por venda registradas</div>
        </div>
        <div class="card metric-card">
            <h3>Óbitos</h3>
            <div class="value"><?= (int) $rebanho['obitos'] ?></div>
            <div class="metric-meta">Baixas por óbito registradas</div>
        </div>
        <?php endif; ?>
        <?php if ($podeMovimentacao): ?>
        <div class="card metric-card">
            <h3>Total de pesagens</h3>
            <div class="value"><?= (int) $pesagens['total_pesagens'] ?></div>
            <div class="metric-meta">Registros de peso salvos</div>
        </div>
        <div class="card metric-card">
            <h3>Pesagens hoje</h3>
            <div class="value"><?= (int) $pesagens['pesagens_hoje'] ?></div>
            <div class="metric-meta">Movimentação do dia</div>
        </div>
        <div class="card metric-card">
            <h3>Peso médio</h3>
            <div class="value"><?= $pesagens['total_pesagens'] > 0 ? number_format((float) $pesagens['peso_medio'], 1, ',', '.') . ' kg' : '--' ?></div>
            <div class="metric-meta">Média geral das pesagens</div>
        </div>
        <div class="card metric-card">
            <h3>Última pesagem</h3>
            <div class="value value-sm"><?= $ultimaPesagem['peso_kg'] !== null ? number_format((float) $ultimaPesagem['peso_kg'], 2, ',', '.') . ' kg' : '--' ?></div>
            <div class="metric-meta">
                <?= $ultimaPesagem['nome_apelido'] !== '' ? htmlspecialchars($ultimaPesagem['nome_apelido'], ENT_QUOTES, 'UTF-8') . ' em ' . formatarData($ultimaPesagem['data_pesagem']) : 'Nenhuma pesagem registrada' ?>
            </div>
        </div>
        <div class="card metric-card">
            <h3>Leite registrado</h3>
            <div class="value"><?= number_format((float) $producaoLeite['total_litros'], 1, ',', '.') ?> L</div>
            <div class="metric-meta"><?= (int) $producaoLeite['total_registros'] ?> registros de produção</div>
        </div>
        <div class="card metric-card">
            <h3>Leite hoje</h3>
            <div class="value"><?= number_format((float) $producaoLeite['litros_hoje'], 1, ',', '.') ?> L</div>
            <div class="metric-meta">Produção registrada no dia</div>
        </div>
        <div class="card metric-card">
            <h3>Média por registro</h3>
            <div class="value"><?= number_format((float) $producaoLeite['media_litros'], 1, ',', '.') ?> L</div>
            <div class="metric-meta">Média dos lançamentos de leite</div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($podeEstoque): ?>
<section class="dashboard-section">
    <div class="section-title">
        <h2>Estoque</h2>
        <p>Indicadores rápidos para acompanhar produtos e alertas operacionais.</p>
    </div>

    <div class="cards dashboard-cards">
        <div class="card metric-card">
            <h3>Produtos</h3>
            <div class="value"><?= (int) $estoqueResumo['total_produtos'] ?></div>
            <div class="metric-meta">Itens cadastrados no estoque</div>
        </div>
        <div class="card metric-card">
            <h3>Produtos ativos</h3>
            <div class="value"><?= (int) $estoqueResumo['produtos_ativos'] ?></div>
            <div class="metric-meta">Itens disponíveis para movimentação</div>
        </div>
        <div class="card metric-card">
            <h3>Estoque zerado</h3>
            <div class="value"><?= (int) $estoqueResumo['produtos_zerados'] ?></div>
            <div class="metric-meta">Produtos sem saldo atual</div>
        </div>
        <div class="card metric-card">
            <h3>Vencendo em 30 dias</h3>
            <div class="value"><?= (int) $estoqueResumo['vencendo_30_dias'] ?></div>
            <div class="metric-meta">Produtos com validade próxima</div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($podeFinanceiro): ?>
<section class="dashboard-section">
    <div class="section-title">
        <h2>Financeiro</h2>
        <p>Fluxo de receitas, despesas, compras, vendas e compromissos pendentes.</p>
    </div>

    <div class="cards dashboard-cards">
        <div class="card metric-card">
            <h3>Receitas</h3>
            <div class="value value-money"><?= formatarMoeda((float) $financeiro['total_receitas']) ?></div>
            <div class="metric-meta">Total registrado no financeiro</div>
        </div>
        <div class="card metric-card">
            <h3>Despesas</h3>
            <div class="value value-money"><?= formatarMoeda((float) $financeiro['total_despesas']) ?></div>
            <div class="metric-meta">Saídas já lançadas</div>
        </div>
        <div class="card metric-card">
            <h3>Saldo</h3>
            <div class="value value-money <?= $saldoFinanceiro >= 0 ? 'value-positive' : 'value-negative' ?>"><?= formatarMoeda($saldoFinanceiro) ?></div>
            <div class="metric-meta">Receitas menos despesas</div>
        </div>
        <div class="card metric-card">
            <h3>Receitas hoje</h3>
            <div class="value value-money"><?= formatarMoeda((float) $financeiro['receitas_hoje']) ?></div>
            <div class="metric-meta">Entradas do dia atual</div>
        </div>
        <div class="card metric-card">
            <h3>Despesas hoje</h3>
            <div class="value value-money"><?= formatarMoeda((float) $financeiro['despesas_hoje']) ?></div>
            <div class="metric-meta">Saídas do dia atual</div>
        </div>
        <div class="card metric-card">
            <h3>Compras lançadas</h3>
            <div class="value"><?= (int) $financeiro['total_compras'] ?></div>
            <div class="metric-meta"><?= formatarMoeda((float) $financeiro['valor_compras']) ?> em compras</div>
        </div>
        <div class="card metric-card">
            <h3>Vendas lançadas</h3>
            <div class="value"><?= (int) $financeiro['total_vendas'] ?></div>
            <div class="metric-meta"><?= formatarMoeda((float) $financeiro['valor_vendas']) ?> em vendas</div>
        </div>
        <div class="card metric-card">
            <h3>Contas pendentes</h3>
            <div class="value"><?= (int) $contas['contas_pendentes'] ?></div>
            <div class="metric-meta"><?= formatarMoeda((float) $contas['valor_pendente']) ?> a pagar</div>
        </div>
        <div class="card metric-card">
            <h3>Contas atrasadas</h3>
            <div class="value"><?= (int) $contas['contas_atrasadas'] ?></div>
            <div class="metric-meta">Pendências vencidas</div>
        </div>
        <div class="card metric-card">
            <h3>Vencendo em 7 dias</h3>
            <div class="value"><?= (int) $contas['contas_semana'] ?></div>
            <div class="metric-meta">Contas para esta semana</div>
        </div>
        <div class="card metric-card">
            <h3>Lançamentos</h3>
            <div class="value"><?= (int) $financeiro['total_lancamentos'] ?></div>
            <div class="metric-meta">Movimentações financeiras totais</div>
        </div>
        <div class="card metric-card">
            <h3>Categorias</h3>
            <div class="value"><?= (int) $financeiro['total_categorias'] ?></div>
            <div class="metric-meta">Plano de contas em uso</div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($podeMovimentacao || $podeUsuarios || usuarioEhDesenvolvedor()): ?>
<section class="dashboard-section">
    <div class="section-title">
        <h2>Sanidade, usuários e suporte</h2>
        <p>Indicadores de rotina sanitária e administração do sistema.</p>
    </div>

    <div class="cards dashboard-cards">
        <?php if ($podeMovimentacao): ?>
        <div class="card metric-card">
            <h3>Manejos sanitários</h3>
            <div class="value"><?= (int) $sanitario['total_manejos'] ?></div>
            <div class="metric-meta">Registros totais da agenda sanitária</div>
        </div>
        <div class="card metric-card">
            <h3>Vacinações</h3>
            <div class="value"><?= (int) $sanitario['total_vacinacoes'] ?></div>
            <div class="metric-meta">Aplicações já cadastradas</div>
        </div>
        <div class="card metric-card">
            <h3>Aplicadas hoje</h3>
            <div class="value"><?= (int) $sanitario['vacinacoes_hoje'] ?></div>
            <div class="metric-meta">Vacinações realizadas no dia</div>
        </div>
        <div class="card metric-card">
            <h3>Próximas vacinações</h3>
            <div class="value"><?= (int) $sanitario['vacinacoes_proximas'] ?></div>
            <div class="metric-meta">Agendamentos futuros</div>
        </div>
        <div class="card metric-card">
            <h3>Vacinações atrasadas</h3>
            <div class="value"><?= (int) $sanitario['vacinacoes_atrasadas'] ?></div>
            <div class="metric-meta">Pendências de sanidade</div>
        </div>
        <?php endif; ?>
        <?php if ($podeUsuarios): ?>
        <div class="card metric-card">
            <h3>Usuários ativos</h3>
            <div class="value"><?= (int) $usuarios['usuarios_ativos'] ?></div>
            <div class="metric-meta"><?= (int) $usuarios['total_usuarios'] ?> usuários cadastrados</div>
        </div>
        <div class="card metric-card">
            <h3>Administradores</h3>
            <div class="value"><?= (int) $usuarios['administradores'] ?></div>
            <div class="metric-meta">Perfis com acesso administrativo</div>
        </div>
        <?php endif; ?>
        <?php if (usuarioEhDesenvolvedor()): ?>
        <div class="card metric-card">
            <h3>Chamados abertos</h3>
            <div class="value"><?= (int) $suporte['chamados_abertos'] ?></div>
            <div class="metric-meta"><?= (int) $suporte['total_chamados'] ?> chamados no total</div>
        </div>
        <div class="card metric-card">
            <h3>Chamados hoje</h3>
            <div class="value"><?= (int) $suporte['chamados_hoje'] ?></div>
            <div class="metric-meta">Demandas abertas hoje</div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($podeCadastros || $podeFinanceiro || $podeMovimentacao || $podeEstoque): ?>
<div class="grid-panels dashboard-grid">
    <?php if ($podeCadastros): ?>
    <section class="panel">
        <h2>Animais recentes</h2>
        <p>Últimos cadastros de animais no backend.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Brinco</th>
                        <th>Nome</th>
                        <th>Raça</th>
                        <th>Sexo</th>
                        <th>Lote</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($animaisRecentes)): ?>
                        <tr>
                            <td colspan="5">Nenhum animal cadastrado ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($animaisRecentes as $animal): ?>
                            <tr>
                                <td><?= htmlspecialchars($animal['brinco'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['nome_apelido'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['raca'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['sexo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($animal['lote'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($podeFinanceiro): ?>
    <section class="panel">
        <h2>Lançamentos recentes</h2>
        <p>Movimentações mais novas do módulo financeiro.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Origem</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lancamentosRecentes)): ?>
                        <tr>
                            <td colspan="5">Nenhum lançamento financeiro encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lancamentosRecentes as $lancamento): ?>
                            <tr>
                                <td><?= formatarData($lancamento['data_lancamento']) ?></td>
                                <td><?= htmlspecialchars($lancamento['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lancamento['origem'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($lancamento['categoria'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatarMoeda((float) $lancamento['valor']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <h2>Contas a pagar</h2>
        <p>Próximos vencimentos e pendências financeiras.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contasProximas)): ?>
                        <tr>
                            <td colspan="4">Nenhuma conta cadastrada.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contasProximas as $conta): ?>
                            <tr>
                                <td><?= htmlspecialchars($conta['descricao'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatarData($conta['data_vencimento']) ?></td>
                                <td><?= formatarMoeda((float) $conta['valor']) ?></td>
                                <td>
                                    <span class="badge <?= badgeConta((string) $conta['status'], (string) $conta['data_vencimento']) ?>">
                                        <?= htmlspecialchars(ucfirst((string) $conta['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($podeMovimentacao): ?>
    <section class="panel">
        <h2>Agenda sanitária</h2>
        <p>Próximos eventos e situação dos registros sanitários.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>Tipo</th>
                        <th>Próxima data</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agendaSanitaria)): ?>
                        <tr>
                            <td colspan="4">Nenhum manejo sanitário cadastrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($agendaSanitaria as $registro): ?>
                            <tr>
                                <td><?= htmlspecialchars($registro['nome_apelido'] . ' / ' . $registro['brinco'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($registro['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatarData($registro['proxima_data']) ?></td>
                                <td>
                                    <span class="badge <?= badgeSanitario((string) $registro['status'], $registro['proxima_data']) ?>">
                                        <?= htmlspecialchars($registro['status'] ?: 'Pendente', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($podeEstoque): ?>
    <section class="panel">
        <h2>Estoque em atenção</h2>
        <p>Produtos sem saldo ou com validade próxima.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Saldo</th>
                        <th>Validade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtosAtencao)): ?>
                        <tr>
                            <td colspan="4">Nenhum produto em atenção.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produtosAtencao as $produto): ?>
                            <tr>
                                <td><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($produto['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= number_format((float) $produto['quantidade_atual'], 2, ',', '.') . ' ' . htmlspecialchars($produto['unidade'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatarData($produto['validade']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php layoutFim(); ?>
