<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Chamados - Black TI</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        /* CSS específico para os cards do menu */
        .menu-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 50px;
        }

        .menu-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 250px;
            padding: 30px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
            background-color: #f9f9f9;
        }

        .menu-card i {
            font-size: 40px;
            display: block;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            margin: 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>

    <header>
        <h1>🖥️ Sistema de Chamados - Black TI</h1>
    </header>
    <hr>

    <main>
        <div class="menu-container">
            <a href="paginas/chamados/lista_chamados.php" class="menu-card">
                <span>📋</span>
                <h3>Chamados</h3>
                <p>Visualizar e abrir novos tickets</p>
            </a>

            <a href="paginas/clientes/lista_clientes.php" class="menu-card">
                <span>🏢</span>
                <h3>Clientes</h3>
                <p>Gerenciar empresas e contatos</p>
            </a>

            <a href="paginas/tecnicos/lista_tecnicos.php" class="menu-card">
                <span>🛠️</span>
                <h3>Técnicos</h3>
                <p>Cadastro da equipe técnica</p>
            </a>
        </div>
    </main>

</body>
</html>