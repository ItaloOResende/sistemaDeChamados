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
$tecnico = null;
$id_tecnico = 0;

// ---------------------------------------------
// 2. LÓGICA DE ATUALIZAÇÃO (POST)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_tecnico = $_POST['id_tecnico'];
    $nome = $_POST['nome_tecnico'];
    $email = $_POST['email'];
    $num_celular_limpo = preg_replace("/[^0-9]/", "", $_POST['num_celular']); 

    $sql_update = "UPDATE tecnicos SET nome_tecnico = ?, email = ?, num_celular = ? WHERE id_tecnico = ?";
    
    $stmt_update = $conexao->prepare($sql_update);
    $stmt_update->bind_param("sssi", $nome, $email, $num_celular_limpo, $id_tecnico); 

    if ($stmt_update->execute()) {
        $mensagem = "<div class='msg-sucesso'>Técnico atualizado com sucesso!</div>";
    } else {
        $mensagem = "<div class='msg-erro'>Erro ao atualizar: " . $stmt_update->error . "</div>";
    }

    $stmt_update->close();
}

// ---------------------------------------------
// 3. LÓGICA DE CARREGAMENTO DE DADOS (GET)
// ---------------------------------------------
if (isset($_GET['id']) || (isset($id_tecnico) && $id_tecnico > 0)) {
    $id_tecnico = isset($_GET['id']) ? $_GET['id'] : $id_tecnico;
    
    $sql_select = "SELECT id_tecnico, nome_tecnico, email, num_celular FROM tecnicos WHERE id_tecnico = ?";
    
    $stmt_select = $conexao->prepare($sql_select);
    $stmt_select->bind_param("i", $id_tecnico);
    $stmt_select->execute();
    $resultado = $stmt_select->get_result();

    if ($resultado->num_rows == 1) {
        $tecnico = $resultado->fetch_assoc();
    } else {
        $mensagem = "<div class='msg-erro'>Técnico não encontrado!</div>";
    }
    $stmt_select->close();
}

$conexao->close();

if (!$tecnico && $id_tecnico == 0) {
    $mensagem = "<div class='msg-erro'>Nenhum ID de técnico fornecido para edição.</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Técnico</title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
</head>
<body>

    <header>
        <h1>✏️ Editar Técnico</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; ?>

        <?php if ($tecnico): ?>
            <form method="POST" action="">
                <h2>Editando: <?php echo htmlspecialchars($tecnico['nome_tecnico']); ?></h2>
                
                <input type="hidden" name="id_tecnico" value="<?php echo htmlspecialchars($tecnico['id_tecnico']); ?>">

                <label for="nome_tecnico">Nome Completo do Técnico:</label>
                <input type="text" id="nome_tecnico" name="nome_tecnico" value="<?php echo htmlspecialchars($tecnico['nome_tecnico']); ?>" required>

                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($tecnico['email']); ?>" required>

                <label for="num_celular">Número de Celular:</label>
                <input type="tel" id="num_celular" name="num_celular" value="<?php echo htmlspecialchars($tecnico['num_celular']); ?>" placeholder="(00) 00000-0000" maxlength="15">
                
                <button type="submit">Salvar Alterações</button>
            </form>
        <?php endif; ?>
        
        <div class="voltar">
             <a href="lista_tecnicos.php">← Voltar para Lista de Técnicos</a>
        </div>
    </main>

    <script src="../../js/mascaras.js"></script>
</body>
</html>