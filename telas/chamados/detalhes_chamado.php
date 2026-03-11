<?php
// Define o fuso horário para o horário de Brasília
date_default_timezone_set('America/Sao_Paulo');
// ---------------------------------------------
// 1. CONFIGURAÇÃO E CONEXÃO
// ---------------------------------------------
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "sistemadechamados"; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);
if ($conexao->connect_error) { die("Erro: " . $conexao->connect_error); }
$conexao->set_charset("utf8mb4");

$id_chamado = $_GET['id'] ?? null;
$cadastro_sucesso = false; 

if (!$id_chamado) { header("Location: lista_chamados.php"); exit; }

// ---------------------------------------------
// 2. PROCESSAR ATUALIZAÇÃO (POST)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST['id_cliente'];
    $id_tecnico = !empty($_POST['id_tecnico']) ? $_POST['id_tecnico'] : NULL;
    $prioridade = $_POST['prioridade'];
    $status     = $_POST['status'];
    $origem     = $_POST['origem'];
    $solucao    = $_POST['solucao'];
    
    // Lógica para data de fechamento: Grava se concluir ou cancelar, senão limpa
    if ($status == 'Concluido' || $status == 'Cancelado') {
        $data_fechamento = date('Y-m-d H:i:s');
    } else {
        $data_fechamento = NULL;
    }

    // Query padronizada com 8 parâmetros (incluindo o ID no WHERE)
    $sql_update = "UPDATE chamados SET 
                    id_cliente = ?, 
                    id_tecnico_atribuido = ?, 
                    prioridade = ?, 
                    status = ?, 
                    origem = ?, 
                    solucao = ?, 
                    data_fechamento = ? 
                   WHERE id_chamado = ?";
    
    $stmt = $conexao->prepare($sql_update);
    
    // "iisssssi": 2 inteiros, 5 strings (solucao e data inclusas), 1 inteiro final
    $stmt->bind_param("iisssssi", 
        $id_cliente, 
        $id_tecnico, 
        $prioridade, 
        $status, 
        $origem, 
        $solucao, 
        $data_fechamento, 
        $id_chamado
    );
    
    if ($stmt->execute()) {
        $cadastro_sucesso = true; 
    } else {
        $mensagem = "<div class='msg-erro'>❌ Erro ao atualizar: " . $conexao->error . "</div>";
    }
}

// ---------------------------------------------
// 3. BUSCAR DADOS PARA O FORMULÁRIO (ATUALIZADO)
// ---------------------------------------------
$sql = "SELECT c.*, 
               cli.nome_empresa, 
               cli.num_celular AS tel_cliente, 
               cli.localizacao AS local_cliente,
               tec.nome_tecnico,
               tec.num_celular AS tel_tecnico,
               tec.localizacao AS local_tecnico
        FROM chamados c 
        JOIN clientes cli ON c.id_cliente = cli.id_cliente 
        LEFT JOIN tecnicos tec ON c.id_tecnico_atribuido = tec.id_tecnico
        WHERE c.id_chamado = $id_chamado";

$resultado = $conexao->query($sql);
$chamado = $resultado->fetch_assoc();

