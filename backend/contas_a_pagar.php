<?php
// Sua conexão e consulta originais, usando a tabelacontas
$conn = new mysqli("localhost","root","","sga_pecuaria");

// Verifica a conexão para evitar erros silenciosos
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
    <style>
        
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f8; color: #222; }
        .layout { display: flex; min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { width: 240px; background: linear-gradient(180deg, #264d2f, #1f3f27); color: white; padding: 20px 0; flex-shrink: 0; }
        .sidebar .logo { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.12); }
        .sidebar .logo h2 { margin: 0; font-size: 22px; }
        .sidebar .logo p { margin: 6px 0 0; font-size: 13px; opacity: 0.8; }
        .menu { margin-top: 20px; }
        .menu-title { padding: 10px 20px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.65; }
        .menu a { display: block; padding: 12px 20px; color: white; text-decoration: none; transition: background 0.2s ease; border-left: 4px solid transparent; }
        .menu a:hover, .menu a.active { background: rgba(255,255,255,0.08); border-left-color: #66d18f; }
        .menu .disabled { opacity: 0.55; cursor: default; }
        
        /* ESTILOS DO SUBMENU */
        .submenu { display: none; list-style: none; padding: 0; margin: 0; background: rgba(0, 0, 0, 0.15); }
        .submenu li a { border-left: 4px solid transparent; }
        .submenu li a:hover { border-left-color: #66d18f; background: rgba(255,255,255,0.05); }
        .setinha.girar { transform: rotate(180deg); }

        /* MAIN CONTENT */
        .main { flex: 1; display: flex; flex-direction: column; }
        .topbar { background: white; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .topbar h1 { margin: 0; font-size: 28px; color: #1f7a3f; }
        .topbar p { margin: 6px 0 0; color: #666; font-size: 14px; }
        .content { max-width: 1200px; width: 100%; padding: 24px; }
        
        /* PANELS E TABELAS */
        .panel { background: white; border-radius: 14px; padding: 20px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); margin-bottom: 24px;}
        .panel h2 { margin-top: 0; margin-bottom: 20px; font-size: 22px; color: #1f7a3f; border-bottom: 2px solid #f0f7f2; padding-bottom: 10px;}
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e7e7e7; }
        th { background: #f0f7f2; color: #1f7a3f; }
        tr:hover { background: #fafafa; }
        
        /* FORMULÁRIOS */
        form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: span 2; }
        label { font-size: 14px; font-weight: bold; color: #444; margin-bottom: 6px;}
        input, select { width: 100%; padding: 10px 12px; border: 1px solid #d8d8d8; border-radius: 10px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #2fa35a; box-shadow: 0 0 0 3px rgba(47,163,90,0.12); }
        button { border: none; border-radius: 10px; padding: 12px 16px; background: #1f7a3f; color: white; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s ease; margin-top: 10px;}
        button:hover { background: #186232; }
        
        /* STATUS TAGS */
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; }
        .badge-alta { background: #fdeaea; color: #b42318; }
        .badge-media { background: #fef0c7; color: #b54708; }
        .badge-baixa { background: #e7f6ec; color: #1f7a3f; }

        @media (max-width: 900px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; }
            form { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="logo">
                <h2>SGA Pecuária</h2>
                <p>Fazenda Paraíso</p>
            </div>
            <nav class="menu">
                <div class="menu-title">Principal</div>
                <a href="dashboard.php">Dashboard</a> <a href="#animais">Animais</a>
                <a href="cadastro_animal.php">Cadastrar animal</a>

                <div class="menu-title">Módulos</div>
                <a href="#" class="disabled">Pesagens</a>
                
                <div class="menu-item">
                    <a href="#" class="menu-link" onclick="toggleSubMenu('submenu-financeiro', this); return false;" style="display: flex; justify-content: space-between; align-items: center;">
                        Financeiro
                        <span class="setinha girar" style="transition: transform 0.3s ease;">▾</span>
                    </a>
                    <ul id="submenu-financeiro" class="submenu" style="display: block;">
                        <li><a href="#" class="active" style="padding-left: 40px; font-size: 14px; opacity: 0.9;">Contas a Pagar</a></li>
                    </ul>
                </div>
                <a href="#" class="disabled">Relatórios</a>
            </nav>
        </aside>

        <main class="main">
            <div class="topbar">
                <h1>Financeiro</h1>
                <p>Gestão de contas a pagar da fazenda</p>
            </div>

            <div class="content">
                
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Verifica se tem resultados no banco
                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) { 
                                        
                                        // Formata a data para o padrão brasileiro (dia/mês/ano)
                                        $data_formatada = date('d/m/Y', strtotime($row['data_vencimento']));
                                        
                                        // Formata o valor com vírgula e ponto
                                        $valor_formatado = number_format($row['valor'], 2, ',', '.');
                                        
                                        // Cria uma tag bonita para a prioridade baseada na palavra
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
                                    </tr>
                                <?php 
                                    } 
                                } else {
                                ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #777;">Nenhuma conta cadastrada ainda.</td>
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
        // Mantive a função do menu expansível caso precise fechar
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
    </script>
</body>
</html>