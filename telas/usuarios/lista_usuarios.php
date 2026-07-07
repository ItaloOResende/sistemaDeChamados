<?php
session_start();
// ---------------------------------------------
// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$cadastro_sucesso = false;

// ---------------------------------------------
// 2. TRATAMENTO DE FEEDBACK VIA URL (Para Usuários)
// ---------------------------------------------
$mensagem_feedback = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success_add') {
        $mensagem_feedback = "✅ Usuário cadastrado com sucesso!";
    } elseif ($_GET['status'] == 'success_edit') {
        $mensagem_feedback = "✅ Usuário atualizado com sucesso!";
    } elseif ($_GET['status'] == 'success_delete') {
        $mensagem_feedback = "✅ Usuário excluído com sucesso!";
    } elseif ($_GET['status'] == 'error_delete' || $_GET['status'] == 'error_no_id') {
        $mensagem_feedback = "❌ Erro ao excluir o usuário.";
    }
}

// ---------------------------------------------
// 3. LÓGICA DE BUSCA DE DADOS (🚀 FILTRADO APENAS ATIVOS)
// ---------------------------------------------
// Mudado para LEFT JOIN para técnicos masters sem empresa aparecerem e adicionado o WHERE status = 'Ativo'
$sql = "SELECT u.id, u.nome, u.email, u.num_celular, u.perfil, u.criado_em, c.nome_empresa 
        FROM usuarios u
        LEFT JOIN clientes c ON u.id_cliente = c.id_cliente 
        WHERE u.status = 'Ativo'
        ORDER BY u.nome ASC";
        
$resultado = $conexao->query($sql);

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
    <title>Lista de Usuários Cadastrados</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>👥 Usuários Cadastrados</h1>
    </header>
    <hr>

    <main>
        <div class="acoes">
            <a href="cadastrar_usuario.php" class="btn-adicionar" style="background-color: #4CAF50; color: white; padding: 10px; text-decoration: none; border-radius: 5px;">➕ Adicionar Novo Usuário</a>
        </div>
        
        <?php if ($erro_query): ?>
            <div class="msg-erro"><?php echo $erro_query; ?></div>
        <?php elseif ($resultado->num_rows == 0): ?>
            <div class="msg-alerta">Nenhum usuário ativo encontrado.</div>
        <?php else: ?>
            
            <p>Total de usuários ativos: <strong><?php echo $resultado->num_rows; ?></strong></p>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Celular</th>
                        <th>Perfil</th>
                        <th>Empresa / Cliente</th>
                        <th>Criado Em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($usuario = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                        <td><?php echo !empty($usuario['num_celular']) ? htmlspecialchars($usuario['num_celular']) : '<span style="color: #999; font-style: italic;">Não informado</span>'; ?></td>
                        <td>
                            <span class="badge-perfil <?php echo $usuario['perfil']; ?>" style="text-transform: uppercase; font-weight: bold;">
                                <?php echo htmlspecialchars($usuario['perfil']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                echo $usuario['nome_empresa'] ? htmlspecialchars($usuario['nome_empresa']) : '<span style="color:#999;">Nenhuma</span>'; 
                            ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['criado_em'])); ?></td>
                        <td>
                            <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn-acao btn-editar">Editar</a>
                            
                            <button type="button" class="btn-acao btn-excluir" style="background-color: #f44336; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;" 
                                    onclick="excluirUsuario(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')">
                                 Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </main>
    
    <script>
        // Exibe o alerta de feedback caso exista na URL (simplificado direto com a variável JS)
        <?php if (!empty($mensagem_feedback)): ?>
        var feedbackMensagem = "<?php echo $mensagem_feedback; ?>";
        alert(feedbackMensagem);

        // Limpa os parâmetros da URL para evitar o reenvio ao dar F5
        if (window.history.replaceState) {
            var urlSemStatus = window.location.href.split('?')[0];
            window.history.replaceState(null, null, urlSemStatus);
        }
        <?php endif; ?>

        // Função JS para confirmar a exclusão do usuário
        function excluirUsuario(id, nome) {
            if (confirm("Tem certeza que deseja excluir o usuário '" + nome + "'?")) {
                window.location.href = "excluir_usuario.php?id=" + id;
            }
        }
    </script>
    
</body>
</html>