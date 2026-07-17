<?php
session_start();

// Garante que o usuário está logado
if (!isset($_SESSION['usuario_perfil'])) {
    header("Location: ../../index.php");
    exit();
}

include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$perfil_logado      = $_SESSION['usuario_perfil'];
$id_cliente_logado  = isset($_SESSION['usuario_id_cliente']) ? (int)$_SESSION['usuario_id_cliente'] : 0;

// Se o usuário comum tentar entrar aqui de xereta, manda ele para a tela simplificada dele
if ($perfil_logado === 'normal') {
    header("Location: processa_chamado_cliente.php");
    exit();
}

/* * INTERCEPTAÇÃO ASSÍNCRONA (API Embutida)
 * Só roda se for Admin ou Técnico geral selecionando empresas no combo
 */
if (isset($_GET['ajax_id_cliente'])) {
    $id_cliente = (int)$_GET['ajax_id_cliente'];
    
    if ($id_cliente > 0) {
        $sql = "SELECT id, nome FROM usuarios WHERE id_cliente = ? AND status = 'Ativo' ORDER BY nome ASC";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $resultado = $stmt->get_result();
    } else {
        $sql = "SELECT id, nome FROM usuarios WHERE status = 'Ativo' ORDER BY nome ASC";
        $resultado = $conexao->query($sql);
    }
    
    $usuarios = [];
    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($usuarios);
    $conexao->close();
    exit();
}

// -----------------------------------------------------------------------------

// CARGA DOS SELECTS (Renderização Inicial da Página baseada em LGPD/Perfil)
if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico') {
    // Admin/Técnico veem todas as empresas e técnicos globais
    $sql_clientes = "SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC";
    $resultado_clientes = $conexao->query($sql_clientes);

    $sql_tecnicos = "SELECT id, nome FROM usuarios WHERE (perfil = 'tecnico' OR perfil = 'admin') AND status = 'Ativo' ORDER BY nome ASC";
    $resultado_tecnicos = $conexao->query($sql_tecnicos);

    $sql_todos_usuarios = "SELECT id, nome FROM usuarios WHERE perfil = 'normal' AND status = 'Ativo' ORDER BY nome ASC";
    $resultado_usuarios = $conexao->query($sql_todos_usuarios);
} elseif ($perfil_logado === 'gestor') {
    // 🧠 REGRA DO GESTOR: Busca APENAS os usuários que pertencem à MESMA empresa que ele
    $sql_gestor_usuarios = "SELECT id, nome FROM usuarios WHERE id_cliente = ? AND status = 'Ativo' ORDER BY nome ASC";
    $stmt_gestor = $conexao->prepare($sql_gestor_usuarios);
    $stmt_gestor->bind_param("i", $id_cliente_logado);
    $stmt_gestor->execute();
    $resultado_usuarios = $stmt_gestor->get_result();
}

$mensagem = "";
$cadastro_sucesso = false;

