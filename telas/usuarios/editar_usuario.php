<?php
session_start();

// TRAVA DE SEGURANÇA: Só administrador acessa essa tela
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'admin') {
    header("Location: ../chamados/lista_chamados.php");
    exit();
}

// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$usuario_encontrado = false;

// 2. BUSCA AS EMPRESAS ATIVAS PARA O SELECT DO FORMULÁRIO
$sql_empresas = "SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC";
$resultado_empresas = $conexao->query($sql_empresas);

// 3. LÓGICA DE ATUALIZAÇÃO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id          = (int)$_POST['id'];
    $nome        = trim($_POST['nome']);
    $email       = trim($_POST['email']); 
    $num_celular = trim($_POST['num_celular']);
    $perfil      = trim($_POST['perfil']);
    $id_cliente  = (int)$_POST['id_cliente'];
    $senha_nova  = trim($_POST['senha']); 

    if (empty($nome) || empty($email) || empty($perfil) || empty($id_cliente)) {
        $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos obrigatórios (*) devem ser preenchidos.</div>";
        $usuario = $_POST;
    } else {
        
        // 🚀 CORRIGIDO: Removida a coluna 'localizacao' do banco das strings de UPDATE
        if (isset($senha_nova) && $senha_nova !== '') {
            $senha_cripto = password_hash($senha_nova, PASSWORD_BCRYPT);
            $sql_update = "UPDATE usuarios SET nome = ?, email = ?, num_celular = ?, perfil = ?, id_cliente = ?, senha = ? WHERE id = ?";
        } else {
            $sql_update = "UPDATE usuarios SET nome = ?, email = ?, num_celular = ?, perfil = ?, id_cliente = ? WHERE id = ?";
        }
        
        try {
            $stmt_update = $conexao->prepare($sql_update);
            
            // 🚀 CORRIGIDO: Recalculados os tipos no bind_param sem a string da localização
            if (isset($senha_nova) && $senha_nova !== '') {
                $stmt_update->bind_param("sssssii", $nome, $email, $num_celular, $perfil, $id_cliente, $senha_cripto, $id);
            } else {
                $stmt_update->bind_param("ssssii", $nome, $email, $num_celular, $perfil, $id_cliente, $id);
            }

            if ($stmt_update->execute()) {
                $conexao->close();
                header("Location: lista_usuarios.php?status=success_edit"); 
                exit();
            }

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensagem = "<div class='msg-erro'>❌ Erro: O e-mail '$email' já está em uso por outro usuário.</div>";
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro ao atualizar: " . $e->getMessage() . "</div>";
            }
            $usuario = $_POST;
        }

        if (isset($stmt_update)) {
            $stmt_update->close();
        }
    }
}

// 4. LÓGICA DE CARREGAMENTO DE DADOS (GET / PÓS-POST)
if ((isset($_GET['id']) && is_numeric($_GET['id'])) || (isset($id) && $id > 0 && !isset($usuario))) {
    $id_para_busca = isset($_GET['id']) ? (int)$_GET['id'] : $id; 
    
    // 🚀 CORRIGIDO: Removido o campo 'localizacao' do SELECT original
    $sql_select = "SELECT id, nome, email, num_celular, perfil, id_cliente, status FROM usuarios WHERE id = ?";
    $stmt_select = $conexao->prepare($sql_select);
    $stmt_select->bind_param("i", $id_para_busca);
    $stmt_select->execute();
    $resultado = $stmt_select->get_result();

    if ($resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();
    } else if (!isset($usuario)) {
        $mensagem = "<div class='msg-erro'>Usuário não encontrado ou ID inválido.</div>";
    }
    $stmt_select->close();
}

$conexao->close();

if (!isset($usuario) && empty($mensagem)) {
    $mensagem = "<div class='msg-alerta'>Nenhum ID de usuário fornecido para edição.</div>";
}
?>

<?php if (isset($usuario) && $usuario) { $usuario_encontrado = true; } ?>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>👥 Editar Usuário</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; ?>

        <?php if ($usuario_encontrado): ?>
            <form method="POST" action="">
                <h2>Editando: <?php echo htmlspecialchars($usuario['nome'] ?? 'Dados Inválidos'); ?></h2>
                
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id']); ?>">

                <label for="nome">Nome (*):</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>

                <label for="email">E-mail (*):</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>

                <label for="num_celular">Telefone (*):</label>
                <input type="text" id="num_celular" name="num_celular" value="<?php echo htmlspecialchars($usuario['num_celular'] ?? ''); ?>" placeholder="(31) 99999-9999" maxlength="20" required oninput="if (typeof mascaraCelular === 'function') mascaraCelular(this)">

                <label for="senha">Nova Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Digite apenas se quiser mudar a senha atual">

                <label for="perfil">Perfil (*):</label>
                <select id="perfil" name="perfil" required>
                    <option value="">-- Selecione o Perfil --</option>
                    <option value="normal" <?php echo ($usuario['perfil'] === 'normal') ? 'selected' : ''; ?>>Usuário Comum (Cliente)</option>
                    <option value="tecnico" <?php echo ($usuario['perfil'] === 'tecnico') ? 'selected' : ''; ?>>Técnico de Suporte</option>
                    <option value="admin" <?php echo ($usuario['perfil'] === 'admin') ? 'selected' : ''; ?>>Administrador do Sistema</option>
                </select>

                <label for="id_cliente">Empresa (*):</label>
                <select id="id_cliente" name="id_cliente" required>
                    <option value="">-- Selecione a Empresa --</option>
                    <?php if ($resultado_empresas && $resultado_empresas->num_rows > 0): ?>
                        <?php while($empresa = $resultado_empresas->fetch_assoc()): ?>
                            <option value="<?php echo $empresa['id_cliente']; ?>" <?php echo ($usuario['id_cliente'] == $empresa['id_cliente']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empresa['nome_empresa']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                
                <button type="submit">Salvar Alterações</button>
            </form>
        <?php endif; ?>
        
        <div class="voltar">
             <a href="lista_usuarios.php">← Voltar para Lista de Usuários</a>
        </div>
    </main>

    <script src="../../js/mascaras.js?v=<?php echo time(); ?>"></script>
    <script>
        // 🚀 CORRIGIDO: Formata o telefone logo na inicialização se já vier do banco de dados
        document.addEventListener("DOMContentLoaded", function() {
            const inputCelular = document.getElementById("num_celular");
            if (inputCelular && typeof mascaraCelular === 'function') {
                mascaraCelular(inputCelular);
            }
        });
    </script>
</body>
</html>