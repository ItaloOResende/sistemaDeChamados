<?php
session_start();

// 1. TRAVA DE SEGURANÇA: Garante que o usuário está logado
if (!isset($_SESSION['usuario_perfil']) || !isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

include_once(__DIR__ . '/../../tabelas/conexao.php'); 
require_once(__DIR__ . '/../../servicos/email_notificacao.php');

$conexao->set_charset("utf8mb4");

$perfil_logado       = $_SESSION['usuario_perfil'];
$id_usuario_logado   = (int)$_SESSION['usuario_id'];
$id_cliente_logado   = isset($_SESSION['usuario_id_cliente']) ? (int)$_SESSION['usuario_id_cliente'] : 0;

/* * INTERCEPTAÇÃO ASSÍNCRONA (Para Admin/Técnico filtrar usuários por empresa) */
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

// CARGA DOS SELECTS CONFORME O PERFIL
if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico') {
    $sql_clientes = "SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC";
    $resultado_clientes = $conexao->query($sql_clientes);

    $sql_tecnicos = "SELECT id, nome FROM usuarios WHERE (perfil = 'tecnico' OR perfil = 'admin') AND status = 'Ativo' ORDER BY nome ASC";
    $resultado_tecnicos = $conexao->query($sql_tecnicos);

    $sql_todos_usuarios = "SELECT id, nome FROM usuarios WHERE perfil = 'normal' AND status = 'Ativo' ORDER BY nome ASC";
    $resultado_usuarios = $conexao->query($sql_todos_usuarios);
} elseif ($perfil_logado === 'gestor') {
    $sql_gestor_usuarios = "SELECT id, nome FROM usuarios WHERE id_cliente = ? AND status = 'Ativo' ORDER BY nome ASC";
    $stmt_gestor = $conexao->prepare($sql_gestor_usuarios);
    $stmt_gestor->bind_param("i", $id_cliente_logado);
    $stmt_gestor->execute();
    $resultado_usuarios = $stmt_gestor->get_result();
}

$mensagem = "";
$cadastro_sucesso = false;

// FUNÇÃO PARA COMPRIMIR E SALVAR IMAGENS VIA GD
function comprimirESalvarImagem($caminho_tmp, $destino, $qualidade = 80, $largura_maxima = 1600) {
    list($largura_orig, $altura_orig, $tipo) = getimagesize($caminho_tmp);

    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $origem = imagecreatefromjpeg($caminho_tmp);
            break;
        case IMAGETYPE_PNG:
            $origem = imagecreatefrompng($caminho_tmp);
            break;
        case IMAGETYPE_WEBP:
            $origem = imagecreatefromwebp($caminho_tmp);
            break;
        default:
            return false;
    }

    if ($largura_orig > $largura_maxima) {
        $nova_largura = $largura_maxima;
        $nova_altura = ($altura_orig / $largura_orig) * $nova_largura;
    } else {
        $nova_largura = $largura_orig;
        $nova_altura = $altura_orig;
    }

    $nova_imagem = imagecreatetruecolor($nova_largura, $nova_altura);

    $branco = imagecolorallocate($nova_imagem, 255, 255, 255);
    imagefilledrectangle($nova_imagem, 0, 0, $nova_largura, $nova_altura, $branco);

    imagecopyresampled($nova_imagem, $origem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_orig, $altura_orig);
    imagejpeg($nova_imagem, $destino, $qualidade);

    imagedestroy($origem);
    imagedestroy($nova_imagem);

    return true;
}

