<?php
// ... CÓDIGO PHP DE CONEXÃO E CONSULTA ...
$servidor = "localhost";
$usuario = "root";
$senha = ""; // Senha do XAMPP
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

$mensagem_feedback = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_add') {
        $mensagem_feedback = "<div class='msg-sucesso'>✅ Técnico cadastrado com sucesso!</div>";
    } elseif ($_GET['status'] == 'success_edit') {
        $mensagem_feedback = "<div class='msg-sucesso'>✅ Técnico atualizado com sucesso!</div>";
    } elseif ($_GET['status'] == 'success_delete') {
        $mensagem_feedback = "<div class='msg-sucesso'>✅ Técnico excluído com sucesso!</div>";
    } elseif ($_GET['status'] == 'error_fk') {
        $mensagem_feedback = "<div class='msg-erro'>❌ Erro: Não é possível excluir este técnico. Ele possui chamados no sistema.</div>";
    } elseif ($_GET['status'] == 'error_delete' || $_GET['status'] == 'error_no_id') {
        $mensagem_feedback = "<div class='msg-erro'>❌ Erro ao excluir o técnico.</div>";
    }
}

// 🚩 ALTERAÇÃO FEITA AQUI: Ordenando por id_tecnico
$sql = "SELECT id_tecnico, nome_tecnico, email, num_celular FROM tecnicos ORDER BY id_tecnico ASC";
$resultado = $conexao->query($sql);

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Técnicos Cadastrados</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
</head>
<body>

    <header>
        <h1>👥 Técnicos Cadastrados</h1>
    </header>
    <hr>

    <main>
        <p><a href="cadastro_tecnicos.php" class="btn-acao" style="background-color: #4CAF50;">+ Cadastrar Novo Técnico</a></p>

        <?php if ($resultado->num_rows > 0): ?>
            <p>Total de técnicos: <strong><?php echo $resultado->num_rows; ?></strong></p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Técnico</th>
                        <th>E-mail</th>
                        <th>Celular</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($linha = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $linha['id_tecnico']; ?></td>
                            <td><?php echo $linha['nome_tecnico']; ?></td>
                            <td><?php echo $linha['email']; ?></td>
                            <td><?php echo $linha['num_celular']; ?></td>
                            <td>
                                <a href="editar_tecnico.php?id=<?php echo $linha['id_tecnico']; ?>" class="btn-acao">Editar</a>
                                <a href="excluir_tecnico.php?id=<?php echo $linha['id_tecnico']; ?>" class="btn-acao"
                                onclick="return confirm('Tem certeza que deseja excluir o técnico <?php echo addslashes($linha['nome_tecnico']); ?>?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum técnico cadastrado ainda.</p>
        <?php endif; ?>
    </main>

    <script>
        <?php if (!empty($mensagem_feedback)): ?>
        // Remove as tags HTML da mensagem, deixando apenas o texto (e emojis)
        var feedbackMensagem = "<?php echo strip_tags($mensagem_feedback); ?>";

        // 1. Exibe a mensagem de feedback na caixa de diálogo de alerta
        alert(feedbackMensagem);

        // 2. Limpa o parâmetro 'status' da URL para a mensagem não reaparecer ao recarregar a página (F5)
        if (window.history.replaceState) {
            var urlSemStatus = window.location.href.split('?')[0];
            window.history.replaceState(null, null, urlSemStatus);
        }
        <?php endif; ?>
    </script>
    
    <script src="../js/mascaras.js"></script>

</body>
</html>