<?php
require_once 'db.php';

$erro = '';

function valorAntigo(string $chave, string $padrao = ''): string
{
    return htmlspecialchars($_POST[$chave] ?? $padrao, ENT_QUOTES, 'UTF-8');
}

function selecionado(string $chave, string $valor): string
{
    return (($_POST[$chave] ?? '') === $valor) ? 'selected' : '';
}

/* resto da lógica PHP aqui */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Animal</title>

    <link rel="stylesheet" href="styles.css">
    
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        .page-content {
            flex: 1;
            padding: 24px;
        }

        .header {
            background: linear-gradient(135deg, #1f7a3f, #2fa35a);
            color: white;
            padding: 24px;
            border-radius: 14px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header p {
            margin: 8px 0 0;
            opacity: 0.95;
        }

        .container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px 32px;
        }

        .top-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-link {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            background: white;
            color: #1f7a3f;
            border: 1px solid #d8e3db;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 20px;
            color: #1f7a3f;
        }

        .card p {
            margin-top: 0;
            color: #666;
            font-size: 14px;
        }

        .full {
            grid-column: 1 / -1;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: bold;
            color: #444;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8d8d8;
            border-radius: 10px;
            font-size: 14px;
            background: white;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2fa35a;
            box-shadow: 0 0 0 3px rgba(47,163,90,0.12);
        }

        .erro {
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 10px;
            background: #fdeaea;
            color: #b42318;
            font-size: 14px;
            font-weight: bold;
        }

        .actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        button {
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            background: #1f7a3f;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s ease;
        }

        button:hover {
            background: #186232;
        }

        .secondary {
            background: #eef2f7;
            color: #333;
        }

        .secondary:hover {
            background: #dde5ee;
        }

        .help {
            font-size: 13px;
            color: #666;
            margin-top: 6px;
        }

        @media (max-width: 900px) {
            .grid,
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="layout">
        <?php include __DIR__ . '/includes/menu.php'; ?>

        <main class="page-content">
            <div class="header">
                <h1>Cadastrar Animal</h1>
                <p>Preencha a ficha do animal com o máximo de informações possíveis</p>
            </div>

            <div class="container">
                <div class="top-actions">
                    <a href="dashboard.php" class="btn-link">← Voltar ao dashboard</a>
                </div>

                <?php if ($erro !== ''): ?>
                    <div class="erro"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <!-- resto do formulário -->
                </form>
            </div>
        </main>
    </div>

    <script>
        function toggleSubMenu(idSubmenu, elementoLink) {
            const submenu = document.getElementById(idSubmenu);
            if (!submenu) return;

            const setinha = elementoLink.querySelector('.setinha');

            if (submenu.style.display === "none" || submenu.style.display === "") {
                submenu.style.display = "block";
                if (setinha) setinha.classList.add("girar");
            } else {
                submenu.style.display = "none";
                if (setinha) setinha.classList.remove("girar");
            }
        }
    </script>
</body>
</html>