// Consultas para alimentar os selects - Atualizadas com os novos nomes de coluna
$res_tecnicos = $conexao->query("SELECT id_tecnico, nome_tecnico FROM tecnicos WHERE status_tecnico = 'Ativo' ORDER BY nome_tecnico ASC");
$res_clientes = $conexao->query("SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC");
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
        .info-estatica { background: #eee; padding: 10px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; height: 38px; box-sizing: border-box; display: flex; align-items: center; }
        select, textarea { width: 100%; box-sizing: border-box; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .btn-salvar { margin-top: 20px; width: 100%; padding: 12px; cursor: pointer; background-color: #28a745; color: white; border: none; font-weight: bold; border-radius: 4px; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>

    <header><h1 style="text-align:center;">🛠️ Editar Chamado #<?php echo $id_chamado; ?></h1></header>
    
    <main style="max-width: 800px; margin: 0 auto; padding: 20px;">
        <?php if(isset($mensagem)) echo $mensagem; ?>
        
        <form method="POST" action="">
<div class="grid-detalhes">
    <div>
        <label>Cliente:</label>
        <select name="id_cliente" required>
            <?php while($c = $res_clientes->fetch_assoc()): ?>
                <option value="<?php echo $c['id_cliente']; ?>" <?php echo ($c['id_cliente'] == $chamado['id_cliente']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c['nome_empresa']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Celular:</label>
        <div class="info-estatica"><?php echo $chamado['tel_cliente'] ?? '---'; ?></div>
    </div>
    <div>
        <label>Localização:</label>
        <div class="info-estatica"><?php echo $chamado['local_cliente'] ?? '---'; ?></div>
    </div>

    <div>
        <label>Técnico:</label>
        <select name="id_tecnico">
            <option value="">-- Sem Técnico --</option>
            <?php while($t = $res_tecnicos->fetch_assoc()): ?>
                <option value="<?php echo $t['id_tecnico']; ?>" <?php echo ($t['id_tecnico'] == $chamado['id_tecnico_atribuido']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t['nome_tecnico']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label>Celular:</label>
        <div class="info-estatica"><?php echo !empty($chamado['nome_tecnico']) ? ($chamado['tel_tecnico'] ?? '---') : '---'; ?></div>
    </div>
    <div>
        <label>Localização:</label>
        <div class="info-estatica"><?php echo !empty($chamado['nome_tecnico']) ? ($chamado['local_tecnico'] ?? '---') : '---'; ?></div>
    </div>

    <div>
        <label>Origem:</label>
        <select name="origem">
            <option value="Telefone" <?php echo ($chamado['origem'] == 'Telefone') ? 'selected' : ''; ?>>Telefone</option>
            <option value="Email" <?php echo ($chamado['origem'] == 'Email') ? 'selected' : ''; ?>>E-mail</option>
            <option value="WhatsApp" <?php echo ($chamado['origem'] == 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
            <option value="Portal" <?php echo ($chamado['origem'] == 'Portal') ? 'selected' : ''; ?>>Sistema</option>
        </select>
    </div>
    <div>
        <label>Prioridade:</label>
        <select name="prioridade">
            <option value="Baixa" <?php echo ($chamado['prioridade'] == 'Baixa') ? 'selected' : ''; ?>>Baixa</option>
            <option value="Média" <?php echo ($chamado['prioridade'] == 'Média') ? 'selected' : ''; ?>>Média</option>
            <option value="Alta" <?php echo ($chamado['prioridade'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
            <option value="Urgente" <?php echo ($chamado['prioridade'] == 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
        </select>
    </div>
    <div>
        <label>Status Atual:</label>
        <select name="status">
            <option value="Novo" <?php echo ($chamado['status'] == 'Novo') ? 'selected' : ''; ?>>Novo</option>
            <option value="Em Atendimento" <?php echo ($chamado['status'] == 'Em Atendimento') ? 'selected' : ''; ?>>Em Atendimento</option>
            <option value="Concluido" <?php echo ($chamado['status'] == 'Concluido') ? 'selected' : ''; ?>>Concluido</option>
            <option value="Cancelado" <?php echo ($chamado['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
        </select>
    </div>

    <div>
        <label>Aberto em:</label>
        <div class="info-estatica"><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></div>
    </div>
    <div class="campo-cheio" style="grid-column: span 2;">
        <label>Problema Relatado:</label>
        <div style="background: #fff; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <?php echo nl2br(htmlspecialchars($chamado['descricao_solicitacao'])); ?>
        </div>
    </div>
</div>

            <label for="solucao"><strong>Solução Técnica:</strong></label>
            <textarea id="solucao" name="solucao" placeholder="Descreva aqui o que foi feito para resolver o problema..." style="height: 100px; margin-top: 5px;"><?php echo htmlspecialchars($chamado['solucao'] ?? ''); ?></textarea>
            
            <button type="submit" class="btn-salvar">💾 Salvar Alterações</button>
        </form>

        <div style="margin-top: 20px; text-align: center;">
            <a href="lista_chamados.php" style="text-decoration: none; color: #666;">← Cancelar e Voltar</a>
        </div>
    </main>

    <?php if ($cadastro_sucesso): ?>
        <script>
            alert("✅ Chamado atualizado com sucesso!");
            window.location.href = "lista_chamados.php";
        </script>
        <?php endif; ?>

        <script src="../../js/mascaras.js?v=<?php echo time(); ?>"></script>
    </body>
</html>