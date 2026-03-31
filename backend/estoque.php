<?php
require_once __DIR__ . '/includes/layout.php';
layoutInicio('Estoque');
?>

<div class="page-header">
    <h1>Estoque</h1>
    <p>Controle de produtos, vacinas, insumos e entradas.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Total de itens</h3>
        <div class="value">0</div>
    </div>
    <div class="card">
        <h3>Vacinas no estoque</h3>
        <div class="value">0</div>
    </div>
    <div class="card">
        <h3>Itens vencendo</h3>
        <div class="value">0</div>
    </div>
</div>

<div class="panel">
    <h2>Produtos</h2>
    <p>Campos esperados: nome, código, preço de custo, entrada no estoque, unidade, lote, validade e data da entrada.</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Preço de custo</th>
                    <th>Entrada</th>
                    <th>Unidade</th>
                    <th>Lote</th>
                    <th>Validade</th>
                    <th>Data da entrada</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="8">Nenhum produto cadastrado.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php layoutFim(); ?>
