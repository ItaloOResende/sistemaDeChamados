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
// 2. BUSCA AS EMPRESAS ATIVAS PARA O SELECT DO FORMULÁRIO
// ---------------------------------------------
$sql_empresas = "SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC";
$resultado_empresas = $conexao->query($sql_empresas);

// ---------------------------------------------
// 3. LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (USUÁRIOS)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpeza básica dos dados
    $nome       = trim($_POST['nome']);
    $email      = trim($_POST['email']); 
    $senha_pura = trim($_POST['senha']);
    $perfil     = trim($_POST['perfil']);
    $id_cliente = trim($_POST['id_cliente']); // Recebe o ID da empresa vinculada

    // Validação: Todos os campos são obrigatórios
    if (empty($nome) || empty($email) || empty($senha_pura) || empty($perfil) || empty($id_cliente)) {
        $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos são obrigatórios. Por favor, preencha todos os dados.</div>";
    } else {
        // Criptografa a senha usando o BCRYPT nativo do seu ambiente
        $senha_cripto = password_hash($senha_pura, PASSWORD_BCRYPT);

        // QUERY SQL INCLUINDO AS COLUNAS DA SUA TABELA USUARIOS
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil, id_cliente) VALUES (?, ?, ?, ?, ?)";
        
        // Prepared Statement para segurança total contra SQL Injection
        $stmt = $conexao->prepare($sql);
        
        // "ssssi" -> 4 strings (nome, email, senha, perfil) e 1 inteiro (id_cliente)
        $stmt->bind_param("ssssi", $nome, $email, $senha_cripto, $perfil, $id_cliente); 

        if ($stmt->execute()) {
            $cadastro_sucesso = true; 
        } else {
            // Caso tente cadastrar um e-mail que já existe
            if ($conexao->errno == 1062) {
                $mensagem = "<div class='msg-erro'>❌ Erro: Este e-mail já está cadastrado no sistema.</div>";
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro ao cadastrar usuário: " . $conexao->error . "</div>";
            }
        }
        
        if (isset($stmt)) {
            $stmt->close(); 
        }
    }
}

// ---------------------------------------------
// 4. REDIRECIONAMENTO APÓS CADASTRO BEM-SUCEDIDO
// ---------------------------------------------
if ($cadastro_sucesso === true) {
    $conexao->close();
    header("Location: lista_usuarios.php?status=success_add"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuários</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>👥 Cadastro de Usuários</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; // Exibe as mensagens de erro se houver ?>

        <form method="POST" action="">
            <h2>Novo Usuário / Colaborador</h2>
            
            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" maxlength="255" required>

            <label for="email">Email (Login):</label>
            <input type="email" id="email" name="email" maxlength="100" autocomplete="off" required>

            <label for="senha">Senha de Acesso:</label>
            <input type="password" id="senha" name="senha" placeholder="Digite uma senha segura" autocomplete="new-password" required>

            <label for="perfil">Perfil de Acesso:</label>
            <select id="perfil" name="perfil" required>
                <option value="">-- Selecione o Perfil --</option>
                <option value="user">Usuário Comum (Cliente)</option>
                <option value="tecnico">Técnico de Suporte</option>
                <option value="admin">Administrador do Sistema</option>
            </select>

            <label for="id_cliente">Empresa / Cliente Vinculado:</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">-- Selecione a Empresa --</option>
                <?php if ($resultado_empresas && $resultado_empresas->num_rows > 0): ?>
                    <?php while($empresa = $resultado_empresas->fetch_assoc()): ?>
                        <option value="<?php echo $empresa['id_cliente']; ?>">
                            <?php echo htmlspecialchars($empresa['nome_empresa']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php else: ?>
                    <option value="" disabled>Nenhuma empresa cadastrada ou ativa</option>
                <?php endif; ?>
            </select>
            
            <button type="submit">Cadastrar Usuário</button>
        </form>
        
        <div class="voltar">
             <a href="lista_usuarios.php">← Voltar para Lista de Usuários</a>
        </div>
    </main>

    <?php 
    // Fecha a conexão depois de renderizar as empresas no select
    $conexao->close();

    // Executa o script de sucesso e redirecionamento idêntico ao seu padrão
    if ($cadastro_sucesso === true) {
        echo "
            <script>
                mostrarSucessoERedirecionar('Usuário cadastrado com sucesso!', 'lista_usuarios.php?status=success_add');
            </script>
        ";
    }
    ?>
</body>
</html>