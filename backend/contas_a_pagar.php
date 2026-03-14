<?php
$conn = new mysqli("localhost","root","","sga_pecuaria");

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM tabelacontas ORDER BY data_vencimento ASC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - Contas a Pagar</title>
    <link rel="stylesheet" href="styles.css"> 
</head>
<body>

    <header class="topbar">
        <button id="btnMenu" class="btn-Menu">☰</button>
        <div class="titulo">
            <h2>SGA Pecuária</h2>
            <p>Fazenda Paraíso</p>
        </div>
    </header>
    <div id="overlay" class="overlay"></div>
    <div class="layout">
        
        <aside class="sidebar">
            <nav class="menu">
                <div class="menu-title">Principal</div>
                <a href="dashboard.php">Dashboard</a> 
                <a href="#animais">Animais</a>
                <a href="cadastro_animal.php">Cadastrar animal</a>

                <div class="menu-title">Módulos</div>
                <a href="#" class="disabled">Pesagens</a>
                
                <div class="menu-item">
                    <a href="#" class="menu-link" onclick="toggleSubMenu('submenu-financeiro', this); return false;" style="display: flex; justify-content: space-between; align-items: center;">
                        Financeiro
                        <span class="setinha">▾</span>
                    </a>
                    <ul id="submenu-financeiro" class="submenu" style="display: block;">
                        <li><a href="#" class="active" style="padding-left: 40px; font-size: 14px; opacity: 0.9;">Contas a Pagar</a></li>
                    </ul>
                </div>
                <a href="#" class="disabled">Relatórios</a>
            </nav>
        </aside>

        <main class="main">
            <div class="content">
                
                <div style="margin-bottom: 24px;">
                    <h1 style="margin: 0; font-size: 28px; color: #1f7a3f;">Financeiro</h1>
                    <p style="margin: 6px 0 0; color: #666; font-size: 14px;">Gestão de contas a pagar da fazenda</p>
                </div>

                <div class="panel">
                    <h2>Nova Conta</h2>
                    <form action="salvar_conta.php" method="POST">
                        <div class="form-group full-width">
                            <label>Descrição (Ex: Ração, Funcionários, Sementes)</label>
                            <input type="text" name="descricao" required placeholder="Digite do que se trata a conta">
                        </div>

                        <div class="form-group">
                            <label>Valor (R$)</label>
                            <input type="number" step="0.01" name="valor" required placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label>Data de vencimento</label>
                            <input type="date" name="data_vencimento" required>
                        </div>

                        <div class="form-group">
                            <label>Natureza (Ex: Insumos, Mão de obra)</label>
                            <input type="text" name="natureza" required>
                        </div>

                        <div class="form-group">
                            <label>Prioridade</label>
                            <select name="prioridade">
                                <option value="baixa">🟢 Normal / Baixa</option>
                                <option value="media">🟡 Atenção / Média</option>
                                <option value="alta">🔴 Urgente / Alta</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit">Salvar Conta no Sistema</button>
                        </div>
                    </form>
                </div>

                <div class="panel">
                    <h2>Contas Lançadas</h2>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Natureza</th>
                                    <th>Vencimento</th>
                                    <th>Prioridade</th>
                                    <th>Valor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) { 
                                        $data_formatada = date('d/m/Y', strtotime($row['data_vencimento']));
                                        $valor_formatado = number_format($row['valor'], 2, ',', '.');
                                        
                                        $cor_badge = "badge-baixa";
                                        if($row['prioridade'] == 'alta') $cor_badge = "badge-alta";
                                        if($row['prioridade'] == 'media') $cor_badge = "badge-media";
                                ?>
                                    <tr>
                                        <td style="font-weight: bold;"><?= htmlspecialchars($row['descricao']) ?></td>
                                        <td><?= htmlspecialchars($row['natureza']) ?></td>
                                        <td><?= $data_formatada ?></td>
                                        <td><span class="badge <?= $cor_badge ?>"><?= strtoupper($row['prioridade']) ?></span></td>
                                        <td style="color: #b42318; font-weight: bold;">R$ <?= $valor_formatado ?></td>
                                        <td>
                                            <a href="#" class="btn-acao btn-pagar">✔ Pagar</a>
                                            <a href="excluir_conta.php?id=<?= $row['id'] ?>" class="btn-acao btn-excluir" onclick="return confirm('Tem certeza que deseja apagar?');">🗑️ Excluir</a>
                                        </td>
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #777;">Nenhuma conta cadastrada ainda.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        function toggleSubMenu(idSubmenu, elementoLink) {
            const submenu = document.getElementById(idSubmenu);
            const setinha = elementoLink.querySelector('.setinha');

            if (submenu.style.display === "none" || submenu.style.display === "") {
                submenu.style.display = "block";
                setinha.classList.add("girar");
            } else {
                submenu.style.display = "none";
                setinha.classList.remove("girar");
            }
        }

        const btnMenu = document.getElementById('btnMenu');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        btnMenu.addEventListener('click', function() {
            sidebar.classList.toggle('aberto');
            overlay.classList.toggle('ativo');
        });
        overlay.addEventListener('click', function(){
            sidebar.classList.remove('aberto');
            overlay.classList.remove('ativo');
        })
    </script>
</body>
</html>