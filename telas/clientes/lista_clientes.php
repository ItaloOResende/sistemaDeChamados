<?php
// ---------------------------------------------
// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

// Criar a conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Checar a conexão
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// ---------------------------------------------
// 2. TRATAMENTO DE FEEDBACK VIA URL (PARA USO EXCLUSIVO NO JS)
// ---------------------------------------------
$mensagem_feedback = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_add') {
        $mensagem_feedback = "<div class='msg-sucesso'>✅ Cliente cadastrado com sucesso!</div>";
    } elseif ($_GET['status'] == 'success_edit') {
        $mensagem_feedback = "<div class='msg-sucesso'>✅ Cliente atualizado com sucesso!</div>";
    } elseif ($_GET['status'] == 'success_delete') {
        $mensagem_feedback = "<div class='msg-sucesso'>✅ Cliente excluído com sucesso!</div>";
    } elseif ($_GET['status'] == 'error_fk') {
        // Erro de Foreign Key
        $mensagem_feedback = "<div class='msg-erro'>❌ Erro: Não é possível excluir este cliente. Ele possui chamados ativos no sistema.</div>";
    } elseif ($_GET['status'] == 'error_delete' || $_GET['status'] == 'error_no_id') {
        $mensagem_feedback = "<div class='msg-erro'>❌ Erro ao excluir o cliente.</div>";
    }
}

// ---------------------------------------------
// 3. LÓGICA DE BUSCA DE DADOS (QUERY ATUALIZADA)
// ---------------------------------------------

// Query para selecionar todos os clientes, INCLUINDO num_celular
$sql = "SELECT id_cliente, nome_empresa, localizacao, contato_principal, num_celular, email_contato FROM clientes ORDER BY nome_empresa ASC";
$resultado = $conexao->query($sql);

// Verifica se houve erro na query
if (!$resultado) {
    $erro_query = "Erro na consulta: " . $conexao->error;
} else {
    $erro_query = null;
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes Cadastrados</title>
    <link rel="stylesheet" href="../../estilos/estilos.css"> 
</head>
<body>

    <header>
        <h1>Clientes Cadastrados</h1>
    </header>
    <hr>

    <main>
        <div class="acoes">
            <a href="cadastrar_cliente.php" class="btn-adicionar">➕ Adicionar Novo Cliente</a>
        </div>
        
        <?php if ($erro_query): ?>
            <div class="msg-erro"><?php echo $erro_query; ?></div>
        <?php elseif ($resultado->num_rows == 0): ?>
            <div class="msg-alerta">Nenhum cliente cadastrado ainda.</div>
        <?php else: ?>
            
            <p>Total de clientes: <strong><?php echo $resultado->num_rows; ?></strong></p>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Email</th>
                        <th>Contato Principal</th>
                        <th>Celular</th> 
                        <th>Localização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($cliente = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $cliente['id_cliente']; ?></td>
                        <td><?php echo htmlspecialchars($cliente['nome_empresa']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['email_contato']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['contato_principal']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['num_celular']); ?></td>
                        <td><?php echo htmlspecialchars($cliente['localizacao']); ?></td>
                        <td>
                            <a href="editar_cliente.php?id=<?php echo $cliente['id_cliente']; ?>" class="btn-acao btn-editar">Editar</a>
                            
                            <a href="excluir_cliente.php?id=<?php echo $cliente['id_cliente']; ?>" 
                               class="btn-acao btn-excluir"
                               onclick="return confirm('Tem certeza que deseja excluir o cliente <?php echo addslashes($cliente['nome_empresa']); ?>?');"
                            >
                                Excluir
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </main>
    
    <script>
        <?php if (!empty($mensagem_feedback)): ?>
        
        // 1. Remove as tags HTML e exibe a mensagem de feedback no alerta
        var feedbackMensagem = "<?php echo strip_tags($mensagem_feedback); ?>";
        alert(feedbackMensagem);

        // 2. Limpa o parâmetro 'status' da URL para que a mensagem não reapareça em F5
        if (window.history.replaceState) {
            var urlSemStatus = window.location.href.split('?')[0];
            window.history.replaceState(null, null, urlSemStatus);
        }
        <?php endif; ?>
    </script>
    
    <script src="../../js/mascaras.js"></script> 

</body>
</html>