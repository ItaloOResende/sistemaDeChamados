<?php
session_start();

// TRAVA DE SEGURANÇA: Só administrador acessa essa tela
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'admin') {
    header("Location: ../chamados/lista_chamados.php");
    exit();
}

include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem_erro = "";
$sucesso_js = false;

// Pega o ID da rotina vindo pela URL
$id_rotina = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_rotina === 0) {
    header("Location: lista_rotinas.php");
    exit();
}

// 1. PROCESSA A ATUALIZAÇÃO DOS DADOS (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST['id_cliente'];
    $titulo     = trim($_POST['titulo']);
    $descricao  = trim($_POST['descricao']);
    $prioridade = $_POST['prioridade'];
    $frequencia = $_POST['frequencia'];

    if (empty($titulo) || empty($descricao) || empty($id_cliente)) {
        $mensagem_erro = "<div class='msg-erro'>❌ Preencha todos os campos obrigatórios (*).</div>";
    } else {
        // Se selecionou 'todas', salva NULL na coluna id_cliente
        $id_cliente_db = ($id_cliente === 'todas') ? NULL : (int)$id_cliente;

        $sql_update = "UPDATE rotinas SET id_cliente = ?, titulo = ?, descricao = ?, prioridade = ?, frequencia = ? WHERE id_rotina = ?";
        $stmt = $conexao->prepare($sql_update);
        $stmt->bind_param("issssi", $id_cliente_db, $titulo, $descricao, $prioridade, $frequencia, $id_rotina);

        if ($stmt->execute()) {
            $sucesso_js = true;
        } else {
            $mensagem_erro = "<div class='msg-erro'>❌ Erro ao atualizar rotina: " . $conexao->error . "</div>";
        }
        $stmt->close();
    }
}

// 2. BUSCA DADOS DA ROTINA PARA PREENCHER O FORMULÁRIO
$stmt_rotina = $conexao->prepare("SELECT * FROM rotinas WHERE id_rotina = ?");
$stmt_rotina->bind_param("i", $id_rotina);
$stmt_rotina->execute();
$res = $stmt_rotina->get_result();

if ($res->num_rows === 0) {
    $stmt_rotina->close();
    $conexao->close();
    header("Location: lista_rotinas.php");
    exit();
}

$rotina = $res->fetch_assoc();
$stmt_rotina->close();

// 3. BUSCA EMPRESAS ATIVAS PARA O SELECT
$res_empresas = $conexao->query("SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC");

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Rotina #<?php echo $rotina['id_rotina']; ?></title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>✏️ Editar Rotina Automática</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem_erro; ?>

        <form method="POST" action="">
            <h2>Editar Rotina (#<?php echo $rotina['id_rotina']; ?>)</h2>

            <label for="id_cliente">Empresa Alvo (*):</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">-- Selecione a Empresa --</option>
                <option value="todas" style="font-weight: bold; color: #0056b3;" <?php echo is_null($rotina['id_cliente']) ? 'selected' : ''; ?>>🏢 [TODAS AS EMPRESAS ATIVAS]</option>
                <?php if ($res_empresas && $res_empresas->num_rows > 0): ?>
                    <?php while($emp = $res_empresas->fetch_assoc()): ?>
                        <option value="<?php echo $emp['id_cliente']; ?>" <?php echo ($rotina['id_cliente'] == $emp['id_cliente']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($emp['nome_empresa']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <label for="titulo">Título da Rotina (*):</label>
            <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($rotina['titulo']); ?>" required>

            <label for="descricao">Descrição / Instruções do Chamado (*):</label>
            <textarea id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($rotina['descricao']); ?></textarea>

            <label for="prioridade">Prioridade (*):</label>
            <select id="prioridade" name="prioridade" required>
                <option value="Baixa" <?php echo ($rotina['prioridade'] === 'Baixa') ? 'selected' : ''; ?>>Baixa</option>
                <option value="Media" <?php echo ($rotina['prioridade'] === 'Media') ? 'selected' : ''; ?>>Média</option>
                <option value="Alta" <?php echo ($rotina['prioridade'] === 'Alta') ? 'selected' : ''; ?>>Alta</option>
                <option value="Urgente" <?php echo ($rotina['prioridade'] === 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
            </select>

            <label for="frequencia">Frequência (*):</label>
            <select id="frequencia" name="frequencia" required>
                <option value="Diario" <?php echo ($rotina['frequencia'] === 'Diario') ? 'selected' : ''; ?>>Diário</option>
                <option value="Semanal" <?php echo ($rotina['frequencia'] === 'Semanal') ? 'selected' : ''; ?>>Semanal</option>
                <option value="Mensal" <?php echo ($rotina['frequencia'] === 'Mensal') ? 'selected' : ''; ?>>Mensal</option>
            </select>

            <button type="submit">Salvar Alterações</button>
        </form>

        <div class="voltar" style="margin-top: 20px;">
             <a href="lista_rotinas.php">← Cancelar e Voltar</a>
        </div>
    </main>

    <?php if ($sucesso_js): ?>
    <script>
        alert("✅ Rotina atualizada com sucesso!");
        window.location.href = "lista_rotinas.php";
    </script>
    <?php endif; ?>

</body>
</html>