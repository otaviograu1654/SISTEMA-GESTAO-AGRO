<?php
require_once __DIR__ . '/includes/layout.php';
layoutInicio('Vacinação');
?>

<div class="page-header">
    <h1>Vacinação</h1>
    <p>Registro de aplicações e acompanhamento das próximas vacinações.</p>
</div>

<div class="cards">
    <div class="card">
        <h3>Aplicadas hoje</h3>
        <div class="value">0</div>
    </div>
    <div class="card">
        <h3>Próximas vacinações</h3>
        <div class="value">0</div>
    </div>
    <div class="card">
        <h3>Atrasadas</h3>
        <div class="value">0</div>
    </div>
</div>

<div class="grid-panels">
    <section class="panel">
        <h2>Registrar vacinação</h2>
        <p>Espaço para escolher animal, vacina, data da aplicação, próxima data e status.</p>
    </section>

    <section class="panel">
        <h2>Últimos registros</h2>
        <p>Tabela das vacinações mais recentes do sistema.</p>
    </section>
</div>

<?php layoutFim(); ?>
