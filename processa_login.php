<?php
session_start();

// 1. CONFIGURAÇÃO DE CONEXÃO COM O BANCO DE DADOS
$caminhos_possiveis = [
    __DIR__ . '/tabelas/conexao.php',
    __DIR__ . '/../tabelas/conexao.php',
    __DIR__ . '/config/conexao.php',
    __DIR__ . '/conexao.php'
];

$conexao_incluida = false;
foreach ($caminhos_possiveis as $caminho) {
    if (file_exists($caminho)) {
        include_once($caminho);
        $conexao_incluida = true;
        break;
    }
}

if (!$conexao_incluida) {
    $diretorio = new RecursiveDirectoryIterator(__DIR__);
    $iterator = new RecursiveIteratorIterator($diretorio);
    foreach ($iterator as $arquivo) {
        if ($arquivo->getFilename() === 'conexao.php') {
            include_once($arquivo->getPathname());
            $conexao_incluida = true;
            break;
        }
    }
}

if (!isset($conexao) || $conexao->connect_error) {
    die("❌ Erro: Arquivo 'conexao.php' não encontrado.");
}

$conexao->set_charset("utf8mb4");

// ---------------------------------------------
// 2. PROCESSAMENTO DO LOGIN
// ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 🚀 [Temporário] Grava a senha 'admin123' limpa para o admin@teste.com.br
    $conexao->query("UPDATE usuarios SET senha = '" . password_hash('admin123', PASSWORD_BCRYPT) . "' WHERE email = 'admin@teste.com.br'");
    
    $email_digitado = trim($_POST['email']);
    $senha_digitada = trim($_POST['senha']);

    if (empty($email_digitado) || empty($senha_digitada)) {
        header("Location: index.php?erro=dados_invalidos");
        exit();
    }

    // 🚀 ADICIONADO: Puxando também o 'id_cliente' no SELECT para salvar na sessão
    $sql = "SELECT id, nome, email, senha, perfil, status, id_cliente FROM usuarios WHERE email = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email_digitado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        $status_atual = $usuario['status'] ?? 'Ativo';

        if ($status_atual !== 'Ativo') {
            header("Location: index.php?erro=dados_invalidos");
            exit();
        }

        if (password_verify($senha_digitada, $usuario['senha'])) {
            // Guardando dados fundamentais na sessão
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil']; 
            
            // 🚀 SALVA O ID DA EMPRESA: Isso mata de vez o erro de Foreign Key no chamado do cliente!
            $_SESSION['id_cliente']     = $usuario['id_cliente'];

            $perfil = $usuario['perfil'];
            $conexao->close();

            // 🚀 3. LÓGICA DE DIRECIONAMENTO CONFORME O PERFIL
            if ($perfil === 'admin' || $perfil === 'tecnico') {
                // Administradores e Técnicos vão direto para a gerência de chamados
                header("Location: telas/chamados/lista_chamados.php");
                exit();
            } else {
                // Clientes comuns ('normal') vão direto para a tela de abertura/detalhes deles
                header("Location: telas/chamados/cadastrar_chamado_usuario.php");
                exit();
            }
        }
    }

    $conexao->close();
    header("Location: index.php?erro=dados_invalidos");
    exit();

} else {
    header("Location: index.php");
    exit();
}