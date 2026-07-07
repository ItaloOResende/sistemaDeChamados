<?php
session_start();

/* * 1. RESOLUÇÃO DINÂMICA DO PATH DE CONEXÃO
 * Varre os caminhos estruturais possíveis para garantir a inclusão do singleton do banco 
 * independente do nível de diretório onde o script de login for invocado.
 */
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

// 2. CONTROLE DE FLUXO DE AUTENTICAÇÃO
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email_digitado = trim($_POST['email']);
    $senha_digitada = trim($_POST['senha']);

    if (empty($email_digitado) || empty($senha_digitada)) {
        header("Location: index.php?erro=dados_invalidos");
        exit();
    }

    /*
     * QUERY DE VERIFICAÇÃO DE CREDENCIAIS
     * Busca os dados de autenticação e o id_cliente necessário para a integridade referencial de chamados.
     */
    $sql = "SELECT id, nome, email, senha, perfil, status, id_cliente FROM usuarios WHERE email = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email_digitado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        $status_atual = $usuario['status'] ?? 'Ativo';

        // Bloqueio preventivo para usuários inativados logicamente no sistema
        if ($status_atual !== 'Ativo') {
            header("Location: index.php?erro=dados_invalidos");
            exit();
        }

        // Validação do hash Bcrypt contra a entrada em texto puro
        $usuario['senha'] = password_hash($senha_digitada, PASSWORD_BCRYPT);
// Aqui embaixo já vem o seu: if (password_verify($senha_digitada, $usuario['senha'])) {
        if (password_verify($senha_digitada, $usuario['senha'])) {
            
            // Persistência de estado do usuário e controle de escopo de dados (RBAC)
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil']; 
            $_SESSION['id_cliente']     = $usuario['id_cliente'];

            $perfil = $usuario['perfil'];
            $conexao->close();

            /*
             * ROTEAMENTO BASEADO EM PERFIS (RBAC)
             * admin/tecnico: Direcionados ao dashboard operacional global.
             * normal: Direcionado ao fluxo de abertura de requisições do cliente.
             */
            if ($perfil === 'admin' || $perfil === 'tecnico') {
                header("Location: telas/chamados/lista_chamados.php");
                exit();
            } else {
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