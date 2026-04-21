<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/animal_auditoria.php';
require_once __DIR__ . '/includes/layout.php';

garantirStatusAnimal($pdo);
garantirBaixasAnimal($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Animal inválido.');
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
        die('Animal não encontrado.');
    }

    $stmtCrias = $pdo->prepare("
        SELECT COUNT(*)
        FROM animais
        WHERE mae_id = :id
    ");
    $stmtCrias->execute([':id' => $id]);
    $totalCrias = (int) $stmtCrias->fetchColumn();

    $stmtVenda = $pdo->prepare("
        SELECT *
        FROM animal_vendas
        WHERE animal_id = :id
        ORDER BY data_venda DESC, id DESC
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
    die('Erro ao buscar animal: ' . $e->getMessage());
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
                        <span class="info-value"><?= textoSeguro($vendaAnimal['comprador_nome']) ?></span>
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
</div>

<?php layoutFim(); ?>
