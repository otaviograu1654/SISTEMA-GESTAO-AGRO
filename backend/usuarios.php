<?php
$pageTitle = 'SGA Pecuária - Usuários';
$usuarios = [
    ['nome' => 'Administrador', 'email' => 'admin@sga.local', 'perfil' => 'Administrador', 'status' => 'Ativo'],
    ['nome' => 'Operador de Campo', 'email' => 'campo@sga.local', 'perfil' => 'Operador', 'status' => 'Ativo'],
    ['nome' => 'Financeiro', 'email' => 'financeiro@sga.local', 'perfil' => 'Financeiro', 'status' => 'Ativo'],
];
include __DIR__ . '/includes/page_start.php';
?>
<div class="page-header"><div><h1>Usuários</h1><p>Gestão simples de acessos e perfis de uso do sistema.</p></div></div>
<div class="grid-resumo"><div class="card-resumo"><div class="label">Usuários ativos</div><div class="valor">3</div></div><div class="card-resumo"><div class="label">Perfis</div><div class="valor">3</div></div></div>
<section class="panel"><h2>Novo usuário</h2><form action="#" method="post"><div class="form-grid"><div class="form-group"><label>Nome</label><input type="text"></div><div class="form-group"><label>E-mail</label><input type="email"></div><div class="form-group"><label>Perfil</label><select><option>Administrador</option><option>Operador</option><option>Financeiro</option></select></div><div class="form-group"><label>Status</label><select><option>Ativo</option><option>Inativo</option></select></div><div class="form-group full-width"><button type="submit">Salvar usuário</button></div></div></form></section>
<section class="panel"><h2>Usuários cadastrados</h2><div class="table-wrapper"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th></tr></thead><tbody><?php foreach ($usuarios as $usuario): ?><tr><td><?= htmlspecialchars($usuario['nome']) ?></td><td><?= htmlspecialchars($usuario['email']) ?></td><td><?= htmlspecialchars($usuario['perfil']) ?></td><td><span class="badge-status badge-verde"><?= htmlspecialchars($usuario['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php include __DIR__ . '/includes/page_end.php'; ?>
