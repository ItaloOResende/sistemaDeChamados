<?php
// ---------------------------------------------
// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$cadastro_sucesso = false;

// ---------------------------------------------
// 2. LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (ATUALIZADO)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e limpeza básica dos dados
    $nome_empresa = trim($_POST['nome_empresa']);
    $localizacao = trim($_POST['localizacao']);
    $contato_principal = trim($_POST['contato_principal']);
    $email_contato = trim($_POST['email_contato']); 
    
    // NOVO: Coleta e limpa o celular
    // Assumimos que você está usando o ID 'num_celular' no HTML, mas o nome do campo é 'num_celular'
    // Limpamos a máscara (se o JS tiver aplicado)
    $num_celular = preg_replace("/[^0-9]/", "", $_POST['num_celular']);
    
    
    // ATUALIZAÇÃO DA VALIDAÇÃO: Todos os 5 campos agora são obrigatórios
    if (empty($nome_empresa) || empty($email_contato) || empty($contato_principal) || empty($num_celular) || empty($localizacao)) {
        $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos são obrigatórios. Por favor, preencha todos os dados.</div>";
    } else {
        // QUERY SQL ATUALIZADA (Incluindo a nova coluna)
        $sql = "INSERT INTO clientes (nome_empresa, localizacao, contato_principal, num_celular, email_contato) VALUES (?, ?, ?, ?, ?)";
        
            // Prepared Statement para segurança
            $stmt = $conexao->prepare($sql);
            
            // "sssss" para 5 parâmetros string (nome, localizacao, contato, celular, email)
            $stmt->bind_param("sssss", $nome_empresa, $localizacao, $contato_principal, $num_celular, $email_contato); 

            if ($stmt->execute()) {
                // Sucesso na inserção 
                $cadastro_sucesso = true; 
            }
        
        if (isset($stmt)) {
            $stmt->close(); 
        }
    }
}

// ---------------------------------------------
// 3. REDIRECIONAMENTO APÓS CADASTRO BEM-SUCEDIDO
// ---------------------------------------------
if ($cadastro_sucesso === true) {
    $conexao->close();
    header("Location: lista_clientes.php?status=success_add"); 
    exit();
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Clientes</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        /* Manter o estilo, mesmo que o campo tenha mudado para input */
        textarea { resize: vertical; min-height: 100px; } 
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>Cadastro de Clientes</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; // Exibe a mensagem de erro ou duplicidade ?>

        <form method="POST" action="">
            <h2>Nova Empresa Cliente</h2>
            
            <label for="nome_empresa">Nome da Empresa:</label>
            <input type="text" id="nome_empresa" name="nome_empresa" maxlength="255" required>

            <label for="email_contato">Email de Contato:</label>
            <input type="email" id="email_contato" name="email_contato" maxlength="100" required>

            <label for="contato_principal">Contato Principal:</label>
            <input type="text" id="contato_principal" name="contato_principal" maxlength="100" required>

            <label for="num_celular">Número:</label>
            <input type="text" id="num_celular" name="num_celular" placeholder="(00) 00000-0000" maxlength="15" required>

            <label for="localizacao">Localização:</label>
            <input type="text" id="localizacao" name="localizacao" maxlength="255" required>
            
            <button type="submit">Cadastrar Cliente</button>
        </form>
        
        <div class="voltar">
             <a href="lista_clientes.php">← Voltar para Lista de Clientes</a>
        </div>
    </main>

    <script src="../../js/mascaras.js"></script>

    <?php 
    // SE o cadastro_sucesso for TRUE, executa o JS de redirecionamento
    if ($cadastro_sucesso === true) {
        echo "
            <script>
                // Chama a função JS que exibe o alerta e redireciona
                mostrarSucessoERedirecionar('Cliente cadastrado com sucesso!', 'lista_clientes.php?status=success_add');

            </script>
        ";
    }
    ?>
</body>
</html>