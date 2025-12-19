<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) { die("Erro: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

$id_chamado = $_GET['id'] ?? null;
$cadastro_sucesso = false; // Variável para controlar o disparo do JS

if (!$id_chamado) { header("Location: ../lista_chamados.php"); exit; }

// 1. PROCESSAR ATUALIZAÇÃO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST['id_cliente'];
    $id_tecnico = !empty($_POST['id_tecnico']) ? $_POST['id_tecnico'] : NULL;
    $prioridade = $_POST['prioridade'];
    $status = $_POST['status'];
    $origem = $_POST['origem'];
    $solucao = $_POST['solucao'];
    
    $data_fechamento_sql = ($status == 'Concluído') ? "data_fechamento = CURRENT_TIMESTAMP" : "data_fechamento = NULL";

    $sql_update = "UPDATE chamados SET 
                    id_cliente = ?, 
                    id_tecnico_atribuido = ?, 
                    prioridade = ?, 
                    status = ?, 
                    origem = ?, 
                    solucao = ?, 
                    $data_fechamento_sql 
                   WHERE id_chamado = ?";
    
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("iissssi", $id_cliente, $id_tecnico, $prioridade, $status, $origem, $solucao, $id_chamado);
    
    if ($stmt->execute()) {
        // Marcamos como sucesso para disparar o JS no final da página
        $cadastro_sucesso = true; 
    } else {
        $mensagem = "<div class='msg-erro'>❌ Erro ao atualizar: " . $conexao->error . "</div>";
    }
}

// 2. BUSCAR DADOS ATUAIS (Para preencher o formulário)
$sql = "SELECT c.*, cli.nome_empresa FROM chamados c 
        JOIN clientes cli ON c.id_cliente = cli.id_cliente 
        WHERE c.id_chamado = $id_chamado";
$resultado = $conexao->query($sql);
$chamado = $resultado->fetch_assoc();

$res_tecnicos = $conexao->query("SELECT id_tecnico, nome_tecnico FROM tecnicos ORDER BY nome_tecnico ASC");
$res_clientes = $conexao->query("SELECT id_cliente, nome_empresa FROM clientes ORDER BY nome_empresa ASC");
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Chamado #<?php echo $id_chamado; ?></title>
    <link rel="stylesheet" href="../../estilos/estilos.css">
    <style>
        .grid-detalhes { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .campo-cheio { grid-column: span 2; }
        .info-estatica { background: #eee; padding: 10px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; height: 38px; box-sizing: border-box; }
        select, textarea { width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>
    <header><h1>🛠️ Editar Chamado #<?php echo $id_chamado; ?></h1></header>
    <main>
        <?php if(isset($mensagem)) echo $mensagem; ?>
        
        <form method="POST" action="">
            <div class="grid-detalhes">
                <div>
                    <label for="id_cliente">Cliente:</label>
                    <select name="id_cliente" id="id_cliente" required>
                        <?php while($c = $res_clientes->fetch_assoc()): ?>
                            <option value="<?php echo $c['id_cliente']; ?>" <?php echo ($c['id_cliente'] == $chamado['id_cliente']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['nome_empresa']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="origem">Origem:</label>
                    <select name="origem" id="origem">
                        <option value="Telefone" <?php echo ($chamado['origem'] == 'Telefone') ? 'selected' : ''; ?>>Telefone</option>
                        <option value="Email" <?php echo ($chamado['origem'] == 'Email') ? 'selected' : ''; ?>>E-mail</option>
                        <option value="WhatsApp" <?php echo ($chamado['origem'] == 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
                        <option value="Portal" <?php echo ($chamado['origem'] == 'Portal') ? 'selected' : ''; ?>>Portal Web</option>
                    </select>
                </div>

                <div>
                    <label for="id_tecnico">Técnico:</label>
                    <select name="id_tecnico" id="id_tecnico">
                        <option value="">-- Sem Técnico --</option>
                        <?php 
                        $res_tecnicos->data_seek(0);
                        while($t = $res_tecnicos->fetch_assoc()): ?>
                            <option value="<?php echo $t['id_tecnico']; ?>" <?php echo ($t['id_tecnico'] == $chamado['id_tecnico_atribuido']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['nome_tecnico']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="prioridade">Prioridade:</label>
                    <select name="prioridade" id="prioridade">
                        <option value="Baixa" <?php echo ($chamado['prioridade'] == 'Baixa') ? 'selected' : ''; ?>>Baixa</option>
                        <option value="Média" <?php echo ($chamado['prioridade'] == 'Média') ? 'selected' : ''; ?>>Média</option>
                        <option value="Alta" <?php echo ($chamado['prioridade'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                        <option value="Urgente" <?php echo ($chamado['prioridade'] == 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
                    </select>
                </div>

                <div>
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="Novo" <?php echo ($chamado['status'] == 'Novo') ? 'selected' : ''; ?>>Novo</option>
                        <option value="Em Atendimento" <?php echo ($chamado['status'] == 'Em Atendimento') ? 'selected' : ''; ?>>Em Atendimento</option>
                        <option value="Aguardando Cliente" <?php echo ($chamado['status'] == 'Aguardando Cliente') ? 'selected' : ''; ?>>Aguardando Cliente</option>
                        <option value="Concluído" <?php echo ($chamado['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                        <option value="Cancelado" <?php echo ($chamado['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>

                <div>
                    <label>Aberto em:</label>
                    <div class="info-estatica"><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></div>
                </div>

                <div class="campo-cheio">
                    <label>Problema Relatado:</label>
                    <div style="background: #fff; padding: 10px; border: 1px solid #ccc; min-height: 80px; border-radius: 4px;">
                        <?php echo nl2br(htmlspecialchars($chamado['descricao_solicitacao'])); ?>
                    </div>
                </div>
            </div>

            <label for="solucao">Solução Técnica:</label>
            <textarea id="solucao" name="solucao" style="height: 120px;"><?php echo htmlspecialchars($chamado['solucao'] ?? ''); ?></textarea>
            
            <button type="submit" style="margin-top: 20px; width: 100%; padding: 12px; cursor: pointer;">Salvar Alterações</button>
        </form>

        <div class="voltar">
            <a href="lista_chamados.php">← Cancelar e Voltar</a>
        </div>
    </main>

    <?php if ($cadastro_sucesso): ?>
    <script>
        alert("Chamado atualizado com sucesso!");
        window.location.href = "lista_chamados.php";
    </script>
    <?php endif; ?>

</body>
</html>