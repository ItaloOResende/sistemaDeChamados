<?php
// ---------------------------------------------
// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

$mensagem = "";
$cliente = null; // Variável para armazenar os dados do cliente
$id_cliente = 0; // ID do cliente em foco

// ---------------------------------------------
// 2. LÓGICA DE ATUALIZAÇÃO (POST)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2.1 Coleta e Limpeza
    $id_cliente = (int)$_POST['id_cliente'];
    $nome_empresa = trim($_POST['nome_empresa']);
    $localizacao = trim($_POST['localizacao']);
    $contato_principal = trim($_POST['contato_principal']);
    $email_contato = trim($_POST['email_contato']); 
    // Limpeza da máscara
    $num_celular = preg_replace("/[^0-9]/", "", $_POST['num_celular']); 

    // 2.2 Validação de Obrigatoriedade (Os 5 campos)
    if (empty($nome_empresa) || empty($email_contato) || empty($contato_principal) || empty($num_celular) || empty($localizacao)) {
        $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos são obrigatórios.</div>";
        // Mantém os dados no formulário em caso de erro
        $cliente = $_POST; 
        $cliente['id_cliente'] = $id_cliente; 
    } else {
        // 2.3 Query de Atualização
        $sql_update = "UPDATE clientes SET nome_empresa = ?, localizacao = ?, contato_principal = ?, num_celular = ?, email_contato = ? WHERE id_cliente = ?";
        
        try {
            $stmt_update = $conexao->prepare($sql_update);
            // Tipos: sssssi (5 strings e 1 inteiro para o ID)
            $stmt_update->bind_param("sssssi", $nome_empresa, $localizacao, $contato_principal, $num_celular, $email_contato, $id_cliente); 

            if ($stmt_update->execute()) {
                // Sucesso na atualização -> Redireciona para a lista com status
                $conexao->close();
                header("Location: lista_clientes.php?status=success_edit"); 
                exit();
            }

        } catch (mysqli_sql_exception $e) {
            // Tratamento de erro de Duplicidade no campo UNIQUE (email_contato)
            if ($e->getCode() == 1062) {
                $mensagem = "<div class='msg-erro'>❌ Erro: O e-mail '$email_contato' já está cadastrado para outro cliente.</div>";
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro ao atualizar: " . $e->getMessage() . "</div>";
            }
            // Recarrega os dados preenchidos em caso de erro
            $cliente = $_POST;
            $cliente['id_cliente'] = $id_cliente;
        }

        if (isset($stmt_update)) {
            $stmt_update->close();
        }
    }
}

// ---------------------------------------------
// 3. LÓGICA DE CARREGAMENTO DE DADOS (GET/PÓS-POST)
// ---------------------------------------------
if ((isset($_GET['id']) && is_numeric($_GET['id'])) || (isset($id_cliente) && $id_cliente > 0 && !$cliente)) {
    // Define o ID a ser buscado (do GET ou o ID que falhou no POST)
    $id_para_busca = isset($_GET['id']) ? (int)$_GET['id'] : $id_cliente; 
    
    // Query de busca - COM TODOS OS CAMPOS
    $sql_select = "SELECT id_cliente, nome_empresa, localizacao, contato_principal, email_contato, num_celular FROM clientes WHERE id_cliente = ?";
    
    $stmt_select = $conexao->prepare($sql_select);
    $stmt_select->bind_param("i", $id_para_busca);
    $stmt_select->execute();
    $resultado = $stmt_select->get_result();

    if ($resultado->num_rows == 1) {
        $cliente = $resultado->fetch_assoc();
    } else if (!$cliente) {
        $mensagem = "<div class='msg-erro'>Cliente não encontrado ou ID inválido.</div>";
    }
    $stmt_select->close();
}

$conexao->close();

if (!$cliente && empty($mensagem)) {
    $mensagem = "<div class='msg-alerta'>Nenhum ID de cliente fornecido para edição.</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
    <style>
        textarea { resize: vertical; min-height: 100px; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>✏️ Editar Cliente</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; ?>

        <?php if ($cliente): ?>
            <form method="POST" action="">
                <h2>Editando: <?php echo htmlspecialchars($cliente['nome_empresa'] ?? 'Dados Inválidos'); ?></h2>
                
                <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>">

                <label for="nome_empresa">Nome da Empresa (*):</label>
                <input type="text" id="nome_empresa" name="nome_empresa" value="<?php echo htmlspecialchars($cliente['nome_empresa']); ?>" required>

                <label for="email_contato">E-mail (*):</label>
                <input type="email" id="email_contato" name="email_contato" value="<?php echo htmlspecialchars($cliente['email_contato']); ?>" required>

                <label for="contato_principal">Contato Principal (*):</label>
                <input type="text" id="contato_principal" name="contato_principal" value="<?php echo htmlspecialchars($cliente['contato_principal']); ?>" required>

                <label for="num_celular">Número de Celular (*):</label>
                <input type="tel" id="num_celular" name="num_celular" value="<?php echo htmlspecialchars($cliente['num_celular']); ?>" placeholder="(00) 00000-0000" maxlength="15" required>
                
                <label for="localizacao">Localização/Endereço (*):</label>
                <input type="text" id="localizacao" name="localizacao" value="<?php echo htmlspecialchars($cliente['localizacao']); ?>" required>
                
                <button type="submit">Salvar Alterações</button>
            </form>
        <?php endif; ?>
        
        <div class="voltar">
             <a href="lista_clientes.php">← Voltar para Lista de Clientes</a>
        </div>
    </main>

    <script src="../../js/mascaras.js"></script>
</body>
</html>