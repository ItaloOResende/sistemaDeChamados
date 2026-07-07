<?php
session_start();

// TRAVA DE SEGURANÇA: Só o administrador pode inativar usuários
if (!isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] !== 'admin') {
    header("Location: ../chamados/lista_chamados.php");
    exit();
}

// ---------------------------------------------
// 1. CONFIGURAÇÕES E CONEXÃO
// ---------------------------------------------
include_once(__DIR__ . '/../../tabelas/conexao.php'); 
$conexao->set_charset("utf8mb4");

// ---------------------------------------------
// 2. PROCESSAMENTO DO ID VIA GET (Vindo do JavaScript)
// ---------------------------------------------
$id_usuario = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_usuario > 0) {
    // ---------------------------------------------
    // 3. LÓGICA DE EXCLUSÃO LÓGICA (SOFT DELETE)
    // 🚀 Em vez de deletar, altera o status para 'Inativo'
    // ---------------------------------------------
    $sql = "UPDATE usuarios SET status = 'Inativo' WHERE id = ?";
    
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Inativou com sucesso -> Volta para a lista com status de sucesso
                $conexao->close();
                header("Location: lista_usuarios.php?status=success_delete");
                exit();
            } else {
                // O ID não foi encontrado ou o usuário já estava Inativo
                $conexao->close();
                header("Location: lista_usuarios.php?status=error_no_id");
                exit();
            }
        }
    } catch (mysqli_sql_exception $e) {
        // Trata qualquer erro inesperado do banco
        $conexao->close();
        header("Location: lista_usuarios.php?status=error_delete");
        exit();
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
} else {
    $conexao->close();
    header("Location: lista_usuarios.php?status=error_no_id");
    exit();
}

$conexao->close();
?>