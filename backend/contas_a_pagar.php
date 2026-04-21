<!--PAGINA SERVE SO PARA REGISTRAR PENDENCIAS -->
<?php
//PAGINA SERVE SO PARA REGISTRAR PENDENCIAS
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/layout.php';

$erroPagina = '';
$contas = [];

try {
    $stmt = $pdo->query("SELECT * FROM tabelacontas ORDER BY data_vencimento ASC");
    $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erroPagina = 'Não foi possível carregar as contas cadastradas.';
}

layoutInicio('Contas a pagar');
?>

<div class="page-header">
    <h1>Contas a pagar</h1>
    <p>Gestão de contas, vencimentos e prioridades financeiras da fazenda.</p>
</div>

<?php if ($erroPagina !== ''): ?>
    <div class="mensagem erro mensagem-bloco">
        <?= htmlspecialchars($erroPagina, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (($_GET['paga'] ?? '') === '1'): ?>
    <div class="mensagem sucesso mensagem-bloco">
        Conta marcada como paga.
    </div>
<?php endif; ?>

<?php if (($_GET['excluida'] ?? '') === '1'): ?>
    <div class="mensagem sucesso mensagem-bloco">
        Conta excluída com sucesso.
    </div>
<?php endif; ?>

<?php if (($_GET['ja_paga'] ?? '') === '1'): ?>
    <div class="mensagem erro mensagem-bloco">
        Esta conta já estava paga.
    </div>
<?php endif; ?>

<div class="grid-panels">
    <section class="panel">
        <h2>Nova conta</h2>

        <form action="salvar_conta.php" method="POST">
            <div class="form-group full-width">
                <label for="descricao">Descrição</label>
                <input type="text" id="descricao" name="descricao" required placeholder="Digite do que se trata a conta">
            </div>

            <div class="form-group">
                <label for="valor">Valor (R$)</label>
                <input type="number" id="valor" step="0.01" name="valor" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label for="data_vencimento">Data de vencimento</label>
                <input type="date" id="data_vencimento" name="data_vencimento" required>
            </div>

            <div class="form-group">
                <label for="natureza">Natureza</label>
                <input type="text" id="natureza" name="natureza" required placeholder="Ex: Insumos, Mão de obra">
            </div>

            <div class="form-group">
                <label for="prioridade">Prioridade</label>
                <select id="prioridade" name="prioridade">
                    <option value="baixa">Normal / Baixa</option>
                    <option value="media">Atenção / Média</option>
                    <option value="alta">Urgente / Alta</option>
                </select>
            </div>

            <div class="form-group full-width">
                <button type="submit">Salvar conta no sistema</button>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2>Contas lançadas</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Natureza</th>
                        <th>Vencimento</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contas)): ?>
                        <tr>
                            <td colspan="7">Nenhuma conta cadastrada ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contas as $conta): ?>
                            <?php
                            $dataFormatada = date('d/m/Y', strtotime($conta['data_vencimento']));
                            $valorFormatado = number_format((float) $conta['valor'], 2, ',', '.');
                            $classePrioridade = 'conta-prioridade-baixa';

                            if (($conta['prioridade'] ?? '') === 'media') {
                                $classePrioridade = 'conta-prioridade-media';
                            }

                            if (($conta['prioridade'] ?? '') === 'alta') {
                                $classePrioridade = 'conta-prioridade-alta';
                            }
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($conta['descricao']) ?></strong></td>
                                <td><?= htmlspecialchars($conta['natureza']) ?></td>
                                <td><?= $dataFormatada ?></td>
                                <td>
                                    <span class="badge <?= $classePrioridade ?>">
                                        <?= htmlspecialchars(strtoupper((string) $conta['prioridade'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= ($conta['status'] ?? '') === 'pago' ? 'badge-sucesso' : 'badge-alerta' ?>">
                                        <?= htmlspecialchars(ucfirst((string) ($conta['status'] ?? 'pendente'))) ?>
                                    </span>
                                </td>
                                <td class="valor-conta">R$ <?= $valorFormatado ?></td>
                                <td>
                                    <div class="acoes-conta">
                                        <?php if (($conta['status'] ?? '') !== 'pago'): ?>
                                            <a href="pagar_conta.php?id=<?= (int) $conta['id'] ?>" class="btn-link" onclick="return confirm('Marcar esta conta como paga?');">Pagar</a>
                                        <?php endif; ?>
                                        <a href="excluir_conta.php?id=<?= (int) $conta['id'] ?>" class="btn-link" onclick="return confirm('Tem certeza que deseja apagar?');">Excluir</a>
                                    </div>
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
