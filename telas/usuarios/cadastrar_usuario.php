<?php
session_start();

// CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

$mensagem = "";
$cadastro_sucesso = false;
$e_admin_logado = isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'admin';

// BUSCA AS EMPRESAS ATIVAS APENAS SE FOR O ADMIN LOGADO NAVEGANDO NO PAINEL
if ($e_admin_logado) {
    $sql_empresas = "SELECT id_cliente, nome_empresa FROM clientes WHERE status_cliente = 'Ativo' ORDER BY nome_empresa ASC";
    $resultado_empresas = $conexao->query($sql_empresas);
}

// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO (USUÁRIOS)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome        = trim($_POST['nome']);
    $email       = trim($_POST['email']); 
    $num_celular = trim($_POST['num_celular']);
    $senha_pura  = trim($_POST['senha']);

    // 1. VERIFICA SE A TABELA DE USUÁRIOS ESTÁ TOTALMENTE VAZIA
    $sql_check = "SELECT COUNT(*) AS total FROM usuarios";
    $resultado_check = $conexao->query($sql_check);
    $total_usuarios = 0;
    
    if ($resultado_check) {
        $row_check = $resultado_check->fetch_assoc();
        $total_usuarios = (int)$row_check['total'];
    }

    // 2. DEFINE O PERFIL
    if ($total_usuarios === 0) {
        $perfil = 'admin';
    } elseif ($e_admin_logado) {
        $perfil = trim($_POST['perfil']);
    } else {
        $perfil = 'normal';
    }

    // 3. VALIDAÇÃO E RECUPERAÇÃO DO ID_CLIENTE (LGPD SAFE)
    $id_cliente = null;

    if ($e_admin_logado) {
        // Admin pega direto do select
        $id_cliente = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
    } else {
        // Autocadastro/Gestor: Valida pelo Código da Empresa
        $codigo_informado = strtoupper(trim($_POST['codigo_empresa'] ?? ''));
        
        if (!empty($codigo_informado)) {
            $sql_cod = "SELECT id_cliente FROM clientes WHERE UPPER(codigo_empresa) = ? AND status_cliente = 'Ativo'";
            $stmt_cod = $conexao->prepare($sql_cod);
            $stmt_cod->bind_param("s", $codigo_informado);
            $stmt_cod->execute();
            $res_cod = $stmt_cod->get_result();

            if ($res_cod && $res_cod->num_rows === 1) {
                $dados_cli = $res_cod->fetch_assoc();
                $id_cliente = (int)$dados_cli['id_cliente'];
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro: Código de empresa inválido ou inativo. Verifique com seu gestor.</div>";
            }
            $stmt_cod->close();
        }
    }

    // Validação dos campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha_pura) || empty($perfil) || empty($id_cliente)) {
        if (empty($mensagem)) {
            $mensagem = "<div class='msg-erro'>❌ Erro: Todos os campos obrigatórios (*) devem ser preenchidos.</div>";
        }
    } else {
        $senha_cripto = password_hash($senha_pura, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nome, email, senha, num_celular, perfil, id_cliente, status) VALUES (?, ?, ?, ?, ?, ?, 'Ativo')";
        
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sssssi", $nome, $email, $senha_cripto, $num_celular, $perfil, $id_cliente); 

        try {
            if ($stmt->execute()) {
                $cadastro_sucesso = true; 
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062 || $conexao->errno == 1062) {
                $mensagem = "<div class='msg-erro'>❌ Erro: Este e-mail já está cadastrado no sistema.</div>";
            } else {
                $mensagem = "<div class='msg-erro'>❌ Erro ao cadastrar usuário: " . $e->getMessage() . "</div>";
            }
        }
        
        if (isset($stmt)) {
            $stmt->close(); 
        }
    }
}

if ($cadastro_sucesso === true) {
    if (!$e_admin_logado) {
        $conexao->close();
        header("Location: ../../index.php?cadastro=sucesso");
    } else {
        $conexao->close();
        header("Location: lista_usuarios.php?status=success_add"); 
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Usuários</title>
    <link rel="stylesheet" href="../../estilos/estilos.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include_once('../principal/menu.php'); ?>
    
    <header>
        <h1>👥 Cadastro de Usuários</h1>
    </header>
    <hr>

    <main>
        <?php echo $mensagem; ?>

        <form method="POST" action="">           
            <label for="nome">Nome (*):</label>
            <input type="text" id="nome" name="nome" maxlength="255" required>

            <label for="email">Email (*):</label>
            <input type="email" id="email" name="email" maxlength="100" autocomplete="off" required>

            <label for="num_celular">Telefone (*):</label>
            <input type="text" id="num_celular" name="num_celular" placeholder="(31) 99999-9999" maxlength="20" required>

            <label for="senha">Senha (*):</label>
            <input type="password" id="senha" name="senha" placeholder="Digite uma senha segura" autocomplete="new-password" required>

            <?php if ($e_admin_logado): ?>
                <label for="perfil">Perfil (*):</label>
                <select id="perfil" name="perfil" required>
                    <option value="">-- Selecione o Perfil --</option>
                    <option value="normal">Usuário Comum (Cliente)</option>
                    <option value="gestor">Gestor da empresa</option> 
                    <option value="tecnico">Técnico de Suporte</option> 
                    <option value="admin">Administrador do Sistema</option>
                </select>

                <label for="id_cliente">Empresa (*):</label>
                <select id="id_cliente" name="id_cliente" required>
                    <option value="">-- Selecione a Empresa --</option>
                    <?php if (isset($resultado_empresas) && $resultado_empresas->num_rows > 0): ?>
                        <?php while($empresa = $resultado_empresas->fetch_assoc()): ?>
                            <option value="<?php echo $empresa['id_cliente']; ?>">
                                <?php echo htmlspecialchars($empresa['nome_empresa']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>Nenhuma empresa cadastrada ou ativa</option>
                    <?php endif; ?>
                </select>
            <?php else: ?>
                <!-- 🚀 LGPD SAFE: O usuário público ou gestor digita o Código fornecido pela TI -->
                <label for="codigo_empresa">Código da Empresa (*):</label>
                <input type="text" id="codigo_empresa" name="codigo_empresa" placeholder="Ex: EMPRESA123" maxlength="20" style="text-transform: uppercase;" required>
                <small style="color: #666; font-size: 11px; display: block; margin-top: -8px; margin-bottom: 12px;">Solicite o código de acesso ao responsável de TI da sua empresa.</small>
            <?php endif; ?>
            
            <button type="submit">Cadastrar Usuário</button>
        </form>
        
        <?php if ($e_admin_logado): ?>
            <div class="voltar">
                 <a href="lista_usuarios.php">← Voltar para Lista de Usuários</a>
            </div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const inputCelular = document.getElementById("num_celular");
            if (inputCelular) {
                inputCelular.addEventListener("input", function(e) {
                    let tel = e.target.value.replace(/\D/g, "");
                    if (tel.length > 0) {
                        tel = tel.replace(/^(\d{2})(\d)/g, "($1) $2");
                    }
                    if (tel.length > 9) {
                        tel = tel.replace(/(\d{5})(\d)/, "$1-$2");
                    } else if (tel.length > 5) {
                        tel = tel.replace(/(\d{4})(\d)/, "$1-$2");
                    }
                    e.target.value = tel.substring(0, 15);
                });
            }
        });
    </script>

    <?php 
    $conexao->close();
    ?>
</body>
</html>