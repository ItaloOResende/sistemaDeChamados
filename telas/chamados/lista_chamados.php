<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Garante que caracteres especiais (acentos) funcionem
$conexao->set_charset("utf8mb4");

$sql = "SELECT 
            c.id_chamado, 
            IFNULL(cli.nome_empresa, 'Cliente não encontrado') AS cliente, 
            IFNULL(t.nome_tecnico, 'Pendente') AS tecnico, 
            c.prioridade, 
            c.status, 
            c.data_abertura
        FROM chamados c
        LEFT JOIN clientes cli ON c.id_cliente = cli.id_cliente
        LEFT JOIN tecnicos t ON c.id_tecnico_atribuido = t.id_tecnico
        ORDER BY c.id_chamado DESC";

$resultado = $conexao->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Chamados</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
    <style>
        header h1 { text-align: center; }
        .prioridade-Alta, .prioridade-Urgente { color: red; font-weight: bold; }
        .prioridade-Média { color: orange; font-weight: bold; }
        .prioridade-Baixa { color: green; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <header>
        <h1>📋 Chamados Registrados</h1>
    </header>
    <hr>
    <main>
        <div style="margin-bottom: 20px; text-align: right;">
            <a href="cadastrar_chamado.php" class="btn-novo">+ Abrir Novo Chamado</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Se chegar aqui e não mostrar nada, o problema é que $resultado está vazio
                if ($resultado && $resultado->num_rows > 0): 
                    while($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id_chamado']; ?></td>
                            <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($row['tecnico']); ?></td>
                            <td class="prioridade-<?php echo $row['prioridade']; ?>">
                                <?php echo $row['prioridade']; ?>
                            </td>
                            <td><?php echo $row['status']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['data_abertura'])); ?></td>
                        </tr>
                    <?php endwhile; 
                else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            Nenhum chamado encontrado no banco de dados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>