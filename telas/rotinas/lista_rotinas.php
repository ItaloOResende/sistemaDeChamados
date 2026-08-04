<?php
session_start();

// TRAVA DE SEGURANÇA: Só administrador acessa essa tela
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'admin') {
    header("Location: ../chamados/lista_chamados.php");
    exit();
}

include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

// ALTERAÇÃO DE STATUS / SOFT DELETE (Ativar / Desativar)
if (isset($_GET['toggle_id'])) {
    $id_toggle = (int)$_GET['toggle_id'];
    $sql_toggle = "UPDATE rotinas SET ativo = IF(ativo = 1, 0, 1) WHERE id_rotina = ?";
    $stmt = $conexao->prepare($sql_toggle);
    $stmt->bind_param("i", $id_toggle);
    $stmt->execute();
    $stmt->close();
    header("Location: lista_rotinas.php");
    exit();
}

// Busca todas as rotinas trazendo o nome da empresa correspondente
$sql_lista = "SELECT r.*, c.nome_empresa 
              FROM rotinas r 
              LEFT JOIN clientes c ON r.id_cliente = c.id_cliente 
              ORDER BY r.id_rotina DESC";
$res_rotinas = $conexao->query($sql_lista);
$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Rotinas Cadastradas</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        .topo-acoes { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-novo { background-color: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-novo:hover { background-color: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .status-ativo { color: #28a745; font-weight: bold; }
        .status-inativo { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>🤖 Rotinas Cadastradas</h1>
    </header>
    <hr>

    <main>
        <div class="topo-acoes">
            <a href="cadastrar_rotina.php" class="btn-novo">+ Nova Rotina</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa Alvo</th>
                    <th>Título</th>
                    <th>Prioridade</th>
                    <th>Frequência</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_rotinas && $res_rotinas->num_rows > 0): ?>
                    <?php while($r = $res_rotinas->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $r['id_rotina']; ?></td>
                            <td>
                                <?php 
                                    echo (!empty($r['nome_empresa'])) 
                                        ? htmlspecialchars($r['nome_empresa']) 
                                        : '<strong>🏢 TODAS AS EMPRESAS</strong>'; 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($r['prioridade']); ?></td>
                            <td><?php echo htmlspecialchars($r['frequencia']); ?></td>
                            <td>
                                <?php echo $r['ativo'] ? '<span class="status-ativo">Ativa</span>' : '<span class="status-inativo">Inativa</span>'; ?>
                            </td>
                            <td>
    <!-- Botão Editar (Azul) -->
    <a href="editar_rotina.php?id=<?php echo $r['id_rotina']; ?>" class="btn-acao">
        Editar
    </a>
    
    <!-- Botão Desativar/Ativar (Vermelho) -->
    <a href="lista_rotinas.php?toggle_id=<?php echo $r['id_rotina']; ?>" 
       class="btn-acao exclui" 
       onclick="return confirm('Tem certeza que deseja <?php echo $r['ativo'] ? 'desativar' : 'ativar'; ?> esta rotina?')">
        <?php echo $r['ativo'] ? 'Desativar' : 'Ativar'; ?>
    </a>
</td>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Nenhuma rotina cadastrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

</body>
</html>