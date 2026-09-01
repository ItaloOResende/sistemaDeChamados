<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// Garante que o usuário está logado
if (!isset($_SESSION['usuario_perfil'])) {
    header("Location: ../../index.php");
    exit();
}

// 1. CONFIGURAÇÃO E CONEXÃO
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$id_chamado = $_GET['id'] ?? null;
$cadastro_sucesso = false;
$perfil_logado = $_SESSION['usuario_perfil'];

if (!$id_chamado) { 
    header("Location: lista_chamados.php"); 
    exit; 
}

// ---------------------------------------------
// 2. PROCESSAR ATUALIZAÇÃO (POST)
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Busca os valores originais do próprio chamado atual para proteção e consistência
    $sql_origem_dados = "SELECT id_cliente, id_usuario, id_tecnico_atribuido, prioridade, status, origem, descricao_solicitacao, solucao FROM chamados WHERE id_chamado = ?";
    $stmt_origem = $conexao->prepare($sql_origem_dados);
    $stmt_origem->bind_param("i", $id_chamado);
    $stmt_origem->execute();
    $dados_originais = $stmt_origem->get_result()->fetch_assoc();
    $stmt_origem->close();

    $id_cliente = $dados_originais['id_cliente'];
    $id_usuario = $dados_originais['id_usuario'];
    
    // 🔒 REQUISITO INVERTIDO: Quem altera o quê?
    if ($perfil_logado === 'normal' || $perfil_logado === 'gestor') {
        // Usuário/Gestor mudam APENAS a descrição. O resto herda o que já estava no banco.
        $id_tecnico            = !empty($dados_originais['id_tecnico_atribuido']) ? (int)$dados_originais['id_tecnico_atribuido'] : NULL;
        $prioridade            = $dados_originais['prioridade'];
        $status                = $dados_originais['status'];
        $origem                = $dados_originais['origem'];
        $solucao               = $dados_originais['solucao'];
        $descricao_solicitacao = $_POST['descricao_solicitacao'];
    } else {
        // Admin e Técnico mudam a gestão do chamado e a solução, mas a descrição do cliente fica intacta
        $id_tecnico            = !empty($_POST['id_tecnico']) ? (int)$_POST['id_tecnico'] : NULL;
        $prioridade            = $_POST['prioridade'];
        $status                = $_POST['status'];
        $origem                = $_POST['origem'];
        $solucao               = $_POST['solucao'];
        $descricao_solicitacao = $dados_originais['descricao_solicitacao'];
    }
    
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
                    data_fechamento = ?,
                    descricao_solicitacao = ?
                   WHERE id_chamado = ?";
    
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("iiissssssi", 
        $id_cliente, 
        $id_usuario, 
        $id_tecnico, 
        $prioridade, 
        $status, 
        $origem, 
        $solucao, 
        $data_fechamento, 
        $descricao_solicitacao, 
        $id_chamado
    );
    
    if ($stmt->execute()) {
        $cadastro_sucesso = true; 
    } else {
        $mensagem = "<div class='msg-erro'>❌ Erro ao atualizar: " . $conexao->error . "</div>";
    }
}

