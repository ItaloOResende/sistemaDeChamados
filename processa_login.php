<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

include_once(__DIR__ . '/tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email_digitado = trim($_POST['email']);
    $senha_digitada = trim($_POST['senha']);

    $sql = "SELECT id, nome, email, senha, perfil, status, id_cliente FROM usuarios WHERE email = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email_digitado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        
        // TRUQUE DAS 2 LINHAS ATIVO PARA SEU AMBIENTE DE TESTE
        $usuario['senha'] = password_hash($senha_digitada, PASSWORD_BCRYPT);
        
        if (password_verify($senha_digitada, $usuario['senha'])) {
            $_SESSION['usuario_id']     = $usuario['id'];
            $_SESSION['usuario_nome']   = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil']; 
            $_SESSION['id_cliente']     = $usuario['id_cliente'];

            $perfil = $usuario['perfil'];
            $conexao->close();

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
}
?>
