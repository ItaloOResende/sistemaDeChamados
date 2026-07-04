<?php
// Inicia a sessão para salvar os dados do usuário logado
session_start();

$host = 'localhost';
$db   = 'sistemadechamados';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Busca o usuário pelo e-mail
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, perfil, id_cliente FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agora o password_verify vai bater com o hash novo!
if ($usuario && password_verify($senha, $usuario['senha'])) {
        
        // Salva as variáveis na Sessão
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nome']   = $usuario['nome'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];     
        $_SESSION['id_cliente']     = $usuario['id_cliente']; 

        // REDIRECIONAMENTO BASEADO NO PERFIL
        if ($_SESSION['usuario_perfil'] === 'admin') {
            // Se for Admin, vai para a listagem geral
            header("Location: telas/chamados/lista_chamados.php");
        } else {
            // Se for Usuário Comum, vai DIRETO para a tela de criar chamado
            header("Location: telas/chamados/cadastrar_chamado_usuario.php");
        }
        exit();
        
    } else {
        header("Location: index.php?erro=dados_invalidos");
        exit();
    }
}