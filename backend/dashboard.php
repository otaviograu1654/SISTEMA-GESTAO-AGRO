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

$rebanho = buscarResumo(
    $pdo,
    "
        SELECT
            COUNT(*) AS total_animais,
            SUM(CASE WHEN LOWER(sexo) = 'macho' THEN 1 ELSE 0 END) AS machos,
            SUM(CASE WHEN LOWER(sexo) IN ('femea', 'fêmea') THEN 1 ELSE 0 END) AS femeas,
            SUM(CASE WHEN prenha = 1 THEN 1 ELSE 0 END) AS prenhas
        FROM animais
    ",
    [
        'total_animais' => 0,
        'machos' => 0,
        'femeas' => 0,
        'prenhas' => 0,
    ],
    $errosDashboard,
    'Não foi possível carregar o resumo do rebanho.'
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
            SUM(CASE WHEN perfil = 'Administrador' THEN 1 ELSE 0 END) AS administradores
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
        SELECT tipo, categoria, descricao, valor, data_lancamento
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

$saldoFinanceiro = (float) $financeiro['total_receitas'] - (float) $financeiro['total_despesas'];

layoutInicio('Dashboard');
?>

<div class="page-header dashboard-header">
    <div>
        <h1>Dashboard geral</h1>
        <p>Resumo consolidado dos módulos do backend com indicadores operacionais, financeiros e de cadastro.</p>
    </div>

    <div class="top-actions">
        <a class="btn-link" href="animais.php">Ver animais</a>
        <a class="btn-link" href="pesagens.php">Registrar pesagem</a>
        <a class="btn-link" href="vacinacao.php">Registrar vacinação</a>
        <a class="btn-link" href="compras.php">Lançar compra</a>
        <a class="btn-link" href="vendas.php">Lançar venda</a>
    </div>
</div>

<?php if (!empty($errosDashboard)): ?>
    <div class="mensagem erro mensagem-bloco">
        Algumas informações do dashboard não puderam ser carregadas.
    </div>
<?php endif; ?>

<section class="dashboard-section">
    <div class="section-title">
        <h2>Rebanho e produção</h2>
        <p>Indicadores principais de cadastro animal e acompanhamento de peso.</p>
    </div>

    <div class="cards dashboard-cards">
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
    </div>
</section>

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

<section class="dashboard-section">
    <div class="section-title">
        <h2>Sanidade, usuários e suporte</h2>
        <p>Indicadores de rotina sanitária e administração do sistema.</p>
    </div>

    <div class="cards dashboard-cards">
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
    </div>
</section>

<div class="grid-panels dashboard-grid">
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

    <section class="panel">
        <h2>Lançamentos recentes</h2>
        <p>Movimentações mais novas do módulo financeiro.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lancamentosRecentes)): ?>
                        <tr>
                            <td colspan="4">Nenhum lançamento financeiro encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lancamentosRecentes as $lancamento): ?>
                            <tr>
                                <td><?= formatarData($lancamento['data_lancamento']) ?></td>
                                <td><?= htmlspecialchars($lancamento['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
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
</div>

<?php layoutFim(); ?>