// ---------------------------------------------
// 3. RECUPERAÇÃO DOS DADOS DO CHAMADO
// ---------------------------------------------
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
        
        /* Container dos Anexos Múltiplos */
        .box-anexo { background: #fff; border: 1px dashed #bbb; border-radius: 6px; padding: 15px; margin-top: 5px; }
        .galeria-anexos { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; }
        .card-anexo { background: #fafafa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .preview-imagem { width: 130px; height: 95px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; display: block; margin-bottom: 8px; transition: transform 0.2s ease; }
        .preview-imagem:hover { transform: scale(1.03); }
        .btn-download-anexo { display: inline-block; background-color: #007bff; color: #fff !important; padding: 4px 10px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .btn-download-anexo:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>

    <header>
        <h1 style="text-align:center;">
            <?php echo ($perfil_logado === 'normal' || $perfil_logado === 'gestor') ? '📋 Detalhes do Chamado #' : '🛠️ Editar Chamado #'; ?><?php echo $id_chamado; ?>
        </h1>
    </header>
    
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
                    <select id="id_tecnico" name="id_tecnico" onchange="atualizarCelularTecnico(this.value)" <?php echo ($perfil_logado === 'normal' || $perfil_logado === 'gestor') ? 'disabled class="disabled-select"' : ''; ?>>
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
                    <select name="origem" <?php echo ($perfil_logado === 'normal' || $perfil_logado === 'gestor') ? 'disabled class="disabled-select"' : ''; ?>>
                        <option value="Telefone" <?php echo ($chamado['origem'] == 'Telefone') ? 'selected' : ''; ?>>Telefone</option>
                        <option value="Email" <?php echo ($chamado['origem'] == 'Email') ? 'selected' : ''; ?>>E-mail</option>
                        <option value="WhatsApp" <?php echo ($chamado['origem'] == 'WhatsApp') ? 'selected' : ''; ?>>WhatsApp</option>
                        <option value="Portal" <?php echo ($chamado['origem'] == 'Portal') ? 'selected' : ''; ?>>Sistema</option>
                    </select>
                </div>
                <div>
                    <label>Prioridade:</label>
                    <select name="prioridade" <?php echo ($perfil_logado === 'normal' || $perfil_logado === 'gestor') ? 'disabled class="disabled-select"' : ''; ?>>
                        <option value="Baixa" <?php echo ($chamado['prioridade'] == 'Baixa') ? 'selected' : ''; ?>>Baixa</option>
                        <option value="Média" <?php echo ($chamado['prioridade'] == 'Média') ? 'selected' : ''; ?>>Média</option>
                        <option value="Alta" <?php echo ($chamado['prioridade'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                        <option value="Urgente" <?php echo ($chamado['prioridade'] == 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
                    </select>
                </div>
                <div>
                    <label>Status Atual:</label>
                    <select name="status" <?php echo ($perfil_logado === 'normal' || $perfil_logado === 'gestor') ? 'disabled class="disabled-select"' : ''; ?>>
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
                    <label for="descricao_solicitacao"><strong>Problema Relatado:</strong></label>
                    <textarea id="descricao_solicitacao" name="descricao_solicitacao" required style="height: 100px; margin-top: 5px;" <?php echo ($perfil_logado === 'admin' || $perfil_logado === 'tecnico') ? 'readonly style="background: #e9ecef; cursor: not-allowed;"' : ''; ?>><?php echo htmlspecialchars($chamado['descricao_solicitacao']); ?></textarea>
                </div>

                <!-- 📎 ÁREA DE ANEXOS DO CHAMADO (MÚLTIPLAS FOTOS) -->
                <div class="campo-cheio">
                    <label><strong>Anexos / Prints:</strong></label>
                    <div class="box-anexo">
                        <?php if (!empty($chamado['anexo'])): 
                            $lista_anexos = explode(',', $chamado['anexo']);
                        ?>
                            <div class="galeria-anexos">
                                <?php foreach ($lista_anexos as $item_anexo): 
                                    $item_anexo = trim($item_anexo);
                                    if (empty($item_anexo)) continue;
                                    
                                    $caminho_anexo = '../../' . htmlspecialchars($item_anexo);
                                    $extensao = strtolower(pathinfo($item_anexo, PATHINFO_EXTENSION));
                                    $eh_imagem = in_array($extensao, ['jpg', 'jpeg', 'png', 'webp']);
                                ?>
                                    <div class="card-anexo">
                                        <?php if ($eh_imagem): ?>
                                            <a href="<?php echo $caminho_anexo; ?>" target="_blank" title="Clique para ampliar em tela cheia">
                                                <img src="<?php echo $caminho_anexo; ?>" alt="Anexo" class="preview-imagem">
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo $caminho_anexo; ?>" target="_blank" download class="btn-download-anexo">
                                            📥 Baixar (<?php echo strtoupper($extensao); ?>)
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span style="color: #777; font-size: 13px;">Nenhum anexo ou foto foi enviado neste chamado.</span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <label for="solucao"><strong>Solução Técnica:</strong></label>
            <textarea id="solucao" name="solucao" placeholder="Nenhuma solução registrada pela TI até o momento..." style="height: 100px; margin-top: 5px;" <?php echo ($perfil_logado === 'normal' || $perfil_logado === 'gestor') ? 'readonly style="background: #e9ecef; cursor: not-allowed;"' : ''; ?>><?php echo htmlspecialchars($chamado['solucao'] ?? ''); ?></textarea>
            
            <button type="submit" class="btn-salvar">💾 Salvar Alterações</button>
        </form>

        <div style="margin-top: 20px; text-align: center;">
            <a href="lista_chamados.php" style="text-decoration: none; color: #666;">← Voltar para a Lista</a>
        </div>
    </main>

    <script>
    const dadosTecnicos = <?php echo json_encode($tecnicos_data); ?>;

    function atualizarCelularTecnico(idSelecionado) {
        const divCelular = document.getElementById('tel_tecnico_exibicao');
        if (!divCelular) return;
        
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
            alert("✅ Chamado atualizado com sucesso!");
            window.location.href = "lista_chamados.php";
        </script>
    <?php endif; ?>

    <script src="../../js/mascaras.js?v=<?php echo time(); ?>"></script>
</body>
</html>