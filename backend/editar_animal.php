<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/animal_auditoria.php';
require_once __DIR__ . '/includes/layout.php';

garantirTabelaAuditoriaAnimal($pdo);
garantirStatusAnimal($pdo);
garantirBaixasAnimal($pdo);

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

function textoSeguro($valor, string $padrao = 'Não informado'): string
{
    if ($valor === null || $valor === '') {
        return $padrao;
    }

    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function formatarDataTela($data): string
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
} catch (PDOException $e) {
    die('Erro ao buscar animal: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? 'salvar';

    if ($acao === 'salvar') {
        $brinco = trim($_POST['brinco'] ?? '');
        $nome_apelido = trim($_POST['nome_apelido'] ?? '');
        $data_nascimento = trim($_POST['data_nascimento'] ?? '');
        $lote = trim($_POST['lote'] ?? '');
        $data_ultimo_cio = trim($_POST['data_ultimo_cio'] ?? '');
        $prenha = trim($_POST['prenha'] ?? '0');

        if ($brinco === '' || $nome_apelido === '') {
            $erro = 'Preencha os campos obrigatórios: brinco e nome/apelido.';
        }

        if (($animal['sexo'] ?? '') !== 'Fêmea') {
            $data_ultimo_cio = '';
            $prenha = '0';
        }

        if ($erro === '') {
            try {
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
                    ':nome_apelido' => $nome_apelido,
                    ':data_nascimento' => $data_nascimento !== '' ? $data_nascimento : null,
                    ':lote' => $lote !== '' ? $lote : null,
                    ':data_ultimo_cio' => $data_ultimo_cio !== '' ? $data_ultimo_cio : null,
                    ':prenha' => ($prenha === '1') ? 1 : 0,
                    ':id' => $id,
                ]);

                registrarAlteracaoAnimal(
                    $pdo,
                    $id,
                    $brinco,
                    $nome_apelido,
                    'edicao',
                    'Dados do animal atualizados.'
                );

                header('Location: editar_animal.php?id=' . $id . '&sucesso=1');
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

    if ($acao === 'registrar_venda') {
        $comprador_nome = trim($_POST['comprador_nome'] ?? '');
        $data_venda = trim($_POST['data_venda'] ?? '');
        $valor_venda = trim($_POST['valor_venda'] ?? '');
        $observacao_venda = trim($_POST['observacao_venda'] ?? '');

        if (($animal['status'] ?? 'Ativo') === 'Vendido') {
            $erro = 'Animal já foi vendido.';
        }

        if (($animal['status'] ?? 'Ativo') === 'Óbito') {
            $erro = 'Animal foi dado como óbito.';
        }

        if ($erro === '' && ($comprador_nome === '' || $data_venda === '')) {
            $erro = 'Informe comprador e data da venda.';
        }

        if ($erro === '' && ($valor_venda === '' || (float) str_replace(',', '.', $valor_venda) <= 0)) {
            $erro = 'Informe o valor da venda.';
        }

        if ($erro === '') {
            try {
                $valorBanco = (float) str_replace(',', '.', $valor_venda);
                $pdo->beginTransaction();

                $stmtVenda = $pdo->prepare("
                    INSERT INTO animal_vendas (
                        animal_id,
                        comprador_nome,
                        data_venda,
                        valor,
                        observacao
                    ) VALUES (
                        :animal_id,
                        :comprador_nome,
                        :data_venda,
                        :valor,
                        :observacao
                    )
                ");

                $stmtVenda->execute([
                    ':animal_id' => $id,
                    ':comprador_nome' => $comprador_nome,
                    ':data_venda' => $data_venda,
                    ':valor' => $valorBanco,
                    ':observacao' => $observacao_venda !== '' ? $observacao_venda : null,
                ]);

                $stmt = $pdo->prepare("UPDATE animais SET status = 'Vendido' WHERE id = :id");
                $stmt->execute([':id' => $id]);

                $stmtFinanceiro = $pdo->prepare("
                    INSERT INTO financeiro (
                        tipo,
                        categoria,
                        descricao,
                        valor,
                        data_lancamento
                    ) VALUES (
                        :tipo,
                        :categoria,
                        :descricao,
                        :valor,
                        :data_lancamento
                    )
                ");

                $stmtFinanceiro->execute([
                    ':tipo' => 'Receita',
                    ':categoria' => 'Venda de animais',
                    ':descricao' => 'Venda do animal ' . $animal['nome_apelido'] . ' / brinco ' . $animal['brinco'] . ' para ' . $comprador_nome,
                    ':valor' => $valorBanco,
                    ':data_lancamento' => $data_venda,
                ]);

                registrarAlteracaoAnimal(
                    $pdo,
                    $id,
                    (string) $animal['brinco'],
                    (string) $animal['nome_apelido'],
                    'venda',
                    'Animal vendido para ' . $comprador_nome . '.'
                );

                $pdo->commit();

                header('Location: editar_animal.php?id=' . $id . '&sucesso=status');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erro = 'Erro ao registrar venda: ' . $e->getMessage();
            }
        }
    }

    if ($acao === 'registrar_obito') {
        $data_obito = trim($_POST['data_obito'] ?? '');
        $causa_obito = trim($_POST['causa_obito'] ?? '');
        $observacao_obito = trim($_POST['observacao_obito'] ?? '');

        if (($animal['status'] ?? 'Ativo') === 'Vendido') {
            $erro = 'Animal já foi vendido.';
        }

        if (($animal['status'] ?? 'Ativo') === 'Óbito') {
            $erro = 'Animal foi dado como óbito.';
        }

        if ($erro === '' && $data_obito === '') {
            $erro = 'Informe a data do óbito.';
        }

        if ($erro === '') {
            try {
                $stmtObito = $pdo->prepare("
                    INSERT INTO animal_obitos (
                        animal_id,
                        data_obito,
                        causa,
                        observacao
                    ) VALUES (
                        :animal_id,
                        :data_obito,
                        :causa,
                        :observacao
                    )
                ");

                $stmtObito->execute([
                    ':animal_id' => $id,
                    ':data_obito' => $data_obito,
                    ':causa' => $causa_obito !== '' ? $causa_obito : null,
                    ':observacao' => $observacao_obito !== '' ? $observacao_obito : null,
                ]);

                $stmt = $pdo->prepare("UPDATE animais SET status = 'Óbito' WHERE id = :id");
                $stmt->execute([':id' => $id]);

                registrarAlteracaoAnimal(
                    $pdo,
                    $id,
                    (string) $animal['brinco'],
                    (string) $animal['nome_apelido'],
                    'obito',
                    'Óbito do animal registrado.'
                );

                header('Location: editar_animal.php?id=' . $id . '&sucesso=status');
                exit;
            } catch (PDOException $e) {
                $erro = 'Erro ao registrar óbito: ' . $e->getMessage();
            }
        }
    }

    if ($acao === 'excluir') {
        try {
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

            $stmtVendas = $pdo->prepare("SELECT COUNT(*) FROM animal_vendas WHERE animal_id = :id");
            $stmtVendas->execute([':id' => $id]);
            $totalVendas = (int) $stmtVendas->fetchColumn();

            if ($totalVendas > 0) {
                $dependencias[] = $totalVendas . ' venda(s)';
            }

            $stmtObitos = $pdo->prepare("SELECT COUNT(*) FROM animal_obitos WHERE animal_id = :id");
            $stmtObitos->execute([':id' => $id]);
            $totalObitos = (int) $stmtObitos->fetchColumn();

            if ($totalObitos > 0) {
                $dependencias[] = $totalObitos . ' registro(s) de óbito';
            }

            $stmtFilhos = $pdo->prepare("
                SELECT COUNT(*)
                FROM animais
                WHERE mae_id = :id OR pai_id = :id
            ");
            $stmtFilhos->execute([':id' => $id]);
            $totalFilhos = (int) $stmtFilhos->fetchColumn();

            if ($totalFilhos > 0) {
                $dependencias[] = $totalFilhos . ' vínculo(s) de genealogia';
            }

            if (!empty($dependencias)) {
                $erro = 'Não foi possível excluir este animal porque ele já possui registros vinculados: ' . implode(', ', $dependencias) . '.';
            } else {
                registrarAlteracaoAnimal(
                    $pdo,
                    $id,
                    (string) $animal['brinco'],
                    (string) $animal['nome_apelido'],
                    'exclusao',
                    'Animal excluído do sistema.'
                );

                $stmtDesvincular = $pdo->prepare("UPDATE animal_alteracoes SET animal_id = NULL WHERE animal_id = :id");
                $stmtDesvincular->execute([':id' => $id]);

                $stmtExcluir = $pdo->prepare("DELETE FROM animais WHERE id = :id");
                $stmtExcluir->execute([':id' => $id]);

                header('Location: animais.php?excluido=1');
                exit;
            }
        } catch (PDOException $e) {
            $erro = 'Erro ao excluir animal: ' . $e->getMessage();
        }
    }
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro ao recarregar animal: ' . $e->getMessage());
}

if (($_GET['sucesso'] ?? '') === '1') {
    $sucesso = 'Alterações salvas com sucesso.';
}

if (($_GET['sucesso'] ?? '') === 'status') {
    $sucesso = 'Situação do animal atualizada com sucesso.';
}

$maeTexto = $animal['nome_mae']
    ? textoSeguro($animal['nome_mae']) . ' (Brinco ' . textoSeguro($animal['brinco_mae']) . ')'
    : 'Não informado';

$paiTexto = $animal['nome_pai']
    ? textoSeguro($animal['nome_pai']) . ' (Brinco ' . textoSeguro($animal['brinco_pai']) . ')'
    : 'Não informado';

$ehFemea = (($animal['sexo'] ?? '') === 'Fêmea');

layoutInicio('Editar animal');
?>

<style>
    .animal-form-edicao {
        display: block !important;
    }

    .edicao-grid {
        display: grid;
        gap: 20px;
    }

    .campo-bloqueado label::after {
        content: ' bloqueado';
        margin-left: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
    }

    .campo-bloqueado input {
        background: #f4f6f8;
        color: #5f6b76;
        cursor: not-allowed;
    }

    .campo-bloqueado input:focus {
        border-color: #d5dadd;
        box-shadow: none;
    }

    .edit-bottom-grid {
        grid-template-columns: 1fr;
        align-items: start;
    }

    .danger-zone {
        max-width: none;
        justify-self: start;
    }

    .baixa-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(180px, 1fr));
        gap: 14px;
        margin-top: 14px;
    }

    .baixa-card {
        border: 1px solid #e4e9e6;
        border-radius: 14px;
        padding: 14px;
        background: #fbfcfb;
    }

    .baixa-card h3 {
        margin-bottom: 8px;
        color: #1f7a3f;
    }

    .baixa-card p {
        min-height: 48px;
        margin-bottom: 12px;
        font-size: 13px;
        line-height: 1.45;
        color: #555;
    }

    .baixa-card form,
    .baixa-card button {
        width: 100%;
    }

    .danger-zone h2 {
        color: #1f7a3f;
    }

    .danger-zone p {
        font-size: 13px;
        line-height: 1.45;
    }

    .danger-button {
        background: #b42318 !important;
        padding: 10px 14px !important;
        font-size: 14px !important;
        width: 100% !important;
        min-width: 0 !important;
        border-radius: 10px !important;
    }

    .danger-button:hover {
        background: #8f1c13 !important;
    }

    .baixa-button {
        background: #1f7a3f !important;
        color: white !important;
        padding: 10px 14px !important;
        font-size: 14px !important;
        width: 100% !important;
        min-width: 0 !important;
        border-radius: 10px !important;
    }

    .baixa-button:hover {
        background: #176331 !important;
    }

    .animal-form-edicao .actions {
        display: flex;
        gap: 12px;
        margin-top: 18px;
        align-items: center;
    }

    .animal-form-edicao .actions button,
    .animal-form-edicao .actions .btn-link {
        width: auto !important;
        min-width: 0 !important;
    }

    .animal-form-edicao .actions .btn-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .danger-zone .actions {
        margin-top: 12px;
        justify-content: flex-start;
    }

    .danger-zone form {
        display: block;
    }

    .janela-baixa {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 20;
        background: rgba(0, 0, 0, 0.28);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .janela-baixa.aberta {
        display: flex;
    }

    .janela-conteudo {
        width: min(560px, 100%);
        background: white;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18);
    }

    .janela-conteudo h2 {
        margin-bottom: 8px;
    }

    .janela-conteudo form {
        margin-top: 16px;
    }

    @media (max-width: 980px) {
        .edit-bottom-grid {
            grid-template-columns: 1fr;
        }

        .danger-zone {
            max-width: none;
            justify-self: stretch;
        }

        .baixa-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <h1>Editar animal</h1>
    <p>Altere só os dados operacionais do cadastro sem mexer em raça, sexo e genealogia.</p>
</div>

<div class="top-actions">
    <a href="animal.php?id=<?= (int) $animal['id'] ?>" class="btn-link">← Voltar aos detalhes</a>
    <a href="animais.php" class="btn-link secondary">Voltar à lista</a>
</div>

<?php if ($erro !== ''): ?>
    <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($sucesso !== ''): ?>
    <div class="mensagem sucesso mensagem-bloco">
        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="edicao-grid">
    <section class="panel">
        <h2>Dados do animal</h2>
        <p>Atualize os dados do cadastro e confira aqui os campos que ficam travados nesta tela.</p>

        <form method="POST" action="" class="animal-form animal-form-edicao">
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

                <div class="field campo-bloqueado">
                    <label for="raca_bloqueada">Raça</label>
                    <input type="text" id="raca_bloqueada" value="<?= textoSeguro($animal['raca']) ?>" disabled>
                </div>

                <div class="field campo-bloqueado">
                    <label for="sexo_bloqueado">Sexo</label>
                    <input type="text" id="sexo_bloqueado" value="<?= textoSeguro($animal['sexo']) ?>" disabled>
                </div>

                <div class="field campo-bloqueado">
                    <label for="mae_bloqueada">Mãe</label>
                    <input type="text" id="mae_bloqueada" value="<?= $maeTexto ?>" disabled>
                </div>

                <div class="field campo-bloqueado">
                    <label for="pai_bloqueado">Pai</label>
                    <input type="text" id="pai_bloqueado" value="<?= $paiTexto ?>" disabled>
                </div>

                <div class="field campo-bloqueado">
                    <label for="status_bloqueado">Situação</label>
                    <input type="text" id="status_bloqueado" value="<?= textoSeguro($animal['status'] ?? 'Ativo') ?>" disabled>
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

            <div class="actions">
                <button type="submit">Salvar alterações</button>
                <a href="animal.php?id=<?= (int) $animal['id'] ?>" class="btn-link secondary">Cancelar</a>
            </div>
        </form>
    </section>
</div>

<div class="grid-panels panel-spaced edit-bottom-grid">
    <section class="panel">
        <h2>Reprodução</h2>

        <?php if ($ehFemea): ?>
            <p>Este animal é fêmea. A página própria de reprodução ainda pode ser criada depois para lançar inseminação, cobertura e outros eventos.</p>
        <?php else: ?>
            <div class="mensagem erro mensagem-bloco">
                Animal é macho. O histórico de reprodução deve ser usado somente para fêmeas.
            </div>
        <?php endif; ?>
    </section>

    <section class="panel danger-zone">
        <h2>Baixa do animal</h2>
        <p>Use venda ou óbito para manter o histórico. Exclua só se o cadastro foi criado por engano.</p>

        <div class="baixa-grid">
            <div class="baixa-card">
                <h3>Venda</h3>
                <p>Registra comprador, data e valor da venda.</p>
                <button type="button" class="baixa-button" onclick="abrirJanela('janelaVenda')">
                    Registrar venda
                </button>
            </div>

            <div class="baixa-card">
                <h3>Óbito</h3>
                <p>Registra data, causa e observação do ocorrido.</p>
                <button type="button" class="baixa-button" onclick="abrirJanela('janelaObito')">
                    Registrar óbito
                </button>
            </div>

            <div class="baixa-card">
                <h3>Excluir</h3>
                <p>Use somente quando o cadastro foi criado errado.</p>
                <form method="POST" action="" class="animal-form">
                    <input type="hidden" name="acao" value="excluir">

                    <button
                        type="submit"
                        class="danger-button"
                        onclick="return confirm('Tem certeza que deseja excluir este animal?')"
                    >
                        Excluir animal
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

<div class="janela-baixa" id="janelaVenda">
    <div class="janela-conteudo">
        <h2>Registrar venda</h2>
        <p>Informe os dados principais da venda deste animal.</p>

        <form method="POST" action="" class="animal-form animal-form-edicao">
            <input type="hidden" name="acao" value="registrar_venda">

            <div class="form-grid">
                <div class="field">
                    <label for="comprador_nome">Comprador *</label>
                    <input type="text" id="comprador_nome" name="comprador_nome" required>
                </div>

                <div class="field">
                    <label for="data_venda">Data da venda *</label>
                    <input type="date" id="data_venda" name="data_venda" required>
                </div>

                <div class="field">
                    <label for="valor_venda">Valor (R$)</label>
                    <input type="number" step="0.01" id="valor_venda" name="valor_venda" required>
                </div>

                <div class="field full-width">
                    <label for="observacao_venda">Observação</label>
                    <input type="text" id="observacao_venda" name="observacao_venda">
                </div>
            </div>

            <div class="actions">
                <button type="submit">Salvar venda</button>
                <button type="button" class="btn-link secondary" onclick="fecharJanela('janelaVenda')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="janela-baixa" id="janelaObito">
    <div class="janela-conteudo">
        <h2>Registrar óbito</h2>
        <p>Informe os dados principais do ocorrido.</p>

        <form method="POST" action="" class="animal-form animal-form-edicao">
            <input type="hidden" name="acao" value="registrar_obito">

            <div class="form-grid">
                <div class="field">
                    <label for="data_obito">Data do óbito *</label>
                    <input type="date" id="data_obito" name="data_obito" required>
                </div>

                <div class="field">
                    <label for="causa_obito">Causa</label>
                    <input type="text" id="causa_obito" name="causa_obito">
                </div>

                <div class="field full-width">
                    <label for="observacao_obito">Observação</label>
                    <input type="text" id="observacao_obito" name="observacao_obito">
                </div>
            </div>

            <div class="actions">
                <button type="submit">Salvar óbito</button>
                <button type="button" class="btn-link secondary" onclick="fecharJanela('janelaObito')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirJanela(id) {
        const janela = document.getElementById(id);

        if (janela) {
            janela.classList.add('aberta');
        }
    }

    function fecharJanela(id) {
        const janela = document.getElementById(id);

        if (janela) {
            janela.classList.remove('aberta');
        }
    }
</script>

<?php layoutFim(); ?>