// PROCESSAMENTO DO FORMULÁRIO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Se for gestor, captura do campo hidden, senão captura do select
    $id_cliente = ($perfil_logado === 'gestor') ? $id_cliente_logado : (int)$_POST['id_cliente'];
    
    $id_usuario = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : NULL;
    
    // Gestor não envia técnico atribuído (fica nulo/fila) nem altera a prioridade (vai oculta como Média)
    $id_tecnico_atribuido = isset($_POST['id_tecnico_atribuido']) && !empty($_POST['id_tecnico_atribuido']) ? (int)$_POST['id_tecnico_atribuido'] : NULL;
    $prioridade           = $_POST['prioridade'] ?? 'Média';
    $descricao_solicitacao = $_POST['descricao_solicitacao'];
    $origem               = $_POST['origem'] ?? 'Sistema';

    $sql = "INSERT INTO chamados (id_cliente, id_usuario, id_tecnico_atribuido, prioridade, descricao_solicitacao, origen) VALUES (?, ?, ?, ?, ?, ?)";
    
    // Nota: Verifique se no seu banco de dados a coluna se chama 'origem' ou 'origen'. Ajuste no INSERT acima se necessário.
    $sql = "INSERT INTO chamados (id_cliente, id_usuario, id_tecnico_atribuido, prioridade, descricao_solicitacao, origem) VALUES (?, ?, ?, ?, ?, ?)";

    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("iiisss", $id_cliente, $id_usuario, $id_tecnico_atribuido, $prioridade, $descricao_solicitacao, $origem); 

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
    <?php include_once('../principal/menu.php'); ?>
    <header>
        <h1>📋 Abrir Novo Chamado</h1>
    </header>
    <hr>
    <main>
        <?php echo $mensagem; ?>

        <form method="POST" action="">
            <h2>Detalhes da Solicitação</h2>
            
            <!-- 🔒 LGPD: O select de clientes só renderiza se for Admin ou Técnico Geral -->
            <?php if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico'): ?>
                <label for="id_cliente">Cliente (Empresa):</label>
                <select id="id_cliente" name="id_cliente" required onchange="filtrarUsuariosPorEmpresa(this.value)">
                    <option value="">-- Selecione a Empresa --</option>
                    <?php while($cliente = $resultado_clientes->fetch_assoc()): ?>
                        <option value="<?php echo $cliente['id_cliente']; ?>">
                            <?php echo htmlspecialchars($cliente['nome_empresa']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            <?php else: ?>
                <!-- Passa o ID da empresa do gestor oculto para o processamento do POST -->
                <input type="hidden" name="id_cliente" value="<?php echo $id_cliente_logado; ?>">
            <?php endif; ?>

            <label for="id_usuario">Usuário / Solicitante (*):</label>
            <select id="id_usuario" name="id_usuario" required>
                <option value="">-- Selecione o Usuário --</option>
                <?php while($usuario = $resultado_usuarios->fetch_assoc()): ?>
                    <option value="<?php echo $usuario['id']; ?>">
                        <?php echo htmlspecialchars($usuario['nome']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <!-- 🔒 REQUISITO: Gestor não atribui Técnico -->
            <?php if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico'): ?>
                <label for="id_tecnico_atribuido">Técnico Atribuído (Opcional):</label>
                <select id="id_tecnico_atribuido" name="id_tecnico_atribuido">
                    <option value="">-- Nenhum Técnico Atribuído --</option>
                    <?php while($tecnico = $resultado_tecnicos->fetch_assoc()): ?>
                        <option value="<?php echo $tecnico['id']; ?>">
                            <?php echo htmlspecialchars($tecnico['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            <?php endif; ?>

            <!-- 🔒 REQUISITO: Gestor não define prioridade (vai oculta como 'Média') -->
            <?php if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico'): ?>
                <label for="prioridade">Prioridade:</label>
                <select id="prioridade" name="prioridade" required>
                    <option value="Baixa">Baixa</option>
                    <option value="Média" selected>Média</option>
                    <option value="Alta">Alta</option>
                    <option value="Urgente">Urgente</option>
                </select>
            <?php else: ?>
                <input type="hidden" name="prioridade" value="Média">
            <?php endif; ?>

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
    </main>
    
    <div class="voltar">
        <a href="lista_chamados.php">← Voltar para a Lista de Chamados</a>
    </div>
    <script src="../js/mascaras.js"></script>

    <script>
    /* * Manipulação dinâmica do DOM usando Fetch
     * Só executa o fetch se o elemento de cliente existir na tela (Admin/Tecnico)
     */
    function filtrarUsuariosPorEmpresa(idCliente) {
        const selectUsuario = document.getElementById('id_usuario');
        if (!selectUsuario) return;
        
        fetch(`${window.location.pathname}?ajax_id_cliente=${idCliente}`)
            .then(response => response.json())
            .then(data => {
                selectUsuario.innerHTML = '<option value="">-- Selecione o Usuário --</option>';
                
                data.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = usuario.nome;
                    selectUsuario.appendChild(option);
                });
            })
            .catch(error => console.error('Erro na requisição:', error));
    }
    </script>

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