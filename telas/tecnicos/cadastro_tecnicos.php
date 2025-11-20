<?php
// ---------------------------------------------
// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
// ---------------------------------------------
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; // Mantenha o nome exato do seu BD

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

$mensagem = "";
$cadastro_sucesso = false; // Flag para indicar ao HTML que deve rodar o script JS

// ---------------------------------------------
// 2. LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (Com tratamento de exceção)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome_tecnico'];
    $email = $_POST['email'];
    $num_celular_limpo = preg_replace("/[^0-9]/", "", $_POST['num_celular']); 

    $sql = "INSERT INTO tecnicos (nome_tecnico, email, num_celular) VALUES (?, ?, ?)";
    
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sss", $nome, $email, $num_celular_limpo); 

        if ($stmt->execute()) {
            // Sucesso na inserção -> Define a flag para rodar o JS no final da página
            $cadastro_sucesso = true; 
        }

    } catch (mysqli_sql_exception $e) {
        // Captura erro de Duplicidade (código 1062) ou outros erros SQL
        if ($e->getCode() == 1062) {
            $mensagem = "<div class='msg-erro'>Erro: O e-mail '$email' já está cadastrado para outro técnico.</div>";
        } else {
            $mensagem = "<div class='msg-erro'>Erro ao cadastrar técnico: " . $e->getMessage() . "</div>";
        }
    }
    
    if (isset($stmt)) {
        $stmt->close(); 
    }
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Técnicos</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
</head>
<body>

    <header>
        <h1>Cadastro de Técnicos</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; // Exibe a mensagem de erro formatada por CSS ?>

        <form method="POST" action="">
            <h2>Novo Técnico</h2>
            
            <label for="nome_tecnico">Nome:</label>
            <input type="text" id="nome_tecnico" name="nome_tecnico" required>

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>

            <label for="num_celular">Celular:</label>
            <input type="tel" id="num_celular" name="num_celular" placeholder="(00) 00000-0000" maxlength="15">
            
            <button type="submit">Cadastrar Técnico</button>
        </form>
        
        <div class="voltar">
             <a href="lista_tecnicos.php">← Voltar para Lista de Técnicos</a>
        </div>
    </main>

    <script src="../../js/mascaras.js"></script>

    <?php 
    // SE o cadastro_sucesso for TRUE, executa o script JS que fará o ALERTA e o REDIRECIONAMENTO
    if ($cadastro_sucesso === true) {
        echo "
            <script>
                // Chama a função JS (que está no mascaras.js)
                mostrarSucessoERedirecionar('Técnico cadastrado com sucesso!', 'lista_tecnicos.php');

            </script>
        ";
    }
    ?>
</body>
</html>