<?php
// 1. EXIBIÇÃO DE ERROS E CONFIGURAÇÃO DE SESSÃO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('America/Sao_Paulo');

// 2. TRAVA DE SEGURANÇA: Garante que o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

// 3. RECUPERA O ID DO CLIENTE DA SESSÃO (USANDO O NOME CORRETO SALVO NO LOGIN)
$id_cliente = $_SESSION['usuario_id_cliente'] ?? null; 

if (!$id_cliente) {
    die("❌ Erro: Seu usuário não possui uma empresa vinculada na sessão. Contate o suporte.");
}

$mensagem = "";

// 4. CONEXÃO COM O BANCO DE DADOS
$caminho_banco = __DIR__ . '/../../tabelas/conexao.php';
if (!file_exists($caminho_banco)) {
    die("❌ Erro fatal: O PHP não achou o arquivo de conexão no caminho: " . $caminho_banco);
}
include_once($caminho_banco);
$conexao->set_charset("utf8mb4");

// 5. PROCESSAMENTO DO FORMULÁRIO (QUANDO CLICA EM ENVIAR)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_usuario = $_SESSION['usuario_id'];
    $descricao_solicitacao = trim($_POST['descricao']);

    if (empty($descricao_solicitacao)) {
        header("Location: cadastrar_chamado_usuario.php?erro=campo_vazio");
        exit();
    }

    $status = 'Novo';
    $prioridade = 'Media';
    $origem = 'Portal';
    $id_tecnico_atribuido = NULL; 

    $sql = "INSERT INTO chamados (id_cliente, id_usuario, id_tecnico_atribuido, status, prioridade, descricao_solicitacao, origem) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("iiissss", $id_cliente, $id_usuario, $id_tecnico_atribuido, $status, $prioridade, $descricao_solicitacao, $origem);

    if ($stmt->execute()) {
        $stmt->close();
        $conexao->close();
        header("Location: cadastrar_chamado_usuario.php?sucesso=1");
        exit();
    } else {
        die("❌ Erro ao salvar no banco de dados: " . $conexao->error);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Abrir Chamado</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        textarea { resize: vertical; min-height: 150px; width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        .form-container { max-width: 500px; margin: 30px auto; padding: 20px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; font-family: sans-serif; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header style="text-align: center; margin-top: 20px;">
        <h1>📝 Abrir Novo Chamado</h1>
    </header>
    <hr>

    <main>
        <div class="form-container">
            <?php 
                if (isset($_GET['erro']) && $_GET['erro'] == 'campo_vazio') {
                    echo "<p style='color: red; font-weight: bold;'>⚠️ Por favor, preencha a descrição do problema.</p>";
                }
            ?>

            <form method="POST" action="cadastrar_chamado_usuario.php">
                <h2>Detalhes da Solicitação</h2>
                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                    Descreva o problema com o máximo de detalhes possível para que possamos te ajudar o quanto antes.
                </p>
                
                <label for="descricao" style="font-weight: bold; display: block; margin-bottom: 8px;">Descrição Detalhada do Problema (*):</label>
                <textarea id="descricao" name="descricao" placeholder="Ex: Minha impressora não está puxando papel / O sistema X está dando erro de conexão..." required></textarea>
                
                <button type="submit" style="background-color: #4CAF50; color: white; border: none; padding: 12px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 15px;">
                    Enviar Solicitação
                </button>
            </form>
        </div>
    </main>

    <script src="../../js/mascaras.js"></script>

    <script>
        // Identifica se o chamado foi gravado com sucesso
        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            alert('Chamado aberto com sucesso!');
            window.location.href = 'cadastrar_chamado_usuario.php';
        <?php endif; ?>
    </script>
</body>
</html>