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
$cliente_encontrado = false;

// 2. LÓGICA DE ATUALIZAÇÃO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente        = (int)$_POST['id_cliente'];
    $nome_empresa      = trim($_POST['nome_empresa']);
    $codigo_empresa    = strtoupper(trim($_POST['codigo_empresa']));
    $email_contato     = trim($_POST['email_contato']); 
    $contato_principal = trim($_POST['contato_principal']);
    $num_celular       = preg_replace("/[^0-9]/", "", $_POST['num_celular']);
    $localizacao       = trim($_POST['localizacao']);

    if (empty($nome_empresa) || empty($codigo_empresa) || empty($email_contato) || empty($contato_principal) || empty($num_celular) || empty($localizacao)) {
        $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos são obrigatórios.</div>";
        $cliente = $_POST;
    } else {
        $sql_update = "UPDATE clientes SET nome_empresa = ?, codigo_empresa = ?, email_contato = ?, contato_principal = ?, num_celular = ?, localizacao = ? WHERE id_cliente = ?";
        
        try {
            $stmt_update = $conexao->prepare($sql_update);
            $stmt_update->bind_param("ssssssi", $nome_empresa, $codigo_empresa, $email_contato, $contato_principal, $num_celular, $localizacao, $id_cliente);

            if ($stmt_update->execute()) {
                $conexao->close();
                header("Location: lista_clientes.php?status=success_edit"); 
                exit();
            }

        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $mensagem = "<div class='msg-erro'>❌ Erro: O código de empresa '$codigo_empresa' ou e-mail já está em uso por outro cliente.</div>";
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro ao atualizar: " . $e->getMessage() . "</div>";
            }
            $cliente = $_POST;
        }

        if (isset($stmt_update)) {
            $stmt_update->close();
        }
    }
}

// 3. LÓGICA DE CARREGAMENTO DOS DADOS DO CLIENTE (GET / PÓS-POST)
if ((isset($_GET['id']) && is_numeric($_GET['id'])) || (isset($id_cliente) && $id_cliente > 0 && !isset($cliente))) {
    $id_para_busca = isset($_GET['id']) ? (int)$_GET['id'] : $id_cliente; 
    
    $sql_select = "SELECT id_cliente, nome_empresa, codigo_empresa, email_contato, contato_principal, num_celular, localizacao FROM clientes WHERE id_cliente = ?";
    $stmt_select = $conexao->prepare($sql_select);
    $stmt_select->bind_param("i", $id_para_busca);
    $stmt_select->execute();
    $resultado = $stmt_select->get_result();

    if ($resultado->num_rows == 1) {
        $cliente = $resultado->fetch_assoc();
    } else if (!isset($cliente)) {
        $mensagem = "<div class='msg-erro'>Cliente não encontrado ou ID inválido.</div>";
    }
    $stmt_select->close();
}

$conexao->close();

if (isset($cliente) && $cliente) { $cliente_encontrado = true; }
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Empresa</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        .caixa-codigo { background: #e7f3fe; border-left: 5px solid #2196F3; padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>🏭 Editar Empresa</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; ?>

        <?php if ($cliente_encontrado): ?>
            <form method="POST" action="">
                <h2>Editando: <?php echo htmlspecialchars($cliente['nome_empresa'] ?? 'Dados Inválidos'); ?></h2>
                
                <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>">

                <label for="nome_empresa">Nome da Empresa (*):</label>
                <input type="text" id="nome_empresa" name="nome_empresa" value="<?php echo htmlspecialchars($cliente['nome_empresa']); ?>" required>

                <!-- 🔑 CÓDIGO DA EMPRESA PARA AUTOCADASTRO / LGPD -->
                <label for="codigo_empresa">Código da Empresa / LGPD (*):</label>
                <input type="text" id="codigo_empresa" name="codigo_empresa" value="<?php echo htmlspecialchars($cliente['codigo_empresa'] ?? ''); ?>" required style="text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">
                <small style="color: #666; font-size: 11px; display: block; margin-top: -8px; margin-bottom: 15px;">Este é o código de acesso que os funcionários usam para se cadastrar no sistema.</small>

                <label for="email_contato">Email de Contato (*):</label>
                <input type="email" id="email_contato" name="email_contato" value="<?php echo htmlspecialchars($cliente['email_contato']); ?>" required>

                <label for="contato_principal">Contato Principal (*):</label>
                <input type="text" id="contato_principal" name="contato_principal" value="<?php echo htmlspecialchars($cliente['contato_principal']); ?>" required>

                <label for="num_celular">Número / Celular (*):</label>
                <input type="text" id="num_celular" name="num_celular" value="<?php echo htmlspecialchars($cliente['num_celular'] ?? ''); ?>" placeholder="(00) 00000-0000" maxlength="15" required>

                <label for="localizacao">Localização (*):</label>
                <input type="text" id="localizacao" name="localizacao" value="<?php echo htmlspecialchars($cliente['localizacao']); ?>" required>
                
                <button type="submit">Salvar Alterações</button>
            </form>
        <?php endif; ?>
        
        <div class="voltar">
             <a href="lista_clientes.php">← Voltar para Lista de Clientes</a>
        </div>
    </main>

    <script src="../../js/mascaras.js?v=<?php echo time(); ?>"></script>
</body>
</html>