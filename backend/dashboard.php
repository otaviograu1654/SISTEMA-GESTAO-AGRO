
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGA Pecuária - Dashboard</title>
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

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
    width: 240px;
    left: -240px; /* Começa escondido fora da tela */
    background: linear-gradient(180deg, #264d2f, #1f3f27);
    color: white;
    padding: 20px 0;
    position: fixed; /* A mágica: Tira o menu do chão e faz ele flutuar */
    top: 70px; /* Altura da sua barra branca superior */
    height: calc(100vh - 70px);
    z-index: 990; /* Fica por cima da sombra */
    transition: left 0.3s ease;
}
.sidebar.aberto {
    left: 0; /* desliza para dentro da tela */
}

        .menu {
            margin-top: 20px;
        }

        .menu-title {
            padding: 10px 20px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.65;
        }

        .menu a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.2s ease;
            border-left: 4px solid transparent;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(255,255,255,0.08);
            border-left-color: #66d18f;
        }

        .menu .disabled {
            opacity: 0.55;
            cursor: default;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
    display: flex;
    align-items: center;
    background: white;
    padding: 12px 24px;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    position: sticky;
    top: 0; z-index: 100; 
        }

        .btn-Menu {
    margin-right: 20px;
    padding: 4px 10px;
    background: transparent;
    color: #1f7a3f;
    border: 1px solid #1f7a3f;
    border-radius: 8px;
    font-size: 24px;
    cursor: pointer;
    transition: 0.2s; }

    .btnMenu:hover { background: #e7f6ec; }

    .overlay {
    position: fixed;
    top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0, 0, 0, 0.5); /* preto com 50% de transparência */
    z-index: 980; /* Fica acima da página, mas abaixo do menu */
    opacity: 0; visibility: hidden;
    transition: all 0.3s ease;
    }
    .overlay.ativo {
    opacity: 1; visibility: visible; /* opacidade suave*/
    }



        .topbar h2 {
            margin: 0;
            font-size: 28px;
            color: #1f7a3f;
        }

        .topbar p {
            margin: 6px 0 0;
            color: #666;
            font-size: 14px;
        }

        .content {
            max-width: 1200px;
            width: 100%;
            padding: 24px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 16px;
            color: #555;
        }

        .card .value {
            font-size: 30px;
            font-weight: bold;
            color: #1f7a3f;
        }

        .grid-panels {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .panel {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 22px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e7e7e7;
        }

        th {
            background: #f0f7f2;
            color: #1f7a3f;
        }

        tr:hover {
            background: #fafafa;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: #e7f6ec;
            color: #1f7a3f;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            padding: 20px;
            text-align: center;
            color: #777;
        }

        .loading {
            padding: 20px;
            text-align: center;
            color: #555;
        }

        form {
            display: grid;
            gap: 12px;
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
        }

        input:focus, select:focus {
            outline: none;
            border-color: #2fa35a;
            box-shadow: 0 0 0 3px rgba(47,163,90,0.12);
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

        .mensagem {
            margin-top: 10px;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            display: none;
        }

        .mensagem.sucesso {
            background: #e7f6ec;
            color: #1f7a3f;
            display: block;
        }

        .mensagem.erro {
            background: #fdeaea;
            color: #b42318;
            display: block;
        }

        .helper-text {
            margin-top: 8px;
            font-size: 13px;
            color: #667;
        }

        @media (max-width: 900px) {
            .layout {
                flex-direction: column;
            }

            

            .grid-panels {
                grid-template-columns: 1fr;
            }
        }
        .search-box {
    margin-bottom: 16px;
}

.search-box input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d8d8d8;
    border-radius: 10px;
    font-size: 14px;
    background: white;
}

.search-box input:focus {
    outline: none;
    border-color: #2fa35a;
    box-shadow: 0 0 0 3px rgba(47,163,90,0.12);
}

.search-help {
    margin-top: 8px;
    font-size: 13px;
    color: #666;
}

/* --- ESTILOS DO SUBMENU --- */
        .submenu {
            display: none;
            list-style: none;
            padding: 0;
            margin: 0;
            background: rgba(0, 0, 0, 0.15); /* Fundo um pouco mais escuro */
        }

        .submenu li a {
            border-left: 4px solid transparent;
        }

        .submenu li a:hover {
            border-left-color: #66d18f;
            background: rgba(255,255,255,0.05);
        }

        .setinha.girar {
            transform: rotate(180deg);
        }

    </style>
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
                <a href="#dashboard" class="active">Dashboard</a>
                <a href="#animais">Animais</a>
                <a href="cadastro_animal.php">Cadastrar animal</a>

                <div class="menu-title">Módulos</div>
                <a href="#" class="disabled">Pesagens</a>
                <div class="menu-title">Módulos</div>
                <a href="#" class="disabled">Pesagens</a>
                
                <div class="menu-item">
                    <a href="#" class="menu-link" onclick="toggleSubMenu('submenu-financeiro', this); return false;" style="display: flex; justify-content: space-between; align-items: center;">
                        Financeiro
                        <span class="setinha" style="transition: transform 0.3s ease;">▾</span>
                    </a>
                    
                    <ul id="submenu-financeiro" class="submenu">
                        <li><a href="contas_a_pagar.php" style="padding-left: 40px; font-size: 14px; opacity: 0.9;">Contas a Pagar</a></li>
                        </ul>
                </div>
                <a href="#" class="disabled">Relatórios</a>
            </nav>
        </aside>

        <main class="main">

            <div class="content">
                <div class="cards">
                    <div class="card">
                        <h3>Total de animais</h3>
                        <div class="value" id="totalAnimais">0</div>
                    </div>

                    <div class="card">
                        <h3>Machos</h3>
                        <div class="value" id="totalMachos">0</div>
                    </div>

                    <div class="card">
                        <h3>Fêmeas</h3>
                        <div class="value" id="totalFemeas">0</div>
                    </div>

                    <div class="card">
                        <h3>Status do sistema</h3>
                        <div class="value" style="font-size: 20px;">Online</div>
                    </div>
                </div>

                <div class="grid-panels">
                    <div class="panel" id="animais">
                        <h2>Animais cadastrados</h2>
                        <div class="search-box">
    <input
        type="text"
        id="buscaAnimais"
        placeholder="Pesquisar por brinco, nome, raça ou lote..."
    >
    <div class="search-help">
        A busca filtra os animais exibidos na tabela.
    </div>
</div>

                        <div id="loading" class="loading">Carregando animais...</div>

                        <div class="table-wrapper" id="tableWrapper" style="display: none;">
                            <table>
                                <thead>
                                    <tr>
                                       
    <th>ID</th>
    <th>Brinco</th>
    <th>Nome</th>
    <th>Raça</th>
    <th>Sexo</th>
    <th>Nascimento</th>
    <th>Lote</th>
    <th>Status</th>
    <th>Ações</th>

                                    </tr>
                                </thead>
                                <tbody id="tabelaAnimais"></tbody>
                            </table>
                        </div>

                        <div id="emptyState" class="empty" style="display: none;">
                            Nenhum animal cadastrado.
                        </div>
                    </div>

                   <div class="panel" id="cadastro">
    <h2>Ações rápidas</h2>

    <p style="color:#666; font-size:14px; margin-top:0;">
        Use os atalhos abaixo para navegar pelo sistema.
    </p>

    <div style="display:grid; gap:12px;">
        <a href="cadastro_animal.php" style="display:block; padding:12px 14px; background:#1f7a3f; color:white; text-decoration:none; border-radius:10px; font-weight:bold;">
            + Novo animal
        </a>

        <a href="animal.php?id=1" style="display:block; padding:12px 14px; background:#eef2f7; color:#333; text-decoration:none; border-radius:10px; font-weight:bold;">
            Ver animal exemplo
        </a>
    </div>

    <div class="helper-text">
        O cadastro completo agora fica em uma página separada.
    </div>
</div>
                </div>
            </div>
        </main>
    </div>

    <script>

        let todosAnimais = [];

function normalizarTexto(texto) {
    return (texto || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

        async function carregarAnimais() {
    const loading = document.getElementById('loading');

    try {
        const resposta = await fetch('animais.php');

        if (!resposta.ok) {
            throw new Error('Erro ao buscar animais');
        }

        const animais = await resposta.json();
        todosAnimais = Array.isArray(animais) ? animais : [];
        aplicarFiltro();

    } catch (erro) {
        loading.textContent = 'Erro ao carregar animais.';
        console.error('Erro:', erro);
    }
}

                function atualizarCards(animais) {
    const totalAnimais = animais.length;

    const totalMachos = animais.filter(animal =>
        normalizarTexto(animal.sexo) === 'macho'
    ).length;

    const totalFemeas = animais.filter(animal =>
        normalizarTexto(animal.sexo) === 'femea'
    ).length;

    document.getElementById('totalAnimais').textContent = totalAnimais;
    document.getElementById('totalMachos').textContent = totalMachos;
    document.getElementById('totalFemeas').textContent = totalFemeas;
}
                function renderizarAnimais(animais) {
    const loading = document.getElementById('loading');
    const tableWrapper = document.getElementById('tableWrapper');
    const emptyState = document.getElementById('emptyState');
    const tabelaAnimais = document.getElementById('tabelaAnimais');

    loading.style.display = 'none';

    if (!Array.isArray(animais) || animais.length === 0) {
        tableWrapper.style.display = 'none';
        emptyState.style.display = 'block';
        tabelaAnimais.innerHTML = '';
        atualizarCards([]);
        return;
    }

    emptyState.style.display = 'none';
    tableWrapper.style.display = 'block';
    tabelaAnimais.innerHTML = '';

    animais.forEach(animal => {
        const tr = document.createElement('tr');

        tr.innerHTML = `
            <td>${animal.id ?? ''}</td>
            <td>${animal.brinco ?? ''}</td>
            <td>${animal.nome_apelido ?? ''}</td>
            <td>${animal.raca ?? ''}</td>
            <td>${animal.sexo ?? ''}</td>
            <td>${animal.data_nascimento ?? ''}</td>
            <td>${animal.lote ?? ''}</td>
            <td><span class="status">Ativo</span></td>
            <td>
                <a href="animal.php?id=${animal.id}" style="color:#1f7a3f; font-weight:bold; text-decoration:none;">
                    Ver detalhes
                </a>
            </td>
        `;

        tabelaAnimais.appendChild(tr);
    });

    atualizarCards(animais);
}
        function aplicarFiltro() {
    const campoBusca = document.getElementById('buscaAnimais');
    const termo = normalizarTexto(campoBusca.value);

    if (termo === '') {
        renderizarAnimais(todosAnimais);
        return;
    }

    const animaisFiltrados = todosAnimais.filter(animal => {
        const textoBusca = normalizarTexto(
            `${animal.brinco} ${animal.nome_apelido} ${animal.raca} ${animal.lote} ${animal.sexo}`
        );

        return textoBusca.includes(termo);
    });

    renderizarAnimais(animaisFiltrados);
}
        document.getElementById('buscaAnimais').addEventListener('input', aplicarFiltro);
        carregarAnimais();

        // Função para abrir/fechar o submenu e girar a setinha
        function toggleSubMenu(idSubmenu, elementoLink) {
            const submenu = document.getElementById(idSubmenu);
            const setinha = elementoLink.querySelector('.setinha');

            // Se o menu estiver escondido, a gente mostra e gira a seta
            if (submenu.style.display === "none" || submenu.style.display === "") {
                submenu.style.display = "block";
                setinha.classList.add("girar");
            } else {
                // Se já estiver aberto, a gente esconde e volta a seta ao normal
                submenu.style.display = "none";
                setinha.classList.remove("girar");
            }
        }
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
