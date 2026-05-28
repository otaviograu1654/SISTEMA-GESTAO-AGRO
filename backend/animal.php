<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/animal_auditoria.php';
require_once __DIR__ . '/includes/layout.php';

garantirStatusAnimal($pdo);
garantirBaixasAnimal($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    die('Animal invalido.');
}

try {
    $sql = "
        SELECT
            a.id,
            a.brinco,
            a.nome_apelido,
            a.raca,
            a.sexo,
            a.data_nascimento,
            a.lote,
            a.data_ultimo_cio,
            a.prenha,
            a.status,
            a.mae_id,
            a.pai_id,
            mae.nome_apelido AS nome_mae,
            mae.brinco AS brinco_mae,
            pai.nome_apelido AS nome_pai,
            pai.brinco AS brinco_pai
        FROM animais a
        LEFT JOIN animais mae ON a.mae_id = mae.id
        LEFT JOIN animais pai ON a.pai_id = pai.id
        WHERE a.id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$animal) {
        http_response_code(404);
        die('Animal nao encontrado.');
    }

    $stmtCrias = $pdo->prepare("
        SELECT COUNT(*)
        FROM animais
        WHERE mae_id = :id
    ");
    $stmtCrias->execute([':id' => $id]);
    $totalCrias = (int) $stmtCrias->fetchColumn();

    $stmtVenda = $pdo->prepare("
        SELECT
            av.*,
            p.nome AS parceiro_nome
        FROM animal_vendas av
        LEFT JOIN parceiros p ON p.id = av.parceiro_id
        WHERE av.animal_id = :id
        ORDER BY av.data_venda DESC, av.id DESC
        LIMIT 1
    ");
    $stmtVenda->execute([':id' => $id]);
    $vendaAnimal = $stmtVenda->fetch(PDO::FETCH_ASSOC);

    $stmtObito = $pdo->prepare("
        SELECT *
        FROM animal_obitos
        WHERE animal_id = :id
        ORDER BY data_obito DESC, id DESC
        LIMIT 1
    ");
    $stmtObito->execute([':id' => $id]);
    $obitoAnimal = $stmtObito->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erro em animal.php: ' . $e->getMessage());
    http_response_code(500);
    die('Nao foi possivel carregar este animal agora.');
}

$historicoAnimal = [];

try {
    $stmtAlteracoes = $pdo->prepare("
        SELECT tipo_alteracao, descricao, created_at
        FROM animal_alteracoes
        WHERE animal_id = :id
        ORDER BY created_at DESC, id DESC
    ");
    $stmtAlteracoes->execute([':id' => $id]);

    foreach ($stmtAlteracoes->fetchAll(PDO::FETCH_ASSOC) as $alteracao) {
        $historicoAnimal[] = [
            'data' => substr((string) $alteracao['created_at'], 0, 10),
            'tipo' => ucfirst((string) $alteracao['tipo_alteracao']),
            'descricao' => (string) $alteracao['descricao'],
        ];
    }

    $stmtPesagens = $pdo->prepare("
        SELECT data_pesagem, peso_kg, observacao
        FROM pesagens
        WHERE animal_id = :id
        ORDER BY data_pesagem DESC, id DESC
    ");
    $stmtPesagens->execute([':id' => $id]);

    foreach ($stmtPesagens->fetchAll(PDO::FETCH_ASSOC) as $pesagem) {
        $historicoAnimal[] = [
            'data' => (string) $pesagem['data_pesagem'],
            'tipo' => 'Pesagem',
            'descricao' => 'Peso registrado: ' . number_format((float) $pesagem['peso_kg'], 2, ',', '.') . ' kg' . (!empty($pesagem['observacao']) ? ' - ' . $pesagem['observacao'] : ''),
        ];
    }

    $stmtManejos = $pdo->prepare("
        SELECT tipo, descricao, data_evento, proxima_data, status
        FROM manejos_sanitarios
        WHERE animal_id = :id
        ORDER BY data_evento DESC, id DESC
    ");
    $stmtManejos->execute([':id' => $id]);

    foreach ($stmtManejos->fetchAll(PDO::FETCH_ASSOC) as $manejo) {
        $detalhesManejo = trim((string) ($manejo['descricao'] ?? ''));

        if (!empty($manejo['status'])) {
            $detalhesManejo .= ($detalhesManejo !== '' ? ' - ' : '') . 'Status: ' . $manejo['status'];
        }

        if (!empty($manejo['proxima_data'])) {
            $detalhesManejo .= ($detalhesManejo !== '' ? ' - ' : '') . 'Próxima data: ' . formatarDataDetalhe($manejo['proxima_data']);
        }

        $historicoAnimal[] = [
            'data' => (string) $manejo['data_evento'],
            'tipo' => (string) $manejo['tipo'],
            'descricao' => $detalhesManejo !== '' ? $detalhesManejo : 'Manejo registrado.',
        ];
    }

    $stmtLeite = $pdo->prepare("
        SELECT data_producao, turno, litros, observacao
        FROM producao_leite
        WHERE animal_id = :id
        ORDER BY data_producao DESC, id DESC
    ");
    $stmtLeite->execute([':id' => $id]);

    foreach ($stmtLeite->fetchAll(PDO::FETCH_ASSOC) as $producao) {
        $historicoAnimal[] = [
            'data' => (string) $producao['data_producao'],
            'tipo' => 'Produção de leite',
            'descricao' => number_format((float) $producao['litros'], 2, ',', '.') . ' litros no turno ' . $producao['turno'] . (!empty($producao['observacao']) ? ' - ' . $producao['observacao'] : ''),
        ];
    }

    if ($vendaAnimal) {
        $historicoAnimal[] = [
            'data' => (string) $vendaAnimal['data_venda'],
            'tipo' => 'Venda',
            'descricao' => 'Venda para ' . ($vendaAnimal['parceiro_nome'] ?: $vendaAnimal['comprador_nome']) . ' por ' . ($vendaAnimal['valor'] !== null ? 'R$ ' . number_format((float) $vendaAnimal['valor'], 2, ',', '.') : 'valor não informado'),
        ];
    }

    if ($obitoAnimal) {
        $historicoAnimal[] = [
            'data' => (string) $obitoAnimal['data_obito'],
            'tipo' => 'Óbito',
            'descricao' => trim((string) (($obitoAnimal['causa'] ?? '') . (!empty($obitoAnimal['observacao']) ? ' - ' . $obitoAnimal['observacao'] : ''))) ?: 'Óbito registrado.',
        ];
    }

    usort($historicoAnimal, function ($a, $b) {
        return strcmp((string) $b['data'], (string) $a['data']);
    });
} catch (PDOException $e) {
    $historicoAnimal = [];
}

function textoSeguro($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function textoOuPadrao($valor, string $padrao = 'Não informado'): string
{
    if ($valor === null || $valor === '') {
        return $padrao;
    }

    return textoSeguro($valor);
}

function formatarDataDetalhe($data): string
{
    if ($data === null || $data === '') {
        return 'Não informado';
    }

    $timestamp = strtotime((string) $data);

    if ($timestamp === false) {
        return textoSeguro($data);
    }

    return date('d/m/Y', $timestamp);
}

$prenhaTexto = ((int) ($animal['prenha'] ?? 0) === 1) ? 'Sim' : 'Não';
$statusAnimal = $animal['status'] ?? 'Ativo';
$classeStatus = 'badge-sucesso';

if ($statusAnimal === 'Vendido') {
    $classeStatus = 'badge-alerta';
}

if ($statusAnimal === 'Óbito') {
    $classeStatus = 'badge-erro';
}

$maeTexto = $animal['nome_mae']
    ? textoSeguro($animal['nome_mae']) . ' (Brinco ' . textoSeguro($animal['brinco_mae']) . ')'
    : 'Não informado';

$paiTexto = $animal['nome_pai']
    ? textoSeguro($animal['nome_pai']) . ' (Brinco ' . textoSeguro($animal['brinco_pai']) . ')'
    : 'Não informado';

layoutInicio('Detalhes do animal');
?>

<div class="page-header">
    <h1>Detalhes do animal</h1>
    <p>Visualize os dados de identificação, genealogia e reprodução do animal cadastrado.</p>
</div>

<div class="top-actions">
    <a href="animais.php" class="btn-link">← Voltar</a>
    <a href="editar_animal.php?id=<?= (int) $animal['id'] ?>" class="btn-link">Editar animal</a>
</div>

<div class="animal-grid">
    <section class="panel">
        <h2>Identificação</h2>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Brinco</span>
                <span class="info-value"><?= textoSeguro($animal['brinco']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Nome / Apelido</span>
                <span class="info-value"><?= textoSeguro($animal['nome_apelido']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Raça</span>
                <span class="info-value"><?= textoSeguro($animal['raca']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Sexo</span>
                <span class="info-value"><?= textoSeguro($animal['sexo']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Nascimento</span>
                <span class="info-value"><?= formatarDataDetalhe($animal['data_nascimento']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Lote</span>
                <span class="info-value"><?= textoOuPadrao($animal['lote']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Situação</span>
                <span class="info-value">
                    <span class="badge <?= $classeStatus ?>">
                        <?= textoSeguro($statusAnimal) ?>
                    </span>
                </span>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Genealogia</h2>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Mãe</span>
                <span class="info-value"><?= $maeTexto ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Pai</span>
                <span class="info-value"><?= $paiTexto ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Número de crias</span>
                <span class="info-value"><?= $totalCrias ?></span>
            </div>
        </div>
    </section>

    <section class="panel">
        <h2>Reprodução</h2>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Último cio</span>
                <span class="info-value"><?= formatarDataDetalhe($animal['data_ultimo_cio']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Prenha</span>
                <span class="info-value">
                    <span class="badge <?= $prenhaTexto === 'Sim' ? 'badge-sucesso' : 'badge-erro' ?>">
                        <?= $prenhaTexto ?>
                    </span>
                </span>
            </div>
        </div>
    </section>

    <?php if ($vendaAnimal || $obitoAnimal): ?>
        <section class="panel">
            <h2>Baixa do animal</h2>

            <div class="info-list">
                <?php if ($vendaAnimal): ?>
                    <div class="info-item">
                        <span class="info-label">Comprador</span>
                        <span class="info-value"><?= textoSeguro($vendaAnimal['parceiro_nome'] ?: $vendaAnimal['comprador_nome']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Data da venda</span>
                        <span class="info-value"><?= formatarDataDetalhe($vendaAnimal['data_venda']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Valor</span>
                        <span class="info-value">
                            <?= $vendaAnimal['valor'] !== null ? 'R$ ' . number_format((float) $vendaAnimal['valor'], 2, ',', '.') : 'Não informado' ?>
                        </span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Observação</span>
                        <span class="info-value"><?= textoOuPadrao($vendaAnimal['observacao']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($obitoAnimal): ?>
                    <div class="info-item">
                        <span class="info-label">Data do óbito</span>
                        <span class="info-value"><?= formatarDataDetalhe($obitoAnimal['data_obito']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Causa</span>
                        <span class="info-value"><?= textoOuPadrao($obitoAnimal['causa']) ?></span>
                    </div>

                    <div class="info-item">
                        <span class="info-label">Observação</span>
                        <span class="info-value"><?= textoOuPadrao($obitoAnimal['observacao']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Histórico do animal</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historicoAnimal)): ?>
                        <tr>
                            <td colspan="3">Nenhum histórico registrado para este animal.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historicoAnimal as $itemHistorico): ?>
                            <tr>
                                <td><?= formatarDataDetalhe($itemHistorico['data']) ?></td>
                                <td><?= textoSeguro($itemHistorico['tipo']) ?></td>
                                <td><?= textoSeguro($itemHistorico['descricao']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php layoutFim(); ?>
