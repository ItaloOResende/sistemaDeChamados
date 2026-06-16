<?php
// ... CÓDIGO PHP DE CONEXÃO, BUSCA E PROCESSAMENTO (ALTERADO PARA PRODUÇÃO) ...
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

// BUSCA APENAS CLIENTES QUE ESTÃO COM STATUS_CLIENTE 'ATIVO'
$sql_clientes = "SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC";
$resultado_clientes = $conexao->query($sql_clientes);

// BUSCA APENAS TÉCNICOS QUE ESTÃO COM STATUS_TECNICO 'ATIVO'
$sql_tecnicos = "SELECT id_tecnico, nome_tecnico FROM tecnicos WHERE status_tecnico = 'Ativo' ORDER BY nome_tecnico ASC";
$resultado_tecnicos = $conexao->query($sql_tecnicos);

$mensagem = "";
$cadastro_sucesso = false;

// ... LÓGICA DE INSERT (INALTERADA) ...

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... (Variáveis do POST) ...
    $id_cliente = $_POST['id_cliente'];
    $id_tecnico_atribuido = !empty($_POST['id_tecnico_atribuido']) ? $_POST['id_tecnico_atribuido'] : NULL;
    $prioridade = $_POST['prioridade'];
    $descricao_solicitacao = $_POST['descricao_solicitacao'];
    $origem = $_POST['origem'];

    $sql = "INSERT INTO chamados (id_cliente, id_tecnico_atribuido, prioridade, descricao_solicitacao, origem) VALUES (?, ?, ?, ?, ?)";
    
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("iisss", $id_cliente, $id_tecnico_atribuido, $prioridade, $descricao_solicitacao, $origem); 

        if ($stmt->execute()) {
            $cadastro_sucesso = true; 
        }

    } catch (mysqli_sql_exception $e) {
        $mensagem = "<div class='msg-erro'>Erro ao abrir chamado: " . $e->getMessage() . "</div>";
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
    <title>Abrir Novo Chamado</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php 
        include_once('../principal/menu.php'); 
    ?>
    <header>
        <h1>📞 Abrir Novo Chamado</h1>
    </header>
    <hr>
    <main>
        <?php echo $mensagem; ?>

        <form method="POST" action="">
            <h2>Detalhes da Solicitação</h2>
            
            <label for="id_cliente">Cliente:</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">-- Selecione a Empresa --</option>
                <?php while($cliente = $resultado_clientes->fetch_assoc()): ?>
                    <option value="<?php echo $cliente['id_cliente']; ?>">
                        <?php echo htmlspecialchars($cliente['nome_empresa']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="id_tecnico_atribuido">Técnico Atribuído (Opcional):</label>
            <select id="id_tecnico_atribuido" name="id_tecnico_atribuido">
                <option value="">-- Nenhum Técnico Atribuído (Novo) --</option>
                <?php while($tecnico = $resultado_tecnicos->fetch_assoc()): ?>
                    <option value="<?php echo $tecnico['id_tecnico']; ?>">
                        <?php echo htmlspecialchars($tecnico['nome_tecnico']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="prioridade">Prioridade:</label>
            <select id="prioridade" name="prioridade" required>
                <option value="Baixa">Baixa</option>
                <option value="Média" selected>Média</option>
                <option value="Alta">Alta</option>
                <option value="Urgente">Urgente</option>
            </select>

            <label for="origem">Origem da Solicitação:</label>
            <select id="origem" name="origem" required>
                <option value="Sistema" selected>Sistema</option>
                <option value="Telefone">Telefone</option>
                <option value="Whatsapp">Whatsapp</option>
                <option value="Email">E-mail</option>
            </select>
            <label for="descricao_solicitacao">Descrição Detalhada do Problema:</label>
            <textarea id="descricao_solicitacao" name="descricao_solicitacao" required></textarea>
            <button type="submit">Abrir Chamado</button>

        </form>
        
        <div class="voltar">
             <a href="lista_chamados.php">← Voltar para a Lista de Chamados</a>
        </div>
    </main>
    
    <script src="../js/mascaras.js"></script>

<?php 
if ($cadastro_sucesso === true) {
    echo "
        <script>
            window.onload = function() {
                if (typeof mostrarSucessoERedirecionar === 'function') {
                    mostrarSucessoERedirecionar('✅ Chamado aberto com sucesso!', 'lista_chamados.php');
                } else {
                    alert('✅ Chamado aberto com sucesso!');
                    window.location.href = 'lista_chamados.php';
                }
            };
        </script>
    ";
}
?>
</body>
</html>