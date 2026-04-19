<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/animal_auditoria.php';
require_once __DIR__ . '/includes/layout.php';

garantirEstruturaAuditoriaAnimal($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Animal inválido.');
}

$erro = '';
$sucesso = '';

function valorEdicao(string $chave, array $animal): string
{
    return htmlspecialchars((string) ($_POST[$chave] ?? $animal[$chave] ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatarDataPagina(?string $data): string
{
    if (!$data) {
        return 'Não informado';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    return date('d/m/Y', $timestamp);
}

function textoSeguro($valor, string $padrao = 'Não informado'): string
{
    if ($valor === null || $valor === '') {
        return $padrao;
    }

    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function carregarAnimalEdicao(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.brinco,
            a.nome_apelido,
            a.raca,
            a.sexo,
            a.data_nascimento,
            a.lote,
            a.mae_id,
            a.pai_id,
            a.data_ultimo_cio,
            a.prenha,
            mae.nome_apelido AS nome_mae,
            mae.brinco AS brinco_mae,
            pai.nome_apelido AS nome_pai,
            pai.brinco AS brinco_pai
        FROM animais a
        LEFT JOIN animais mae ON a.mae_id = mae.id
        LEFT JOIN animais pai ON a.pai_id = pai.id
        WHERE a.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);

    $animal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$animal) {
        die('Animal não encontrado.');
    }

    return $animal;
}

function carregarHistoricoReprodutivo(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("
        SELECT id, data_evento, tipo_evento, observacao, created_at
        FROM animal_historico_reprodutivo
        WHERE animal_id = :id
        ORDER BY data_evento DESC, id DESC
    ");
    $stmt->execute([':id' => $id]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$animal = carregarAnimalEdicao($pdo, $id);
$historicoReprodutivo = carregarHistoricoReprodutivo($pdo, $id);
$sexoAnimal = mb_strtolower((string) $animal['sexo'], 'UTF-8');
$ehFemea = in_array($sexoAnimal, ['fêmea', 'femea'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'salvar';

    if ($acao === 'excluir') {
        try {
            $pdo->beginTransaction();

            $dependencias = [];

            $stmtPesagens = $pdo->prepare("SELECT COUNT(*) FROM pesagens WHERE animal_id = :id");
            $stmtPesagens->execute([':id' => $id]);
            $totalPesagens = (int) $stmtPesagens->fetchColumn();

            if ($totalPesagens > 0) {
                $dependencias[] = $totalPesagens . ' pesagem(ns)';
            }

            $stmtManejos = $pdo->prepare("SELECT COUNT(*) FROM manejos_sanitarios WHERE animal_id = :id");
            $stmtManejos->execute([':id' => $id]);
            $totalManejos = (int) $stmtManejos->fetchColumn();

            if ($totalManejos > 0) {
                $dependencias[] = $totalManejos . ' manejo(s) sanitário(s)';
            }

            $stmtFilhos = $pdo->prepare("
                SELECT COUNT(*)
                FROM animais
                WHERE mae_id = :id OR pai_id = :id
            ");
            $stmtFilhos->execute([':id' => $id]);
            $totalFilhosRelacionados = (int) $stmtFilhos->fetchColumn();

            if ($totalFilhosRelacionados > 0) {
                $dependencias[] = $totalFilhosRelacionados . ' vínculo(s) de genealogia';
            }

            if (!empty($dependencias)) {
                $pdo->rollBack();
                $erro = 'Não foi possível excluir este animal porque ele já possui registros vinculados: ' . implode(', ', $dependencias) . '.';
            } else {
                registrarAlteracaoAnimal(
                    $pdo,
                    $id,
                    (string) $animal['brinco'],
                    (string) $animal['nome_apelido'],
                    'exclusao',
                    'Animal excluído do sistema.',
                    [
                        'raca' => $animal['raca'],
                        'sexo' => $animal['sexo'],
                    ]
                );

                $stmtDesvincularLog = $pdo->prepare("
                    UPDATE animal_alteracoes
                    SET animal_id = NULL
                    WHERE animal_id = :id
                ");
                $stmtDesvincularLog->execute([':id' => $id]);

                $stmtExcluirHistorico = $pdo->prepare("DELETE FROM animal_historico_reprodutivo WHERE animal_id = :id");
                $stmtExcluirHistorico->execute([':id' => $id]);

                $stmtExcluirAnimal = $pdo->prepare("DELETE FROM animais WHERE id = :id");
                $stmtExcluirAnimal->execute([':id' => $id]);

                $pdo->commit();

                header('Location: animais.php?status=animal_excluido');
                exit;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = 'Erro ao excluir animal: ' . $e->getMessage();
        }
    }

    if ($acao === 'salvar') {
        $brinco = trim($_POST['brinco'] ?? '');
        $nomeApelido = trim($_POST['nome_apelido'] ?? '');
        $dataNascimento = trim($_POST['data_nascimento'] ?? '');
        $lote = trim($_POST['lote'] ?? '');
        $dataUltimoCio = trim($_POST['data_ultimo_cio'] ?? '');
        $prenha = (($_POST['prenha'] ?? '0') === '1') ? 1 : 0;

        if ($brinco === '' || $nomeApelido === '') {
            $erro = 'Preencha os campos obrigatórios: brinco e nome/apelido.';
        }

        if (!$ehFemea) {
            $dataUltimoCio = '';
            $prenha = 0;
        }

        if ($erro === '') {
            try {
                $antes = $animal;

                $stmt = $pdo->prepare("
                    UPDATE animais
                    SET
                        brinco = :brinco,
                        nome_apelido = :nome_apelido,
                        data_nascimento = :data_nascimento,
                        lote = :lote,
                        data_ultimo_cio = :data_ultimo_cio,
                        prenha = :prenha
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':brinco' => $brinco,
                    ':nome_apelido' => $nomeApelido,
                    ':data_nascimento' => $dataNascimento !== '' ? $dataNascimento : null,
                    ':lote' => $lote !== '' ? $lote : null,
                    ':data_ultimo_cio' => $dataUltimoCio !== '' ? $dataUltimoCio : null,
                    ':prenha' => $prenha,
                    ':id' => $id,
                ]);

                $depois = [
                    'brinco' => $brinco,
                    'nome_apelido' => $nomeApelido,
                    'data_nascimento' => $dataNascimento,
                    'lote' => $lote,
                    'data_ultimo_cio' => $dataUltimoCio,
                    'prenha' => (string) $prenha,
                ];

                $mudancas = descreverMudancasAnimal($antes, $depois, [
                    'brinco' => 'Brinco',
                    'nome_apelido' => 'Nome / apelido',
                    'data_nascimento' => 'Data de nascimento',
                    'lote' => 'Lote',
                    'data_ultimo_cio' => 'Último cio',
                    'prenha' => 'Prenha',
                ]);

                if (!empty($mudancas)) {
                    registrarAlteracaoAnimal(
                        $pdo,
                        $id,
                        $brinco,
                        $nomeApelido,
                        'edicao',
                        'Dados operacionais do animal atualizados.',
                        ['mudancas' => $mudancas]
                    );
                }

                header('Location: editar_animal.php?id=' . $id . '&status=salvo');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $erro = 'Já existe um animal cadastrado com esse brinco.';
                } else {
                    $erro = 'Erro ao atualizar animal: ' . $e->getMessage();
                }
            }
        }
    }

    if ($acao === 'adicionar_historico') {
        if (!$ehFemea) {
            $erro = 'Animal é macho. O histórico de reprodução só pode ser lançado para fêmeas.';
        } else {
            $dataEvento = trim($_POST['hist_data_evento'] ?? '');
            $tipoEvento = trim($_POST['hist_tipo_evento'] ?? '');
            $observacao = trim($_POST['hist_observacao'] ?? '');

            if ($dataEvento === '' || $tipoEvento === '') {
                $erro = 'Informe a data e o tipo do evento reprodutivo.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO animal_historico_reprodutivo (
                            animal_id,
                            data_evento,
                            tipo_evento,
                            observacao
                        ) VALUES (
                            :animal_id,
                            :data_evento,
                            :tipo_evento,
                            :observacao
                        )
                    ");

                    $stmt->execute([
                        ':animal_id' => $id,
                        ':data_evento' => $dataEvento,
                        ':tipo_evento' => $tipoEvento,
                        ':observacao' => $observacao !== '' ? $observacao : null,
                    ]);

                    registrarAlteracaoAnimal(
                        $pdo,
                        $id,
                        (string) $animal['brinco'],
                        (string) $animal['nome_apelido'],
                        'reproducao',
                        'Histórico reprodutivo lançado para o animal.',
                        [
                            'data_evento' => $dataEvento,
                            'tipo_evento' => $tipoEvento,
                            'observacao' => $observacao !== '' ? $observacao : null,
                        ]
                    );

                    header('Location: editar_animal.php?id=' . $id . '&status=historico');
                    exit;
                } catch (PDOException $e) {
                    $erro = 'Erro ao registrar histórico reprodutivo: ' . $e->getMessage();
                }
            }
        }
    }
}

$animal = carregarAnimalEdicao($pdo, $id);
$historicoReprodutivo = carregarHistoricoReprodutivo($pdo, $id);
$sexoAnimal = mb_strtolower((string) $animal['sexo'], 'UTF-8');
$ehFemea = in_array($sexoAnimal, ['fêmea', 'femea'], true);

if (($_GET['status'] ?? '') === 'salvo') {
    $sucesso = 'Alterações salvas com sucesso.';
}

if (($_GET['status'] ?? '') === 'historico') {
    $sucesso = 'Histórico reprodutivo registrado com sucesso.';
}

$maeTexto = $animal['nome_mae']
    ? textoSeguro($animal['nome_mae']) . ' (Brinco ' . textoSeguro($animal['brinco_mae']) . ')'
    : 'Não informado';

$paiTexto = $animal['nome_pai']
    ? textoSeguro($animal['nome_pai']) . ' (Brinco ' . textoSeguro($animal['brinco_pai']) . ')'
    : 'Não informado';

layoutInicio('Editar animal');
?>

<style>
    .edit-grid {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 20px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .field.full {
        grid-column: 1 / -1;
    }

    .readonly-note {
        margin-top: 14px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #f6faf7;
        color: #35523f;
        font-size: 14px;
    }

    .danger-zone {
        border: 1px solid #f3d1d1;
        background: #fff8f8;
    }

    .danger-zone h2 {
        color: #b42318;
    }

    .danger-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 16px;
    }

    .danger-button {
        background: #b42318 !important;
    }

    .danger-button:hover {
        background: #8f1c13 !important;
    }

    .history-list {
        display: grid;
        gap: 12px;
    }

    .history-item {
        border: 1px solid #e6ece8;
        border-radius: 12px;
        padding: 14px 16px;
        background: #fafcfb;
    }

    .history-item strong {
        display: block;
        color: #1f7a3f;
        margin-bottom: 6px;
    }

    .history-meta {
        font-size: 13px;
        color: #667085;
        margin-bottom: 8px;
    }

    @media (max-width: 980px) {
        .edit-grid,
        .form-grid {
            grid-template-columns: 1fr;
        }

        .field.full {
            grid-column: auto;
        }
    }
</style>

<div class="page-header">
    <h1>Editar animal</h1>
    <p>Ajuste somente dados operacionais. Raça, sexo e genealogia ficam bloqueados nesta etapa.</p>
</div>

<div class="top-actions">
    <a href="animal.php?id=<?= $id ?>" class="btn-link">← Voltar aos detalhes</a>
    <a href="animais.php" class="btn-link secondary">Voltar à lista</a>
</div>

<?php if ($sucesso !== ''): ?>
    <div class="mensagem sucesso" style="display: block; margin-bottom: 16px;">
        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="edit-grid">
    <section class="panel">
        <h2>Dados editáveis</h2>
        <p>Use esta tela para corrigir cadastro operacional sem alterar a base genética do animal.</p>

        <form method="POST" action="" class="animal-form" style="display: block;">
            <input type="hidden" name="acao" value="salvar">

            <div class="form-grid">
                <div class="field">
                    <label for="brinco">Brinco *</label>
                    <input type="text" id="brinco" name="brinco" value="<?= valorEdicao('brinco', $animal) ?>" required>
                </div>

                <div class="field">
                    <label for="nome_apelido">Nome / Apelido *</label>
                    <input type="text" id="nome_apelido" name="nome_apelido" value="<?= valorEdicao('nome_apelido', $animal) ?>" required>
                </div>

                <div class="field">
                    <label for="data_nascimento">Data de nascimento</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= valorEdicao('data_nascimento', $animal) ?>">
                </div>

                <div class="field">
                    <label for="lote">Lote</label>
                    <input type="text" id="lote" name="lote" value="<?= valorEdicao('lote', $animal) ?>">
                </div>

                <?php if ($ehFemea): ?>
                    <div class="field">
                        <label for="data_ultimo_cio">Data do último cio</label>
                        <input type="date" id="data_ultimo_cio" name="data_ultimo_cio" value="<?= valorEdicao('data_ultimo_cio', $animal) ?>">
                    </div>

                    <div class="field">
                        <label for="prenha">Prenha?</label>
                        <select id="prenha" name="prenha">
                            <option value="0" <?= ((string) ($animal['prenha'] ?? '0') === '0') ? 'selected' : '' ?>>Não</option>
                            <option value="1" <?= ((string) ($animal['prenha'] ?? '0') === '1') ? 'selected' : '' ?>>Sim</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div class="readonly-note">
                Raça, sexo, mãe e pai não podem ser alterados nesta página para evitar mudanças estruturais indevidas no cadastro.
            </div>

            <div class="actions">
                <button type="submit">Salvar alterações</button>
                <a href="animal.php?id=<?= $id ?>" class="btn-link secondary">Cancelar</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Dados bloqueados</h2>
        <p>Esses campos ficam visíveis para conferência, mas sem edição nesta etapa.</p>

        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Raça</span>
                <span class="info-value"><?= textoSeguro($animal['raca']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Sexo</span>
                <span class="info-value"><?= textoSeguro($animal['sexo']) ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Mãe</span>
                <span class="info-value"><?= $maeTexto ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Pai</span>
                <span class="info-value"><?= $paiTexto ?></span>
            </div>

            <div class="info-item">
                <span class="info-label">Nascimento atual</span>
                <span class="info-value"><?= formatarDataPagina($animal['data_nascimento']) ?></span>
            </div>
        </div>
    </section>
</div>

<div class="grid-panels" style="margin-top: 24px;">
    <section class="panel">
        <h2>Histórico de reprodução</h2>
        <p>Espaço para registrar eventos reprodutivos até existir uma tela dedicada de reprodução.</p>

        <?php if (!$ehFemea): ?>
            <div class="mensagem erro" style="display: block;">
                Animal é macho. O histórico de reprodução só pode ser usado para fêmeas.
            </div>
        <?php else: ?>
            <form method="POST" action="" class="animal-form" style="display: block;">
                <input type="hidden" name="acao" value="adicionar_historico">

                <div class="form-grid">
                    <div class="field">
                        <label for="hist_data_evento">Data do evento *</label>
                        <input type="date" id="hist_data_evento" name="hist_data_evento" value="<?= htmlspecialchars($_POST['hist_data_evento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="field">
                        <label for="hist_tipo_evento">Tipo do evento *</label>
                        <select id="hist_tipo_evento" name="hist_tipo_evento">
                            <option value="">Selecione</option>
                            <?php
                            $tiposReproducao = ['Cio observado', 'Cobertura', 'Diagnóstico de prenhez', 'Parto', 'Aborto', 'Secagem', 'Observação'];
                            $tipoSelecionado = $_POST['hist_tipo_evento'] ?? '';
                            ?>
                            <?php foreach ($tiposReproducao as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>" <?= $tipoSelecionado === $tipo ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field full">
                        <label for="hist_observacao">Observação</label>
                        <textarea id="hist_observacao" name="hist_observacao" rows="3"><?= htmlspecialchars($_POST['hist_observacao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit">Adicionar histórico</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Lançamentos recentes</h2>
        <p>Registro dos eventos reprodutivos já vinculados ao animal.</p>

        <?php if (empty($historicoReprodutivo)): ?>
            <div class="empty">Nenhum histórico reprodutivo lançado ainda.</div>
        <?php else: ?>
            <div class="history-list">
                <?php foreach ($historicoReprodutivo as $evento): ?>
                    <div class="history-item">
                        <strong><?= htmlspecialchars($evento['tipo_evento'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <div class="history-meta">
                            Evento em <?= formatarDataPagina($evento['data_evento']) ?> · lançado em <?= formatarDataHoraAlteracao($evento['created_at']) ?>
                        </div>
                        <div><?= textoSeguro($evento['observacao'], 'Sem observação.') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<section class="panel danger-zone" style="margin-top: 24px;">
    <h2>Excluir animal</h2>
    <p>Use somente quando o cadastro tiver sido criado por engano. Se o animal já tiver pesagens, manejos ou vínculos de genealogia, a exclusão será bloqueada.</p>

    <form method="POST" action="" style="display: block;">
        <input type="hidden" name="acao" value="excluir">

        <div class="danger-actions">
            <button
                type="submit"
                class="danger-button"
                onclick="return confirm('Tem certeza que deseja excluir este animal? Esta ação remove o cadastro principal.')"
            >
                Excluir animal
            </button>
            <span class="help">O histórico de alterações será preservado no painel de controle.</span>
        </div>
    </form>
</section>

<?php layoutFim(); ?>
