<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// 1. CONFIGURAÇÃO E CONEXÃO
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$id_chamado = $_GET['id'] ?? null;
$cadastro_sucesso = false;

if (!$id_chamado) { 
    header("Location: lista_chamados.php"); 
    exit; 
}

// ---------------------------------------------
// 2. PROCESSAR ATUALIZAÇÃO (POST)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Busca os valores originais do próprio chamado atual para não quebrar o bind_param.
    $sql_origem_dados = "SELECT id_cliente, id_usuario FROM chamados WHERE id_chamado = ?";
    $stmt_origem = $conexao->prepare($sql_origem_dados);
    $stmt_origem->bind_param("i", $id_chamado);
    $stmt_origem->execute();
    $dados_originais = $stmt_origem->get_result()->fetch_assoc();
    $stmt_origem->close();

    $id_cliente = $dados_originais['id_cliente'];
    $id_usuario = $dados_originais['id_usuario'];
    
    $id_tecnico = !empty($_POST['id_tecnico']) ? (int)$_POST['id_tecnico'] : NULL;
    $prioridade = $_POST['prioridade'];
    $status     = $_POST['status'];
    $origem     = $_POST['origem'];
    $solucao    = $_POST['solucao'];
    
    if ($status == 'Concluido' || $status == 'Cancelado') {
        $data_fechamento = date('Y-m-d H:i:s');
    } else {
        $data_fechamento = NULL;
    }

    $sql_update = "UPDATE chamados SET 
                    id_cliente = ?, 
                    id_usuario = ?,
                    id_tecnico_atribuido = ?, 
                    prioridade = ?, 
                    status = ?, 
                    origem = ?, 
                    solucao = ?, 
                    data_fechamento = ? 
                   WHERE id_chamado = ?";
    
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("iiisssssi", 
        $id_cliente, 
        $id_usuario,
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
// 3. RECUPERAÇÃO DOS DADOS DO CHAMADO (Mapeamento de Chaves Estrangeiras)
// ---------------------------------------------
// QUERY CORRIGIDA: Agora busca o 'num_celular' da tabela 'usuarios' (solicitante)
$sql = "SELECT c.*, 
               cli.nome_empresa, 
               cli.localizacao AS local_cliente,
               solicitante.nome AS nome_solicitante,
               solicitante.num_celular AS tel_usuario, 
               u.nome AS nome_tecnico,
               u.num_celular AS tel_tecnico
        FROM chamados c 
        JOIN clientes cli ON c.id_cliente = cli.id_cliente 
        LEFT JOIN usuarios solicitante ON c.id_usuario = solicitante.id
        LEFT JOIN usuarios u ON c.id_tecnico_atribuido = u.id
        WHERE c.id_chamado = $id_chamado";

$resultado = $conexao->query($sql);
$chamado = $resultado->fetch_assoc();

// Carga dos arrays de técnicos ativos para mapeamento dinâmico no front-end
$res_tecnicos = $conexao->query("SELECT id, nome, num_celular FROM usuarios WHERE (perfil = 'tecnico' OR perfil = 'admin') AND status = 'Ativo' ORDER BY nome ASC");
$tecnicos_data = [];
while($t = $res_tecnicos->fetch_assoc()) {
    $tecnicos_data[] = $t;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Chamado #<?php echo $id_chamado; ?></title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
    <style>
        .grid-detalhes { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f4f4f4; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .campo-cheio { grid-column: span 2; }
        .info-estatica { background: #eee; padding: 10px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; height: 38px; box-sizing: border-box; display: flex; align-items: center; color: #555; }
        select, textarea { width: 100%; box-sizing: border-box; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .disabled-select { background: #e9ecef; cursor: not-allowed; color: #6c757d; }
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
                    <label>Empresa:</label>
                    <select class="disabled-select" disabled>
                        <option value=""><?php echo htmlspecialchars($chamado['nome_empresa']); ?></option>
                    </select>
                </div>
                
                <div>
                    <label>Usuário:</label>
                    <div class="info-estatica"><?php echo htmlspecialchars($chamado['nome_solicitante'] ?? 'Não Identificado'); ?></div>
                </div>

                <div>
                    <label>Telefone:</label>
                    <div class="info-estatica"><?php echo htmlspecialchars($chamado['tel_usuario'] ?? '---'); ?></div>
                </div>
                
                <div>
                    <label>Localização:</label>
                    <div class="info-estatica" style="background: #fff3cd; border-color: #ffeba2; font-weight: bold; color: #856404;"><?php echo htmlspecialchars($chamado['local_cliente'] ?? '---'); ?></div>
                </div>

                <div>
                    <label>Técnico:</label>
                    <select id="id_tecnico" name="id_tecnico" onchange="atualizarCelularTecnico(this.value)">
                        <option value="">-- Sem Técnico --</option>
                        <?php foreach($tecnicos_data as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($t['id'] == $chamado['id_tecnico_atribuido']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label>Celular:</label>
                    <div id="tel_tecnico_exibicao" class="info-estatica"><?php echo htmlspecialchars($chamado['tel_tecnico'] ?? '---'); ?></div>
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
                
                <div class="campo-cheio">
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

    <script>
    const dadosTecnicos = <?php echo json_encode($tecnicos_data); ?>;

    function atualizarCelularTecnico(idSelecionado) {
        const divCelular = document.getElementById('tel_tecnico_exibicao');
        
        if (!idSelecionado) {
            divCelular.textContent = '---';
            return;
        }
        
        const tecnicoEncontrado = dadosTecnicos.find(t => t.id == idSelecionado);
        
        if (tecnicoEncontrado && tecnicoEncontrado.num_celular) {
            divCelular.textContent = tecnicoEncontrado.num_celular;
        } else {
            divCelular.textContent = '---';
        }
    }
    </script>

    <?php if ($cadastro_sucesso): ?>
        <script>
            alert("✅ Chamado updated com sucesso!");
            window.location.href = "lista_chamados.php";
        </script>
    <?php endif; ?>

    <script src="../../js/mascaras.js?v=<?php echo time(); ?>"></script>
</body>
</html>