// PROCESSAMENTO DO FORMULÁRIO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Tratamento dos dados conforme o perfil logado
    if ($perfil_logado === 'normal') {
        $id_cliente           = $id_cliente_logado;
        $id_usuario           = $id_usuario_logado;
        $id_tecnico_atribuido = NULL;
        $prioridade           = 'Média';
        $origem               = 'Portal';
    } elseif ($perfil_logado === 'gestor') {
        $id_cliente           = $id_cliente_logado;
        $id_usuario           = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : $id_usuario_logado;
        $id_tecnico_atribuido = NULL;
        $prioridade           = 'Média';
        $origem               = 'Portal';
    } else {
        // Admin / Técnico
        $id_cliente           = (int)$_POST['id_cliente'];
        $id_usuario           = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : NULL;
        $id_tecnico_atribuido = !empty($_POST['id_tecnico_atribuido']) ? (int)$_POST['id_tecnico_atribuido'] : NULL;
        $prioridade           = $_POST['prioridade'] ?? 'Média';
        $origem               = $_POST['origem'] ?? 'Sistema';
    }

    $descricao_solicitacao = trim($_POST['descricao_solicitacao']);

    // Tratamento dos Anexos Múltiplos
    $caminhos_anexos = [];
    $diretorio_upload = __DIR__ . '/../../uploads/chamados/';

    if (!is_dir($diretorio_upload)) {
        mkdir($diretorio_upload, 0777, true);
    }

    if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
        $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $total_arquivos = count($_FILES['anexos']['name']);

        for ($i = 0; $i < $total_arquivos; $i++) {
            if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                $nome_original = $_FILES['anexos']['name'][$i];
                $tmp_name = $_FILES['anexos']['tmp_name'][$i];
                $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

                if (in_array($extensao, $extensoes_permitidas)) {
                    $nome_seguro = uniqid('print_', true) . '.jpg';
                    $destino = $diretorio_upload . $nome_seguro;

                    if (comprimirESalvarImagem($tmp_name, $destino)) {
                        $caminhos_anexos[] = 'uploads/chamados/' . $nome_seguro;
                    }
                } else {
                    $mensagem = "<div class='msg-erro'>Apenas fotos e prints nos formatos JPG, PNG ou WEBP são permitidos!</div>";
                    break;
                }
            }
        }
    }

    $caminho_anexo_final = !empty($caminhos_anexos) ? implode(',', $caminhos_anexos) : NULL;

    if (empty($mensagem)) {
        $sql = "INSERT INTO chamados (id_cliente, id_usuario, id_tecnico_atribuido, prioridade, descricao_solicitacao, anexo, origem) VALUES (?, ?, ?, ?, ?, ?, ?)";

        try {
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param("iiissss", $id_cliente, $id_usuario, $id_tecnico_atribuido, $prioridade, $descricao_solicitacao, $caminho_anexo_final, $origem); 

            if ($stmt->execute()) {
                $novo_id_chamado = $stmt->insert_id;
                $cadastro_sucesso = true; 

                // 📧 DISPARO AUTOMÁTICO DE E-MAIL
                // 1. Busca Admins e Técnicos
                $sql_dest = "SELECT nome, email FROM usuarios 
                             WHERE (LOWER(perfil) = 'admin' OR LOWER(perfil) = 'tecnico') 
                               AND (status = 'Ativo' OR status = 'ativo' OR status = '1' OR status = 1)
                               AND email IS NOT NULL 
                               AND email != ''";
                $res_dest = $conexao->query($sql_dest);
                $destinatarios = [];
                if ($res_dest) {
                    while ($dest = $res_dest->fetch_assoc()) {
                        $destinatarios[] = $dest;
                    }
                }

                // 2. Se não achou destinatário no banco, envia para o e-mail do admin configurado
                if (empty($destinatarios) && defined('SMTP_USER')) {
                    $destinatarios[] = ['nome' => 'Equipe de Suporte TI', 'email' => SMTP_USER];
                }

                // 3. Busca o nome da empresa e solicitante
                $sql_info = "SELECT c.nome_empresa, u.nome AS nome_usuario 
                             FROM clientes c 
                             LEFT JOIN usuarios u ON u.id = ? 
                             WHERE c.id_cliente = ?";
                $stmt_info = $conexao->prepare($sql_info);
                if ($stmt_info) {
                    $stmt_info->bind_param("ii", $id_usuario, $id_cliente);
                    $stmt_info->execute();
                    $info = $stmt_info->get_result()->fetch_assoc();
                    $stmt_info->close();
                }

                // 4. Monta os dados e dispara
                $dadosEnvio = [
                    'id'         => $novo_id_chamado,
                    'empresa'    => $info['nome_empresa'] ?? 'Empresa não identificada',
                    'usuario'    => $info['nome_usuario'] ?? 'Solicitante não identificado',
                    'prioridade' => $prioridade,
                    'origem'     => $origem,
                    'descricao'  => $descricao_solicitacao
                ];

                enviarNotificacaoNovoChamado($dadosEnvio, $destinatarios);
            }
        } catch (mysqli_sql_exception $e) {
            $mensagem = "<div class='msg-erro'>Erro ao abrir chamado: " . $e->getMessage() . "</div>";
        }
        
        if (isset($stmt)) {
            $stmt->close(); 
        }
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

        <form method="POST" action="" enctype="multipart/form-data">
            <h2>Detalhes da Solicitação</h2>
            
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

                <label for="id_usuario">Usuário / Solicitante (*):</label>
                <select id="id_usuario" name="id_usuario" required>
                    <option value="">-- Selecione o Usuário --</option>
                    <?php while($usuario = $resultado_usuarios->fetch_assoc()): ?>
                        <option value="<?php echo $usuario['id']; ?>">
                            <?php echo htmlspecialchars($usuario['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="id_tecnico_atribuido">Técnico Atribuído (Opcional):</label>
                <select id="id_tecnico_atribuido" name="id_tecnico_atribuido">
                    <option value="">-- Nenhum Técnico Atribuído --</option>
                    <?php while($tecnico = $resultado_tecnicos->fetch_assoc()): ?>
                        <option value="<?php echo $tecnico['id']; ?>">
                            <?php echo htmlspecialchars($tecnico['nome']); ?>
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

            <?php elseif ($perfil_logado === 'gestor'): ?>
                <label for="id_usuario">Usuário / Solicitante da Empresa (*):</label>
                <select id="id_usuario" name="id_usuario" required>
                    <option value="">-- Selecione o Usuário --</option>
                    <?php while($usuario = $resultado_usuarios->fetch_assoc()): ?>
                        <option value="<?php echo $usuario['id']; ?>">
                            <?php echo htmlspecialchars($usuario['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            <?php endif; ?>

            <label for="descricao_solicitacao">Descrição Detalhada do Problema (*):</label>
            <textarea id="descricao_solicitacao" name="descricao_solicitacao" style="min-height: 160px;" placeholder="Descreva o problema com o máximo de detalhes possível..." required></textarea>

            <label for="anexos">Fotos/Prints (Opcional):</label>
            <input type="file" id="anexos" name="anexos[]" multiple accept="image/png, image/jpeg, image/jpg, image/webp">
            
            <button type="submit">Abrir Chamado</button>
        </form>
    </main>
    
    <div class="voltar">
        <a href="lista_chamados.php">← Voltar para a Lista de Chamados</a>
    </div>
    <script src="../js/mascaras.js"></script>

    <?php if ($perfil_logado === 'admin' || $perfil_logado === 'tecnico'): ?>
    <script>
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
    <?php endif; ?>

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