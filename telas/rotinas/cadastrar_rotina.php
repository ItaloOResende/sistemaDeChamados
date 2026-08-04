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

// Busca todas as empresas ativas para preencher o select
$res_empresas = $conexao->query("SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC");

// PROCESSA O CADASTRO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST['id_cliente'];
    $titulo     = trim($_POST['titulo']);
    $descricao  = trim($_POST['descricao']);
    $prioridade = $_POST['prioridade'];
    $frequencia = $_POST['frequencia'];

    if (empty($titulo) || empty($descricao) || empty($id_cliente)) {
        $mensagem_erro = "<div class='msg-erro'>❌ Erro: Todos os campos obrigatórios (*) devem ser preenchidos.</div>";
    } else {
        $id_cliente_db = ($id_cliente === 'todas') ? NULL : (int)$id_cliente;

        $sql = "INSERT INTO rotinas (id_cliente, titulo, descricao, prioridade, frequencia) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("issss", $id_cliente_db, $titulo, $descricao, $prioridade, $frequencia);

        if ($stmt->execute()) {
            $sucesso_js = true;
        } else {
            $mensagem_erro = "<div class='msg-erro'>❌ Erro ao cadastrar rotina: " . $conexao->error . "</div>";
        }
        $stmt->close();
    }
}

$conexao->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Rotina</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>🤖 Cadastrar Rotina</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem_erro; ?>

        <form method="POST" action="">
            <h2>Dados da Rotina</h2>

            <label for="id_cliente">Empresa Alvo (*):</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">-- Selecione a Empresa --</option>
                <option value="todas" style="font-weight: bold; color: #0056b3;">🏢 [TODAS AS EMPRESAS ATIVAS]</option>
                <?php if ($res_empresas && $res_empresas->num_rows > 0): ?>
                    <?php while($emp = $res_empresas->fetch_assoc()): ?>
                        <option value="<?php echo $emp['id_cliente']; ?>">
                            <?php echo htmlspecialchars($emp['nome_empresa']); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <label for="titulo">Título da Rotina (*):</label>
            <input type="text" id="titulo" name="titulo" placeholder="Ex: Verificação Diária de Logs de Firewall" required>

            <label for="descricao">Descrição / Instruções do Chamado (*):</label>
            <textarea id="descricao" name="descricao" rows="5" placeholder="Digite aqui o texto padrão que virá no chamado gerado..." required></textarea>

            <label for="prioridade">Prioridade (*):</label>
            <select id="prioridade" name="prioridade" required>
                <option value="Baixa">Baixa</option>
                <option value="Media" selected>Média</option>
                <option value="Alta">Alta</option>
                <option value="Urgente">Urgente</option>
            </select>

            <label for="frequencia">Frequência (*):</label>
            <select id="frequencia" name="frequencia" required>
                <option value="Diario">Diário</option>
                <option value="Semanal">Semanal</option>
                <option value="Mensal">Mensal</option>
            </select>

            <button type="submit">Salvar Rotina</button>
        </form>

        <div class="voltar" style="margin-top: 20px;">
             <a href="lista_rotinas.php">← Voltar para Lista de Rotinas</a>
        </div>
    </main>

    <?php if ($sucesso_js): ?>
    <script>
        alert("✅ Rotina cadastrada com sucesso!");
        window.location.href = "lista_rotinas.php";
    </script>
    <?php endif; ?>

</body>
</html>